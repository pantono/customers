<?php

namespace Pantono\Customers\Filter;

use Pantono\Database\Traits\Pageable;
use Pantono\Contracts\Filter\PageableInterface;

class CustomerFilter implements PageableInterface
{
    use Pageable;

    private ?string $search = null;
    private ?string $email = null;
    /**
     * @var array<string,mixed>
     */
    private ?array $fields = null;

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function setSearch(?string $search): void
    {
        $this->search = $search;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function addField(string $name, mixed $value): void
    {
        if ($this->fields === null) {
            $this->fields = [];
        }
        $this->fields[$name] = $value;
    }

    public function getFields(): ?array
    {
        return $this->fields;
    }
}
