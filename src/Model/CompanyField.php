<?php

namespace Pantono\Customers\Model;

use Pantono\Contracts\Attributes\Locator;
use Pantono\Customers\Companies;
use Pantono\Contracts\Attributes\FieldName;
use Pantono\Contracts\Attributes\Lazy;
use Pantono\Utilities\DateTimeParser;

class CompanyField
{
    private ?int $id = null;
    private int $companyId;
    #[Locator(methodName: 'getFieldTypeById', className: Companies::class), FieldName('field_type_id'), Lazy]
    private CompanyFieldType $type;
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

    public function getType(): CompanyFieldType
    {
        return $this->type;
    }

    public function setType(CompanyFieldType $type): void
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
        $type = $this->getType()->getType();
        $value = $this->getValue();
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
        return $value;
    }
}
