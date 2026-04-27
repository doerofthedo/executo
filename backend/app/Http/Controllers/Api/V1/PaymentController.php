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
use App\Models\Debtor;
use App\Models\Debt;
use App\Models\District;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final class PaymentController extends Controller
{
    public function __construct(
        private readonly ListPaymentsAction $listPayments,
        private readonly CreatePaymentAction $createPayment,
        private readonly UpdatePaymentAction $updatePayment,
        private readonly DeletePaymentAction $deletePayment,
    ) {}

    public function districtIndex(District $district): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $limit = min((int) (request()->query('per_page', 5)), 1000);

        $payments = Payment::query()
            ->whereHas('debtor', fn ($q) => $q->where('district_id', $district->id))
            ->with(['debtor', 'debt'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $data = $payments->map(fn (Payment $payment): array => [
            'ulid'         => $payment->ulid,
            'debtor_ulid'  => $payment->debtor?->ulid,
            'debt_ulid'    => $payment->debt?->ulid,
            'debtor_name'  => $this->debtorDisplayName($payment->debtor),
            'case_number'  => $payment->debtor?->case_number,
            'amount'       => (string) $payment->amount,
            'date'         => $payment->date?->toDateString(),
            'description'  => $payment->description,
        ]);

        return response()->json(['data' => $data]);
    }

    public function index(District $district, Debtor $debtor, Debt $debt): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Payment::class);

        return PaymentResource::collection($this->listPayments->execute($debt));
    }

    public function store(StorePaymentRequest $request, District $district, Debtor $debtor, Debt $debt): PaymentResource
    {
        $this->authorize('create', Payment::class);

        $payment = $this->createPayment->execute($debtor, $debt, $request->validated());

        return new PaymentResource($payment->load(['debtor', 'debt']));
    }

    public function show(District $district, Debtor $debtor, Debt $debt, Payment $payment): PaymentResource
    {
        $this->authorize('view', $payment);

        return new PaymentResource($payment->load(['debtor', 'debt']));
    }

    public function update(
        UpdatePaymentRequest $request,
        District $district,
        Debtor $debtor,
        Debt $debt,
        Payment $payment,
    ): PaymentResource {
        $this->authorize('update', $payment);

        return new PaymentResource($this->updatePayment->execute($payment, $request->validated()));
    }

    public function destroy(District $district, Debtor $debtor, Debt $debt, Payment $payment): \Illuminate\Http\JsonResponse
    {
        $this->authorize('delete', $payment);

        $this->deletePayment->execute($payment);

        return (new EmptyResource())
            ->response()
            ->setStatusCode(Response::HTTP_NO_CONTENT);
    }

    private function debtorDisplayName(?Debtor $debtor): ?string
    {
        if ($debtor === null) {
            return null;
        }

        if ($debtor->company_name !== null && $debtor->company_name !== '') {
            return $debtor->company_name;
        }

        $parts = array_filter([$debtor->first_name, $debtor->last_name]);

        if (!empty($parts)) {
            return implode(' ', $parts);
        }

        return $debtor->name;
    }
}
