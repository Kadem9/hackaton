<?php

namespace App\Entity;

use App\Repository\UserPreferenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPreferenceRepository::class)]
class UserPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userPreferences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $preferenceKey = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $preference = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPreferenceKey(): ?string
    {
        return $this->preferenceKey;
    }

    public function setPreferenceKey(string $preferenceKey): static
    {
        $this->preferenceKey = $preferenceKey;

        return $this;
    }

    public function getPreference(): ?string
    {
        return $this->preference;
    }

    public function setPreference(string $preference): static
    {
        $this->preference = $preference;

        return $this;
    }
}
