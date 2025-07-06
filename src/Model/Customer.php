<?php

namespace Pantono\Customers\Model;

use Pantono\Authentication\Model\User;
use Pantono\Contracts\Attributes\Locator;
use Pantono\Customers\Customers;
use Pantono\Contracts\Attributes\FieldName;
use Pantono\Authentication\UserAuthentication;
use Pantono\Contracts\Attributes\Lazy;
use Pantono\Contracts\Attributes\NoSave;
use Pantono\Core\Helper\ConfigHelper;
use Pantono\Utilities\DateTimeParser;

class Customer
{
    private ?int $id = null;
    private \DateTimeInterface $dateCreated;
    #[Locator(methodName: 'getUserById', className: UserAuthentication::class), FieldName('user_id'), Lazy]
    private ?User $user = null;
    #[Locator(methodName: 'getDetailsById', className: Customers::class), FieldName('details_id')]
    private ?CustomerDetail $details = null;
    /**
     * @var CustomerExternalId[]
     */
    #[Locator(methodName: 'getExternalIdsForCustomer', className: Customers::class), FieldName('$this')]
    private array $externalIds = [];
    #[NoSave]
    private bool $needsUpdate = false;

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

    public function getDetails(): ?CustomerDetail
    {
        return $this->details;
    }

    public function setDetails(?CustomerDetail $details): void
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

    public function isNeedsUpdate(): bool
    {
        return $this->needsUpdate;
    }

    public function setNeedsUpdate(bool $needsUpdate): void
    {
        $this->needsUpdate = $needsUpdate;
    }

    public function getHash(): string
    {
        $parts = [
            'user_id' => $this->getUser()?->getId(),
        ];
        if ($this->getDetails()) {
            foreach ($this->getDetails()->getAllData() as $field => $value) {
                $parts[$field] = $value;
            }
        }
        $json = serialize($parts);
        return md5($json);
    }

    public function getUpdates(Customer $previous): array
    {
        $updates = [];
        if ($this->getUser()?->getId() !== $previous->getUser()?->getId()) {
            $updates[] = ['field' => 'user_id', 'old' => $previous->getUser()?->getId(), 'new' => $this->getUser()?->getId()];
        }
        $prevData = $previous->getDetails()?->getAllData() ?? [];
        $currentData = $this->getDetails()?->getAllData() ?? [];
        foreach ($currentData as $field => $value) {
            if ($field === 'id' || $field === 'date_created' || $field === 'field_json') {
                continue;
            }
            if ($value !== $prevData[$field]) {
                $oldValue = $prevData[$field] ?? null;
                $newValue = $value ?? null;
                if ($field === 'date_of_birth') {
                    if ($oldValue) {
                        $oldValue = DateTimeParser::parseDate($oldValue)->format(ConfigHelper::getDateFormat());
                    }
                    if ($newValue) {
                        $newValue = DateTimeParser::parsedate($newValue)->format(ConfigHelper::getDateFormat());
                    }
                }
                $updates[] = ['field' => $field, 'old' => $oldValue, 'new' => $newValue];
            }
        }

        if ($this->getDetails()) {
            foreach ($this->getDetails()->getFields() as $field) {
                $previousField = $previous->getDetails()?->getFieldByName($field->getField()->getName());
                if (!$previousField) {
                    $updates[] = ['field' => $field->getField()->getName(), 'old' => 'N/A', 'new' => $field->getValue()];
                    continue;
                }
                if ($field->getValue() !== $previousField->getValue()) {
                    $updates[] = ['field' => $field->getField()->getName(), 'old' => $previousField->getValue(), 'new' => $field->getValue()];
                }
            }
        }

        return $updates;
    }
}
