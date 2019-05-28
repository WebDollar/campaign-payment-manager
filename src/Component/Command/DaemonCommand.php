<?php

declare(ticks = 1);

namespace App\Component\Command;

use App\Contracts\Command\IEntityManagerAwareCommand;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Monolog\Handler\FingersCrossedHandler;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DaemonCommand
 * @package App\Component\Command
 */
abstract class DaemonCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var InputInterface
     */
    private $_oInput;

    /**
     * @var OutputInterface
     */
    private $_oOutput;

    /**
     * @var int
     */
    private $_nPausePerIteration = 1;

    /**
     * @var LoopInterface
     */
    private $_oLoop;

    /**
     * @var int
     */
    private $_nCycles = 0;

    /**
     * @var integer
     */
    private $_nMaxCycles;

    /**
     * @var bool
     */
    private $_bPingDatabase;

    /**
     * @var bool
     */
    private $_bClearEntityManager;

    /**
     * @var bool
     */
    private $_bClearMonolog = TRUE;

    /**
     * @var integer
     */
    private $_nCycleDelay;

    abstract protected function _cycle();

    public function __construct(LoggerInterface $oLogger)
    {
        parent::__construct(NULL);

        $this->setLogger($oLogger);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->_oInput  = $input;
        $this->_oOutput = $output;

        if ($this instanceof IEntityManagerAwareCommand)
        {
            /** @var EntityManagerInterface $oEntityManager */
            $oEntityManager = $this->getApplication()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
            $oEntityManager->getConnection()->getConfiguration()->setSQLLogger(NULL);

            if ($this->getHelperSet() !== NULL)
            {
                $this->getHelperSet()->set(new EntityManagerHelper($oEntityManager), 'em');
            }
            else
            {
                $this->setHelperSet(new HelperSet([
                    'em' => new EntityManagerHelper($oEntityManager),
                ]));
            }

            if ($this->_bPingDatabase === NULL)
            {
                $this->_setPingDatabase(TRUE);
                $this->_setClearEntityManager(TRUE);
            }
        }

        parent::initialize($input, $output);
    }

    protected function _input()
    {
        return $this->_oInput;
    }

    protected function _output()
    {
        return $this->_oOutput;
    }

    /**
     * @param int $nPausePerIteration Seconds between intervals
     */
    protected function _setPausePerIteration(int $nPausePerIteration)
    {
        $this->_nPausePerIteration = $nPausePerIteration;
    }

    /**
     * @param int $nMaxCycles Maximum cycles to run
     */
    protected function _setMaxCycles(int $nMaxCycles)
    {
        $this->_nMaxCycles = $nMaxCycles;
    }

    /**
     * @param bool $bPingDatabase
     */
    protected function _setPingDatabase(bool $bPingDatabase)
    {
        $this->_bPingDatabase = $bPingDatabase;
    }

    /**
     * @param bool $bClearEntityManager
     */
    protected function _setClearEntityManager(bool $bClearEntityManager)
    {
        $this->_bClearEntityManager = $bClearEntityManager;
    }

    /**
     * @param bool $bClearMonolog
     */
    protected function _setClearMonolog(bool $bClearMonolog)
    {
        $this->_bClearMonolog = $bClearMonolog;
    }

    /**
     * @param int $nDelay
     */
    protected function _delayNextCycle(int $nDelay)
    {
        $this->_nCycleDelay = $nDelay;
    }

    protected function _delay(float $nDelay)
    {
        if ($nDelay > 0)
        {
            usleep($nDelay * 1000000);
        }
    }

    /**
     * @return int
     */
    protected function _getCycleNumber()
    {
        return $this->_nCycles;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_oLoop = Factory::create();

        $this->_oLoop->addSignal(SIGINT,  [$this, 'handleSigInt']);
        $this->_oLoop->addSignal(SIGTERM, [$this, 'handleSigTerm']);
        $this->_oLoop->addSignal(SIGHUP,  [$this, 'handleSigHup']);

        $this->_oLoop->addPeriodicTimer($this->_nPausePerIteration, function() {
            if ($this->_nCycleDelay !== NULL && $this->_nCycleDelay > 0)
            {
                if (--$this->_nCycleDelay === 0)
                {
                    $this->_nCycleDelay = NULL;
                }
                else
                {
                    $this->logger->notice(sprintf('Remaining delays until next cycle %s', $this->_nCycleDelay));
                    return;
                }
            }

            $this->logger->notice('Cycle ' . ++$this->_nCycles);

            if ($this->_nMaxCycles !== NULL && $this->_nCycles >= $this->_nMaxCycles)
            {
                $this->terminate();
                return;
            }

            if (!$this->_preCycle())
            {
                return;
            }

            $this->_cycle();
            $this->_postCycle();
        });

        $this->_oLoop->run();
    }

    protected function _pingDatabase()
    {
        if ($this->_bPingDatabase === TRUE && ($this instanceof IEntityManagerAwareCommand) && $this->getEntityManager() !== NULL)
        {
            if ($this->getEntityManager()->getConnection()->ping() === FALSE)
            {
                $this->getEntityManager()->getConnection()->close();
                $this->getEntityManager()->getConnection()->connect();
            }

            if ($this->getEntityManager()->isOpen() === FALSE)
            {
                $this->logger->error('Closing daemon to reset EntityManager ...');
                exit(1);
            }

            $this->logger->notice('Pinged database...');
        }
    }

    protected function _clearMonolog()
    {
        if ($this->_bClearMonolog === FALSE)
        {
            return;
        }

        foreach ($this->logger->getHandlers() as $oHandler)
        {
            if ($oHandler instanceof FingersCrossedHandler)
            {
                $this->logger->notice('Clearing Monolog Handler');
                $oHandler->clear();
            }
        }
    }

    protected function _clearEntityManager()
    {
        if ($this->_bClearEntityManager === TRUE && ($this instanceof IEntityManagerAwareCommand) && $this->getEntityManager() !== NULL)
        {
            $this->logger->notice('Clearing Entity Manager');
            $this->getEntityManager()->clear();
        }
    }

    protected function _preCycle()
    {
        $this->_pingDatabase();
        $this->_clearMonolog();

        return TRUE;
    }

    protected function _postCycle()
    {
        gc_collect_cycles();

        $this->_displayMemoryUsage();
        $this->_clearEntityManager();
    }

    /**
     * @return LoopInterface
     */
    protected function _getLoop()
    {
        return $this->_oLoop;
    }

    protected function _scheduleTermination()
    {
        $this->_nMaxCycles = 1;
    }

    public function handleSigHup()
    {
        $this->logger->warning('Caught SIGHUP');
        $this->terminate();
    }

    public function handleSigTerm()
    {
        $this->logger->warning('Caught SIGTERM');
        $this->terminate();
    }

    public function handleSigInt()
    {
        $this->logger->warning('Caught SIGINT');
        $this->terminate();
    }

    public function terminate($nStatus = 0)
    {
        $this->_oLoop->stop();
    }

    protected function _displayMemoryUsage()
    {
        $size    = memory_get_usage(TRUE);
        $unit    = array('b','kb','mb','gb','tb','pb');
        $sMemory = @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
        $this->logger->notice(sprintf('Memory Usage: %s', $sMemory));
    }
}
