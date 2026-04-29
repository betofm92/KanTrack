<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('subscription_history', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid')->nullable()->unique();
            $table->string('company_uuid')->index();
            $table->string('subscription_uuid')->nullable()->index();
            $table->string('event');           // assigned, updated, suspended, activated, payment_registered, plan_changed, cancelled
            $table->string('previous_plan_slug')->nullable();
            $table->string('new_plan_slug')->nullable();
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            $table->timestamp('previous_expires_at')->nullable();
            $table->timestamp('new_expires_at')->nullable();
            $table->string('payment_uuid')->nullable(); // FK a subscription_payments si aplica
            $table->string('performed_by_uuid')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('company_uuid')->references('uuid')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_history');
    }
};
