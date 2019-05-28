<?php

namespace App\Controller;

use App\Contracts\Entity\ICampaign;
use App\Entity\Campaign;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CampaignController
 * @package App\Controller
 */
class CampaignController extends AbstractController
{
    /**
     * @param Campaign $data
     * @Route("/api/campaigns/{id}/startPayment",
     *     name="campaigns_start_payment",
     *     methods={"PUT"},
     *     defaults={
     *         "_api_resource_class"=Campaign::class,
     *         "_api_item_operation_name"="startPayment",
     *         "_api_receive"=false
     *     }
     * )
     *
     * @return
     */
    public function startPayment(Campaign $data)
    {
        if ($data->getState() === ICampaign::STATE_NEW)
        {
            $data->setState(ICampaign::STATE_PAYING);
        }

        $this->getDoctrine()->getManager()->persist($data);
        $this->getDoctrine()->getManager()->flush();

        return [];
    }

    /**
     * @param Campaign $data
     * @Route("/api/campaigns/{id}/stopPayment",
     *     name="campaigns_stop_payment",
     *     methods={"PUT"},
     *     defaults={
     *         "_api_resource_class"=Campaign::class,
     *         "_api_item_operation_name"="stopPayment",
     *         "_api_receive"=false
     *     }
     * )
     * @return
     */
    public function stopPayment(Campaign $data)
    {
        if ($data->getState() === ICampaign::STATE_PAYING)
        {
            $data->setState(ICampaign::STATE_NEW);
        }

        $this->getDoctrine()->getManager()->persist($data);
        $this->getDoctrine()->getManager()->flush();

        return [];
    }
}
