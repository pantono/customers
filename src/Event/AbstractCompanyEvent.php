<?php

namespace Pantono\Customers\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Pantono\Customers\Model\Company;

abstract class AbstractCompanyEvent extends Event
{
    private Company $current;
    private ?Company $previous;

    public function getCurrent(): Company
    {
        return $this->current;
    }

    public function setCurrent(Company $current): void
    {
        $this->current = $current;
    }

    public function getPrevious(): ?Company
    {
        return $this->previous;
    }

    public function setPrevious(?Company $previous): void
    {
        $this->previous = $previous;
    }
}
