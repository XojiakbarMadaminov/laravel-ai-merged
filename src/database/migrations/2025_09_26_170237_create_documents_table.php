<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('project_key')->default('default')->index();
            $table->text('content');
            $table->jsonb('meta')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE documents ADD COLUMN embedding vector(768)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_docs_embed ON documents USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
