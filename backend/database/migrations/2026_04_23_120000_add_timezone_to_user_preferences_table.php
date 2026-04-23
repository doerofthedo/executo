<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', static function (Blueprint $table): void {
            $table->string('timezone')->default('Europe/Riga')->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', static function (Blueprint $table): void {
            $table->dropColumn('timezone');
        });
    }
};
