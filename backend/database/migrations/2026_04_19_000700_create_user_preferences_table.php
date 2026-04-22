<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('locale', 5)->default('lv');
            $table->string('date_format')->default('DD.MM.YYYY.');
            $table->string('decimal_separator', 5)->default(',');
            $table->string('thousand_separator', 5)->default(' ');
            $table->unsignedSmallInteger('table_page_size')->default(25);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
