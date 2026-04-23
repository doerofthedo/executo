<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Models\District;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class UpdateUserProfileAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(User $user, User $currentUser, array $payload): User
    {
        $isElevated = ! $currentUser->is($user)
            || $currentUser->can('app.user.manage');

        if (! $isElevated) {
            unset($payload['email'], $payload['disabled']);
        }

        if ($payload === []) {
            abort(422, 'No changes submitted.');
        }

        DB::transaction(function () use ($payload, $user): void {
            $this->updateUser($user, $payload);
            $this->updatePreferences($user, $payload);
        });

        $freshUser = $user->fresh();

        if ($freshUser === null) {
            throw new NotFoundHttpException();
        }

        return $freshUser;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function updateUser(User $user, array $payload): void
    {
        $userPayload = array_intersect_key($payload, array_flip([
            'name',
            'surname',
            'email',
            'password',
            'disabled',
        ]));

        if ($userPayload === []) {
            return;
        }

        $user->fill($userPayload);

        if (! $user->isDirty()) {
            return;
        }

        $user->save();

        if (array_key_exists('password', $userPayload)) {
            $user->tokens()->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function updatePreferences(User $user, array $payload): void
    {
        $preferencePayload = array_intersect_key($payload, array_flip([
            'default_district_ulid',
            'locale',
            'date_format',
            'decimal_separator',
            'thousand_separator',
            'table_page_size',
        ]));

        if ($preferencePayload === []) {
            return;
        }

        if (array_key_exists('default_district_ulid', $preferencePayload)) {
            $defaultDistrictUlid = $preferencePayload['default_district_ulid'];
            $preferencePayload['default_district_id'] = $this->resolveDefaultDistrictId(
                $user,
                is_string($defaultDistrictUlid) ? $defaultDistrictUlid : null,
            );

            unset($preferencePayload['default_district_ulid']);
        }

        UserPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            $preferencePayload,
        );
    }

    private function resolveDefaultDistrictId(User $user, ?string $defaultDistrictUlid): ?int
    {
        if ($defaultDistrictUlid === null || $defaultDistrictUlid === '') {
            return null;
        }

        $district = District::query()
            ->where('ulid', (string) $defaultDistrictUlid)
            ->first();

        if ($district === null) {
            abort(422, 'Selected default district does not exist.');
        }

        $canAccessDistrict = $user->districts()
            ->where('districts.id', $district->id)
            ->exists()
            || $district->owner_id === $user->id
            || $user->hasRole('app.admin');

        if (! $canAccessDistrict) {
            abort(422, 'Selected default district is not accessible for this user.');
        }

        return $district->id;
    }
}
