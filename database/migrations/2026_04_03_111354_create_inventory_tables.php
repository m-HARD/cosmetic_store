<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('batch_code', 100)->nullable();
            $table->date('expiration_date')->index();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('remaining_quantity')->index();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'expiration_date']);
        });

        Schema::create('losses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('loss_value', 12, 2);
            $table->string('reason', 100);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('losses');
        Schema::dropIfExists('product_batches');
    }
};
