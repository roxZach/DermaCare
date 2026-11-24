<?php

namespace App\Http\Controllers;

use App\Models\ChatLog;
use App\Models\Faq;
use App\Services\AiService;
use Illuminate\Http\Request;

/**
 * Intelligent FAQ Chatbot module.
 * Ask a Question <<include>> Match Question to FAQ Intent (Python AI Engine: TF-IDF / Sentence-BERT)
 * <<extend>> Suggest Booking Appointment, <<extend>> Escalate to Staff.
 */
class ChatController extends Controller
{
    /** Fallback threshold (PHP matcher). The AI Engine sends its own calibrated threshold. */
    private const MIN_CONFIDENCE = 0.30;

    /** Words that suggest the user is describing a personal symptom (we never diagnose in chat). */
    private const SYMPTOM_WORDS = ['rash', 'itch', 'itchy', 'mole', 'bleeding', 'bleeds', 'pain', 'painful', 'spot',
        'lump', 'growing', 'changed', 'changing', 'swelling', 'blister', 'wound', 'sore', 'burning', 'patch', 'my skin'];

    public function ask(Request $request, AiService $ai)
    {
        $question = trim($request->validate(['question' => 'required|string|max:500'])['question']);

        $result = $ai->chatAsk($question);
        if (! ($result['ok'] ?? false) && ($result['error'] ?? '') === 'index_empty') {
            // The AI Engine was restarted without an index: send the FAQs and try again.
            \App\Http\Controllers\Admin\FaqController::pushIndex($ai);
            $result = $ai->chatAsk($question);
        }
        if ($result['ok'] ?? false) {
            $faqId = $result['faq_id'] ?? null;
            $score = (float) ($result['score'] ?? 0);
            $engine = $result['engine'] ?? 'ai-engine';
            $threshold = (float) ($result['threshold'] ?? self::MIN_CONFIDENCE); // calibrated by chatbot_eval.py
        } else {
            // Fallback when the Python service is offline: simple word-overlap matching in PHP.
            [$faqId, $score] = $this->fallbackMatch($question);
            $engine = 'php-fallback';
            $threshold = self::MIN_CONFIDENCE;
        }

        $faq = $faqId ? Faq::where('is_active', true)->find($faqId) : null;
        $escalated = ! $faq || $score < $threshold;
        $suggestBooking = $this->looksLikeSymptom($question);

        if ($escalated) {
            $answer = "I'm not sure about that one. I've passed your question to our staff, who will add an answer soon. "
                . 'You can also call the clinic during opening hours.';
        } else {
            $answer = $faq->answer;
        }
        if ($suggestBooking) {
            $answer .= "\n\nIt sounds like you may be describing a skin problem. I can't diagnose conditions, "
                . 'so please book an appointment with one of our dermatologists.';
        }

        $log = ChatLog::create([
            'user_id' => auth()->id(),
            'session_id' => $request->session()->getId(),
            'question' => $question,
            'answer' => $answer,
            'faq_id' => $escalated ? null : $faq?->id,
            'confidence' => round($score, 4),
            'engine' => $engine,
            'escalated' => $escalated,
            'suggest_booking' => $suggestBooking,
        ]);

        return response()->json([
            'id' => $log->id,
            'answer' => $answer,
            'confidence' => round($score, 2),
            'escalated' => $escalated,
            'suggest_booking' => $suggestBooking,
            'booking_url' => auth()->user()?->hasRole('patient') ? route('patient.appointments.create') : route('register'),
            'matched_question' => $escalated ? null : $faq?->question,
            'engine' => $engine,
        ]);
    }

    // Use case: Rate Answer
    public function rate(Request $request, ChatLog $log)
    {
        $request->validate(['rating' => 'required|in:1,-1']);
        abort_unless($log->session_id === $request->session()->getId() || ($log->user_id && $log->user_id === auth()->id()), 403);
        $log->update(['rating' => (int) $request->rating]);
        return response()->json(['ok' => true]);
    }

    // Use case: View Chat History (patients)
    public function history()
    {
        return view('patient.chat-history', ['logs' => ChatLog::where('user_id', auth()->id())->latest()->paginate(20)]);
    }

    private function looksLikeSymptom(string $q): bool
    {
        $q = strtolower($q);
        foreach (self::SYMPTOM_WORDS as $w) {
            if (preg_match('/\b' . preg_quote($w, '/') . '\b/', $q)) {
                return true;
            }
        }
        return false;
    }

    /** Jaccard similarity over word sets, used only if the AI Engine is unreachable. */
    private function fallbackMatch(string $question): array
    {
        $stop = ['the', 'a', 'an', 'is', 'are', 'do', 'does', 'i', 'you', 'to', 'of', 'for', 'in', 'on', 'and', 'my',
            'can', 'what', 'how', 'it', 'be', 'with', 'your', 'me', 'at', 'there'];
        $tok = fn ($s) => array_values(array_diff(array_unique(preg_split('/[^a-z0-9]+/', strtolower($s), -1, PREG_SPLIT_NO_EMPTY)), $stop));
        $q = $tok($question);
        $best = [null, 0.0];
        foreach (Faq::where('is_active', true)->get() as $faq) {
            foreach (array_merge([$faq->question], preg_split('/\r?\n/', (string) $faq->paraphrases)) as $variant) {
                $v = $tok($variant);
                if (! $v || ! $q) {
                    continue;
                }
                $score = count(array_intersect($q, $v)) / count(array_unique(array_merge($q, $v)));
                if ($score > $best[1]) {
                    $best = [$faq->id, $score];
                }
            }
        }
        return $best;
    }
}
