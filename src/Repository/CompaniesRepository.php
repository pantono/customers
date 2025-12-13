<?php

namespace Pantono\Customers\Repository;

use Pantono\Database\Repository\MysqlRepository;
use Pantono\Customers\Model\Company;
use Pantono\Customers\Filter\CompanyFilter;

class CompaniesRepository extends MysqlRepository
{
    public function getCompanyById(int $id): ?array
    {
        return $this->selectSingleRow('company', 'id', $id);
    }

    public function getCompanyStatusById(int $id): ?array
    {
        return $this->selectSingleRow('company_status', 'id', $id);
    }

    public function getAllCompanyStatuses(): ?array
    {
        return $this->selectAll('company_status', 'name');
    }

    public function saveCompany(Company $company): void
    {
        $id = $this->insertOrUpdateCheck('company', 'id', $company->getId(), $company->getAllData());
        if ($id) {
            $company->setId($id);
        }
        $this->getDb()->delete('company_file', ['company_id=?' => $company->getId()]);
        foreach ($company->getFiles() as $file) {
            $this->getDb()->insert('company_file', ['company_id' => $company->getId(), 'file_id' => $file->getId()]);
        }

        $params = ['company_id=?' => $company->getId()];
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
            $params['id NOT IN (?)'] = $ids;
        }
        $this->getDb()->delete('company_field', $params);
    }

    public function getFilesForCompany(int $companyId): array
    {
        $select = $this->getDb()->select()->from('company_file', [])
            ->joinInner('stored_file', 'company_file.file_id=stored_file.id')
            ->where('company_file.company_id=?', $companyId);

        return $this->getDb()->fetchAll($select);
    }

    public function getCompaniesByFilter(CompanyFilter $filter): array
    {
        $select = $this->getDb()->select()->from('company');

        if ($filter->getStatus() !== null) {
            $select->where('status_id=?', $filter->getStatus()->getId());
        }
        if ($filter->getSearch() !== null) {
            $select->where('(name like ?', '%' . $filter->getSearch() . '%')
                ->orWhere('email_address like ?)', '%' . $filter->getSearch() . '%');
        }
        if ($filter->getDateCreatedStart() !== null) {
            $select->where('date_created >= ?', $filter->getDateCreatedStart()->format('Y-m-d H:i:s'));
        }
        if ($filter->getDateCreatedEnd() !== null) {
            $select->where('date_created <= ?', $filter->getDateCreatedEnd()->format('Y-m-d H:i:s'));
        }
        if ($filter->getDateUpdatedStart() !== null) {
            $select->where('date_updated >= ?', $filter->getDateUpdatedStart()->format('Y-m-d H:i:s'));
        }
        if ($filter->getDateUpdatedEnd() !== null) {
            $select->where('date_updated <= ?', $filter->getDateUpdatedEnd()->format('Y-m-d H:i:s'));
        }
        if ($filter->getFields() !== null) {
            $index = 0;
            foreach ($filter->getFields() as $name => $value) {
                $select->joinInner(['value_' . $index => 'company_field'], 'company.id=value_' . $index . '.company_id', [])
                    ->joinInner(['field_' . $index => 'company_field_type'], 'value_' . $index . '.field_type_id=field_' . $index . '.id', [])
                    ->where('field_' . $index . '.name=?', $name)
                    ->where('value_' . $index . '.value=?', $value);
            }
        }
        $filter->setTotalResults($this->getCount($select));
        $select->limitPage($filter->getPage(), $filter->getPerPage());
        return $this->getDb()->fetchAll($select);
    }

    public function getFieldTypeById(int $id): ?array
    {
        return $this->selectSingleRow('company_field_type', 'id', $id);
    }

    public function getFieldTypeByName(string $name): ?array
    {
        return $this->selectSingleRow('company_field_type', 'name', $name);
    }

    public function getFieldsForCompany(int $companyId): array
    {
        return $this->selectRowsByValues('company_field', ['company_id' => $companyId]);
    }
}
