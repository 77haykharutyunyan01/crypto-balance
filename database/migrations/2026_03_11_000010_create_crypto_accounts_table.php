<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crypto_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('currency', 20);
            $table->decimal('available_balance', 24, 8)->default(0);
            $table->decimal('locked_balance', 24, 8)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_accounts');
    }
};

