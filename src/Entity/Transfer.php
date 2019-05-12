<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as SWG;


/**
 * @ORM\Entity(repositoryClass="App\Repository\TransferRepository")
 */
class Transfer
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Reservation", inversedBy="transfer")
     * @ORM\JoinColumn(nullable=false)
     * @SWG\Property(type="int", example=1)
     */
    private $reservation;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Client", inversedBy="transfer")
     * @ORM\JoinColumn(nullable=false)
     * @SWG\Property(type="int", example=1)
     */
    private $client;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): self
    {
        $this->reservation = $reservation;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }
}
