<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payment\Actions\CreatePaymentAction;
use App\Domain\Payment\Actions\DeletePaymentAction;
use App\Domain\Payment\Actions\ListPaymentsAction;
use App\Domain\Payment\Actions\UpdatePaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\UpdatePaymentRequest;
use App\Http\Resources\EmptyResource;
use App\Http\Resources\PaymentResource;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\District;
use App\Models\Payment;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final class PaymentController extends Controller
{
    public function __construct(
        private readonly ListPaymentsAction $listPayments,
        private readonly CreatePaymentAction $createPayment,
        private readonly UpdatePaymentAction $updatePayment,
        private readonly DeletePaymentAction $deletePayment,
    ) {
    }

    public function index(District $district, Customer $customer, Debt $debt): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Payment::class);

        return PaymentResource::collection($this->listPayments->execute($debt));
    }

    public function store(StorePaymentRequest $request, District $district, Customer $customer, Debt $debt): PaymentResource
    {
        $this->authorize('create', Payment::class);

        $payment = $this->createPayment->execute($customer, $debt, $request->validated());

        return new PaymentResource($payment->load(['customer', 'debt']));
    }

    public function show(District $district, Customer $customer, Debt $debt, Payment $payment): PaymentResource
    {
        $this->authorize('view', $payment);

        return new PaymentResource($payment->load(['customer', 'debt']));
    }

    public function update(
        UpdatePaymentRequest $request,
        District $district,
        Customer $customer,
        Debt $debt,
        Payment $payment,
    ): PaymentResource {
        $this->authorize('update', $payment);

        return new PaymentResource($this->updatePayment->execute($payment, $request->validated()));
    }

    public function destroy(District $district, Customer $customer, Debt $debt, Payment $payment): \Illuminate\Http\JsonResponse
    {
        $this->authorize('delete', $payment);

        $this->deletePayment->execute($payment);

        return (new EmptyResource())
            ->response()
            ->setStatusCode(Response::HTTP_NO_CONTENT);
    }
}
