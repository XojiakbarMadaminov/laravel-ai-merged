<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class QdrantService
{
    public function __construct(
        protected string $host,
        protected string $collection
    ) {}

    public static function make(): static
    {
        return new static(
            config('services.qdrant.host'),
            config('services.qdrant.collection')
        );
    }

    public function createCollection(): bool
    {
        $response = Http::put("{$this->host}/collections/{$this->collection}", [
            'vectors' => ['size' => 768, 'distance' => 'Cosine'],
        ]);
        return $response->successful();
    }

    public function upsert(array $points): bool
    {
        $response = Http::put("{$this->host}/collections/{$this->collection}/points?wait=true", [
            'points' => $points,
        ]);

        return $response->successful();
    }

    public function search(array $vector, int $limit = 5, array $filter = []): array
    {
        $body = [
            'vector' => $vector,
            'limit'  => $limit,
            'with_payload' => true,
        ];
        if ($filter) $body['filter'] = $filter;

        $response = Http::post("{$this->host}/collections/{$this->collection}/points/search", $body);

        return $response->json('result', []);
    }
}
