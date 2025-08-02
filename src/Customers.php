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
use Pantono\Customers\Model\CustomerExternalId;
use Pantono\Customers\Filter\CustomerFilter;
use Pantono\Customers\Model\CustomerDetail;
use Pantono\Authentication\Model\User;
use Pantono\Customers\Model\CustomerDetailField;
use Pantono\Authentication\Users;

class Customers
{
    private CustomersRepository $repository;
    private Hydrator $hydrator;
    private EventDispatcher $dispatcher;
    private Users $users;

    public function __construct(CustomersRepository $repository, Hydrator $hydrator, EventDispatcher $dispatcher, Users $users)
    {
        $this->repository = $repository;
        $this->hydrator = $hydrator;
        $this->dispatcher = $dispatcher;
        $this->users = $users;
    }

    public function getCustomerById(int $id): ?Customer
    {
        return $this->hydrator->hydrate(Customer::class, $this->repository->getCustomerById($id));
    }

    public function getCustomerByEmail(string $email): ?Customer
    {
        return $this->hydrator->hydrate(Customer::class, $this->repository->getCustomerByEmail($email));
    }

    public function getCustomerListById(int $id): ?CustomerList
    {
        return $this->hydrator->hydrate(CustomerList::class, $this->repository->getCustomerListById($id));
    }

    /**
     * @return CustomerExternalId[]
     */
    public function getExternalIdsForCustomer(Customer $customer): array
    {
        return $this->hydrator->hydrateSet(CustomerExternalId::class, $this->repository->getExternalIdsForCustomer($customer));
    }

    public function getDetailsById(int $id): ?CustomerDetail
    {
        return $this->hydrator->hydrate(CustomerDetail::class, $this->repository->getDetailsById($id));
    }

    /**
     * @param CustomerFilter $filter
     * @return Customer[]
     */
    public function getCustomersByFilter(CustomerFilter $filter): array
    {
        return $this->hydrator->hydrateSet(Customer::class, $this->repository->getCustomersByFilter($filter));
    }

    public function getCustomerByExternalIdentifier(string $identifierType, mixed $identifier): ?Customer
    {
        $filter = new CustomerFilter();
        $filter->setExternalIdType($identifierType);
        $filter->setExternalIdValue($identifier);
        $filter->setPerPage(1);
        $filter->setPage(1);

        $customers = $this->getCustomersByFilter($filter);
        return $customers[0];
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
        if ($customer->isNeedsUpdate() === false) {
            return;
        }
        $this->repository->saveCustomer($customer);

        $event = new PostCustomerSaveEvent();
        $event->setCurrent($customer);
        if ($previous) {
            $event->setPrevious($previous);
        }
        $this->dispatcher->dispatch($event);
    }


    public function customerFromParameters(ParameterBag $parameters, bool $autoCreateFields = false): Customer
    {
        $customer = null;
        if ($parameters->has('id')) {
            $customer = $this->getCustomerById($parameters->get('id'));
            if (!$customer) {
                $customer = new Customer();
                $customer->setDateCreated(new \DateTime);
                $customer->setDetails(new CustomerDetail());
            }
        } elseif ($parameters->has('email')) {
            $customer = $this->getCustomerByEmail($parameters->get('email'));
            if (!$customer) {
                $customer = new Customer();
                $customer->setDateCreated(new \DateTime);
                $customer->setDetails(new CustomerDetail());
                $customer->setNeedsUpdate(true);
            }
        }
        if (!$customer) {
            $customer = new Customer();
            $customer->setDateCreated(new \DateTime);;
            $customer->setDetails(new CustomerDetail());
            $customer->setNeedsUpdate(true);
        }
        if ($parameters->has('user_id')) {
            if (!$customer->getUser()) {
                $user = $this->users->getUserById($parameters->get('user_id'));
                if ($user) {
                    if ($user->isSystemUser() === false) {
                        $current = $this->getCustomerByUserId($parameters->get('user_id'));
                        if (!$current) {
                            $customer->setUser($user);
                            $customer->setNeedsUpdate(true);
                        }
                    }
                }
            }
        }
        $details = $customer->getDetails();
        if (!$details) {
            $details = new CustomerDetail();
            $details->setDateCreated(new \DateTime);
            $customer->setDetails($details);
        }
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
        $this->saveCustomer($customer);
        if ($parameters->has('create_user')) {
            $userParams = [
                'forename' => $customer->getDetails()->getForename(),
                'surname' => $customer->getDetails()->getSurname(),
                'email_address' => $customer->getDetails()->getEmail()
            ];
            if ($parameters->has('password')) {
                $userParams['password'] = $parameters->get('password');
            }
            $user = $this->users->createUser($userParams);
            $customer->setUser($user);
        }
        return $customer;
    }

    /**
     * @return CustomerDetailField[]
     */
    public function getFieldsForCustomerDetails(CustomerDetail $detail): array
    {
        return $this->hydrator->hydrateSet(CustomerDetailField::class, $this->repository->getFieldsForCustomerDetail($detail));
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

    public function recreateFlatTable(): void
    {
        $this->repository->recreateFlatTable();
    }

    public function updateCustomerFlat(Customer $customer): void
    {
        $this->repository->updateCustomerFlat($customer);
    }

    public function addHistoryToCustomer(Customer $customer, ?User $user, string $entry): void
    {
        $this->repository->addHistoryToCustomer($customer, $user, $entry);
    }

    public function getCustomerByUserId(int $id): ?Customer
    {
        return $this->hydrator->hydrate(Customer::class, $this->repository->getCustomerByUserId($id));
    }
}
