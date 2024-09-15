<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Customers extends AbstractMigration
{
    public function change(): void
    {
        $this->table('customer')
            ->addColumn('details_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('date_created', 'datetime')
            ->addForeignKey('user_id', 'user', 'id')
            ->addIndex('user_id', ['unique' => true])
            ->create();

        $this->table('customer_details')
            ->addColumn('customer_id', 'integer', ['signed' => false])
            ->addColumn('date_created', 'datetime')
            ->addColumn('email', 'string', ['null' => true])
            ->addColumn('forename', 'string', ['null' => true])
            ->addColumn('surname', 'string', ['null' => true])
            ->addColumn('mobile_number', 'string', ['null' => true])
            ->addColumn('date_of_birth', 'date', ['null' => true])
            ->addColumn('field_json', 'json')
            ->addForeignKey('customer_id', 'customer', 'id')
            ->create();

        $this->table('customer_field')
            ->addColumn('name', 'string')
            ->addColumn('label', 'string')
            ->addColumn('type', 'string')
            ->addColumn('config', 'json', ['null' => true])
            ->create();

        $this->table('customer_details_field')
            ->addColumn('details_id', 'integer')
            ->addColumn('field_id', 'integer')
            ->addColumn('value', 'string', ['null' => true])
            ->addForeignKey('details_id', 'customer_details', 'id')
            ->addForeignKey('field_id', 'customer_field', 'id')
            ->create();

        $this->table('customer_locations')
            ->addColumn('customer_id', 'integer', ['signed' => false])
            ->addColumn('location_id', 'integer', ['signed' => false])
            ->addColumn('name', 'string', ['null' => true])
            ->addForeignKey('customer_id', 'customer', 'id')
            ->addForeignKey('location_id', 'location', 'id')
            ->create();

        $this->table('customer')
            ->addForeignKey('details_id', 'customer_details', 'id')
            ->update();

        $this->table('customer_history')
            ->addColumn('customer_id', 'integer', ['signed' => false])
            ->addColumn('date', 'datetime')
            ->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('entry', 'text')
            ->addForeignKey('customer_id', 'customer', 'id')
            ->addForeignKey('user_id', 'user', 'id')
            ->create();

        $this->table('customer_merge', ['id' => false])
            ->addColumn('source_customer_id', 'integer', ['signed' => false])
            ->addColumn('target_customer_id', 'integer', ['signed' => false])
            ->addForeignKey('source_customer_id', 'customer', 'id')
            ->addForeignKey('target_customer_id', 'customer', 'id')
            ->create();

        $this->query('DROP view if exists customer_list');
        $view = <<<VIEW
SELECT c.id, c.user_id, d.email, d.forename, d.surname, d.mobile_number, d.date_of_birth from customer c
INNER JOIN customer_details d on c.details_id=d.id
VIEW;

        $this->query('CREATE view customer_list AS ' . $view);
    }
}
