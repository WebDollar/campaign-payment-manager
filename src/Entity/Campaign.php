<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Contracts\Entity\ICampaign;
use App\Contracts\Entity\IPayment;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Xterr\Symfony\Contracts\Traits\TimestampableEntityTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CampaignRepository")
 * @ApiResource(
 *     normalizationContext={"groups"={"campaign:read"}},
 *     denormalizationContext={"groups"={"campaign:write"}},
 *     itemOperations={
 *         "get",
 *         "startPayment"={"route_name"="campaigns_start_payment"},
 *         "stopPayment"={"route_name"="campaigns_stop_payment"}
 *     }
 * )
 * @ApiFilter(SearchFilter::class, properties={"name": "partial", "slug": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"updatedAt": "DESC"})
 * @ApiFilter(DateFilter::class, properties={"createdAt", "updatedAt"})
 */
class Campaign implements ICampaign
{
    use TimestampableEntityTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    private $id;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=255)
     * @Groups({"campaign:read", "campaign:write", "payment:write"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank
     * @Gedmo\Slug(fields={"name"}, unique=false)
     * @Groups({"campaign:read", "campaign:write"})
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Regex("/^WEBD\$[a-km-zA-NP-Z0-9+@#$]{34}\$$/")
     * @Groups({"campaign:read", "campaign:write"})
     */
    private $paymentAddress;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"campaign:read", "campaign:write"})
     */
    private $paymentAddressPassword;

    /**
     * @ORM\Column(type="smallint", options={"unsigned":true})
     * @Groups({"campaign:read", "campaign:write"})
     */
    private $state = self::STATE_NEW;

    /**
     * @var \DateTimeInterface
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     * @Groups({"campaign:read"})
     */
    protected $createdAt;

    /**
     * @var \DateTimeInterface
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     * @Groups({"campaign:read"})
     */
    protected $updatedAt;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Payment", mappedBy="campaign", orphanRemoval=true)
     * @Groups({"campaign:read"})
     */
    private $payments;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getPaymentAddress(): ?string
    {
        return $this->paymentAddress;
    }

    public function setPaymentAddress(string $address): void
    {
        $this->paymentAddress = $address;
    }

    public function getPaymentAddressPassword(): ?string
    {
        return $this->paymentAddressPassword;
    }

    public function setPaymentAddressPassword(?string $paymentAddressPassword): void
    {
        $this->paymentAddressPassword = $paymentAddressPassword;
    }

    public function getState(): ?int
    {
        return $this->state;
    }

    public function setState(int $state): void
    {
        $this->state = $state;
    }

    /**
     * @return Collection|Payment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(IPayment $payment): void
    {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setCampaign($this);
        }
    }

    public function removePayment(IPayment $payment): void
    {
        if ($this->payments->contains($payment)) {
            $this->payments->removeElement($payment);
            // set the owning side to null (unless already changed)
            if ($payment->getCampaign() === $this) {
                $payment->setCampaign(null);
            }
        }
    }
}
