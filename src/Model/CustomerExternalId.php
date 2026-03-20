<?php

namespace Pantono\Customers\Model;

use Pantono\Database\Traits\SavableModel;

class CustomerExternalId
{
    use SavableModel;

    private ?int $id = null;
    private string $idType;
    private int $customerId;
    private \DateTimeInterface $dateCreated;
    private \DateTimeInterface $dateUpdated;
    private string $identifier;
    private bool $deleted;

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

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function setCustomerId(int $customerId): void
    {
        $this->customerId = $customerId;
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

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }
}
