<?php

namespace Pantono\Customers\Decorator;

use League\Fractal\TransformerAbstract;
use Pantono\Customers\Model\Customer;
use League\Fractal\Resource\ResourceAbstract;
use Pantono\Core\Helper\ConfigHelper;

class CustomerDecorator extends TransformerAbstract
{
    protected array $defaultIncludes = ['details'];

    public function transform(Customer $customer): array
    {
        return [
            'id' => $customer->getId(),
            'user_id' => $customer->getUser()?->getId(),
            'date_created' => $customer->getDateCreated()->format(ConfigHelper::getDateTimeFormat()),
        ];
    }

    public function includeDetails(Customer $customer): ResourceAbstract
    {
        return $this->item($customer->getDetails(), new CustomerDetailsDecorator);
    }
}
