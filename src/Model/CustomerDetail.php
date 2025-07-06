<?php

namespace Pantono\Customers\Model;

use Pantono\Contracts\Attributes\Filter;
use Pantono\Database\Traits\SavableModel;
use Pantono\Contracts\Attributes\Locator;
use Pantono\Customers\Customers;
use Pantono\Contracts\Attributes\FieldName;
use Pantono\Contracts\Attributes\DateFormat;

class CustomerDetail
{
    use SavableModel;

    private ?int $id = null;
    private ?int $customerId = null;
    private ?\DateTimeInterface $dateCreated = null;
    private ?string $email = null;
    private ?string $forename = null;
    private ?string $surname = null;
    private ?string $mobileNumber = null;
    #[DateFormat('Y-m-d')]
    private ?\DateTimeInterface $dateOfBirth = null;
    /**
     * @var CustomerDetailField[]
     */
    #[Locator(methodName: 'getFieldsForCustomerDetails', className: Customers::class), FieldName('$this')]
    private array $fields = [];
    #[Filter('json_decode')]
    private array $fieldJson = [];

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

    public function getDateCreated(): \DateTimeInterface
    {
        if ($this->dateCreated instanceof \DateTimeInterface) {
            return $this->dateCreated;
        }
        $this->dateCreated = new \DateTime;
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): void
    {
        $this->dateCreated = $dateCreated;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getForename(): ?string
    {
        return $this->forename;
    }

    public function setForename(?string $forename): void
    {
        $this->forename = $forename;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(?string $surname): void
    {
        $this->surname = $surname;
    }

    public function getMobileNumber(): ?string
    {
        return $this->mobileNumber;
    }

    public function setMobileNumber(?string $mobileNumber): void
    {
        $this->mobileNumber = $mobileNumber;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTimeInterface $dateOfBirth): void
    {
        $this->dateOfBirth = $dateOfBirth;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getFieldJson(): array
    {
        return $this->fieldJson;
    }

    public function setFieldJson(array $fieldJson): void
    {
        $this->fieldJson = $fieldJson;
    }

    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    public function addField(CustomerDetailField $field): void
    {
        $this->fields[] = $field;
    }

    public function getFieldByName(string $fieldName): ?CustomerDetailField
    {
        foreach ($this->fields as $field) {
            if ($field->getField()->getName() === $fieldName) {
                return $field;
            }
        }
        return null;
    }

    public function getFieldValue(string $fieldName): mixed
    {
        foreach ($this->fields as $field) {
            if ($field->getField()->getName() === $fieldName) {
                return $field->getValue();
            }
        }
        return null;
    }

    public function updateFieldValue(CustomerField $fieldModel, mixed $value): void
    {
        $found = false;
        foreach ($this->getFields() as $field) {
            if ($field->getField()->getName() === $fieldModel->getName()) {
                $field->setValue($value);
                $found = true;
            }
        }

        if ($found === false) {
            $customerField = new CustomerDetailField();
            $customerField->setField($fieldModel);
            $customerField->setValue($value);
            $this->fields[] = $customerField;
        }
        $this->updateFieldJson();
    }

    private function updateFieldJson(): void
    {
        $json = [];
        foreach ($this->getFields() as $field) {
            $json[$field->getField()->getName()] = $field->getValue();
        }
        $this->setFieldJson($json);
    }
}
