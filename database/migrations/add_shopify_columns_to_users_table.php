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
        if (! $table) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->string('domain')->nullable();
            $table->string('access_token')->nullable();
            $table->string('shop')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};