<?php

namespace App\Contracts\Component;

use App\Contracts\Entity\IPaymentTransaction;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Interface IPaymentTransactionChecker
 * @package App\Contracts\Component
 */
interface IPaymentTransactionChecker extends LoggerAwareInterface
{
    /**
     * @param IPaymentTransaction $oPaymentTransaction
     * @param int                 $nLastBlockNumber
     */
    public function checkPaymentTransaction(IPaymentTransaction $oPaymentTransaction, int $nLastBlockNumber): void;

    /**
     * @param EntityManagerInterface $oEntityManager
     */
    public function setEntityManager(EntityManagerInterface $oEntityManager): void;
}
