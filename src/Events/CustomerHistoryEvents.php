<?php

namespace Pantono\Customers\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pantono\Customers\Customers;
use Pantono\Customers\Event\PostCustomerSaveEvent;
use Pantono\Contracts\Security\SecurityContextInterface;
use Pantono\Customers\Model\Customer;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\Users;

class CustomerHistoryEvents implements EventSubscriberInterface
{
    private Customers $customers;
    private SecurityContextInterface $securityContext;
    private Users $users;

    public function __construct(Customers $customers, SecurityContextInterface $securityContext, Users $users)
    {
        $this->customers = $customers;
        $this->securityContext = $securityContext;
        $this->users = $users;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostCustomerSaveEvent::class => ['addHistory', -255]
        ];
    }

    public function addHistory(PostCustomerSaveEvent $event): void
    {
        $current = $event->getCurrent();
        $previous = $event->getPrevious();
        if (!$previous) {
            $this->logHistoryEvent($current, 'Created new customer record');
            return;
        }
        foreach ($current->getUpdates($previous) as $update) {
            $this->logHistoryEvent($current, sprintf('Updated %s from %s to %s', $update['field'], $update['old'], $update['new']));;
        }
    }

    private function logHistoryEvent(Customer $customer, string $entry): void
    {
        $this->customers->addHistoryToCustomer($customer, $this->getUser(), $entry);
    }

    private function getUser(): ?User
    {
        /**
         * @var ?User $user
         */
        $user = $this->securityContext->get('user');
        if (!$user) {
            /**
             * @var ?User $user
             */
            $user = $this->users->getUserById(1);
        }
        return $user;
    }
}
