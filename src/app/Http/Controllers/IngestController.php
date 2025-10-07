<?php

namespace App\Http\Controllers;

use App\Services\PostgresRagService;
use App\Services\QdrantRagService;
use Illuminate\Http\Request;

class IngestController extends Controller
{
    public function __construct(private QdrantRagService $rag)
    {
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'text' => 'required|string',
            'project_key' => 'nullable|string',
            'meta' => 'nullable|array',
        ]);

        $project = $data['project_key'] ?? 'default';
        $meta = $data['meta'] ?? [];

        $chunks = $this->rag->ingestText($data['text'], $project, $meta);


        return response()->json([
            'ok' => true,
            'chunks' => $chunks,
            'project_key' => $project,
        ]);
    }
}
