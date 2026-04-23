<?php

declare(strict_types=1);

namespace App\Domain\Customer\Actions;

use App\Models\Customer;

final readonly class DeleteCustomerAction
{
    public function execute(Customer $customer, bool $forceDelete): void
    {
        if ($forceDelete) {
            $customer->forceDelete();

            return;
        }

        $customer->delete();
    }
}
