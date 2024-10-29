<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Companies extends AbstractMigration
{
    public function up(): void
    {
        $this->table('company_status')
            ->addColumn('name', 'string')
            ->addColumn('approved', 'boolean')
            ->addColumn('pending', 'boolean')
            ->addColumn('archived', 'boolean')
            ->create();

        $this->table('company_status')
            ->insert([
                ['name' => 'Pending', 'approved' => 0, 'pending' => 1, 'archived' => 0],
                ['name' => 'Approved', 'approved' => 1, 'pending' => 0, 'archived' => 0],
                ['name' => 'Archived', 'approved' => 0, 'pending' => 0, 'archived' => 1],
            ])->update();

        $this->table('company')
            ->addColumn('date_created', 'datetime')
            ->addColumn('date_updated', 'datetime')
            ->addColumn('status_id', 'integer')
            ->addColumn('name', 'string')
            ->addColumn('location_id', 'integer')
            ->addColumn('phone_number', 'string', ['null' => true])
            ->addColumn('email_address', 'string', ['null' => true])
            ->addColumn('vat_number', 'string', ['null' => true])
            ->addForeignKey('location_id', 'location', 'id')
            ->addForeignKey('status_id', 'company_status', 'id')
            ->create();

        $this->table('company_file', ['id' => false])
            ->addColumn('company_id', 'integer')
            ->addColumn('file_id', 'integer')
            ->addForeignKey('file_id', 'stored_file', 'id')
            ->addForeignKey('company_id', 'company', 'id')
            ->create();;
    }

    public function down(): void
    {
        $this->table('company_file')
            ->drop()->update();
        $this->table('company')
            ->drop()->update();
        $this->table('company_status')
            ->drop()->update();
    }
}
