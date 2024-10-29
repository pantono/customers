<?php

namespace Pantono\Customers\Filter;

use Pantono\Customers\Model\CompanyStatus;
use Pantono\Database\Traits\Pageable;
use Pantono\Contracts\Filter\PageableInterface;

class CompanyFilter implements PageableInterface
{
    use Pageable;

    private ?CompanyStatus $status = null;
    private ?string $search = null;
    private ?\DateTimeInterface $dateCreatedStart = null;
    private ?\DateTimeInterface $dateCreatedEnd = null;
    private ?\DateTimeInterface $dateUpdatedStart = null;
    private ?\DateTimeInterface $dateUpdatedEnd = null;

    public function getStatus(): ?CompanyStatus
    {
        return $this->status;
    }

    public function setStatus(?CompanyStatus $status): void
    {
        $this->status = $status;
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function setSearch(?string $search): void
    {
        $this->search = $search;
    }

    public function getDateCreatedStart(): ?\DateTimeInterface
    {
        return $this->dateCreatedStart;
    }

    public function setDateCreatedStart(?\DateTimeInterface $dateCreatedStart): void
    {
        $this->dateCreatedStart = $dateCreatedStart;
    }

    public function getDateCreatedEnd(): ?\DateTimeInterface
    {
        return $this->dateCreatedEnd;
    }

    public function setDateCreatedEnd(?\DateTimeInterface $dateCreatedEnd): void
    {
        $this->dateCreatedEnd = $dateCreatedEnd;
    }

    public function getDateUpdatedStart(): ?\DateTimeInterface
    {
        return $this->dateUpdatedStart;
    }

    public function setDateUpdatedStart(?\DateTimeInterface $dateUpdatedStart): void
    {
        $this->dateUpdatedStart = $dateUpdatedStart;
    }

    public function getDateUpdatedEnd(): ?\DateTimeInterface
    {
        return $this->dateUpdatedEnd;
    }

    public function setDateUpdatedEnd(?\DateTimeInterface $dateUpdatedEnd): void
    {
        $this->dateUpdatedEnd = $dateUpdatedEnd;
    }
}
