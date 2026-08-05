<?php

namespace Pantono\Customers\Repository;

use Pantono\Database\Repository\DefaultRepository;
use Pantono\Customers\Filter\CustomerFilter;
use Pantono\Customers\Model\Customer;
use Pantono\Customers\Model\CustomerField;
use Pantono\Authentication\Model\User;
use Pantono\Customers\Model\CustomerDetail;
use Pantono\Database\Adapter\MysqlDb;
use Pantono\Database\Adapter\PgsqlDb;
use Pantono\Database\Adapter\MssqlDb;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\ArrayParameterType;

class CustomersRepository extends DefaultRepository
{
    public function getCustomerById(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('customer'), 'id', $id);
    }

    public function getCustomerListById(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('customer_list'), 'id', $id);
    }

    public function getCustomersByFilter(CustomerFilter $filter): array
    {
        $select = $this->getCustomerByFilterSelect($filter);

        $this->applyCountAndLimit($select, $filter);

        return $this->getDb()->fetchAll($select);
    }

    public function getCustomerListByFilter(CustomerFilter $filter): array
    {
        $select = $this->getDb()->select('l.*')->from($this->pt('customer_list'), 'l');
        if ($filter->getSearch() !== null) {
            $select->where('l.name like :search')
                ->setParameter('search', '%' . $filter->getSearch() . '%');
        }
        if ($filter->getEmail() !== null) {
            $select->where('customer_list.email like :email')
                ->setParameter('email', $filter->getEmail());
        }
        $this->applyCountAndLimit($select, $filter);

        return $this->getDb()->fetchAll($select);
    }

    public function getCustomerByFilterSelect(CustomerFilter $filter): QueryBuilder
    {
        $select = $this->getDb()->select('c.*')->from($this->pt('customer'), 'c')
            ->innerJoin('c', $this->pt('customer_detail'), 'd', 'c.details_id=d.id');
        if ($filter->getSearch() !== null) {
            $select->where('CONCAT(d.forename, \' \', d.surname, \' \', d.email, \' \', d.mobile_number) like :search')
                ->setParameter('search', '%' . $filter->getSearch() . '%');
        }
        if ($filter->getEmail() !== null) {
            $select->where('d.email like :email')
                ->setParameter('email', $filter->getEmail());
        }
        if ($filter->getFields() !== null) {
            $index = 0;
            foreach ($filter->getFields() as $name => $field) {
                $select->innerJoin('d', $this->pt('customer_detail_field'), 'value_' . $index, 'd.id=value_' . $index . '.details_id')
                    ->innerJoin('value_' . $index, $this->pt('customer_field'), 'field_' . $index, 'value_' . $index . '.field_id=field_' . $index . '.id')
                    ->where('field_' . $index . '.name=:name_' . $index, $name)
                    ->setParameter('name_' . $index, $name)
                    ->where('value_' . $index . '.value=:value_' . $index, $field)
                    ->setParameter('value_' . $index, $field);
                $index++;
            }
        }

        if ($filter->getExternalIdType() !== null) {
            $select->innerJoin('c', $this->pt('customer_external_id'), 'external_id', 'c.id=external_id.customer_id');
            if ($filter->getExternalIdValue()) {
                $select->where('external_id.identifier=:id_value')
                    ->setParameter('id_value', $filter->getExternalIdValue());
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
            $this->getDb()->insert($this->pt('customer'), [
                'date_created' => $customer->getDateCreated()->format('Y-m-d H:i:s'),
                'user_id' => $customer->getUser()?->getId(),
            ]);
            $customer->setId((int)$this->getDb()->lastInsertId());
            $details->setCustomerId($customer->getId());
        }
        $this->getDb()->insert($this->pt('customer_detail'), $details->getAllData());
        $details->setId((int)$this->getDb()->lastInsertId());
        $this->getDb()->update($this->pt('customer'), ['user_id' => $customer->getUser()?->getId(), 'details_id' => $details->getId()], ['id' => $customer->getId()]);
        foreach ($details->getFields() as $field) {
            $this->getDb()->insert($this->pt('customer_detail_field'), [
                'field_id' => $field->getField()->getId(),
                'value' => $field->getValue(),
                'details_id' => $details->getId()
            ]);
        }

        foreach ($customer->getExternalIds() as $externalId) {
            $externalId->setCustomerId($customer->getId());
            $externalIdId = $this->insertOrUpdate($this->pt('customer_external_id'), 'id', $externalId->getId(), $externalId->getAllData());
            if ($externalIdId) {
                $externalId->setId($externalIdId);
            }
        }

        $deleteQb = $this->getDb()->createQueryBuilder()->delete($this->pt('customer_locations'))
            ->andWhere('customer_id = :id')
            ->setParameter('id', $customer->getId());
        $doneIds = [];
        foreach ($customer->getLocations() as $location) {
            $location->setCustomerId($customer->getId());
            $id = $this->insertOrUpdateCheck($this->pt('customer_locations'), 'id', $location->getId(), $location->getAllData());
            if ($id) {
                $location->setId($id);
            }
            $doneIds[] = $id;
        }
        if (!empty($doneIds)) {
            $deleteQb->andWhere('id not in (:ids)')
                ->setParameter('ids', $doneIds, ArrayParameterType::INTEGER);
        }
        $deleteQb->executeQuery();
    }

    public function getFieldById(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('customer_field'), 'id', $id);
    }

    public function getFieldByName(string $name): ?array
    {
        return $this->selectSingleRow($this->pt('customer_field'), 'name', $name);
    }

    public function getAllFields(): array
    {
        return $this->selectAll($this->pt('customer_field'));
    }

    public function saveField(CustomerField $field): void
    {
        $id = $this->insertOrUpdateCheck($this->pt('customer_field'), 'id', $field->getId(), $field->getAllData());
        if ($id) {
            $field->setId($id);
        }
    }

    public function getExternalIdsForCustomer(Customer $customer): array
    {
        return $this->selectRowsByValues($this->pt('customer_external_id'), ['customer_id' => $customer->getId(), 'deleted' => 0]);
    }

    public function recreateFlatTable(): void
    {
        $db = $this->getDb();
        $isMysql = $db instanceof MysqlDb;
        $isPgsql = $db instanceof PgsqlDb;
        $isMssql = $db instanceof MssqlDb;

        if ($isMysql || $isPgsql) {
            $db->query('DROP TABLE IF EXISTS customer_flat;');
        } elseif ($isMssql) {
            $db->query("IF OBJECT_ID('customer_flat', 'U') IS NOT NULL DROP TABLE customer_flat;");
        }

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
        $quote = $isMysql ? '`' : ($isPgsql ? '"' : ($isMssql ? '[' : ''));
        $quoteEnd = $isMssql ? ']' : $quote;

        foreach ($fields as $name => $config) {
            $type = $config['type'];
            if ($isPgsql && $type === 'int') {
                $type = 'integer';
            }
            $fieldSql[] = $quote . $name . $quoteEnd . ' ' . $type . ($config['null'] ? ' NULL' : ' NOT NULL');
            if ($config['index'] === true) {
                if ($isMysql) {
                    $indexes[] = 'KEY ' . $quote . $name . $quoteEnd . ' (' . $quote . $name . $quoteEnd . ')';
                } else {
                    $indexes[] = 'CREATE INDEX ' . $quote . 'idx_customer_flat_' . $name . $quoteEnd . ' ON ' . $quote . 'customer_flat' . $quoteEnd . ' (' . $quote . $name . $quoteEnd . ')';
                }
            }
        }
        $allFields = $this->getAllFields();
        foreach ($allFields as &$fieldConfig) {
            $config = json_decode($fieldConfig['config'], true);
            $type = $config['sql_type'] ?? $fieldConfig['type'];
            if ($type === 'select' || $type === 'string') {
                $type = 'varchar(255)';
            }
            if ($type === 'boolean') {
                $type = $isMysql ? 'tinyint(1)' : ($isPgsql ? 'boolean' : 'bit');
            }
            if ($isPgsql && $type === 'int') {
                $type = 'integer';
            }
            $fieldConfig['resolved_type'] = $type;

            $index = $config['index'] ?? false;
            if ($index === true) {
                if ($isMysql) {
                    $indexes[] = 'KEY ' . $quote . $fieldConfig['name'] . $quoteEnd . ' (' . $quote . $fieldConfig['name'] . $quoteEnd . ')';
                } else {
                    $indexes[] = 'CREATE INDEX ' . $quote . 'idx_customer_flat_' . $fieldConfig['name'] . $quoteEnd . ' ON ' . $quote . 'customer_flat' . $quoteEnd . ' (' . $quote . $fieldConfig['name'] . $quoteEnd . ')';
                }
            }
            $fieldSql[] = $quote . $fieldConfig['name'] . $quoteEnd . ' ' . $type . ' NULL';
        }

        $idSql = $quote . 'id' . $quoteEnd;
        if ($isMysql) {
            $idSql .= ' int unsigned NOT NULL AUTO_INCREMENT';
        } elseif ($isPgsql) {
            $idSql .= ' SERIAL';
        } elseif ($isMssql) {
            $idSql .= ' INT IDENTITY(1,1) NOT NULL';
        }

        $sql = 'CREATE TABLE ' . $this->pt('customer_flat') . ' (' . $idSql . ',' . implode(',' . PHP_EOL, $fieldSql) . ',' . PHP_EOL . 'PRIMARY KEY (' . $quote . 'id' . $quoteEnd . ')';
        if ($isMysql) {
            $sql .= ',' . PHP_EOL . implode(',' . PHP_EOL, $indexes);
        }
        $sql .= ')';

        $db->query($sql);

        if (!$isMysql) {
            foreach ($indexes as $indexSql) {
                $db->query($indexSql);
            }
        }

        $select = $this->getCustomerFlatBaseSelect($allFields);

        $db->query('INSERT into ' . $this->pt('customer_flat') . ' (' . $select->__toString() . ')');
    }

    public function getCustomerFlatBaseSelect(?array $fields = null): QueryBuilder
    {
        $db = $this->getDb();
        $isPgsql = $db instanceof PgsqlDb;
        $select = $this->getDb()
            ->select('c.id, c.user_id', 'd.email', 'd.forename', 'd.surname', 'd.mobile_number', 'd.date_of_birth')
            ->from($this->pt('customer'), 'c')
            ->innerJoin('c', $this->pt('customer_detail'), 'd', 'c.details_id=d.id');
        $index = 0;
        foreach ($fields ?? $this->getAllFields() as $fieldConfig) {
            $index++;
            $valueExpr = 'field_' . $index . '.value';
            if ($isPgsql) {
                $type = null;
                if (isset($fieldConfig['resolved_type'])) {
                    $type = $fieldConfig['resolved_type'];
                } else {
                    $config = json_decode($fieldConfig['config'], true);
                    $type = $config['sql_type'] ?? $fieldConfig['type'];
                    if ($type === 'select' || $type === 'string') {
                        $type = 'varchar(255)';
                    }
                    if ($type === 'boolean') {
                        $type = 'boolean';
                    }
                    if ($type === 'int') {
                        $type = 'integer';
                    }
                }
                if ($type) {
                    $valueExpr = 'CAST(' . $valueExpr . ' AS ' . $type . ')';
                }
            }
            $select->leftJoin('d', $this->pt('customer_detail_field'), 'field_' . $index, 'field_' . $index . '.details_id=d.id and field_' . $index . '.field_id=' . $fieldConfig['id']);
            $select->addSelect($valueExpr . ' as ' . $fieldConfig['name']);
        }

        return $select;
    }

    public function updateCustomerFlat(Customer $customer): void
    {
        $this->getDb()->createQueryBuilder()->delete($this->pt('customer_flat'))->where('id = :id')->setParameter('id', $customer->getId())->executeQuery();

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
            $this->getDb()->insert($this->pt('customer_flat'), $fields);
        } catch (\PDOException $e) {

        }
    }

    public function getCustomerByEmail(string $email): ?array
    {
        $select = $this->getDb()->select('c.*')->from('customer', 'c')
            ->innerJoin('c', $this->pt('customer_detail'), 'd', 'c.details_id=d.id')
            ->where('d.email=:email')
            ->setParameter('email', $email);

        return $this->getDb()->fetchRow($select);
    }

    public function getDetailsById(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('customer_detail'), 'id', $id);
    }

    public function addHistoryToCustomer(Customer $customer, ?User $user, string $entry): void
    {
        $this->getDb()->insert($this->pt('customer_history'), [
            'customer_id' => $customer->getId(),
            'date' => (new \DateTime)->format('Y-m-d H:i:s'),
            'user_id' => $user?->getId(),
            'entry' => $entry,
        ]);
    }

    public function getFieldsForCustomerDetail(CustomerDetail $detail): array
    {
        return $this->selectRowsByValues($this->pt('customer_detail_field'), ['details_id' => $detail->getId()]);
    }

    public function getCustomerByUserId(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('customer'), 'user_id', $id);
    }

    public function getLocationsForCustomer(int $id): array
    {
        $select = $this->getDb()->select('cl.*')->from('customer_locations', 'cl')
            ->innerJoin('cl', $this->pt('location'), 'l', 'cl.location_id=l.id')
            ->where('l.deleted=0')
            ->where('cl.customer_id=:customer_id')
            ->setParameter('customer_id', $id);

        return $this->getDb()->fetchAll($select);
    }
}
