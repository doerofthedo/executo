<?php

declare(strict_types=1);

namespace App\Domain\District\Actions;

use App\Models\District;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class UpdateDistrictAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(District $district, array $payload): District
    {
        $district->fill($payload);

        if ($district->isDirty()) {
            $district->save();
        }

        $freshDistrict = $district->fresh();

        if ($freshDistrict === null) {
            throw new NotFoundHttpException();
        }

        return $freshDistrict;
    }
}
