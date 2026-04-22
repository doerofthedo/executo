<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('district_settings', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->string('locale', 5)->default('lv');
            $table->string('date_format')->default('DD.MM.YYYY.');
            $table->string('decimal_separator', 5)->default(',');
            $table->string('thousand_separator', 5)->default(' ');
            $table->timestamps();

            $table->unique('district_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('district_settings');
    }
};
