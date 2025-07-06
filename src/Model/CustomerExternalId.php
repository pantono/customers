<?php

namespace Pantono\Customers\Model;

use Pantono\Database\Traits\SavableModel;

class CustomerExternalId
{
    use SavableModel;

    private ?int $id = null;
    private string $idType;
    private \DateTimeInterface $dateCreated;
    private \DateTimeInterface $dateUpdated;
    private mixed $identifier;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getIdType(): string
    {
        return $this->idType;
    }

    public function setIdType(string $idType): void
    {
        $this->idType = $idType;
    }

    public function getDateCreated(): \DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): void
    {
        $this->dateCreated = $dateCreated;
    }

    public function getDateUpdated(): \DateTimeInterface
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(\DateTimeInterface $dateUpdated): void
    {
        $this->dateUpdated = $dateUpdated;
    }

    public function getIdentifier(): mixed
    {
        return $this->identifier;
    }

    public function setIdentifier(mixed $identifier): void
    {
        $this->identifier = $identifier;
    }
}
