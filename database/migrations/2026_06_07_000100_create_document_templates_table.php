<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table): void {
            $table->id();

            // The document kind this template renders (e.g. 'sales_invoice').
            // Host apps register the available types + their data providers
            // through Darejer\Documents\DocumentTemplateRegistry.
            $table->string('document_type', 64)->index();

            // Translatable display name — Spatie\Translatable JSON bag:
            // {"en":"…","ar":"…","ckb":"…"}
            $table->json('name');

            // Path to the uploaded .docx template on the configured uploads disk.
            $table->string('file_path');

            // Page size hint surfaced to renderers (A4, A5, Letter, 80mm …).
            $table->string('paper_size', 16)->nullable();

            // Optional scoping — left as plain nullable ids (no FK) so the
            // package stays decoupled from any host schema.
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['document_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
