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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('id_document')->nullable();
            $table->string('nssf_document')->nullable();
            $table->string('nhif_document')->nullable();
            $table->string('passport_photo')->nullable();
            $table->string('birth_certificate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['id_document', 'nssf_document', 'nhif_document', 'passport_photo', 'birth_certificate']);
        });
    }
};
