<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The document-template (uploaded .docx → PDF) feature was removed in favour of
 * the built-in Vue print pages, so drop its table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('document_templates');
    }

    public function down(): void
    {
        // Feature removed — nothing to restore.
    }
};
