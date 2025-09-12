<?php

namespace Pantono\Customers\Model;

use Pantono\Contracts\Attributes\Locator;
use Pantono\Customers\Companies;
use Pantono\Contracts\Attributes\FieldName;
use Pantono\Locations\Model\Location;
use Pantono\Contracts\Attributes\NoSave;
use Pantono\Database\Traits\SavableModel;
use Pantono\Contracts\Attributes\Lazy;

#[Locator(methodName: 'getCompanyById', className: Companies::class)]
class Company
{
    use SavableModel;

    private ?int $id = null;
    private \DateTimeImmutable $dateCreated;
    private \DateTimeImmutable $dateUpdated;
    #[FieldName('status_id')]
    private CompanyStatus $status;
    private string $name;
    #[FieldName('location_id')]
    private Location $location;
    private ?string $phoneNumber = null;
    private ?string $emailAddress = null;
    private ?string $vatNumber = null;
    #[Locator(methodName: 'getFilesForCompany', className: Companies::class), NoSave, FieldName('company_id')]
    private array $files = [];
    /**
     * @var CompanyField[]
     */
    #[Locator(methodName: 'getFieldsForCompany', className: Companies::class), NoSave, FieldName('company_id'), Lazy]
    private array $fields = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getDateCreated(): \DateTimeImmutable
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeImmutable $dateCreated): void
    {
        $this->dateCreated = $dateCreated;
    }

    public function getDateUpdated(): \DateTimeImmutable
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(\DateTimeImmutable $dateUpdated): void
    {
        $this->dateUpdated = $dateUpdated;
    }

    public function getStatus(): CompanyStatus
    {
        return $this->status;
    }

    public function setStatus(CompanyStatus $status): void
    {
        $this->status = $status;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function setLocation(Location $location): void
    {
        $this->location = $location;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(?string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): void
    {
        $this->vatNumber = $vatNumber;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setFiles(array $files): void
    {
        $this->files = $files;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    public function getFieldValueByName(string $name): mixed
    {
        foreach ($this->getFields() as $field) {
            if ($field->getType()->getName() === $name) {
                return $field->getCastedValue();
            }
        }
        return null;
    }
}
