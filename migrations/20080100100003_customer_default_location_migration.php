<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Pantono\Database\Migration\Base\BasePantonoMigration;

final class CustomerDefaultLocationMigration extends BasePantonoMigration
{
    public function change(): void
    {
        $this->table('customer_locations')
            ->addColumn('default_billing', 'boolean')
            ->addColumn('default_shipping', 'boolean')
            ->update();
    }
}
