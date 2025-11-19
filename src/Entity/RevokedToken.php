<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class RevokedToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(type:"string", length:255, unique:true)]
    private string $jti;

    #[ORM\Column(type:"datetime_immutable")]
    private \DateTimeImmutable $expiresAt;

    public function __construct(string $jti, \DateTimeImmutable $expiresAt)
    {
        $this->jti = $jti;
        $this->expiresAt = $expiresAt;
    }

    public function getJti(): string { return $this->jti; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
}
