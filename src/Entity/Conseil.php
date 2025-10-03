<?php

namespace App\Entity;

use App\Repository\ConseilRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Hateoas\Relation(
 *      "update",
 *      href = @Hateoas\Route(
 *          "updateConseil",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups={"conseil:read"}, excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
 *      attributes = {
 *          "method" = "PUT", "type" = "application/json", "title" = "Modifier ce conseil"
 *      } 
 * )
 * @Hateoas\Relation(
 *      "delete",
 *      href = @Hateoas\Route(
 *          "deleteConseil",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups={"conseil:read"}, excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
 *      attributes = {
 *          "method" = "DELETE", "type" = "application/json", "title" = "Supprimer ce conseil"
 *      }
 * )
 */

#[ORM\Entity(repositoryClass: ConseilRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Conseil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['conseil:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(min: 10, minMessage: 'La description doit contenir au moins {{ limit }} caractères')]
    #[Assert\Length(max: 2000, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères')]
    #[Groups(['conseil:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
    }

    /**
     * @var Collection<int, ConseilMois>
     */
    #[ORM\OneToMany(targetEntity: ConseilMois::class, mappedBy: 'conseil', orphanRemoval: true, cascade: ['persist'])]
    #[Groups(['conseil:read'])]
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
