<?php

namespace App\Command;

use App\Component\Command\DaemonCommand;
use App\Contracts\Command\IEntityManagerAwareCommand;
use App\Contracts\Entity\ICampaign;
use App\Contracts\Entity\IPayment;
use App\Entity\Address;
use App\Entity\Campaign;
use App\Entity\Payment;
use App\Entity\PaymentTransaction;
use App\Traits\Command\EntityManagerAwareCommandTrait;
use Graze\GuzzleHttp\JsonRpc\Exception\RequestException;
use Graze\GuzzleHttp\JsonRpc\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use WebDollar\WebDollarClientBundle\Contracts\WebDollarClientManagerInterface;
use Xterr\SupervisorBundle\Annotation\Supervisor;

/**
 * Class ProcessPaymentsCommand
 * @package App\Command
 * @Supervisor(processes=1)
 */
class ProcessPaymentsCommand extends DaemonCommand implements IEntityManagerAwareCommand
{
    use EntityManagerAwareCommandTrait;

    private const MAX_PAYMENTS = 255;

    private const WEBD_COINS = 10000;

    protected static $defaultName = 'app:process-payments';

    /**
     * @var \WebDollar\Client\WebDollarClient
     */
    private $_oWebDollarClient;

    public function __construct(LoggerInterface $oLogger, WebDollarClientManagerInterface $oWebDollarClientManager)
    {
        parent::__construct($oLogger);

        $this->_oWebDollarClient = $oWebDollarClientManager->getOneClient();
    }

    protected function _cycle()
    {
        if ($this->_oWebDollarClient->syncing()->isSynchronized() === FALSE)
        {
            $this->logger->notice('WebDollar Node is not synchronized');
            return;
        }

        $oCampaign = $this->getEntityManager()->getRepository(Campaign::class)->findOneBy(['state' => ICampaign::STATE_PAYING]);

        if ($oCampaign === NULL)
        {
            $this->logger->notice('Nothing to do');
            return;
        }

        /** @var IPayment[] $aPayments */
        $aPayments = $this->getEntityManager()->getRepository(Payment::class)->findBy(['state' => IPayment::STATE_READY_TO_PAY, 'campaign' => $oCampaign], NULL, self::MAX_PAYMENTS);

        if (\count($aPayments) === 0)
        {
            $this->logger->notice('Nothing to do');
            return;
        }

        $this->logger->notice(sprintf('Processing %s payments', count($aPayments)));

        $aFromAddresses = [
            [
                'address'  => $oCampaign->getPaymentAddress(),
                'value'    => 0,
                'password' => $oCampaign->getPaymentAddressPassword(),
            ]
        ];

        $aToAddresses = [];

        foreach ($aPayments as $oPayment)
        {
            $aFromAddresses[0]['value'] += $oPayment->getAmount();
            $aToAddresses[] = [
                'address' => $oPayment->getAddress()->getAddress(),
                'value'   => $oPayment->getAmount(),
            ];
        }

        $this->logger->notice(sprintf('Paying %s WEBD to %s addresses', $aFromAddresses[0]['value'] / self::WEBD_COINS, count($aToAddresses)));

        try
        {
            $oWebDollarTransaction = $this->_oWebDollarClient->sendAdvancedTransaction($aFromAddresses, $aToAddresses);
        }
        catch (RequestException $e)
        {
            $oResponse = $e->getResponse();

            if ($oResponse !== NULL && $oResponse instanceof ResponseInterface)
            {
                if (isset($oResponse->getRpcErrorData()['details']) && preg_match('/Address (.*) is invalid/', $oResponse->getRpcErrorData()['details'], $aMatches))
                {
                    $oProblematicAddress = $this->getEntityManager()->getRepository(Address::class)->findForAddress($aMatches[1]);

                    if ($oProblematicAddress !== NULL)
                    {
                        /** @var IPayment[] $aProblematicPayments */
                        $aProblematicPayments = $this->getEntityManager()->getRepository(Payment::class)->createQueryBuilder('t')
                            ->andWhere('t.campaign = :campaign')
                            ->setParameter('campaign', $oCampaign)
                            ->andWhere('t.address = :address')
                            ->setParameter('address', $oProblematicAddress)
                            ->getQuery()
                            ->getResult();

                        foreach ($aProblematicPayments as $oProblematicPayment)
                        {
                            $oProblematicPayment->setState(IPayment::STATE_FAILED);
                            $oProblematicPayment->setFailReason($oResponse->getRpcErrorData()['details']);

                            $this->getEntityManager()->persist($oProblematicPayment);
                        }

                        $this->getEntityManager()->flush();
                    }
                }

                $this->logger->error(var_export($oResponse->getRpcErrorData(), TRUE));
            }

            $this->logger->error($e->getMessage());
            return;
        }
        catch (\Exception $e)
        {
            $this->logger->error($e->getMessage());
            return;
        }
        catch (\Throwable $e)
        {
            $this->logger->error($e->getMessage());
            return;
        }

        $oTransaction = new PaymentTransaction();
        $oTransaction->setHash($oWebDollarTransaction->getTransactionHash());
        $oTransaction->setAmount($aFromAddresses[0]['value']);
        $oTransaction->setFeeAmount(0);

        foreach ($aPayments as $oPayment)
        {
            $oPayment->setState(IPayment::STATE_PENDING);
            $oTransaction->addPayment($oPayment);
            $this->getEntityManager()->persist($oPayment);
        }

        $this->getEntityManager()->persist($oTransaction);
        $this->getEntityManager()->flush();
    }
}
