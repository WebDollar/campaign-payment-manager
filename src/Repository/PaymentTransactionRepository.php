<?php

namespace App\Repository;

use App\Contracts\Entity\IPaymentTransaction;
use App\Entity\PaymentTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method PaymentTransaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaymentTransaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaymentTransaction[]    findAll()
 * @method PaymentTransaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentTransactionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PaymentTransaction::class);
    }

    /**
     * @param int $nLimit
     *
     * @return IPaymentTransaction[]
     */
    public function findPendingTransactions(int $nLimit = 10)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.state = :state')
            ->setParameter('state', IPaymentTransaction::STATE_PENDING)
            ->orderBy('t.updatedAt', 'ASC')
            ->setMaxResults($nLimit)
            ->getQuery()
            ->getResult();
    }
}
