<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('company_subscriptions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid', 191)->nullable()->unique();
            $table->string('company_uuid', 191)->index();
            $table->string('plan_slug', 100)->index();
            $table->enum('billing_cycle', ['monthly', 'annual', 'custom'])->default('monthly');
            $table->unsignedInteger('extra_vehicles')->default(0)->comment('Vehículos adicionales comprados fuera del plan');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable()->comment('NULL = no expira (corporativo custom)');
            $table->enum('status', ['trial', 'active', 'suspended', 'expired', 'cancelled'])->default('trial');
            $table->text('notes')->nullable()->comment('Notas del admin sobre la suscripción');
            $table->timestamps();

            $table->foreign('company_uuid')->references('uuid')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_subscriptions');
    }
};
