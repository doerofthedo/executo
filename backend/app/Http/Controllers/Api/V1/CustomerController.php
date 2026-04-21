<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Customer\Actions\CreateCustomerAction;
use App\Domain\Customer\DTOs\CustomerData;
use App\Domain\Customer\Services\CustomerSearchService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ListCustomersRequest;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\District;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CustomerController extends Controller
{
    public function __construct(
        private readonly CreateCustomerAction $createCustomer,
        private readonly CustomerSearchService $customerSearch,
    ) {
    }

    public function index(ListCustomersRequest $request, District $district): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Customer::class);

        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 25);
        $search = isset($validated['search'])
            ? $this->customerSearch->normaliseQuery($validated['search'])
            : null;

        $customers = Customer::query()
            ->with(['district'])
            ->where('district_id', $district->id)
            ->when(
                ($validated['include_trashed'] ?? false) === true,
                static fn ($query) => $query->withTrashed(),
            )
            ->when(
                isset($validated['type']),
                static fn ($query) => $query->where('type', $validated['type']),
            )
            ->when($search !== null && $search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $like = '%' . $search . '%';

                    $nestedQuery
                        ->where('name', 'like', $like)
                        ->orWhere('case_number', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('personal_code', 'like', $like)
                        ->orWhere('company_name', 'like', $like)
                        ->orWhere('registration_number', 'like', $like);
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);

        return CustomerResource::collection($customers);
    }

    public function show(District $district, string $customer): CustomerResource
    {
        $resolvedCustomer = $this->resolveCustomerInDistrict($district, $customer);
        $this->authorize('view', $resolvedCustomer);

        return new CustomerResource($resolvedCustomer->load('district'));
    }

    public function store(StoreCustomerRequest $request, District $district): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $customer = $this->createCustomer->execute(
            $district,
            $this->customerDataFromPayload($this->normalizeCustomerPayload($request->validated())),
        );

        return (new CustomerResource($customer->load('district')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateCustomerRequest $request, District $district, string $customer): CustomerResource
    {
        $validated = $request->validated();
        $restore = ($validated['restore'] ?? false) === true;
        $resolvedCustomer = $this->resolveCustomerInDistrict($district, $customer, $restore);

        if ($restore) {
            if (count($validated) !== 1 || ! $resolvedCustomer->trashed()) {
                abort(422, 'Restore requests must target a soft-deleted customer only.');
            }

            $this->authorize('restore', $resolvedCustomer);
            $resolvedCustomer->restore();

            return new CustomerResource($resolvedCustomer->fresh()->load('district'));
        }

        $this->authorize('update', $resolvedCustomer);

        if ($validated === []) {
            abort(422, 'No changes submitted.');
        }

        $resolvedCustomer->fill($this->normalizeCustomerPayload($validated, $resolvedCustomer));

        if ($resolvedCustomer->isDirty()) {
            $resolvedCustomer->save();
        }

        return new CustomerResource($resolvedCustomer->fresh()->load('district'));
    }

    public function destroy(District $district, string $customer): JsonResponse
    {
        $forceDelete = request()->boolean('force');
        $resolvedCustomer = $this->resolveCustomerInDistrict($district, $customer, $forceDelete);

        if ($forceDelete) {
            $this->authorize('forceDelete', $resolvedCustomer);
            $resolvedCustomer->forceDelete();

            return response()->json([], 204);
        }

        $this->authorize('delete', $resolvedCustomer);
        $resolvedCustomer->delete();

        return response()->json([], 204);
    }

    private function resolveCustomerInDistrict(District $district, string $customerUlid, bool $withTrashed = false): Customer
    {
        $query = Customer::query()
            ->where('district_id', $district->id)
            ->where('ulid', $customerUlid);

        if ($withTrashed) {
            $query->withTrashed();
        }

        $customer = $query->first();

        if ($customer === null) {
            throw new NotFoundHttpException();
        }

        return $customer;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeCustomerPayload(array $validated, ?Customer $customer = null): array
    {
        if (array_key_exists('restore', $validated)) {
            unset($validated['restore']);
        }

        $type = $validated['type'] ?? $customer?->type;

        if ($type === 'physical') {
            $firstName = $validated['first_name'] ?? null;
            $lastName = $validated['last_name'] ?? null;

            if (($validated['name'] ?? null) === null && ($firstName !== null || $lastName !== null)) {
                $validated['name'] = trim(implode(' ', array_filter([$firstName, $lastName])));
            }

            $validated['company_name'] = null;
            $validated['registration_number'] = null;
            $validated['contact_person'] = null;
        }

        if ($type === 'legal') {
            $companyName = $validated['company_name'] ?? null;

            if (($validated['name'] ?? null) === null && $companyName !== null) {
                $validated['name'] = $companyName;
            }

            $validated['first_name'] = null;
            $validated['last_name'] = null;
            $validated['personal_code'] = null;
            $validated['date_of_birth'] = null;
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function customerDataFromPayload(array $payload): CustomerData
    {
        return new CustomerData(
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
