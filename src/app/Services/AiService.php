<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiService
{
    public function embed(string $text): array
    {
        $res = Http::post(env('OLLAMA_URL', 'http://ollama:11434').'/api/embeddings', [
            'model' => env('EMBED_MODEL', 'nomic-embed-text'),
            'prompt' => $text,
        ])->throw()->json();

        return $res['embedding'] ?? [];
    }

    public function generate(string $prompt, ?string $system = null, ?string $model = null): string
    {
        $payload = [
            'model' => $model ?: env('DEFAULT_MODEL', 'gpt-oss:20b'),
            'prompt' => $prompt,
            'stream' => false,
        ];
        if ($system) $payload['system'] = $system;

        $res = Http::timeout(600)->post(env('OLLAMA_URL', 'http://ollama:11434').'/api/generate', $payload)
            ->throw()
            ->json();

        return $res['response'] ?? '';
    }
}
