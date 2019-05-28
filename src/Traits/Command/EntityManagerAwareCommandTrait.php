<?php

namespace App\Traits\Command;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Trait EntityManagerAwareCommandTrait
 * @package App\Traits\Command
 */
trait EntityManagerAwareCommandTrait
{
    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager() : EntityManagerInterface
    {
        return $this->getHelperSet()->get('em')->getEntityManager();
    }
}
