<?php

namespace App\Repository;

use App\Contracts\Entity\IAddress;
use App\Entity\Address;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Address|null find($id, $lockMode = null, $lockVersion = null)
 * @method Address|null findOneBy(array $criteria, array $orderBy = null)
 * @method Address[]    findAll()
 * @method Address[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Address::class);
    }

    public function findForAddress(string $address):? IAddress
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.address = :address')
            ->setParameter('address', $address)
            ->getQuery()
            ->getSingleResult();
    }
}
