<?php

namespace App\Command;

use App\Component\Command\DaemonCommand;
use App\Contracts\Command\IEntityManagerAwareCommand;
use App\Contracts\Component\IPaymentTransactionChecker;
use App\Entity\PaymentTransaction;
use App\Traits\Command\EntityManagerAwareCommandTrait;
use Psr\Log\LoggerInterface;
use WebDollar\WebDollarClientBundle\Contracts\WebDollarClientManagerInterface;
use Xterr\SupervisorBundle\Annotation\Supervisor;

/**
 * Class ProcessPaymentTransactionsCommand
 * @package App\Command
 * @Supervisor(processes=1)
 */
class ProcessPaymentTransactionsCommand extends DaemonCommand implements IEntityManagerAwareCommand
{
    use EntityManagerAwareCommandTrait;

    protected static $defaultName = 'app:process-payment-transactions';

    /**
     * @var IPaymentTransactionChecker
     */
    private $_oPaymentTransactionChecker;

    /**
     * @var \WebDollar\Client\WebDollarClient
     */
    private $_oWebDollarClient;

    public function __construct(LoggerInterface $oLogger, IPaymentTransactionChecker $oTransactionChecker, WebDollarClientManagerInterface $oWebDollarClientManager)
    {
        parent::__construct($oLogger);

        $this->_oPaymentTransactionChecker = $oTransactionChecker;
        $this->_oWebDollarClient           = $oWebDollarClientManager->getOneClient();
    }

    protected function _cycle()
    {
        $this->_oPaymentTransactionChecker->setEntityManager($this->getEntityManager());

        if ($this->_oWebDollarClient->syncing()->isSynchronized() === FALSE)
        {
            $this->logger->notice('WebDollar Node is not synchronized');
            return;
        }

        $nLastBlock    = $this->_oWebDollarClient->blockNumber()->getQuantity();
        $aTransactions = $this->getEntityManager()->getRepository(PaymentTransaction::class)->findPendingTransactions(10);

        foreach ($aTransactions as $oTransaction)
        {
            $this->_oPaymentTransactionChecker->checkPaymentTransaction($oTransaction, $nLastBlock);
            $this->_delay(1); // prevent triggering the WebDollar Node rate limiter
        }
    }
}
