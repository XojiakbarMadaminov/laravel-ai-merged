<?php

namespace App\Services;

use Illuminate\Support\Str;

class QdrantRagService
{
    public function __construct(private AiService $ai, private QdrantService $qdrant) {}

    public function ingestText(string $content, string $projectKey = 'default', array $meta = []): int
    {
        $chunks = $this->splitChunks($content);
        $points = [];

        foreach ($chunks as $ch) {
            $v = $this->ai->embed($ch);
            if (empty($v)) continue;

            $points[] = [
                'id' => (string) Str::uuid(),
                'vector' => $v,
                'payload' => [
                    'project_key' => $projectKey,
                    'content' => $ch,
                    'meta' => $meta,
                ],
            ];
        }

        return $this->qdrant->upsert($points) ? count($points) : 0;
    }

    public function search(string $query, string $projectKey = 'default', int $k = 5): array
    {
        $qv = $this->ai->embed($query);
        if (empty($qv)) return [];

        $results = $this->qdrant->search($qv, $k, [
            'must' => [
                [
                    'key' => 'project_key',
                    'match' => ['value' => $projectKey],
                ]
            ]
        ]);

        return collect($results)->map(fn($r) => [
            'score' => $r['score'] ?? null,
            'content' => $r['payload']['content'] ?? '',
            'meta' => $r['payload']['meta'] ?? [],
        ])->toArray();
    }

    public function splitChunks(string $text, int $chunk = 800, int $overlap = 120): array
    {
        $words = preg_split('/\s+/', trim($text));
        if (!$words) return [];
        $out = [];
        $i = 0;
        $step = max(1, $chunk - $overlap);
        while ($i < count($words)) {
            $out[] = implode(' ', array_slice($words, $i, $chunk));
            $i += $step;
        }
        return $out;
    }
}
