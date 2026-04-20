<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Debt\Actions\BuildInterestScheduleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Debt\StoreDebtRequest;
use App\Http\Requests\Debt\UpdateDebtRequest;
use App\Http\Resources\DebtDetailResource;
use App\Http\Resources\DebtResource;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\District;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DebtController extends Controller
{
    public function __construct(
        private readonly BuildInterestScheduleAction $buildInterestSchedule,
    ) {
    }

    public function index(District $district, Customer $customer): AnonymousResourceCollection
    {
        $this->ensureNestedOwnership($district, $customer);
        $this->authorize('viewAny', Debt::class);

        return DebtResource::collection(
            $customer->debts()
                ->with(['district', 'customer'])
                ->orderByDesc('date')
                ->get(),
        );
    }

    public function store(StoreDebtRequest $request, District $district, Customer $customer): DebtResource
    {
        $this->ensureNestedOwnership($district, $customer);
        $this->authorize('create', Debt::class);

        $debt = Debt::query()->create([
            ...$request->validated(),
            'district_id' => $district->id,
            'customer_id' => $customer->id,
        ]);

        return new DebtResource($debt->load(['district', 'customer']));
    }

    public function show(District $district, Customer $customer, Debt $debt): DebtDetailResource
    {
        $this->ensureNestedOwnership($district, $customer, $debt);
        $this->authorize('view', $debt);

        $debt->load(['district', 'customer', 'payments.customer', 'payments.debt']);
        $schedule = $this->buildInterestSchedule->execute($debt, $debt->payments);

        return new DebtDetailResource($debt, $schedule);
    }

    public function update(UpdateDebtRequest $request, District $district, Customer $customer, Debt $debt): DebtResource
    {
        $this->ensureNestedOwnership($district, $customer, $debt);
        $this->authorize('update', $debt);

        $debt->fill($request->validated());

        if ($debt->isDirty()) {
            $debt->save();
        }

        return new DebtResource($debt->fresh(['district', 'customer']));
    }

    public function destroy(District $district, Customer $customer, Debt $debt): \Illuminate\Http\JsonResponse
    {
        $this->ensureNestedOwnership($district, $customer, $debt);
        $this->authorize('delete', $debt);

        $debt->delete();

        return response()->json([], 204);
    }

    private function ensureNestedOwnership(District $district, Customer $customer, ?Debt $debt = null): void
    {
        if ($customer->district_id !== $district->id) {
            throw new NotFoundHttpException();
        }

        if ($debt !== null && ($debt->district_id !== $district->id || $debt->customer_id !== $customer->id)) {
            throw new NotFoundHttpException();
        }
    }
}
