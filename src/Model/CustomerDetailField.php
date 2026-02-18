<?php

namespace Pantono\Customers\Model;

use Pantono\Contracts\Attributes\Locator;
use Pantono\Contracts\Attributes\FieldName;
use Pantono\Customers\Customers;

class CustomerDetailField
{
    private ?int $id = null;
    private int $detailsId;
    #[Locator(methodName: 'getFieldById', className: Customers::class), FieldName('field_id')]
    private ?CustomerField $field = null;
    private mixed $value = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getDetailsId(): int
    {
        return $this->detailsId;
    }

    public function setDetailsId(int $detailsId): void
    {
        $this->detailsId = $detailsId;
    }

    public function getField(): ?CustomerField
    {
        return $this->field;
    }

    public function setField(?CustomerField $field): void
    {
        $this->field = $field;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}
