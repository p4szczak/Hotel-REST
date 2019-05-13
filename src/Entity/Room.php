<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as SWG;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RoomRepository")
 */
class Room
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @SWG\Property(example=1)
     */
    private $roomNumber;

    /**
     * @ORM\Column(type="integer")
     * @SWG\Property(example=2)
     */
    private $placesCount;

    /**
     * @ORM\Column(type="float")
     * @SWG\Property(example=99.99)
     */
    private $costPerDay;

    /**
     * @ORM\Column(type="string", length=50)
     * @SWG\Property(example="Apartament")
     */
    private $type;

    /**
     * @ORM\Column(type="boolean")
     * @SWG\Property(example="true")
     */
    private $isAvaiable;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Reservation", mappedBy="Room", orphanRemoval=true)
     * @SWG\Property(type="string", example="")
     */
    private $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoomNumber(): ?int
    {
        return $this->roomNumber;
    }

    public function setRoomNumber(int $roomNumber): self
    {
        $this->roomNumber = $roomNumber;

        return $this;
    }

    public function getPlacesCount(): ?int
    {
        return $this->placesCount;
    }

    public function setPlacesCount(int $placesCount): self
    {
        $this->placesCount = $placesCount;

        return $this;
    }

    public function getCostPerDay(): ?float
    {
        return $this->costPerDay;
    }

    public function setCostPerDay(float $costPerDay): self
    {
        $this->costPerDay = $costPerDay;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getIsAvaiable(): ?bool
    {
        return $this->isAvaiable;
    }

    public function setIsAvaiable(bool $isAvaiable): self
    {
        $this->isAvaiable = $isAvaiable;

        return $this;
    }

    /**
     * @return Collection|Reservation[]
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations[] = $reservation;
            $reservation->setRoom($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->contains($reservation)) {
            $this->reservations->removeElement($reservation);
            // set the owning side to null (unless already changed)
            if ($reservation->getRoom() === $this) {
                $reservation->setRoom(null);
            }
        }

        return $this;
    }
}
