<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('iso_code', 2)->unique();
            $table->string('phone_prefix')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contact_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vat_rates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('rate', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('calendar_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('calendar_actions', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('company_settings', function (Blueprint $table): void {
            $table->id();
            $table->text('name')->nullable();
            $table->text('address')->nullable();
            $table->text('postal_code')->nullable();
            $table->text('city')->nullable();
            $table->text('tax_number')->nullable();
            $table->text('logo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('entities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('number')->unique();
            $table->boolean('is_customer')->default(false);
            $table->boolean('is_supplier')->default(false);
            $table->text('nif')->nullable();
            $table->string('nif_hash')->nullable()->unique();
            $table->text('name');
            $table->text('address')->nullable();
            $table->text('postal_code')->nullable();
            $table->text('city')->nullable();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->text('phone')->nullable();
            $table->text('mobile')->nullable();
            $table->text('website')->nullable();
            $table->text('email')->nullable();
            $table->string('email_hash')->nullable()->index();
            $table->boolean('gdpr_consent')->default(false);
            $table->text('notes')->nullable();
            $table->json('vies_payload')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('number')->unique();
            $table->foreignId('entity_id')->constrained()->cascadeOnDelete();
            $table->text('first_name');
            $table->text('last_name')->nullable();
            $table->foreignId('contact_role_id')->nullable()->constrained('contact_roles')->nullOnDelete();
            $table->text('phone')->nullable();
            $table->text('mobile')->nullable();
            $table->text('email')->nullable();
            $table->string('email_hash')->nullable()->index();
            $table->boolean('gdpr_consent')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->text('reference');
            $table->string('reference_hash')->unique();
            $table->text('name');
            $table->text('description')->nullable();
            $table->text('price');
            $table->foreignId('vat_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->text('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('proposals', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->date('proposal_date');
            $table->date('valid_until');
            $table->foreignId('entity_id')->constrained('entities')->restrictOnDelete();
            $table->json('line_items');
            $table->json('totals');
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->string('kind');
            $table->date('order_date');
            $table->date('valid_until')->nullable();
            $table->foreignId('customer_entity_id')->nullable()->constrained('entities')->nullOnDelete();
            $table->foreignId('supplier_entity_id')->nullable()->constrained('entities')->nullOnDelete();
            $table->foreignId('proposal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->json('line_items');
            $table->json('totals');
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('supplier_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->foreignId('supplier_entity_id')->constrained('entities')->restrictOnDelete();
            $table->foreignId('supplier_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->text('total');
            $table->text('document_path')->nullable();
            $table->text('payment_proof_path')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entity_id')->nullable()->constrained('entities')->nullOnDelete();
            $table->foreignId('calendar_type_id')->nullable()->constrained('calendar_types')->nullOnDelete();
            $table->foreignId('calendar_action_id')->nullable()->constrained('calendar_actions')->nullOnDelete();
            $table->dateTime('scheduled_for');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->boolean('shared')->default(false);
            $table->boolean('knowledge')->default(false);
            $table->text('description')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('supplier_invoices');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('proposals');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('entities');
        Schema::dropIfExists('company_settings');
        Schema::dropIfExists('calendar_actions');
        Schema::dropIfExists('calendar_types');
        Schema::dropIfExists('vat_rates');
        Schema::dropIfExists('contact_roles');
        Schema::dropIfExists('countries');
    }
};
