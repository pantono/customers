<?php

namespace Pantono\Customers\Model;

use Pantono\Authentication\Model\User;
use Pantono\Contracts\Attributes\Locator;
use Pantono\Customers\Customers;
use Pantono\Contracts\Attributes\FieldName;
use Pantono\Authentication\UserAuthentication;
use Pantono\Contracts\Attributes\Lazy;

class Customer
{
    private ?int $id = null;
    private \DateTimeInterface $dateCreated;
    #[Locator(methodName: 'getUserById', className: UserAuthentication::class), FieldName('user_id'), Lazy]
    private ?User $user = null;
    #[Locator(methodName: 'getDetailsById', className: Customers::class), FieldName('details_id')]
    private ?CustomerDetails $details = null;
    /**
     * @var CustomerExternalId[]
     */
    #[Locator(methodName: 'getExternalIdsForCustomer', className: Customers::class), FieldName('$this')]
    private array $externalIds = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getDateCreated(): \DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): void
    {
        $this->dateCreated = $dateCreated;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getDetails(): ?CustomerDetails
    {
        return $this->details;
    }

    public function setDetails(?CustomerDetails $details): void
    {
        $this->details = $details;
    }

    public function getExternalIds(): array
    {
        return $this->externalIds;
    }

    public function setExternalIds(array $externalIds): void
    {
        $this->externalIds = $externalIds;
    }
}
