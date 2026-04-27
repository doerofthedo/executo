<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Debt\Actions\BuildDebtDetailAction;
use App\Domain\Debt\Actions\CreateDebtAction;
use App\Domain\Debt\Actions\DeleteDebtAction;
use App\Domain\Debt\Actions\ListDebtsAction;
use App\Domain\Debt\Actions\UpdateDebtAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Debt\StoreDebtRequest;
use App\Http\Requests\Debt\UpdateDebtRequest;
use App\Http\Resources\DebtDetailResource;
use App\Http\Resources\DebtResource;
use App\Http\Resources\EmptyResource;
use App\Models\Debtor;
use App\Models\Debt;
use App\Models\District;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final class DebtController extends Controller
{
    public function __construct(
        private readonly ListDebtsAction $listDebts,
        private readonly CreateDebtAction $createDebt,
        private readonly BuildDebtDetailAction $buildDebtDetail,
        private readonly UpdateDebtAction $updateDebt,
        private readonly DeleteDebtAction $deleteDebt,
    ) {}

    public function districtIndex(District $district): JsonResponse
    {
        $this->authorize('viewAny', Debt::class);

        $limit = min((int) (request()->query('per_page', 5)), 100);

        $debts = Debt::query()
            ->where('district_id', $district->id)
            ->with('debtor')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $data = $debts->map(fn (Debt $debt): array => [
            'debt_ulid'    => $debt->ulid,
            'debtor_ulid'  => $debt->debtor?->ulid,
            'debtor_name'  => $this->debtorDisplayName($debt->debtor),
            'case_number'  => $debt->debtor?->case_number,
            'description'  => $debt->description,
            'amount'       => (string) $debt->amount,
            'date'         => $debt->date?->toDateString(),
        ]);

        return response()->json(['data' => $data]);
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

    public function index(District $district, Debtor $debtor): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Debt::class);

        return DebtResource::collection($this->listDebts->execute($debtor));
    }

    public function store(StoreDebtRequest $request, District $district, Debtor $debtor): DebtResource
    {
        $this->authorize('create', Debt::class);

        $debt = $this->createDebt->execute($district, $debtor, $request->validated());

        return new DebtResource($debt->load(['district', 'debtor']));
    }

    public function show(District $district, Debtor $debtor, Debt $debt): DebtDetailResource
    {
        $this->authorize('view', $debt);

        return new DebtDetailResource($debt, $this->buildDebtDetail->execute($debt));
    }

    public function update(UpdateDebtRequest $request, District $district, Debtor $debtor, Debt $debt): DebtResource
    {
        $this->authorize('update', $debt);

        return new DebtResource($this->updateDebt->execute($debt, $request->validated()));
    }

    public function destroy(District $district, Debtor $debtor, Debt $debt): \Illuminate\Http\JsonResponse
    {
        $this->authorize('delete', $debt);

        $this->deleteDebt->execute($debt);

        return (new EmptyResource())
            ->response()
            ->setStatusCode(Response::HTTP_NO_CONTENT);
    }
}
