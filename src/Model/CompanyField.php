<?php

namespace Pantono\Customers\Model;

use Pantono\Contracts\Attributes\FieldName;
use Pantono\Utilities\DateTimeParser;
use Pantono\Database\Traits\SavableModel;
use Pantono\Hydrator\Locator\StaticLocator;
use Pantono\Storage\Model\StoredFile;
use Pantono\Contracts\Attributes\Database\OneToOne;
use Pantono\Contracts\Attributes\DatabaseTable;

#[DatabaseTable('company_field')]
class CompanyField
{
    use SavableModel;

    private ?int $id = null;
    private int $companyId;
    #[OneToOne(targetModel: CompanyFieldType::class), FieldName('field_type_id')]
    private ?CompanyFieldType $type = null;
    private string $value;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getCompanyId(): int
    {
        return $this->companyId;
    }

    public function setCompanyId(int $companyId): void
    {
        $this->companyId = $companyId;
    }

    public function getType(): ?CompanyFieldType
    {
        return $this->type;
    }

    public function setType(?CompanyFieldType $type): void
    {
        $this->type = $type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getCastedValue(): mixed
    {
        $type = $this->getType()?->getType();
        $value = $this->getValue();
        if (!$type) {
            return $value;
        }
        if (!$value) {
            return $value;
        }
        if ($type === 'int') {
            return (int)$value;
        }
        if ($type === 'float') {
            return (float)$value;
        }
        if ($type === 'date' || $type === 'datetime') {
            return DateTimeParser::parseDate($value);
        }
        if ($type === 'bool' || $type === 'boolean') {
            return (bool)$value;
        }
        if ($type === 'file') {
            $value = (int)$value;
            if (!$value) {
                return $value;
            }
            return StaticLocator::getLocator()->lookupRecord(StoredFile::class, $value);
        }
        if (str_starts_with($type, 'lookup:')) {
            $value = (int)$value;
            if (!$value) {
                return $value;
            }
            return StaticLocator::getLocator()->lookupRecord(substr($type, 7), $value);
        }
        return $value;
    }
}
