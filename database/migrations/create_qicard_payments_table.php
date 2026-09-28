<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qicard_payments', function (Blueprint $table) {
            $table->id();
            $table->string('terminal')->default('default');
            $table->string('request_id')->nullable()->index();
            $table->string('payment_id')->unique();
            $table->string('status')->default('CREATED')->index();
            $table->boolean('canceled')->default(false);
            $table->decimal('amount', 12, 2);
            $table->decimal('confirmed_amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('IQD');
            $table->string('payment_type')->nullable();
            $table->string('form_url')->nullable();
            $table->json('details')->nullable();
            $table->nullableMorphs('payable');
            $table->timestamp('gateway_created_at')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qicard_payments');
    }
};
