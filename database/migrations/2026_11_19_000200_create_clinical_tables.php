<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clinical tables: consultations, lesions (body map), images, AI analyses,
 * teledermatology, prescriptions and biopsies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->text('complaint')->nullable();
            $table->text('findings')->nullable();
            $table->string('diagnosis')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // A lesion marked on the body map. x / y are percentages on the body diagram.
        Schema::create('lesions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('body_region')->nullable();
            $table->string('view', 5)->default('front'); // front / back
            $table->decimal('x', 5, 2);
            $table->decimal('y', 5, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lesion_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesion_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('source', 20)->default('clinic'); // clinic / teledermatology
            $table->string('image_type', 20)->default('dermoscopic'); // dermoscopic / clinical
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('quality_ok')->default(true);
            $table->string('quality_message')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_model_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version');
            $table->string('architecture')->nullable();
            $table->string('file_name')->nullable();
            $table->text('description')->nullable();
            $table->json('metrics')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // Result of the AI analysis of one image, plus the dermatologist's review.
        Schema::create('lesion_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesion_image_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('model_version')->nullable();
            $table->string('predicted_class', 10)->nullable(); // akiec, bcc, bkl, df, mel, nv, vasc
            $table->decimal('confidence', 5, 4)->nullable();
            $table->json('probabilities')->nullable();
            $table->string('risk_level', 10)->nullable();     // low / medium / high
            $table->string('heatmap_path')->nullable();
            // pending_review, confirmed, overridden, failed
            $table->string('status', 20)->default('pending_review')->index();
            $table->string('final_diagnosis')->nullable();
            $table->text('doctor_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('tele_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lesion_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->text('description');
            $table->string('duration')->nullable();
            $table->string('status', 20)->default('submitted'); // submitted, reviewed
            $table->text('doctor_reply')->nullable();
            $table->boolean('visit_recommended')->default(false);
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained()->nullOnDelete();
            $table->text('advice')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->timestamps();
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->string('medicine');
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->string('duration')->nullable();
            $table->string('instructions')->nullable();
        });

        Schema::create('biopsy_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesion_id')->nullable()->constrained()->nullOnDelete();
            $table->string('test_type')->default('Skin punch biopsy');
            $table->string('site')->nullable();
            $table->text('clinical_note')->nullable();
            // ordered, sample_collected, sent_to_lab, in_process, completed, reviewed
            $table->string('status', 20)->default('ordered')->index();
            $table->text('findings')->nullable();
            $table->string('report_path')->nullable();
            $table->string('histo_image_path')->nullable();
            $table->foreignId('lab_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('doctor_comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['biopsy_orders', 'prescription_items', 'prescriptions', 'tele_requests', 'lesion_analyses',
                  'ai_model_versions', 'lesion_images', 'lesions', 'consultations'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
