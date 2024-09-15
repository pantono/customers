<?php

namespace Pantono\Customers\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Pantono\Customers\Model\Customer;

abstract class AbstractCustomerEvent extends Event
{
    private Customer $current;
    private ?Customer $previous = null;

    public function getCurrent(): Customer
    {
        return $this->current;
    }

    public function setCurrent(Customer $current): void
    {
        $this->current = $current;
    }

    public function getPrevious(): ?Customer
    {
        return $this->previous;
    }

    public function setPrevious(?Customer $previous): void
    {
        $this->previous = $previous;
    }
}
