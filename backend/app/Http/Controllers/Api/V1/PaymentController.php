<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\District;
use App\Models\Payment;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PaymentController extends Controller
{
    public function index(District $district, Customer $customer, Debt $debt): AnonymousResourceCollection
    {
        $this->ensureNestedOwnership($district, $customer, $debt);
        $this->authorize('viewAny', Payment::class);

        return PaymentResource::collection(
            $debt->payments()
                ->with(['customer', 'debt'])
                ->orderByDesc('date')
                ->get(),
        );
    }

    public function store(StorePaymentRequest $request, District $district, Customer $customer, Debt $debt): PaymentResource
    {
        $this->ensureNestedOwnership($district, $customer, $debt);
        $this->authorize('create', Payment::class);

        $payment = Payment::query()->create([
            ...$request->validated(),
            'customer_id' => $customer->id,
            'debt_id' => $debt->id,
        ]);

        return new PaymentResource($payment->load(['customer', 'debt']));
    }

    public function show(District $district, Customer $customer, Debt $debt, Payment $payment): PaymentResource
    {
        $this->ensureNestedOwnership($district, $customer, $debt, $payment);
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
        $this->ensureNestedOwnership($district, $customer, $debt, $payment);
        $this->authorize('update', $payment);

        $payment->fill($request->validated());

        if ($payment->isDirty()) {
            $payment->save();
        }

        return new PaymentResource($payment->fresh(['customer', 'debt']));
    }

    public function destroy(District $district, Customer $customer, Debt $debt, Payment $payment): \Illuminate\Http\JsonResponse
    {
        $this->ensureNestedOwnership($district, $customer, $debt, $payment);
        $this->authorize('delete', $payment);

        $payment->delete();

        return response()->json([], 204);
    }

    private function ensureNestedOwnership(
        District $district,
        Customer $customer,
        Debt $debt,
        ?Payment $payment = null,
    ): void {
        if ($customer->district_id !== $district->id) {
            throw new NotFoundHttpException();
        }

        if ($debt->district_id !== $district->id || $debt->customer_id !== $customer->id) {
            throw new NotFoundHttpException();
        }

        if ($payment !== null && ($payment->debt_id !== $debt->id || $payment->customer_id !== $customer->id)) {
            throw new NotFoundHttpException();
        }
    }
}
