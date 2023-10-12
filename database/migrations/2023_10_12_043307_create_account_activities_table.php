<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('card_id')->nullable();
            $table->string('consent_id_card')->nullable();
            $table->date('date_consent_id_card')->nullable();
            $table->string('consent_capture_country')->nullable();
            $table->string('presented_languaje')->nullable();
            $table->string('consent_id_email')->nullable();
            $table->date('date_consent_id_email')->nullable();
            $table->string('consent_id_phone')->nullable();
            $table->date('date_consent_id_phone')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_activities');
    }
};
