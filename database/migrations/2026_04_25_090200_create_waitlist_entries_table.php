<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->string('email');
            $table->enum('status', ['active', 'notified', 'expired'])->default('active');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['product_variant_id', 'email', 'status'], 'waitlist_variant_email_status_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
