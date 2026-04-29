<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid')->nullable()->unique();
            $table->string('company_uuid')->index();
            $table->string('subscription_uuid')->nullable()->index();
            $table->string('plan_slug')->index();
            $table->string('billing_cycle')->default('monthly'); // monthly, annual, custom
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method')->nullable(); // bank_transfer, cash, card, etc.
            $table->string('reference')->nullable();      // nro. de transferencia, recibo, etc.
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->enum('status', ['pending', 'paid', 'failed'])->default('paid');
            $table->text('notes')->nullable();
            $table->string('registered_by_uuid')->nullable(); // admin que registró
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('company_uuid')->references('uuid')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
