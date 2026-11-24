<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Core clinic tables: patients, doctors, schedules, services, appointments, reviews.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A patient record. user_id is null for walk-in patients who have no online account.
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('address')->nullable();
            $table->text('allergies')->nullable();
            $table->text('medical_history')->nullable();
            // Dermatology-specific information
            $table->unsignedTinyInteger('fitzpatrick_type')->nullable(); // 1 to 6
            $table->string('sun_exposure', 20)->nullable();               // low / moderate / high
            $table->boolean('family_history_skin_cancer')->default(false);
            $table->boolean('personal_history_skin_cancer')->default(false);
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('specialty')->default('General Dermatology');
            $table->string('qualifications')->nullable();
            $table->unsignedSmallInteger('experience_years')->default(0);
            $table->decimal('consultation_fee', 10, 2)->default(0);
            $table->text('bio')->nullable();
            $table->timestamps();
        });

        // Weekly working hours of each doctor. day_of_week: 0 = Sunday ... 6 = Saturday
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('slot_minutes')->default(20);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->default('Medical');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('time');
            // booked, checked_in, completed, cancelled
            $table->string('status', 20)->default('booked')->index();
            $table->string('reason')->nullable();
            $table->unsignedSmallInteger('queue_no')->nullable();
            $table->foreignId('booked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'date', 'time']);
        });

        Schema::create('doctor_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['doctor_reviews', 'appointments', 'services', 'doctor_schedules', 'doctors', 'patients'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
