<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qicard_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('qicard_payments')->cascadeOnDelete();
            $table->string('refund_id')->nullable()->unique();
            $table->string('request_id')->nullable()->index();
            $table->string('status')->default('PROCESSING')->index();
            $table->boolean('canceled')->default(false);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('IQD');
            $table->string('message')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('gateway_created_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qicard_refunds');
    }
};
