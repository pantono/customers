<?php

namespace Pantono\Customers;

use Pantono\Customers\Repository\CustomersRepository;
use Pantono\Hydrator\Hydrator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pantono\Customers\Model\Customer;
use Pantono\Customers\Model\CustomerList;
use Pantono\Customers\Event\PreCustomerSaveEvent;
use Pantono\Customers\Event\PostCustomerSaveEvent;
use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Utilities\DateTimeParser;
use Pantono\Customers\Model\CustomerField;
use Pantono\Customers\Event\PreCustomerFieldSaveEvent;
use Pantono\Customers\Event\PostCustomerFieldSaveEvent;

class Customers
{
    private CustomersRepository $repository;
    private Hydrator $hydrator;
    private EventDispatcher $dispatcher;

    public function __construct(CustomersRepository $repository, Hydrator $hydrator, EventDispatcher $dispatcher)
    {
        $this->repository = $repository;
        $this->hydrator = $hydrator;
        $this->dispatcher = $dispatcher;
    }

    public function getCustomerById(int $id): ?Customer
    {
        return $this->hydrator->hydrate(Customer::class, $this->repository->getCustomerById($id));
    }

    public function getCustomerListById(int $id): ?CustomerList
    {
        return $this->hydrator->hydrate(CustomerList::class, $this->repository->getCustomerListById($id));
    }

    public function saveCustomer(Customer $customer): void
    {
        $event = new PreCustomerSaveEvent();
        $event->setCurrent($customer);
        $previous = null;
        if ($customer->getId()) {
            $previous = $this->getCustomerById($customer->getId());
        }
        if ($previous) {
            $event->setPrevious($previous);
        }
        $this->dispatcher->dispatch($event);

        $this->repository->saveCustomer($customer);

        $event = new PostCustomerSaveEvent();
        $event->setCurrent($customer);
        if ($previous) {
            $event->setPrevious($previous);
        }
        $this->dispatcher->dispatch($event);
    }

    public function updateCustomerFromParameters(Customer $customer, ParameterBag $parameters, bool $autoCreateFields = false): void
    {
        $details = $customer->getDetails();
        if ($parameters->get('email') !== null) {
            $details->setEmail($parameters->get('email'));
        }
        if ($parameters->get('forename') !== null) {
            $details->setForename($parameters->get('forename'));
        }
        if ($parameters->get('surname') !== null) {
            $details->setSurname($parameters->get('surname'));
        }
        if ($parameters->get('mobile_number') !== null) {
            $details->setMobileNumber($parameters->get('mobile_number'));
        }
        if ($parameters->get('date_of_birth') !== null) {
            if ($parameters->get('date_of_birth') instanceof \DateTimeInterface) {
                $details->setDateOfBirth($parameters->get('date_of_birth'));
            } else {
                $details->setDateOfBirth(DateTimeParser::parseDate($parameters->get('date_of_birth')));
            }
        }
        foreach ($parameters->get('fields') as $field => $value) {
            $fieldType = $this->getFieldByName($field);
            if (!$fieldType && $autoCreateFields === true) {
                $fieldType = new CustomerField();
                $fieldType->setName($field);
                $fieldType->setLabel($field);
                $this->saveField($fieldType);
            }
            if ($fieldType) {
                $details->updateFieldValue($fieldType, $value);
            }
        }
    }

    public function getFieldById(int $id): ?CustomerField
    {
        return $this->hydrator->hydrate(CustomerField::class, $this->repository->getFieldById($id));
    }

    public function getFieldByName(string $name): ?CustomerField
    {
        return $this->hydrator->hydrate(CustomerField::class, $this->repository->getFieldByName($name));
    }

    public function saveField(CustomerField $field): void
    {
        $event = new PreCustomerFieldSaveEvent();
        $previous = null;
        if ($field->getId() !== null) {
            $previous = $this->getFieldById($field->getId());
        }
        $event->setPrevious($previous);
        $event->setCurrent($field);
        $this->dispatcher->dispatch($event);

        $this->repository->saveField($field);

        $event = new PostCustomerFieldSaveEvent();
        $event->setPrevious($previous);
        $event->setCurrent($field);
        $this->dispatcher->dispatch($event);
    }
}
