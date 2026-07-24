<?php

namespace Pantono\Customers\Repository;

use Pantono\Customers\Model\Company;
use Pantono\Customers\Filter\CompanyFilter;
use Pantono\Database\Repository\DefaultRepository;
use Doctrine\DBAL\ArrayParameterType;

class CompaniesRepository extends DefaultRepository
{
    public function getCompanyById(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('company'), 'id', $id);
    }

    public function getCompanyStatusById(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('company_status'), 'id', $id);
    }

    public function getAllCompanyStatuses(): array
    {
        return $this->selectAll($this->pt('company_status'), 'name');
    }

    public function saveCompany(Company $company): void
    {
        $id = $this->insertOrUpdateCheck($this->pt('company'), 'id', $company->getId(), $company->getAllData());
        if ($id) {
            $company->setId($id);
        }
        $this->getDb()->delete($this->pt('company_file'), ['company_id' => $company->getId()]);
        foreach ($company->getFiles() as $file) {
            $this->getDb()->insert($this->pt('company_file'), ['company_id' => $company->getId(), 'file_id' => $file->getId()]);
        }

        $qb = $this->getDb()->createQueryBuilder();
        $qb->delete('company_field')
            ->andWhere('company_id = :company_id')
            ->setParameter('company_id', $company->getId());
        $ids = [];
        foreach ($company->getFields() as $field) {
            $field->setCompanyId($company->getId());
            $id = $this->insertOrUpdateCheck('company_field', 'id', $field->getId(), $field->getAllData());
            if ($id) {
                $field->setId($id);
            }
            $ids[] = $id;
        }
        if (!empty($ids)) {
            $qb->andWhere('id NOT IN (:ids)')
                ->setParameter('ids', $ids, ArrayParameterType::INTEGER);
        }
        $qb->executeQuery();
    }

    public function getFilesForCompany(int $companyId): array
    {
        $select = $this->getDb()->select('sf.*')->from($this->pt('company_file'), 'cf')
            ->innerJoin('cf', $this->pt('stored_file'), 'sf', 'cf.file_id=sf.id')
            ->where('cf.company_id=:company_id')
            ->setParameter('company_id', $companyId);

        return $this->getDb()->fetchAll($select);
    }

    public function getCompaniesByFilter(CompanyFilter $filter): array
    {
        $select = $this->getDb()->select('c.*')->from('company', 'c');

        if ($filter->getStatus() !== null) {
            $select->where('c.status_id=:status_id')
                ->setParameter('status_id', $filter->getStatus()->getId());
        }
        if ($filter->getSearch() !== null) {
            $select->where('(name like :search or email_address like :search)')
                ->setParameter('search', '%' . $filter->getSearch() . '%');
        }
        if ($filter->getDateCreatedStart() !== null) {
            $select->where('date_created >= :date_created_start')
                ->setParameter('date_created_start', $filter->getDateCreatedStart()->format('Y-m-d H:i:s'));
        }
        if ($filter->getDateCreatedEnd() !== null) {
            $select->where('date_created <= :date_created_end')
                ->setParameter('date_created_end', $filter->getDateCreatedEnd()->format('Y-m-d H:i:s'));
        }
        if ($filter->getDateUpdatedStart() !== null) {
            $select->where('date_updated >= :date_updated_start')
                ->setParameter('date_updated_start', $filter->getDateUpdatedStart()->format('Y-m-d H:i:s'));
        }
        if ($filter->getDateUpdatedEnd() !== null) {
            $select->where('date_updated <= :date_updated_end')
                ->setParameter('date_updated_end', $filter->getDateUpdatedEnd()->format('Y-m-d H:i:s'));
        }
        if ($filter->getFields() !== null) {
            $index = 0;
            foreach ($filter->getFields() as $name => $value) {
                $select->innerJoin('c', $this->pt('company_field'), 'value_' . $index, 'c.id=value_' . $index . '.company_id')
                    ->innerJoin('value_' . $index, $this->pt('company_field_type'), 'field_' . $index, 'value_' . $index . '.field_type_id=field_' . $index . '.id')
                    ->where('field_' . $index . '.name=:name_' . $index)
                    ->setParameter('name_' . $index, $name)
                    ->where('value_' . $index . '.value=:value_' . $index)
                    ->setParameter('value_' . $index, $value);
            }
        }
        $this->applyCountAndLimit($select, $filter);
        return $this->getDb()->fetchAll($select);
    }

    public function getFieldTypeById(int $id): ?array
    {
        return $this->selectSingleRow($this->pt('company_field_type'), 'id', $id);
    }

    public function getFieldTypeByName(string $name): ?array
    {
        return $this->selectSingleRow($this->pt('company_field_type'), 'name', $name);
    }

    public function getFieldsForCompany(int $companyId): array
    {
        return $this->selectRowsByValues($this->pt('company_field'), ['company_id' => $companyId]);
    }
}
