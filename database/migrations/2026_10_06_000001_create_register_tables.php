<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('warn_days')->default(30);
            $table->unsignedInteger('suggested_interval_months')->nullable();
            $table->timestamps();
        });

        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('kind')->nullable();
            $table->timestamps();
        });

        Schema::create('records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_type_id')->constrained();
            $table->foreignId('entity_id')->constrained();
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('issuer')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('responsible_person')->nullable();
            $table->foreignId('supersedes_id')->nullable()->constrained('records')->nullOnDelete();
            $table->timestamps();

            $table->index('expiry_date');
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('records');
        Schema::dropIfExists('entities');
        Schema::dropIfExists('record_types');
    }
};
