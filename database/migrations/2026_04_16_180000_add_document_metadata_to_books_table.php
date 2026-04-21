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
        Schema::table('entity_container_data', function (Blueprint $table) {
            $table->string('document_version', 120)->nullable()->after('description_html');
            $table->string('prepared_by', 255)->nullable()->after('document_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entity_container_data', function (Blueprint $table) {
            $table->dropColumn(['document_version', 'prepared_by']);
        });
    }
};
