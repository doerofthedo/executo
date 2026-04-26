<?php

declare(strict_types=1);

namespace App\Domain\Customer\Actions;

use App\Models\Customer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class RestoreCustomerAction
{
    public function execute(Customer $customer): Customer
    {
        $customer->restore();

        $freshCustomer = $customer->fresh();

        if ($freshCustomer === null) {
            throw new NotFoundHttpException();
        }

        return $freshCustomer;
    }
}
