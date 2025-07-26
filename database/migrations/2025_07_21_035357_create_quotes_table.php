<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('service');
            $table->longText('describe_issue');
            $table->string('property_type');
            $table->string('service_type');
            $table->string('pool_depth')->nullable();
            $table->date('date');
            $table->time('time');
            $table->string('zip_code');
            $table->string('address');
            $table->decimal('expected_budget', 10, 2)->nullable()->default(0);
            $table->json('photos')->nullable();
            $table->string('video')->nullable();
            $table->enum('status', ['Pending', 'In progress', 'Completed'])->default('Pending');
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
