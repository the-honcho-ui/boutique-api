<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('paystack_secret_key')->nullable()->after('description');
            $table->string('paystack_public_key')->nullable()->after('paystack_secret_key');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['paystack_secret_key', 'paystack_public_key']);
        });
    }
};