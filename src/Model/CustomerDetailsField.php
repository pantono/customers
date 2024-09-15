<?php

namespace Pantono\Customers\Model;

class CustomerDetailsField
{
    private ?int $id = null;
    private int $detailsId;
    private CustomerField $field;
    private mixed $value;

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

    public function getField(): CustomerField
    {
        return $this->field;
    }

    public function setField(CustomerField $field): void
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
