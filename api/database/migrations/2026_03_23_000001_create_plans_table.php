<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid', 191)->nullable()->unique();
            $table->string('slug', 100)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_annual', 10, 2)->default(0);
            $table->unsignedInteger('max_vehicles')->nullable()->comment('NULL = ilimitado');
            $table->unsignedInteger('max_users')->nullable()->comment('NULL = ilimitado');
            $table->unsignedInteger('max_drivers')->nullable()->comment('NULL = ilimitado');
            $table->unsignedInteger('max_places')->nullable()->comment('NULL = ilimitado');
            $table->unsignedInteger('max_customers')->nullable()->comment('NULL = ilimitado');
            $table->unsignedInteger('gps_interval_seconds')->default(30);
            $table->boolean('has_api')->default(false);
            $table->boolean('has_webhooks')->default(false);
            $table->boolean('has_reports')->default(false);
            $table->boolean('has_driver_management')->default(false);
            $table->boolean('has_advanced_analytics')->default(false);
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
