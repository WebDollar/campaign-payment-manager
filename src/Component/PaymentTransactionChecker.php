<?php

namespace App\Component;

use App\Contracts\Component\IPaymentTransactionChecker;
use App\Contracts\Component\IWebDollarTransactionProvider;
use App\Contracts\Entity\IPayment;
use App\Contracts\Entity\IPaymentTransaction;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Class PaymentTransactionChecker
 * @package App\Component
 */
class PaymentTransactionChecker implements IPaymentTransactionChecker
{
    use LoggerAwareTrait;

    private const MAXIMUM_WAIT_TIME     = 900;
    private const MINIMUM_CONFIRMATIONS = 6;

    /**
     * @var IWebDollarTransactionProvider
     */
    private $_oWebDollarTransactionProvider;

    /**
     * @var EntityManagerInterface
     */
    private $_oEM;

    public function __construct(LoggerInterface $oLogger, IWebDollarTransactionProvider $oWebDollarTransactionProvider, EntityManagerInterface $oEM)
    {
        $this->setLogger($oLogger);
        $this->setEntityManager($oEM);
        $this->_oWebDollarTransactionProvider = $oWebDollarTransactionProvider;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->_oEM;
    }

    public function setEntityManager(EntityManagerInterface $oEntityManager): void
    {
        $this->_oEM = $oEntityManager;
    }

    /**
     * @param IPaymentTransaction $oPaymentTransaction
     * @param int                 $nLastBlock
     */
    public function checkPaymentTransaction(IPaymentTransaction $oPaymentTransaction , int $nLastBlock): void
    {
        $this->logger->notice(sprintf('Checking transaction %s', $oPaymentTransaction->getHash()));

        if ($oPaymentTransaction->getState() === IPaymentTransaction::STATE_CONFIRMED)
        {
            $this->logger->notice(sprintf('Transaction %s is already confirmed', $oPaymentTransaction->getHash()));
            return;
        }

        $oWebDollarTransaction = $this->_oWebDollarTransactionProvider->provideByHash($oPaymentTransaction->getHash());

        if ($oWebDollarTransaction === NULL)
        {
            $this->logger->notice(sprintf('Transaction %s was not found', $oPaymentTransaction->getHash()));

            if (Carbon::instance($oPaymentTransaction->getCreatedAt())->addSeconds(self::MAXIMUM_WAIT_TIME)->lessThan(Carbon::now()))
            {
                // Revert the transaction in case the transaction is not present on the blockChain and the wait time has elapsed
                $this->logger->notice(sprintf('Transaction %s exceeded the maximum wait time', $oPaymentTransaction->getHash()));

                foreach ($oPaymentTransaction->getPayments() as $oPayment)
                {
                    $oPayment->setState(IPayment::STATE_READY_TO_PAY);
                    $oPaymentTransaction->removePayment($oPayment);

                    $this->getEntityManager()->persist($oPayment);
                }

                $this->getEntityManager()->remove($oPaymentTransaction);
                $this->getEntityManager()->flush();
            }
            return;
        }

        $this->logger->notice(sprintf('Transaction %s status: %s', $oPaymentTransaction->getHash(), ($oWebDollarTransaction->isConfirmed() ? 'Confirmed' : 'Unconfirmed')));

        if ($oWebDollarTransaction->isConfirmed() === FALSE)
        {
            $this->logger->notice(sprintf('Transaction %s is unconfirmed. Nothing to do', $oPaymentTransaction->getHash()));
            // Nothing to do until the transaction is confirmed
            return;
        }

        $nConfirmations = $nLastBlock - $oWebDollarTransaction->getBlockNumber();
        $this->logger->notice(sprintf('Transaction %s confirmations: %s', $oPaymentTransaction->getHash(), $nConfirmations));

        $oPaymentTransaction->setConfirmations($nConfirmations);
        $oPaymentTransaction->setFeeAmount($oWebDollarTransaction->getFeeRaw());

        if ($nConfirmations >= self::MINIMUM_CONFIRMATIONS)
        {
            $this->logger->notice(sprintf('Transaction %s settling', $oPaymentTransaction->getHash()));

            $oPaymentTransaction->setState(IPaymentTransaction::STATE_CONFIRMED);

            foreach ($oPaymentTransaction->getPayments() as $oPayment)
            {
                $oPayment->setState(IPayment::STATE_COMPLETED);
                $this->getEntityManager()->persist($oPayment);
            }
        }

        $this->getEntityManager()->persist($oPaymentTransaction);
        $this->getEntityManager()->flush();
    }
}
