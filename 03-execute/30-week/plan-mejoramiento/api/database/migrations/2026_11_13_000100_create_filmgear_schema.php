<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * T-02 - Initial migration.
 *
 * The engine arrives empty and all DDL lives here (article V, ADR-001). No
 * column carries a DEFAULT: the domain supplies every value (data-model 3).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('create schema if not exists filmgear');

        Schema::create('filmgear.category', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 120)->unique();
        });

        Schema::create('filmgear.user', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('username', 120)->unique();
            // Never indexed: nobody searches by hash (data-model 7).
            $table->string('password_hash', 512);
            $table->string('role', 40);
        });

        Schema::create('filmgear.producer', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('full_name', 200);
            $table->string('document_number', 30)->unique();
        });

        Schema::create('filmgear.equipment', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 200);
            $table->string('brand', 120);
            $table->string('model_code', 20);
            $table->uuid('category_id');
            $table->integer('total_units');
            $table->integer('available_units');
            $table->string('photo_key', 512)->nullable();
            $table->timestampTz('deleted_at')->nullable();

            $table->foreign('category_id')->references('id')->on('filmgear.category')->restrictOnDelete();
        });

        Schema::create('filmgear.rental', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('producer_id');
            $table->uuid('rented_by_user_id');
            $table->timestampTz('rented_at');

            $table->foreign('producer_id')->references('id')->on('filmgear.producer')->restrictOnDelete();
            $table->foreign('rented_by_user_id')->references('id')->on('filmgear.user')->restrictOnDelete();
            $table->index('rented_at');
        });

        Schema::create('filmgear.rental_item', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('rental_id');
            $table->uuid('equipment_id');
            // Frozen at the instant of the rental (article VIII).
            $table->string('equipment_name', 200);
            $table->string('model_code', 20);
            // Deliberately no FK: the report's category must not follow the catalogue.
            $table->string('category_name', 120);
            $table->timestampTz('due_at');
            $table->timestampTz('returned_at')->nullable();

            $table->foreign('rental_id')->references('id')->on('filmgear.rental')->cascadeOnDelete();
            $table->foreign('equipment_id')->references('id')->on('filmgear.equipment')->restrictOnDelete();
            $table->unique(['rental_id', 'equipment_id']);
            $table->index('equipment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filmgear.rental_item');
        Schema::dropIfExists('filmgear.rental');
        Schema::dropIfExists('filmgear.equipment');
        Schema::dropIfExists('filmgear.producer');
        Schema::dropIfExists('filmgear.user');
        Schema::dropIfExists('filmgear.category');
    }
};
