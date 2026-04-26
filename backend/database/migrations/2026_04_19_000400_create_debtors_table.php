<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debtors', static function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('case_number')->nullable();
            $table->enum('type', ['physical', 'legal'])->default('physical');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('personal_code')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('company_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('contact_person')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['district_id', 'type']);
            $table->index('case_number');
            $table->index('personal_code');
            $table->index('registration_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debtors');
    }
};
