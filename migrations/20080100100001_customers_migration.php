<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CustomersMigration extends AbstractMigration
{
    use \Pantono\Database\Migration\Traits\ReseedIdentityTrait;

    public function change(): void
    {
        if ($this->getAdapter()->getAdapterType() === 'mysql') {
            $this->query('SET FOREIGN_KEY_CHECKS=0');
        }
        $this->table('customer')
            ->addColumn('details_id', 'integer', ['null' => true])
            ->addColumn('user_id', 'integer', ['null' => true])
            ->addColumn('date_created', 'datetime')
            ->addForeignKey('user_id', 'user', 'id', ['delete' => 'CASCADE'])
            ->addIndex('user_id', ['unique' => true])
            ->create();

        $this->table('customer_detail')
            ->addColumn('customer_id', 'integer')
            ->addColumn('date_created', 'datetime')
            ->addColumn('email', 'string', ['null' => true])
            ->addColumn('forename', 'string', ['null' => true])
            ->addColumn('surname', 'string', ['null' => true])
            ->addColumn('mobile_number', 'string', ['null' => true])
            ->addColumn('date_of_birth', 'date', ['null' => true])
            ->addColumn('field_json', 'json')
            ->addForeignKey('customer_id', 'customer', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('customer_field')
            ->addColumn('name', 'string')
            ->addColumn('label', 'string')
            ->addColumn('type', 'string')
            ->addColumn('config', 'json', ['null' => true])
            ->create();

        $this->table('customer_detail_field')
            ->addColumn('details_id', 'integer')
            ->addColumn('field_id', 'integer')
            ->addColumn('value', 'string', ['null' => true])
            ->addForeignKey('details_id', 'customer_detail', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('field_id', 'customer_field', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('customer_locations')
            ->addColumn('customer_id', 'integer')
            ->addColumn('location_id', 'integer')
            ->addColumn('name', 'string', ['null' => true])
            ->addForeignKey('customer_id', 'customer', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('location_id', 'location', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('customer')
            ->addForeignKey('details_id', 'customer_detail', 'id', ['delete' => 'CASCADE'])
            ->update();

        $this->table('customer_history')
            ->addColumn('customer_id', 'integer')
            ->addColumn('date', 'datetime')
            ->addColumn('user_id', 'integer', ['null' => true])
            ->addColumn('entry', 'text')
            ->addForeignKey('customer_id', 'customer', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('user_id', 'user', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('customer_merge', ['id' => false])
            ->addColumn('source_customer_id', 'integer')
            ->addColumn('target_customer_id', 'integer')
            ->addForeignKey('source_customer_id', 'customer', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('target_customer_id', 'customer', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('customer_external_id')
            ->addColumn('customer_id', 'integer')
            ->addColumn('id_type', 'string')
            ->addColumn('date_created', 'datetime')
            ->addColumn('date_updated', 'datetime')
            ->addColumn('identifier', 'string')
            ->addColumn('deleted', 'boolean')
            ->addForeignKey('customer_id', 'customer', 'id', ['delete' => 'CASCADE'])
            ->addIndex('id_type')
            ->addIndex('identifier')
            ->addIndex(['id_type', 'identifier', 'deleted'])
            ->create();

        $this->table('customer_flat')
            ->addColumn('user_id', 'integer', ['null' => true])
            ->addColumn('email', 'string', ['null' => true])
            ->addColumn('forename', 'string')
            ->addColumn('surname', 'string')
            ->addColumn('mobile_number', 'string', ['null' => true])
            ->addColumn('date_of_birth', 'date', ['null' => true])
            ->addIndex('email')
            ->addIndex('forename')
            ->addIndex('surname')
            ->create();

        if ($this->isMigratingUp()) {
            $view = <<<VIEW
SELECT c.id, c.user_id, d.email, d.forename, d.surname, d.mobile_number, d.date_of_birth from customer c
INNER JOIN customer_detail d on c.details_id=d.id
VIEW;
            if ($this->getAdapter()->getAdapterType() === 'pgsql') {
                $this->query('CREATE OR REPLACE view customer_list AS ' . $view);
            } else {
                $this->query('CREATE view customer_list AS ' . $view);
            }
        } else {
            $this->query('DROP view customer_list');
        }
        if ($this->getAdapter()->getAdapterType() === 'mysql') {
            $this->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
