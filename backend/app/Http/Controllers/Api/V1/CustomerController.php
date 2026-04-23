<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Customer\Actions\CreateCustomerAction;
use App\Domain\Customer\Actions\DeleteCustomerAction;
use App\Domain\Customer\Actions\ListCustomersAction;
use App\Domain\Customer\Actions\RestoreCustomerAction;
use App\Domain\Customer\Actions\UpdateCustomerAction;
use App\Domain\Customer\DTOs\CustomerData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ListCustomersRequest;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\EmptyResource;
use App\Models\Customer;
use App\Models\District;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final class CustomerController extends Controller
{
    public function __construct(
        private readonly CreateCustomerAction $createCustomer,
        private readonly ListCustomersAction $listCustomers,
        private readonly UpdateCustomerAction $updateCustomer,
        private readonly RestoreCustomerAction $restoreCustomer,
        private readonly DeleteCustomerAction $deleteCustomer,
    ) {
    }

    public function index(ListCustomersRequest $request, District $district): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Customer::class);

        return CustomerResource::collection($this->listCustomers->execute($district, $request->validated()));
    }

    public function show(District $district, Customer $customer): CustomerResource
    {
        $this->authorize('view', $customer);

        return new CustomerResource($customer->load('district'));
    }

    public function store(StoreCustomerRequest $request, District $district): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $customer = $this->createCustomer->execute(
            $district,
            $this->customerDataFromPayload($this->updateCustomer->normalizePayload($request->validated())),
        );

        return (new CustomerResource($customer->load('district')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateCustomerRequest $request, District $district, Customer $customer): CustomerResource
    {
        $validated = $request->validated();
        $restore = ($validated['restore'] ?? false) === true;

        if ($restore) {
            if (count($validated) !== 1 || ! $customer->trashed()) {
                abort(422, 'Restore requests must target a soft-deleted customer only.');
            }

            $this->authorize('restore', $customer);

            return new CustomerResource($this->restoreCustomer->execute($customer)->load('district'));
        }

        $this->authorize('update', $customer);

        if ($validated === []) {
            abort(422, 'No changes submitted.');
        }

        return new CustomerResource($this->updateCustomer->execute($customer, $validated)->load('district'));
    }

    public function destroy(District $district, Customer $customer): JsonResponse
    {
        $forceDelete = request()->boolean('force');

        if ($forceDelete) {
            $this->authorize('forceDelete', $customer);
            $this->deleteCustomer->execute($customer, true);

            return (new EmptyResource())
                ->response()
                ->setStatusCode(Response::HTTP_NO_CONTENT);
        }

        $this->authorize('delete', $customer);
        $this->deleteCustomer->execute($customer, false);

        return (new EmptyResource())
            ->response()
            ->setStatusCode(Response::HTTP_NO_CONTENT);
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
