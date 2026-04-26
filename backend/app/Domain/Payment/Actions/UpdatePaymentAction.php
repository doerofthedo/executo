<?php

declare(strict_types=1);

namespace App\Domain\Payment\Actions;

use App\Models\Payment;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class UpdatePaymentAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(Payment $payment, array $payload): Payment
    {
        $payment->fill($payload);

        if ($payment->isDirty()) {
            $payment->save();
        }

        $freshPayment = $payment->fresh(['debtor', 'debt']);

        if ($freshPayment === null) {
            throw new NotFoundHttpException();
        }

        return $freshPayment;
    }
}
