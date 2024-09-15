<?php

namespace Pantono\Customers\Repository;

use Pantono\Database\Repository\MysqlRepository;
use Pantono\Customers\Filter\CustomerFilter;
use Pantono\Database\Query\Select\Select;
use Pantono\Customers\Model\Customer;
use Pantono\Customers\Model\CustomerField;

class CustomersRepository extends MysqlRepository
{
    public function getCustomerById(int $id): ?array
    {
        return $this->selectSingleRow('customer', 'id', $id);
    }

    public function getCustomerListById(int $id): ?array
    {
        return $this->selectSingleRow('customer_list', 'id', $id);
    }

    public function getCustomersByFilter(CustomerFilter $filter): array
    {
        $select = $this->getCustomerByFilterSelect($filter);

        $filter->setTotalResults($this->getCount($select));
        $select->limitPage($filter->getPage(), $filter->getPerPage());

        return $this->getDb()->fetchAll($select);
    }

    public function getCustomerListByFilter(CustomerFilter $filter): array
    {
        $select = $this->getDb()->select()->from('customer_list');

        return $this->getDb()->fetchAll($select);

    }

    public function getCustomerByFilterSelect(CustomerFilter $filter): Select
    {
        $select = $this->getDb()->select()->from('customer')
            ->joinInner('customer_details', 'customer.details_id=customer_details.id', []);
        if ($filter->getSearch() !== null) {
            $select->where('CONCAT(customer_details.forename, \' \', customer_details.surname, \' \', customer_details.emails, \' \', customer_details.mobile_number) like ?', '%' . $filter->getSearch() . '%');
        }
        if ($filter->getEmail() !== null) {
            $select->where('customer_details.email like ?', '%' . $filter->getEmail() . '%');
        }
        if ($filter->getFields() !== null) {
            $index = 0;
            foreach ($filter->getFields() as $name => $field) {
                $select->joinInner(['value_' . $index => 'customer_details_field'], 'customer_details.id=value_' . $index . '.details_id', [])
                    ->joinInner(['field_' . $index => 'customer_field'], 'value_' . $index . '.field_id=field_' . $index . '.id', [])
                    ->where('customer_field.name=?', $name)
                    ->where('customer_details_field.value=?', $field);
                $index++;
            }
        }
        return $select;
    }

    public function saveCustomer(Customer $customer): void
    {
        $details = $customer->getDetails();
        if ($customer->getId() === null) {
            $this->getDb()->insert('customer', [
                'date_created' => $customer->getDateCreated()->format('Y-m-d H:i:s'),
                'user_id' => $customer->getUser()?->getId(),
            ]);
            $customer->setId((int)$this->getDb()->lastInsertId());
            $details->setCustomerId($customer->getId());
            $this->getDb()->insert('customer_details', [
                'customer_id' => $customer->getId(),
                'date_created' => $customer->getDateCreated()->format('Y-m-d H:i:s'),
                'email' => $details->getEmail(),
                'forename' => $details->getForename(),
                'surname' => $details->getSurname(),
                'mobile_number' => $details->getMobileNumber(),
                'date_of_birth' => $details->getDateOfBirth()?->format('Y-m-d')
            ]);
            $customer->getDetails()->setId((int)$this->getDb()->lastInsertId());
        } else {
            $this->getDb()->insert('customer_details', [
                'customer_id' => $customer->getId(),
                'date_created' => $customer->getDateCreated()->format('Y-m-d H:i:s'),
                'email' => $details->getEmail(),
                'forename' => $details->getForename(),
                'surname' => $details->getSurname(),
                'mobile_number' => $details->getMobileNumber(),
                'date_of_birth' => $details->getDateOfBirth()?->format('Y-m-d')
            ]);
            $customer->getDetails()->setId((int)$this->getDb()->lastInsertId());
            $this->getDb()->update('customer', ['user_id' => $customer->getUser()?->getId(), 'details_id' => $details->getCustomerId()], ['id' => $customer->getId()]);
        }
        foreach ($customer->getDetails()->getFields() as $field) {
            $this->getDb()->insert('customer_field', [
                'field_id' => $field->getField()->getId(),
                'value' => $field->getValue(),
                'details_id' => $customer->getDetails()->getId()
            ]);
        }
    }

    public function getFieldById(int $id): ?array
    {
        return $this->selectSingleRow('customer_field', 'id', $id);
    }

    public function getFieldByName(string $name): ?array
    {
        return $this->selectSingleRow('customer_field', 'name', $name);
    }

    public function saveField(CustomerField $field): void
    {
        $id = $this->insertOrUpdateCheck('customer_field', 'id', $field->getId(), $field->getAllData());
        if ($id) {
            $field->setId($id);
        }
    }
}
