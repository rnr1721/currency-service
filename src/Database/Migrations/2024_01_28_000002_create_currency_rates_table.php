<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('rate', 20, 8);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['from_currency', 'to_currency']);
            $table->index('created_at');

            $table->foreign('from_currency')
                  ->references('code')
                  ->on('currencies')
                  ->onDelete('cascade');

            $table->foreign('to_currency')
                  ->references('code')
                  ->on('currencies')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
