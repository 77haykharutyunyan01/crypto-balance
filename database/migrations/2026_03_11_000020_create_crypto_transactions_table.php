<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crypto_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crypto_account_id')->constrained('crypto_accounts')->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('direction', 10);
            $table->decimal('amount', 24, 8);
            $table->string('status', 20);
            $table->string('risk_level', 20)->default('normal');
            $table->string('external_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['crypto_account_id', 'created_at']);
            $table->unique(['crypto_account_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_transactions');
    }
};

