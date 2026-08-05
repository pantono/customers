<?php

namespace Pantono\Customers\Model;

use Pantono\Locations\Model\Location;
use Pantono\Contracts\Attributes\FieldName;
use Pantono\Database\Traits\SavableModel;
use Pantono\Contracts\Attributes\DatabaseTable;
use Pantono\Contracts\Attributes\Database\OneToOne;

#[DatabaseTable('customer_location')]
class CustomerLocation
{
    use SavableModel;

    private ?int $id = null;
    private ?int $customerId = null;
    #[OneToOne(targetModel: Location::class), FieldName('location_id')]
    private ?Location $location = null;
    private string $name = '';
    private bool $defaultBilling = false;
    private bool $defaultShipping = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function setCustomerId(?int $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): void
    {
        $this->location = $location;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isDefaultBilling(): bool
    {
        return $this->defaultBilling;
    }

    public function setDefaultBilling(bool $defaultBilling): void
    {
        $this->defaultBilling = $defaultBilling;
    }

    public function isDefaultShipping(): bool
    {
        return $this->defaultShipping;
    }

    public function setDefaultShipping(bool $defaultShipping): void
    {
        $this->defaultShipping = $defaultShipping;
    }
}
