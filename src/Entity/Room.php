<?php

namespace App\Entity;

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
}
