<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RagService
{
    public function __construct(private AiService $ai) {}

    public function splitChunks(string $text, int $chunk = 800, int $overlap = 120): array
    {
        $words = preg_split('/\s+/', trim($text));
        if (!$words || count($words) === 0) return [];
        $out = [];
        $i = 0;
        $step = max(1, $chunk - $overlap);
        while ($i < count($words)) {
            $out[] = implode(' ', array_slice($words, $i, $chunk));
            $i += $step;
        }
        return $out;
    }

    public function ingestText(string $content, string $projectKey = 'default', array $meta = []): int
    {
        $chunks = $this->splitChunks($content);
        if (empty($chunks)) return 0;

        foreach ($chunks as $ch) {
            $v = $this->ai->embed($ch);
            if (empty($v)) continue;
            $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);
            $vec = '[' . implode(',', array_map(fn($x) => number_format((float)$x, 6, '.', ''), $v)) . ']';

            DB::statement(
                "INSERT INTO documents (project_key, content, meta, embedding) VALUES (?, ?, ?::jsonb, ?::vector)",
                [$projectKey, $ch, $metaJson, $vec]
            );


        }
        return count($chunks);
    }

    public function search(string $query, string $projectKey = 'default', int $k = 5): array
    {
        $qv = $this->ai->embed($query);
        if (empty($qv)) return [];

        $k = max(1, (int)$k);
        $vec = '[' . implode(',', array_map(fn($x) => number_format((float)$x, 6, '.', ''), $qv)) . ']';

        $rows = DB::select("
        SELECT content, meta
        FROM documents
        WHERE project_key = ?
        ORDER BY embedding <=> ?::vector
        LIMIT {$k}
    ", [$projectKey, $vec]);

        return array_map(function ($row) {
            return [
                'content' => $row->content,
                'meta' => is_string($row->meta) ? json_decode($row->meta, true) : $row->meta,
            ];
        }, $rows);
    }

}
