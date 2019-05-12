<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as SWG;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ClientRepository")
 */
class Client
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     * @SWG\Property(example="Jan")
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=50)
     * @SWG\Property(example="Nowak")
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=20)
     * @SWG\Property(example="500-100-200")
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=50)
     * @SWG\Property(example="jan.nowak@student.put.poznan.pl")
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=100)
     * @SWG\Property(example="Poznań")
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=25)
     * @SWG\Property(example="10-10-1990")
     */
    private $birthDate;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Reservation", mappedBy="client", orphanRemoval=true)
     */
    private $reservation;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Transfer", mappedBy="client", orphanRemoval=true)
     */
    private $transfer;

    public function __construct()
    {
        $this->reservation = new ArrayCollection();
        $this->transfer = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    public function setBirthDate(string $birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    /**
     * @return Collection|Reservation[]
     */
    public function getReservation(): Collection
    {
        return $this->reservation;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservation->contains($reservation)) {
            $this->reservation[] = $reservation;
            $reservation->setClient($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservation->contains($reservation)) {
            $this->reservation->removeElement($reservation);
            // set the owning side to null (unless already changed)
            if ($reservation->getClient() === $this) {
                $reservation->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Transfer[]
     */
    public function getTransfer(): Collection
    {
        return $this->transfer;
    }

    public function addTransfer(Transfer $transfer): self
    {
        if (!$this->transfer->contains($transfer)) {
            $this->transfer[] = $transfer;
            $transfer->setClient($this);
        }

        return $this;
    }

    public function removeTransfer(Transfer $transfer): self
    {
        if ($this->transfer->contains($transfer)) {
            $this->transfer->removeElement($transfer);
            // set the owning side to null (unless already changed)
            if ($transfer->getClient() === $this) {
                $transfer->setClient(null);
            }
        }

        return $this;
    }
}
