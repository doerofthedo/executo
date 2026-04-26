<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Debtor\Actions\CreateDebtorAction;
use App\Domain\Debtor\Actions\DeleteDebtorAction;
use App\Domain\Debtor\Actions\ListDebtorsAction;
use App\Domain\Debtor\Actions\RestoreDebtorAction;
use App\Domain\Debtor\Actions\UpdateDebtorAction;
use App\Domain\Debtor\DTOs\DebtorData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Debtor\ListDebtorsRequest;
use App\Http\Requests\Debtor\StoreDebtorRequest;
use App\Http\Requests\Debtor\UpdateDebtorRequest;
use App\Http\Resources\DebtorResource;
use App\Http\Resources\EmptyResource;
use App\Models\Debtor;
use App\Models\District;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final class DebtorController extends Controller
{
    public function __construct(
        private readonly CreateDebtorAction $createDebtor,
        private readonly ListDebtorsAction $listDebtors,
        private readonly UpdateDebtorAction $updateDebtor,
        private readonly RestoreDebtorAction $restoreDebtor,
        private readonly DeleteDebtorAction $deleteDebtor,
    ) {}

    public function index(ListDebtorsRequest $request, District $district): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Debtor::class);

        return DebtorResource::collection($this->listDebtors->execute($district, $request->validated()));
    }

    public function show(District $district, Debtor $debtor): DebtorResource
    {
        $this->authorize('view', $debtor);

        return new DebtorResource($debtor->load('district'));
    }

    public function store(StoreDebtorRequest $request, District $district): JsonResponse
    {
        $this->authorize('create', Debtor::class);

        $debtor = $this->createDebtor->execute(
            $district,
            $this->debtorDataFromPayload($this->updateDebtor->normalizePayload($request->validated())),
        );

        return (new DebtorResource($debtor->load('district')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateDebtorRequest $request, District $district, Debtor $debtor): DebtorResource
    {
        $validated = $request->validated();
        $restore = ($validated['restore'] ?? false) === true;

        if ($restore) {
            if (count($validated) !== 1 || ! $debtor->trashed()) {
                abort(422, 'Restore requests must target a soft-deleted debtor only.');
            }

            $this->authorize('restore', $debtor);

            return new DebtorResource($this->restoreDebtor->execute($debtor)->load('district'));
        }

        $this->authorize('update', $debtor);

        if ($validated === []) {
            abort(422, 'No changes submitted.');
        }

        return new DebtorResource($this->updateDebtor->execute($debtor, $validated)->load('district'));
    }

    public function destroy(District $district, Debtor $debtor): JsonResponse
    {
        $forceDelete = request()->boolean('force');

        if ($forceDelete) {
            $this->authorize('forceDelete', $debtor);
            $this->deleteDebtor->execute($debtor, true);

            return (new EmptyResource())
                ->response()
                ->setStatusCode(Response::HTTP_NO_CONTENT);
        }

        $this->authorize('delete', $debtor);
        $this->deleteDebtor->execute($debtor, false);

        return (new EmptyResource())
            ->response()
            ->setStatusCode(Response::HTTP_NO_CONTENT);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function debtorDataFromPayload(array $payload): DebtorData
    {
        return new DebtorData(
            name: (string) ($payload['name'] ?? ''),
            caseNumber: $payload['case_number'] ?? null,
            type: (string) $payload['type'],
            email: $payload['email'] ?? null,
            phone: $payload['phone'] ?? null,
            firstName: $payload['first_name'] ?? null,
            lastName: $payload['last_name'] ?? null,
            personalCode: $payload['personal_code'] ?? null,
            dateOfBirth: $payload['date_of_birth'] ?? null,
            companyName: $payload['company_name'] ?? null,
            registrationNumber: $payload['registration_number'] ?? null,
            contactPerson: $payload['contact_person'] ?? null,
        );
    }
}
