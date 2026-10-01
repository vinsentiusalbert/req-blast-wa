<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('language', 5);
            $table->string('display_name', 100);
            $table->string('header_type', 10)->default('NONE');
            $table->string('header_text', 60)->nullable();
            $table->string('asset_path')->nullable();
            $table->text('body');
            $table->string('footer', 60)->nullable();
            $table->json('buttons')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
