<?php

namespace App\Command;

use App\Component\Command\DaemonCommand;
use App\Contracts\Command\IEntityManagerAwareCommand;
use App\Contracts\Entity\ICampaign;
use App\Contracts\Entity\IPayment;
use App\Entity\Campaign;
use App\Entity\Payment;
use App\Traits\Command\EntityManagerAwareCommandTrait;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Psr\Log\LoggerInterface;
use Xterr\SupervisorBundle\Annotation\Supervisor;

/**
 * Class ProcessCampaignsCommand
 * @package App\Command
 * @Supervisor(processes=1)
 */
class ProcessCampaignsCommand extends DaemonCommand implements IEntityManagerAwareCommand
{
    use EntityManagerAwareCommandTrait;

    protected static $defaultName = 'app:process-campaigns';

    public function __construct(LoggerInterface $oLogger)
    {
        parent::__construct($oLogger);
    }

    protected function _cycle()
    {
        $aCampaigns = $this->getEntityManager()->getRepository(Campaign::class)->findBy(['state' => ICampaign::STATE_PAYING]);

        foreach ($aCampaigns as $oCampaign)
        {
            $this->getEntityManager()->getRepository(Payment::class)->createQueryBuilder('t')
                ->update()
                ->set('t.state', IPayment::STATE_READY_TO_PAY)
                ->andWhere('t.state = :state')
                ->andWhere('t.campaign = :campaign')
                ->setParameter('state', IPayment::STATE_NEW)
                ->setParameter('campaign', $oCampaign)
                ->getQuery()
                ->execute();

            $oPayments = $oCampaign->getPayments();

            if ($oPayments instanceof Selectable)
            {
                $nTotalPayments      = $oPayments->matching((new Criteria())->andWhere(Criteria::expr()->neq('state', IPayment::STATE_FAILED)))->count();
                $nSuccessfulPayments = $oPayments->matching((new Criteria())->andWhere(Criteria::expr()->eq('state', IPayment::STATE_COMPLETED)))->count();

                $this->logger->notice(sprintf('Total Payments: %s, Successful Payments %s', $nTotalPayments, $nSuccessfulPayments));

                if ($nTotalPayments === $nSuccessfulPayments)
                {
                    $oCampaign->setState(ICampaign::STATE_COMPLETED);
                    $this->getEntityManager()->persist($oCampaign);
                    $this->getEntityManager()->flush();
                }
            }
        }

        $aCampaigns = $this->getEntityManager()->getRepository(Campaign::class)->findBy(['state' => ICampaign::STATE_NEW]);

        foreach ($aCampaigns as $oCampaign)
        {
            $this->getEntityManager()->getRepository(Payment::class)->createQueryBuilder('t')
                ->update()
                ->set('t.state', IPayment::STATE_NEW)
                ->andWhere('t.state = :state')
                ->andWhere('t.campaign = :campaign')
                ->setParameter('state', IPayment::STATE_READY_TO_PAY)
                ->setParameter('campaign', $oCampaign)
                ->getQuery()
                ->execute();
        }
    }
}
