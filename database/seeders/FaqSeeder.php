<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/** Loads the FAQ knowledge base from database/data/faqs.json (also used by the chatbot evaluation script). */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $items = json_decode(file_get_contents(database_path('data/faqs.json')), true);
        foreach ($items as $f) {
            Faq::create([
                'category' => $f['category'],
                'question' => $f['question'],
                'paraphrases' => implode("\n", $f['paraphrases']),
                'answer' => $f['answer'],
            ]);
        }
    }
}
