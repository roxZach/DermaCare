<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ChatLog;
use App\Models\Faq;
use App\Services\AiService;
use Illuminate\Http\Request;

/**
 * Manage FAQ Knowledge Base (<<include>> Rebuild Chatbot Index), Review Unanswered Questions.
 */
class FaqController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $faqs = Faq::when($q, fn ($w) => $w->where('question', 'like', "%$q%")->orWhere('answer', 'like', "%$q%"))
            ->orderBy('category')->orderBy('question')->get();
        return view('admin.faqs.index', ['faqs' => $faqs, 'q' => $q, 'ai' => app(AiService::class)->health()]);
    }

    public function create(Request $request)
    {
        $faq = new Faq(['is_active' => true, 'question' => $request->query('question')]);
        return view('admin.faqs.form', ['faq' => $faq, 'fromLog' => $request->query('log')]);
    }

    public function store(Request $request, AiService $ai)
    {
        $faq = Faq::create($this->validated($request));
        if ($request->filled('from_log')) {
            ChatLog::whereKey($request->from_log)->update(['resolved' => true]);
        }
        AuditLog::record('faq_created', $faq->question);
        return $this->afterChange($ai, 'FAQ added.');
    }

    public function edit(Faq $faq)
    {
        return view('admin.faqs.form', ['faq' => $faq, 'fromLog' => null]);
    }

    public function update(Request $request, Faq $faq, AiService $ai)
    {
        $faq->update($this->validated($request));
        AuditLog::record('faq_updated', $faq->question);
        return $this->afterChange($ai, 'FAQ updated.');
    }

    public function destroy(Faq $faq, AiService $ai)
    {
        AuditLog::record('faq_deleted', $faq->question);
        $faq->delete();
        return $this->afterChange($ai, 'FAQ deleted.');
    }

    // <<include>> Rebuild Chatbot Index
    public function rebuild(AiService $ai)
    {
        return $this->afterChange($ai, 'Chatbot index rebuilt.');
    }

    public function unanswered()
    {
        $logs = ChatLog::where('resolved', false)
            ->where(fn ($q) => $q->where('escalated', true)->orWhere('rating', -1))->latest()->paginate(25);
        return view('admin.faqs.unanswered', compact('logs'));
    }

    public function resolve(ChatLog $log)
    {
        $log->update(['resolved' => true]);
        return back()->with('success', 'Marked as resolved.');
    }

    /** Send all active FAQs to the AI Engine so it re-computes the TF-IDF / embedding index. */
    public static function pushIndex(AiService $ai): array
    {
        $faqs = Faq::where('is_active', true)->get()->map(fn ($f) => [
            'id' => $f->id, 'question' => $f->question, 'answer' => $f->answer,
            'paraphrases' => array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $f->paraphrases)))),
        ])->all();
        return $ai->chatRebuild($faqs);
    }

    private function afterChange(AiService $ai, string $msg)
    {
        $r = self::pushIndex($ai);
        $redirect = redirect()->route('admin.faqs.index');
        return ($r['ok'] ?? false)
            ? $redirect->with('success', $msg . ' Chatbot index updated (' . ($r['count'] ?? 0) . ' FAQs, engine: ' . ($r['engine'] ?? '?') . ').')
            : $redirect->with('success', $msg)->with('error', 'The chatbot index could not be rebuilt: ' . ($r['error'] ?? '') . ' The PHP fallback matcher will be used until the AI Engine is running.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category' => 'required|string|max:50',
            'question' => 'required|string|max:255',
            'paraphrases' => 'nullable|string|max:3000',
            'answer' => 'required|string|max:3000',
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
