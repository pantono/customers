<?php

namespace Pantono\Customers\Command;

use Symfony\Component\Console\Command\Command;
use Pantono\Customers\Customers;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class ReCreateCustomerFlatTable extends Command
{
    private Customers $customers;

    public function __construct(Customers $customers)
    {
        $this->customers = $customers;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('customers:re-create-flat');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->write('Re-creating flat table...');
        $this->customers->recreateFlatTable();
        $output->writeln('Done');
        return 0;
    }
}
