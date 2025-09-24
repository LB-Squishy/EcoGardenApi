<?php

namespace App\Entity;

use App\Repository\ConseilMoisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConseilMoisRepository::class)]
class ConseilMois
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotNull]
    #[Assert\NotBlank(message: 'Le mois est obligatoire')]
    #[Assert\Range(min: 1, max: 12)]
    #[Groups(['conseil:read'])]
    private ?int $mois = null;

    #[ORM\ManyToOne(inversedBy: 'mois')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Conseil $conseil = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMois(): ?int
    {
        return $this->mois;
    }

    public function setMois(int $mois): static
    {
        $this->mois = $mois;

        return $this;
    }

    public function getConseil(): ?Conseil
    {
        return $this->conseil;
    }

    public function setConseil(?Conseil $conseil): static
    {
        $this->conseil = $conseil;

        return $this;
    }
}
