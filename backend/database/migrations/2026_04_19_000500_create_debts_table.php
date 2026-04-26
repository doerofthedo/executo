<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', static function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->foreignId('debtor_id')->constrained('debtors')->cascadeOnDelete();
            $table->decimal('amount', 15, 4);
            $table->date('date');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['district_id', 'debtor_id']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
