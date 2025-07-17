<?php

namespace Pantono\Customers\Decorator;

use League\Fractal\TransformerAbstract;
use Pantono\Customers\Model\CustomerDetail;
use Pantono\Core\Helper\ConfigHelper;
use Pantono\Utilities\DateTimeParser;

class CustomerDetailsDecorator extends TransformerAbstract
{
    public function transform(CustomerDetail $details): array
    {
        $fields = [];
        foreach ($details->getFields() as $field) {
            $fields[$field->getField()->getName()] = $field->getValue();
        }
        return [
            'id' => $details->getId(),
            'date_created' => $details->getDateCreated()->format(DateTimeParser::DATE_TIME_FORMAT),
            'email' => $details->getEmail(),
            'forename' => $details->getForename(),
            'surname' => $details->getSurname(),
            'mobile_number' => $details->getMobileNumber(),
            'date_of_birth' => $details->getDateOfBirth()?->format('Y-m-d'),
            'fields' => $fields
        ];
    }
}
