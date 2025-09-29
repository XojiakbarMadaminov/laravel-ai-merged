<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RagService;

class DebugController extends Controller
{
    public function __construct(private RagService $rag) {}

    public function search(Request $request)
    {
        $data = $request->validate([
            'query' => 'required|string',
            'project_key' => 'nullable|string',
            'k' => 'nullable|integer',
        ]);

        $project = $data['project_key'] ?? 'default';
        $k = intval($data['k'] ?? 5);

        $ctxs = $this->rag->search($data['query'], $project, $k);

        return response()->json([
            'k' => $k,
            'project_key' => $project,
            'contexts' => $ctxs,
        ]);
    }
}
