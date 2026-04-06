<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('business_name');
            $table->string('contact_name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('secondary_contact_number')->nullable();
            $table->string('division')->nullable();
            $table->string('township')->nullable();
            $table->text('address')->nullable();
            $table->string('biz_type')->nullable();
            $table->string('source')->nullable();
            $table->string('channel')->nullable();
            $table->string('weighted')->nullable();
            $table->string('potential')->nullable();
            $table->string('product')->nullable();
            $table->string('package')->nullable();
            $table->string('plan')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('package_total', 12, 2)->nullable();
            $table->decimal('discount', 12, 2)->nullable();
            $table->string('status')->default('active');
            $table->dateTime('installation_appointment')->nullable();
            $table->date('est_contract_date')->nullable();
            $table->date('est_start_date')->nullable();
            $table->date('est_follow_up_date')->nullable();
            $table->boolean('is_referral')->default(false);
            $table->text('meeting_note')->nullable();
            $table->text('next_step')->nullable();
            $table->date('contracted_date')->nullable();
            $table->date('installation_appointment_date')->nullable();
            $table->text('customer_note')->nullable();
            $table->text('note')->nullable();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
