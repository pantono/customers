<?php

namespace Pantono\Customers\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Pantono\Customers\Model\CustomerField;

abstract class AbstractCustomerFieldEvent extends Event
{
    private CustomerField $current;
    private ?CustomerField $previous = null;

    public function getCurrent(): CustomerField
    {
        return $this->current;
    }

    public function setCurrent(CustomerField $current): void
    {
        $this->current = $current;
    }

    public function getPrevious(): ?CustomerField
    {
        return $this->previous;
    }

    public function setPrevious(?CustomerField $previous): void
    {
        $this->previous = $previous;
    }
}
