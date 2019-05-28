<?php

namespace App\Contracts\Command;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Interface IEntityManagerAwareCommand
 * @package App\Contracts\Command
 */
interface IEntityManagerAwareCommand
{
    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager() : EntityManagerInterface;
}
