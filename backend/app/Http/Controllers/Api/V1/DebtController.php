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
