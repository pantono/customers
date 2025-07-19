<?php

namespace Pantono\Customers\Repository;

use Pantono\Database\Repository\MysqlRepository;
use Pantono\Customers\Filter\CustomerFilter;
use Pantono\Database\Query\Select\Select;
use Pantono\Customers\Model\Customer;
use Pantono\Customers\Model\CustomerField;
use Pantono\Authentication\Model\User;
use Pantono\Customers\Model\CustomerDetail;

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
        if ($filter->getSearch() !== null) {
            $select->where('customer_list.name like ?', '%' . $filter->getSearch() . '%');
        }
        if ($filter->getEmail() !== null) {
            $select->where('customer_list.email like ?', '%' . $filter->getEmail() . '%');
        }
        $total = $this->getCount($select);
        $filter->setTotalResults($total);
        $select->limitPage($filter->getPage(), $filter->getPerPage());

        return $this->getDb()->fetchAll($select);
    }

    public function getCustomerByFilterSelect(CustomerFilter $filter): Select
    {
        $select = $this->getDb()->select()->from('customer')
            ->joinInner('customer_detail', 'customer.details_id=customer_detail.id', []);
        if ($filter->getSearch() !== null) {
            $select->where('CONCAT(customer_detail.forename, \' \', customer_detail.surname, \' \', customer_detail.emails, \' \', customer_detail.mobile_number) like ?', '%' . $filter->getSearch() . '%');
        }
        if ($filter->getEmail() !== null) {
            $select->where('customer_detail.email like ?', '%' . $filter->getEmail() . '%');
        }
        if ($filter->getFields() !== null) {
            $index = 0;
            foreach ($filter->getFields() as $name => $field) {
                $select->joinInner(['value_' . $index => 'customer_detail_field'], 'customer_detail.id=value_' . $index . '.details_id', [])
                    ->joinInner(['field_' . $index => 'customer_field'], 'value_' . $index . '.field_id=field_' . $index . '.id', [])
                    ->where('customer_field.name=?', $name)
                    ->where('customer_detail_field.value=?', $field);
                $index++;
            }
        }

        if ($filter->getExternalIdType() !== null) {
            $select->joinInner('customer_external_id', 'customer.id=customer_external_id.customer_id', []);
            if ($filter->getExternalIdValue()) {
                $select->where('customer_external_id.identifier=?', $filter->getExternalIdValue());
            }
        }
        return $select;
    }

    public function saveCustomer(Customer $customer): void
    {
        $details = $customer->getDetails();
        if (!$details) {
            throw new \RuntimeException('No customer details set before saving');
        }
        if ($customer->getId() === null) {
            $this->getDb()->insert('customer', [
                'date_created' => $customer->getDateCreated()->format('Y-m-d H:i:s'),
                'user_id' => $customer->getUser()?->getId(),
            ]);
            $customer->setId((int)$this->getDb()->lastInsertId());
            $details->setCustomerId($customer->getId());
        }
        $this->getDb()->insert('customer_detail', $details->getAllData());
        $details->setId((int)$this->getDb()->lastInsertId());
        $this->getDb()->update('customer', ['user_id' => $customer->getUser()?->getId(), 'details_id' => $details->getId()], ['id=?' => $customer->getId()]);
        foreach ($details->getFields() as $field) {
            $this->getDb()->insert('customer_detail_field', [
                'field_id' => $field->getField()->getId(),
                'value' => $field->getValue(),
                'details_id' => $details->getId()
            ]);
        }

        foreach ($customer->getExternalIds() as $externalId) {
            $externalId->setCustomerId($customer->getId());
            $externalIdId = $this->insertOrUpdate('customer_external_id', 'id', $externalId->getId(), $externalId->getAllData());
            if ($externalIdId) {
                $externalId->setId($externalIdId);
            }
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

    public function getAllFields(): array
    {
        return $this->selectAll('customer_field');
    }

    public function saveField(CustomerField $field): void
    {
        $id = $this->insertOrUpdateCheck('customer_field', 'id', $field->getId(), $field->getAllData());
        if ($id) {
            $field->setId($id);
        }
    }

    public function getExternalIdsForCustomer(Customer $customer): array
    {
        return $this->selectRowsByValues('customer_external_id', ['customer_id' => $customer->getId(), 'deleted' => 0]);
    }

    public function recreateFlatTable(): void
    {
        $this->getDb()->query('DROP TABLE IF EXISTS customer_flat;');
        $fields = [
            'user_id' => ['type' => 'int', 'null' => true, 'index' => true],
            'email' => ['type' => 'varchar(255)', 'null' => false, 'index' => true],
            'forename' => ['type' => 'varchar(255)', 'null' => true, 'index' => true],
            'surname' => ['type' => 'varchar(255)', 'null' => true, 'index' => true],
            'mobile_number' => ['type' => 'varchar(255)', 'null' => true, 'index' => true],
            'date_of_birth' => ['type' => 'date', 'null' => true, 'index' => false],
        ];
        $fieldSql = [];
        $indexes = [];
        foreach ($fields as $name => $config) {
            $fieldSql[] = '`' . $name . '` ' . $config['type'] . ($config['null'] ? ' NULL' : ' NOT NULL');
            if ($config['index'] === true) {
                $indexes[] = 'KEY `' . $name . '` (`' . $name . '`)';
            }
        }
        foreach ($this->getAllFields() as $fieldConfig) {
            $config = json_decode($fieldConfig['config'], true);
            $type = $config['sql_type'] ?? $fieldConfig['type'];
            if ($type === 'select') {
                $type = 'varchar(255)';
            }
            if ($type === 'string') {
                $type = 'varchar(255)';
            }
            if ($type === 'boolean') {
                $type = 'tinyint(1)';
            }
            $index = $config['index'] ?? false;
            if ($index === true) {
                $indexes[] = 'KEY `' . $fieldConfig['name'] . '` (`' . $fieldConfig['name'] . '`)';
            }
            $fieldSql[] = '`' . $fieldConfig['name'] . '` ' . $type . ' NULL';
        }

        $sql = 'CREATE TABLE customer_flat ( `id` int unsigned NOT NULL AUTO_INCREMENT,' . implode(',' . PHP_EOL, $fieldSql) . ',' . PHP_EOL . 'PRIMARY KEY (`id`),' . PHP_EOL . implode(',' . PHP_EOL, $indexes) . ')';
        $this->getDb()->query($sql);

        $select = $this->getCustomerFlatBaseSelect();

        $this->getDb()->query('INSERT into customer_flat (' . $select->__toString() . ')');
    }

    public function getCustomerFlatBaseSelect(): Select
    {
        $select = $this->getDb()->select()->from('customer', ['id', 'user_id'])
            ->joinInner('customer_detail', 'customer.details_id=customer_detail.id', ['email', 'forename', 'surname', 'mobile_number', 'date_of_birth']);

        $index = 0;
        foreach ($this->getAllFields() as $fieldConfig) {
            $index++;
            $select->joinLeft(['field_' . $index => 'customer_detail_field'], 'customer_detail.id=field_' . $index . '.details_id and field_' . $index . '.field_id=' . $fieldConfig['id'], ['value AS ' . $fieldConfig['name']]);
        }

        return $select;
    }

    public function updateCustomerFlat(Customer $customer): void
    {
        $this->getDb()->delete('customer_flat', ['id=?' => $customer->getId()]);

        if (!$customer->getDetails()) {
            return;
        }

        $fields = [
            'id' => $customer->getId(),
            'user_id' => $customer->getUser()?->getId(),
            'forename' => $customer->getDetails()->getForename(),
            'surname' => $customer->getDetails()->getSurname(),
            'email' => $customer->getDetails()->getEmail(),
            'mobile_number' => $customer->getDetails()->getMobileNumber(),
            'date_of_birth' => $customer->getDetails()->getDateOfBirth()?->format('Y-m-d'),
        ];

        foreach ($customer->getDetails()->getFields() as $field) {
            if ($field->getField()) {
                $fields[$field->getField()->getName()] = $field->getValue();
            }
        }
        try {
            $this->getDb()->insert('customer_flat', $fields);
        } catch (\PDOException $e) {

        }
    }

    public function getCustomerByEmail(string $email): ?array
    {
        $select = $this->getDb()->select()->from('customer')
            ->joinInner('customer_detail', 'customer.details_id=customer_detail.id', [])
            ->where('customer_detail.email=?', $email);

        return $this->selectSingleRowFromQuery($select);
    }

    public function getDetailsById(int $id): ?array
    {
        return $this->selectSingleRow('customer_detail', 'id', $id);
    }

    public function addHistoryToCustomer(Customer $customer, ?User $user, string $entry): void
    {
        $this->getDb()->insert('customer_history', [
            'customer_id' => $customer->getId(),
            'date' => (new \DateTime)->format('Y-m-d H:i:s'),
            'user_id' => $user?->getId(),
            'entry' => $entry,
        ]);
    }

    public function getFieldsForCustomerDetail(CustomerDetail $detail): array
    {
        return $this->selectRowsByValues('customer_detail_field', ['details_id' => $detail->getId()]);
    }

    public function getCustomerByUserId(int $id): ?array
    {
        return $this->selectSingleRow('customer', 'user_id', $id);
    }
}
