<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Talks to the Python AI Engine (FastAPI service in the /ai-service folder).
 * Every method returns an array with an 'ok' key so callers can handle the
 * "AI Engine unavailable" case (alternative flow 3a in the use case specification).
 */
class AiService
{
    private string $url;
    private int $timeout;

    public function __construct()
    {
        $this->url = rtrim(config('services.ai.url'), '/');
        $this->timeout = (int) config('services.ai.timeout', 60);
    }

    public function health(): array
    {
        try {
            $r = Http::timeout(3)->get($this->url . '/health');
            return $r->successful() ? ['ok' => true] + $r->json() : ['ok' => false, 'error' => 'HTTP ' . $r->status()];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'AI Engine is not reachable at ' . $this->url];
        }
    }

    /** Image quality check only (used when patients upload photos). */
    public function quality(string $storagePath): array
    {
        return $this->postImage('/quality', $storagePath);
    }

    /** Full analysis: quality check, preprocessing, classification and risk level. */
    public function analyze(string $storagePath): array
    {
        return $this->postImage('/analyze', $storagePath);
    }

    /** Grad-CAM explanation heatmap (returned as base64 PNG). */
    public function gradcam(string $storagePath): array
    {
        return $this->postImage('/gradcam', $storagePath);
    }

    public function reloadModel(string $fileName): array
    {
        return $this->postJson('/model/reload', ['file_name' => $fileName]);
    }

    public function uploadModel(string $absolutePath, string $fileName): array
    {
        try {
            $r = Http::timeout(300)->attach('file', fopen($absolutePath, 'r'), $fileName)
                ->post($this->url . '/model/upload');
            return $this->wrap($r);
        } catch (Throwable $e) {
            return $this->fail($e);
        }
    }

    public function chatAsk(string $question): array
    {
        return $this->postJson('/chatbot/ask', ['question' => $question], 10);
    }

    public function chatRebuild(array $faqs): array
    {
        return $this->postJson('/chatbot/rebuild', ['faqs' => $faqs], 120);
    }

    // ------------------------------------------------------------------ helpers

    private function postImage(string $endpoint, string $storagePath): array
    {
        try {
            $abs = Storage::disk('local')->path($storagePath);
            $r = Http::timeout($this->timeout)
                ->attach('file', fopen($abs, 'r'), basename($abs))
                ->post($this->url . $endpoint);
            return $this->wrap($r);
        } catch (Throwable $e) {
            return $this->fail($e);
        }
    }

    private function postJson(string $endpoint, array $data, ?int $timeout = null): array
    {
        try {
            $r = Http::timeout($timeout ?? $this->timeout)->acceptJson()->post($this->url . $endpoint, $data);
            return $this->wrap($r);
        } catch (Throwable $e) {
            return $this->fail($e);
        }
    }

    private function wrap($response): array
    {
        $json = $response->json() ?? [];
        if ($response->successful()) {
            return ['ok' => true] + $json;
        }
        return ['ok' => false, 'error' => $json['detail'] ?? ('AI Engine returned HTTP ' . $response->status())];
    }

    private function fail(Throwable $e): array
    {
        Log::warning('AI Engine call failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'The AI Engine is not available right now. You can continue without AI and retry later.'];
    }
}
