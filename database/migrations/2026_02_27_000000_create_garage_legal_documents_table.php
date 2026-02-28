<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('garage_legal_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('garage_id')->index();

            // fixed doc types
            $table->string('doc_type', 64)->index(); // certificate_of_incorporation, company_registration_certificate, kra_pin_certificate, tax_compliance_certificate

            // storage
            $table->string('path');                 // garages/{garage_id}/legal/xxx.pdf
            $table->string('original_name')->nullable();
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);

            // audit
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('uploaded_at')->nullable();

            $table->timestamps();

            $table->unique(['garage_id', 'doc_type']); // one active per type
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garage_legal_documents');
    }
};