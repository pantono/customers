<?php

namespace Pantono\Customers\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pantono\Customers\Customers;
use Pantono\Customers\Event\PostCustomerSaveEvent;

class UpdateCustomerFlat implements EventSubscriberInterface
{
    private Customers $customers;

    public function __construct(Customers $customers)
    {
        $this->customers = $customers;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostCustomerSaveEvent::class => ['updateFlatEntry', -255]
        ];
    }

    public function updateFlatEntry(PostCustomerSaveEvent $event): void
    {
        $this->customers->updateCustomerFlat($event->getCurrent());
    }
}
