<?php

namespace App\Entity;

use App\Repository\ConseilRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConseilRepository::class)]
class Conseil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, ConseilMois>
     */
    #[ORM\OneToMany(targetEntity: ConseilMois::class, mappedBy: 'conseil', orphanRemoval: true)]
    private Collection $mois;

    public function __construct()
    {
        $this->mois = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, ConseilMois>
     */
    public function getMois(): Collection
    {
        return $this->mois;
    }

    public function addMois(ConseilMois $mois): static
    {
        if (!$this->mois->contains($mois)) {
            $this->mois->add($mois);
            $mois->setConseil($this);
        }

        return $this;
    }

    public function removeMois(ConseilMois $mois): static
    {
        if ($this->mois->removeElement($mois)) {
            // set the owning side to null (unless already changed)
            if ($mois->getConseil() === $this) {
                $mois->setConseil(null);
            }
        }

        return $this;
    }
}
