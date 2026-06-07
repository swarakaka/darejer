<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_templates', function (Blueprint $table): void {
            // Language this template is designed for (e.g. an RTL Kurdish/Arabic
            // layout vs an LTR English one). Null = applies to any language.
            $table->string('locale', 16)->nullable()->after('document_type')->index();
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table): void {
            $table->dropColumn('locale');
        });
    }
};
