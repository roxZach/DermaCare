<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send all active FAQs to the Python AI Engine so the chatbot index is (re)built.
Artisan::command('chatbot:sync', function () {
    $r = \App\Http\Controllers\Admin\FaqController::pushIndex(app(\App\Services\AiService::class));
    ($r['ok'] ?? false)
        ? $this->info("Chatbot index built: {$r['count']} FAQs, engine {$r['engine']}.")
        : $this->error('AI Engine not reachable: ' . ($r['error'] ?? 'unknown error'));
})->purpose('Rebuild the FAQ chatbot index in the AI Engine');
