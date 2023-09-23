<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table = app(config('shopify-app.user_model'))->getTable();

        Schema::table($table, function (Blueprint $table) {
            $table->string('domain')->nullable();
            $table->string('myshopify_domain')->nullable();
            $table->string('access_token')->nullable();
            $table->string('shop')->nullable();
            $table->unsignedBigInteger('recurring_application_charge_id')->nullable();
            $table->string('billing_status')->nullable();
            $table->timestamp('uninstalled_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};