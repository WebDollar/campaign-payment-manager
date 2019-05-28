<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Contracts\Entity\IAddress;
use App\Contracts\Entity\IPayment;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Xterr\Symfony\Contracts\Traits\TimestampableEntityTrait;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"address:read"}},
 *     denormalizationContext={"groups"={"address:write"}}
 * )
 * @ORM\Entity(repositoryClass="App\Repository\AddressRepository")
 * @ApiFilter(SearchFilter::class, properties={"address": "exact"})
 */
class Address implements IAddress
{
    use TimestampableEntityTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank
     * @Assert\Regex("/^WEBD\$[a-km-zA-NP-Z0-9+@#$]{34}\$$/")
     * @Groups({"address:read", "address:write", "payment:write"})
     */
    private $address;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Payment", mappedBy="address", orphanRemoval=true)
     * @Groups({"address:read"})
     */
    private $payments;

    /**
     * @var \DateTimeInterface
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     * @Groups({"address:read"})
     */
    protected $createdAt;

    /**
     * @var \DateTimeInterface
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     * @Groups({"address:read"})
     */
    protected $updatedAt;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    /**
     * @return Collection|IPayment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(IPayment $payment): void
    {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setAddress($this);
        }
    }

    public function removePayment(IPayment $payment): void
    {
        if ($this->payments->contains($payment)) {
            $this->payments->removeElement($payment);
            // set the owning side to null (unless already changed)
            if ($payment->getAddress() === $this) {
                $payment->setAddress(null);
            }
        }
    }
}
