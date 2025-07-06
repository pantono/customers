<?php

namespace Pantono\Customers\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pantono\Customers\Event\PreCustomerSaveEvent;

class PreCustomerSaveChecks implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PreCustomerSaveEvent::class => ['checkForCustomerSave', 255]
        ];
    }

    public function checkForCustomerSave(PreCustomerSaveEvent $event): void
    {
        $current = $event->getCurrent();
        $previous = $event->getPrevious();
        if ($previous) {
            if ($previous->getHash() !== $current->getHash()) {
                $current->setNeedsUpdate(true);
                if ($current->getDetails()) {
                    $current->getDetails()->setDateCreated(new \DateTime);
                }
            }
        }
    }
}
