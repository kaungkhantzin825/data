<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('tenant_field_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('field_name');
            $table->string('option_value');
            $table->timestamps();

            $table->index(['tenant_id', 'field_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_field_options');
    }
};
