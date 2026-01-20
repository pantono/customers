<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CompaniesMigration extends AbstractMigration
{
    public function change(): void
    {
        $this->table('company_status')
            ->addColumn('name', 'string')
            ->addColumn('approved', 'boolean')
            ->addColumn('pending', 'boolean')
            ->addColumn('archived', 'boolean')
            ->create();

        if ($this->isMigratingUp()) {
            $this->table('company_status')
                ->insert([
                    ['name' => 'Pending', 'approved' => 0, 'pending' => 1, 'archived' => 0],
                    ['name' => 'Approved', 'approved' => 1, 'pending' => 0, 'archived' => 0],
                    ['name' => 'Archived', 'approved' => 0, 'pending' => 0, 'archived' => 1],
                ])->saveData();
        }

        $this->table('company')
            ->addColumn('date_created', 'datetime')
            ->addColumn('date_updated', 'datetime')
            ->addColumn('status_id', 'integer', ['signed' => false])
            ->addColumn('name', 'string')
            ->addColumn('location_id', 'integer', ['signed' => false])
            ->addColumn('phone_number', 'string', ['null' => true])
            ->addColumn('email_address', 'string', ['null' => true])
            ->addColumn('vat_number', 'string', ['null' => true])
            ->addForeignKey('location_id', 'location', 'id')
            ->addForeignKey('status_id', 'company_status', 'id')
            ->create();

        $this->table('company_file', ['id' => false])
            ->addColumn('company_id', 'integer', ['signed' => false])
            ->addColumn('file_id', 'integer', ['signed' => false])
            ->addForeignKey('file_id', 'stored_file', 'id')
            ->addForeignKey('company_id', 'company', 'id')
            ->create();

        $this->table('company_field_type')
            ->addColumn('name', 'string')
            ->addColumn('label', 'string')
            ->addColumn('type', 'string')
            ->addColumn('required', 'boolean')
            ->create();

        $this->table('company_field')
            ->addColumn('company_id', 'integer', ['signed' => false])
            ->addColumn('field_type_id', 'integer', ['signed' => false])
            ->addColumn('value', 'text')
            ->create();
    }
}
