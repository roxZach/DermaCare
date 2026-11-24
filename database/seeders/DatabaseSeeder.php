<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Article;
use App\Models\BiopsyOrder;
use App\Models\ChatLog;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\DoctorReview;
use App\Models\Invoice;
use App\Models\Lesion;
use App\Models\LesionAnalysis;
use App\Models\LesionImage;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\Service;
use App\Models\TeleRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo data so every role has something to see. All passwords are "password".
 * Lesion images created here are simple synthetic drawings (not real patients) and the
 * seeded AI results are labelled model "seed-demo".
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(FaqSeeder::class);
        $this->articles();
        $services = $this->services();

        // ---------------- staff accounts
        $mk = fn ($name, $email, $role, $phone = null) => User::create([
            'name' => $name, 'email' => $email, 'role' => $role, 'phone' => $phone,
            'password' => 'password', 'email_verified_at' => now(), 'is_active' => true,
        ]);
        $mk('System Admin', 'admin@dermacare.test', 'admin');
        $reception = $mk('Nadia Karim', 'reception@dermacare.test', 'receptionist', '01700000002');
        $lab = $mk('Imran Hossain', 'lab@dermacare.test', 'lab', '01700000003');

        $doctorData = [
            ['Dr. Ayesha Rahman', 'dr.rahman@dermacare.test', 'Dermato-oncology & Skin Cancer', 'MBBS, FCPS (Dermatology)', 12, 1200,
                'Specialises in mole mapping, dermoscopy and early detection of skin cancer.', [[0, '09:00', '13:00'], [2, '09:00', '13:00'], [4, '15:00', '19:00']]],
            ['Dr. Daniel Okafor', 'dr.okafor@dermacare.test', 'Medical Dermatology', 'MBBS, MD (Dermatology)', 8, 1000,
                'Treats eczema, psoriasis, acne and skin infections in adults and children.', [[1, '10:00', '14:00'], [3, '10:00', '14:00'], [6, '10:00', '13:00']]],
            ['Dr. Mei Lin', 'dr.lin@dermacare.test', 'Cosmetic & Laser Dermatology', 'MBBS, DDV, Fellowship in Lasers', 6, 1500,
                'Acne scars, pigmentation, chemical peels and laser treatments.', [[0, '16:00', '20:00'], [2, '16:00', '20:00'], [5, '15:00', '18:00']]],
        ];
        $doctors = [];
        foreach ($doctorData as [$name, $email, $spec, $qual, $exp, $fee, $bio, $sched]) {
            $u = $mk($name, $email, 'dermatologist');
            $d = Doctor::create(['user_id' => $u->id, 'specialty' => $spec, 'qualifications' => $qual,
                'experience_years' => $exp, 'consultation_fee' => $fee, 'bio' => $bio]);
            // every listed day plus a weekday fallback so a demo on any day has slots
            foreach ($sched as [$day, $start, $end]) {
                $d->schedules()->create(['day_of_week' => $day, 'start_time' => $start, 'end_time' => $end, 'slot_minutes' => 20]);
            }
            $doctors[] = $d;
        }
        // Make sure each doctor also works today (so the demo always has free slots)
        foreach ($doctors as $d) {
            if (! $d->schedules()->where('day_of_week', today()->dayOfWeek)->exists()) {
                $d->schedules()->create(['day_of_week' => today()->dayOfWeek, 'start_time' => '08:00', 'end_time' => '21:00', 'slot_minutes' => 20]);
            }
        }

        // ---------------- patients
        $patientsData = [
            ['Sara Ahmed', 'patient@dermacare.test', '01800000001', '1994-05-12', 'female', 3, 'moderate', true],
            ['Tanvir Hasan', 'tanvir@example.test', '01800000002', '1988-11-02', 'male', 4, 'high', false],
            ['Maria Gomez', 'maria@example.test', '01800000003', '1979-03-21', 'female', 2, 'high', true],
            ['Rafi Islam', null, '01800000004', '2001-07-30', 'male', 5, 'low', false],
            ['Lena Fischer', 'lena@example.test', '01800000005', '1990-09-15', 'female', 1, 'moderate', false],
        ];
        $patients = [];
        foreach ($patientsData as [$name, $email, $phone, $dob, $gender, $fitz, $sun, $fam]) {
            $user = $email ? User::create(['name' => $name, 'email' => $email, 'phone' => $phone, 'role' => 'patient',
                'password' => 'password', 'email_verified_at' => now()]) : null;
            $patients[] = Patient::create(['user_id' => $user?->id, 'name' => $name, 'email' => $email, 'phone' => $phone,
                'date_of_birth' => $dob, 'gender' => $gender, 'fitzpatrick_type' => $fitz, 'sun_exposure' => $sun,
                'family_history_skin_cancer' => $fam, 'address' => '12 Example Road', 'allergies' => $name === 'Sara Ahmed' ? 'Penicillin' : null]);
        }
        [$sara, $tanvir, $maria, $rafi, $lena] = $patients;

        // ---------------- past visits (completed) with consultations, prescriptions, invoices
        $diagnoses = ['Acne vulgaris', 'Atopic dermatitis (eczema)', 'Psoriasis', 'Melanocytic nevus', 'Seborrheic keratosis',
            'Tinea (fungal infection)', 'Melasma', 'Acne vulgaris', 'Urticaria', 'Actinic keratosis'];
        $consult = $services->firstWhere('name', 'Dermatology consultation');
        foreach (range(1, 18) as $i) {
            $p = $patients[$i % 5];
            $d = $doctors[$i % 3];
            $date = today()->subDays(1 + ($i % 13));
            $a = Appointment::create(['patient_id' => $p->id, 'doctor_id' => $d->id, 'date' => $date,
                'time' => sprintf('%02d:%02d', 9 + ($i % 8), ($i % 3) * 20), 'status' => 'completed', 'reason' => 'Follow-up / skin check']);
            $dx = $diagnoses[$i % count($diagnoses)];
            $c = Consultation::create(['patient_id' => $p->id, 'doctor_id' => $d->id, 'appointment_id' => $a->id,
                'complaint' => 'Skin complaint for review', 'findings' => 'Examined; see diagnosis.', 'diagnosis' => $dx,
                'created_at' => $date->copy()->setTime(12, 0), 'updated_at' => $date->copy()->setTime(12, 0)]);
            if ($i % 2 === 0) {
                $rx = Prescription::create(['patient_id' => $p->id, 'doctor_id' => $d->id, 'consultation_id' => $c->id,
                    'advice' => 'Use sunscreen SPF 50 daily. Avoid harsh soaps.', 'created_at' => $c->created_at]);
                $rx->items()->create(['medicine' => 'Emollient (moisturiser)', 'dosage' => 'Apply thinly', 'frequency' => 'Twice daily', 'duration' => '4 weeks']);
                $rx->items()->create(['medicine' => $dx === 'Acne vulgaris' ? 'Adapalene 0.1% gel' : 'Mometasone 0.1% cream',
                    'dosage' => 'Pea-sized amount', 'frequency' => 'Once at night', 'duration' => '6 weeks']);
            }
            $inv = Invoice::create(['number' => 'INV-' . $date->format('Ymd') . '-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'patient_id' => $p->id, 'appointment_id' => $a->id, 'subtotal' => $d->consultation_fee, 'total' => $d->consultation_fee,
                'status' => ($i % 6 === 0 || $i === 5) ? 'unpaid' : 'paid', 'created_by' => $reception->id, 'created_at' => $date, 'updated_at' => $date]);
            $inv->items()->create(['service_id' => $consult?->id, 'description' => 'Consultation – ' . $d->name, 'quantity' => 1,
                'unit_price' => $d->consultation_fee, 'amount' => $d->consultation_fee]);
            if ($inv->status === 'paid') {
                Payment::create(['invoice_id' => $inv->id, 'amount' => $inv->total, 'method' => ['cash', 'card', 'mobile'][$i % 3],
                    'received_by' => $reception->id, 'created_at' => $date->copy()->setTime(13, 0)]);
            }
            if ($i % 4 === 0) {
                DoctorReview::create(['patient_id' => $p->id, 'doctor_id' => $d->id, 'appointment_id' => $a->id,
                    'rating' => 4 + ($i % 2), 'comment' => 'Clear explanation and friendly staff.']);
            }
        }

        // ---------------- upcoming appointments (today and tomorrow)
        $times = ['09:00', '09:20', '10:00', '10:40', '16:00', '16:40', '17:20'];
        foreach ([[$sara, 0, 'Mole check – one mole looks darker', 'checked_in', 1], [$tanvir, 0, 'Itchy rash on both arms', 'booked', null],
                  [$rafi, 1, 'Acne follow-up', 'booked', null], [$maria, 2, 'Pigmentation on cheeks', 'booked', null]] as $k => [$p, $di, $reason, $status, $q]) {
            $d = $doctors[$di];
            $slot = $d->availableSlots(today()->toDateString())[$k] ?? $times[$k];
            Appointment::create(['patient_id' => $p->id, 'doctor_id' => $d->id, 'date' => today(), 'time' => $slot,
                'status' => $status, 'reason' => $reason, 'queue_no' => $q, 'booked_by' => $reception->id]);
        }
        foreach ([[$lena, 0], [$sara, 1]] as $k => [$p, $di]) {
            $d = $doctors[$di];
            $day = today()->addDay();
            $slot = $d->availableSlots($day->toDateString())[$k] ?? '10:00';
            Appointment::create(['patient_id' => $p->id, 'doctor_id' => $d->id, 'date' => $day, 'time' => $slot,
                'status' => 'booked', 'reason' => 'Routine skin check']);
        }

        // ---------------- lesions, synthetic images and sample AI results
        $drR = $doctors[0];
        $lesions = [
            [$sara, 'Mole A – upper back', 'Upper back', 'back', 44, 30, [['nv', 0.81, 'low', 'confirmed']], 1],
            [$sara, 'Mole B – left forearm', 'Left arm', 'front', 82, 42, [['nv', 0.62, 'medium', 'confirmed'], ['mel', 0.47, 'high', 'pending_review']], 2],
            [$maria, 'Lesion on nose', 'Face / head', 'front', 50, 9, [['bcc', 0.71, 'high', 'confirmed']], 3],
            [$lena, 'Scaly patch – scalp', 'Scalp / back of head', 'back', 52, 5, [['akiec', 0.55, 'medium', 'overridden']], 4],
            [$tanvir, 'Brown spot – chest', 'Chest', 'front', 45, 28, [['bkl', 0.77, 'low', 'pending_review']], 5],
        ];
        foreach ($lesions as [$p, $label, $region, $view, $x, $y, $results, $seed]) {
            $lesion = Lesion::create(['patient_id' => $p->id, 'label' => $label, 'body_region' => $region, 'view' => $view,
                'x' => $x, 'y' => $y, 'created_by' => $drR->user_id, 'notes' => 'Sample lesion (synthetic demo image)']);
            foreach ($results as $n => [$cls, $conf, $risk, $status]) {
                $created = now()->subDays(60 - 50 * $n);
                $path = $this->syntheticLesionImage($p->id, $seed * 10 + $n, $cls === 'mel');
                $img = LesionImage::create(['lesion_id' => $lesion->id, 'path' => $path, 'image_type' => 'dermoscopic',
                    'uploaded_by' => $drR->user_id, 'quality_message' => 'Synthetic demo image', 'created_at' => $created, 'updated_at' => $created]);
                $probs = $this->fakeProbabilities($cls, $conf);
                $reviewed = $status !== 'pending_review';
                LesionAnalysis::create([
                    'lesion_image_id' => $img->id, 'requested_by' => $drR->user_id, 'model_version' => 'seed-demo',
                    'predicted_class' => $cls, 'confidence' => $conf, 'probabilities' => $probs, 'risk_level' => $risk,
                    'status' => $status,
                    'final_diagnosis' => $status === 'confirmed' ? LesionAnalysis::className($cls)
                        : ($status === 'overridden' ? 'Seborrheic dermatitis (scalp)' : null),
                    'doctor_note' => $reviewed ? 'Sample data for the demo.' : null,
                    'reviewed_by' => $reviewed ? $drR->user_id : null, 'reviewed_at' => $reviewed ? $created->copy()->addHour() : null,
                    'created_at' => $created, 'updated_at' => $created,
                ]);
            }
        }

        // ---------------- teledermatology request waiting for a doctor
        $teleLesion = Lesion::where('label', 'Brown spot – chest')->first();
        TeleRequest::create(['patient_id' => $tanvir->id, 'lesion_id' => $teleLesion->id, 'subject' => 'Brown spot on chest getting bigger',
            'description' => "I noticed this spot about 6 months ago. It seems a little bigger now and is sometimes itchy.\nNo bleeding.",
            'duration' => '6 months']);

        // ---------------- biopsies: one waiting at the lab, one completed and waiting for doctor review
        $nose = Lesion::where('label', 'Lesion on nose')->first();
        BiopsyOrder::create(['patient_id' => $maria->id, 'doctor_id' => $drR->id, 'lesion_id' => $nose->id,
            'test_type' => 'Shave biopsy', 'site' => 'Nasal tip', 'clinical_note' => 'Pearly papule, suspected BCC.', 'status' => 'sample_collected',
            'lab_user_id' => $lab->id]);
        $moleB = Lesion::where('label', 'Mole B – left forearm')->first();
        Storage::disk('local')->put('pathology/demo-report.pdf', $this->tinyPdf('Pathology report (demo): Compound melanocytic nevus with mild architectural atypia. Margins clear.'));
        BiopsyOrder::create(['patient_id' => $sara->id, 'doctor_id' => $drR->id, 'lesion_id' => $moleB->id,
            'test_type' => 'Excisional biopsy', 'site' => 'Left forearm', 'clinical_note' => 'Changing pigmented lesion.', 'status' => 'completed',
            'findings' => 'Compound melanocytic nevus with mild architectural atypia. No evidence of melanoma. Margins clear.',
            'report_path' => 'pathology/demo-report.pdf', 'lab_user_id' => $lab->id, 'completed_at' => now()->subDay()]);

        // ---------------- chatbot questions for the admin's "unanswered" list
        foreach (['Do you accept health insurance from my employer?', 'Can I bring my 3 year old for eczema?', 'Is there parking at the clinic?'] as $q) {
            ChatLog::create(['session_id' => Str::random(20), 'question' => $q, 'answer' => 'Escalated to staff.',
                'confidence' => 0.12, 'engine' => 'seed', 'escalated' => true]);
        }

        \App\Models\Alert::send($drR->user_id, 'Pathology report ready', 'Excisional biopsy for Sara Ahmed', null);
    }

    private function services()
    {
        $rows = [
            ['Dermatology consultation', 'Medical', 1000, 'Assessment of any skin, hair or nail problem by a dermatologist.'],
            ['Follow-up visit', 'Medical', 600, 'Review within 4 weeks of a previous consultation.'],
            ['Mole check with dermoscopy', 'Medical', 1500, 'Examination of moles with a dermatoscope, with AI-assisted image analysis.'],
            ['Full-body skin check (mole mapping)', 'Medical', 3000, 'Photographic mapping of all moles for follow-up over time.'],
            ['Teledermatology (online review)', 'Medical', 700, 'A dermatologist reviews your photos and replies online.'],
            ['Skin biopsy', 'Procedure', 2500, 'Removal of a small skin sample for laboratory examination.'],
            ['Cryotherapy (per session)', 'Procedure', 1200, 'Freezing treatment for warts and keratoses.'],
            ['Wart / skin tag removal', 'Procedure', 1800, 'Removal of warts or skin tags.'],
            ['Chemical peel', 'Cosmetic', 3500, 'Treatment for acne marks, pigmentation and dull skin.'],
            ['Laser hair removal (small area)', 'Cosmetic', 4000, 'Per session, small area such as upper lip or underarms.'],
            ['Acne scar treatment (microneedling)', 'Cosmetic', 5000, 'Per session.'],
            ['Fungal scraping test (KOH)', 'Laboratory', 500, 'Microscopy test for fungal infection.'],
        ];
        foreach ($rows as [$name, $cat, $price, $desc]) {
            Service::create(['name' => $name, 'category' => $cat, 'price' => $price, 'description' => $desc]);
        }
        return Service::all();
    }

    private function articles(): void
    {
        $items = [
            ['Know your moles: the ABCDE rule', 'How to check your skin for warning signs of melanoma.',
                "Most moles are harmless, but checking your skin once a month helps you notice changes early.\n\nUse the ABCDE rule:\n- A – Asymmetry: one half does not match the other.\n- B – Border: edges are ragged, notched or blurred.\n- C – Colour: several shades of brown, black, red, white or blue.\n- D – Diameter: larger than 6 mm (about the size of a pencil eraser), although melanomas can be smaller.\n- E – Evolving: any change in size, shape, colour, height, or new symptoms such as itching or bleeding.\n\nAlso watch for the \"ugly duckling\" – a mole that looks different from all your other moles.\n\nIf you notice any of these signs, book a skin check. Early detection makes treatment much simpler."],
            ['Sunscreen: how to choose and use it', 'SPF, broad spectrum and how much to apply.',
                "Choose a broad-spectrum sunscreen with SPF 30 or higher. Broad spectrum means it protects against both UVA and UVB rays.\n\nApply generously 15 minutes before going outside – about a teaspoon for the face and neck. Reapply every two hours and after swimming or sweating.\n\nSunscreen is only one part of sun protection: also use shade, hats, sunglasses and clothing, especially between 10 am and 4 pm."],
            ['Living with eczema', 'Simple daily habits that reduce flare-ups.',
                "Eczema (atopic dermatitis) makes the skin dry, itchy and inflamed.\n\nHelpful habits:\n- Moisturise at least twice a day, especially right after bathing.\n- Use lukewarm water and mild, fragrance-free cleansers.\n- Wear soft cotton clothing.\n- Identify and avoid your triggers, such as certain soaps, sweat or stress.\n\nIf your eczema is not controlled with moisturisers, a dermatologist can prescribe treatments to calm flare-ups."],
            ['Acne: myths and facts', 'What really helps clear acne.',
                "Myth: acne is caused by dirty skin. Fact: acne is caused by oil, blocked pores, bacteria and inflammation. Over-washing can make it worse.\n\nMyth: you should pop pimples. Fact: squeezing increases the risk of scars and dark marks.\n\nWhat helps: gentle cleansing twice a day, non-comedogenic products and proven treatments such as benzoyl peroxide or retinoids. Moderate or severe acne should be treated by a dermatologist to prevent scarring."],
            ['How to take a good photo of a skin problem', 'Tips for teledermatology photos.',
                "Good photos help our dermatologists – and our AI quality check – assess your skin.\n\n- Use natural daylight, without flash.\n- Hold the camera 10–15 cm from the skin and keep the lesion in the centre.\n- Tap the screen to focus and keep your hand steady.\n- Take one close-up and one photo from further away to show the location.\n- Remove make-up or creams from the area first.\n\nIf a photo is too blurry or too dark, the system will ask you to retake it."],
        ];
        foreach ($items as [$title, $summary, $body]) {
            Article::create(['title' => $title, 'slug' => Str::slug($title), 'summary' => $summary, 'body' => $body]);
        }
    }

    /** Draw a simple synthetic "lesion" image with GD (demo only, not a real patient photo). */
    private function syntheticLesionImage(int $patientId, int $seed, bool $irregular): string
    {
        mt_srand($seed);
        $w = 448;
        $im = imagecreatetruecolor($w, $w);
        imagefill($im, 0, 0, imagecolorallocate($im, 214 + mt_rand(-8, 8), 172 + mt_rand(-8, 8), 150 + mt_rand(-8, 8)));
        for ($i = 0; $i < 2500; $i++) { // skin texture
            $c = imagecolorallocatealpha($im, 190 + mt_rand(0, 40), 150 + mt_rand(0, 30), 130 + mt_rand(0, 30), 90);
            imagesetpixel($im, mt_rand(0, $w - 1), mt_rand(0, $w - 1), $c);
        }
        $cx = $w / 2 + mt_rand(-20, 20);
        $cy = $w / 2 + mt_rand(-20, 20);
        $layers = $irregular ? [[150, [70, 40, 30]], [110, [40, 25, 20]], [60, [90, 50, 70]]] : [[140, [120, 75, 50]], [90, [95, 58, 40]]];
        foreach ($layers as [$r, $rgb]) {
            $col = imagecolorallocate($im, ...$rgb);
            $pts = [];
            for ($a = 0; $a < 360; $a += 15) {
                $rr = $r / 1.0 + mt_rand(-($irregular ? 30 : 8), $irregular ? 30 : 8);
                $pts[] = (int) ($cx + cos(deg2rad($a)) * $rr * 0.9);
                $pts[] = (int) ($cy + sin(deg2rad($a)) * $rr * 0.75);
            }
            imagefilledpolygon($im, $pts, $col);
        }
        imagefilter($im, IMG_FILTER_GAUSSIAN_BLUR);
        for ($i = 0; $i < 14; $i++) { // a few hairs, as in real dermoscopy images
            imagesetthickness($im, mt_rand(1, 2));
            $x = mt_rand(0, $w);
            imageline($im, $x, 0, $x + mt_rand(-200, 200), $w, imagecolorallocate($im, 60, 40, 30));
        }
        for ($i = 0; $i < 6000; $i++) { // fine skin texture
            $v = mt_rand(-25, 25);
            $rgb = imagecolorat($im, $x = mt_rand(0, $w - 1), $y = mt_rand(0, $w - 1));
            $r = max(0, min(255, (($rgb >> 16) & 255) + $v));
            $g = max(0, min(255, (($rgb >> 8) & 255) + $v));
            $b = max(0, min(255, ($rgb & 255) + $v));
            imagesetpixel($im, $x, $y, imagecolorallocate($im, $r, $g, $b));
        }
        ob_start();
        imagejpeg($im, null, 90);
        $data = ob_get_clean();
        imagedestroy($im);
        $path = "lesions/{$patientId}/demo-{$seed}.jpg";
        Storage::disk('local')->put($path, $data);
        return $path;
    }

    private function fakeProbabilities(string $top, float $conf): array
    {
        $classes = array_keys(LesionAnalysis::CLASSES);
        $rest = array_values(array_diff($classes, [$top]));
        $weights = [0.35, 0.25, 0.15, 0.12, 0.08, 0.05];
        $p = [$top => $conf];
        foreach ($rest as $i => $c) {
            $p[$c] = round((1 - $conf) * $weights[$i], 4);
        }
        return $p;
    }

    /** Minimal one-page PDF, used for the demo pathology report. */
    private function tinyPdf(string $text): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $stream = "BT /F1 12 Tf 50 780 Td (DermaCare AI - Pathology Report) Tj 0 -24 Td /F1 10 Tf ($text) Tj ET";
        $objs = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length ' . strlen($stream) . " >>\nstream\n$stream\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $i => $o) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n$o\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        return $pdf . "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    }
}
