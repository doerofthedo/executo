<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, int>
     */
    private const ROLE_PRIORITY = [
        'district.admin' => 3,
        'district.manager' => 2,
        'district.user' => 1,
    ];

    public function up(): void
    {
        $roleNamesById = DB::table('roles')
            ->pluck('name', 'id')
            ->all();

        /** @var Collection<int, object> $memberships */
        $memberships = DB::table('district_user')
            ->select('id', 'district_id', 'user_id', 'role_id', 'updated_at', 'created_at')
            ->orderBy('district_id')
            ->orderBy('user_id')
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();

        $memberships
            ->groupBy(static fn (object $membership): string => $membership->district_id . ':' . $membership->user_id)
            ->each(function (Collection $group) use ($roleNamesById): void {
                if ($group->count() <= 1) {
                    return;
                }

                $sorted = $group->sortByDesc(function (object $membership) use ($roleNamesById): int {
                    $roleName = $roleNamesById[$membership->role_id] ?? null;

                    return self::ROLE_PRIORITY[$roleName] ?? 0;
                })->values();

                $keepId = $sorted->first()?->id;
                $deleteIds = $sorted
                    ->skip(1)
                    ->pluck('id')
                    ->filter(static fn ($id): bool => is_int($id))
                    ->all();

                if (is_int($keepId) && $deleteIds !== []) {
                    DB::table('district_user')
                        ->whereIn('id', $deleteIds)
                        ->delete();
                }
            });

        if (! $this->hasIndex('district_user', 'district_user_user_id_index')) {
            Schema::table('district_user', static function (Blueprint $table): void {
                $table->index('user_id', 'district_user_user_id_index');
            });
        }

        if (! $this->hasIndex('district_user', 'district_user_role_id_index')) {
            Schema::table('district_user', static function (Blueprint $table): void {
                $table->index('role_id', 'district_user_role_id_index');
            });
        }

        if (! $this->hasIndex('district_user', 'district_user_district_id_user_id_unique')) {
            Schema::table('district_user', static function (Blueprint $table): void {
                $table->unique(['district_id', 'user_id']);
            });
        }

        if ($this->hasIndex('district_user', 'district_user_district_id_user_id_role_id_unique')) {
            Schema::table('district_user', static function (Blueprint $table): void {
                $table->dropUnique('district_user_district_id_user_id_role_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (! $this->hasIndex('district_user', 'district_user_district_id_user_id_role_id_unique')) {
            Schema::table('district_user', static function (Blueprint $table): void {
                $table->unique(['district_id', 'user_id', 'role_id']);
            });
        }

        if ($this->hasIndex('district_user', 'district_user_district_id_user_id_unique')) {
            Schema::table('district_user', static function (Blueprint $table): void {
                $table->dropUnique('district_user_district_id_user_id_unique');
            });
        }

        if ($this->hasIndex('district_user', 'district_user_user_id_index')) {
            Schema::table('district_user', static function (Blueprint $table): void {
                $table->dropIndex('district_user_user_id_index');
            });
        }

        if ($this->hasIndex('district_user', 'district_user_role_id_index')) {
            Schema::table('district_user', static function (Blueprint $table): void {
                $table->dropIndex('district_user_role_id_index');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
