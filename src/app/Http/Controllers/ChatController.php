<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use App\Services\PostgresRagService;
use App\Services\QdrantRagService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private QdrantRagService $rag, private AiService $ai)
    {
    }

    public function chat(Request $request)
    {
        $data = $request->validate([
            'prompt' => 'required|string',
            'project_key' => 'nullable|string',
            'instruction' => 'nullable|string',
            'use_rag' => 'nullable',
            'k' => 'nullable|integer',
            'model' => 'nullable|string',
        ]);

        $user = trim($data['prompt']);
        $project = $data['project_key'] ?? 'default';
        $instruction = $data['instruction'] ?? null;
        $useRag = strtolower(strval($data['use_rag'] ?? 'auto'));
        $k = intval($data['k'] ?? 5);
        $model = $data['model'] ?? env('DEFAULT_MODEL', 'gpt-oss:20b');

        $system = $instruction ?: (env('DEFAULT_SYSTEM', 'Siz yordamchisiz.') . ' Javob tili: ' . env('DEFAULT_LANG', 'uz') . '.');

        $finalPrompt = $user;
        if (in_array($useRag, ['true', '1', 'yes', 'auto'])) {
            $ctxs = $this->rag->search($user, $project, $k);
            $ctxText = implode("\n\n", array_map(fn($c) => $c['content'], $ctxs));

            $finalPrompt = <<<PROMPT
Sizga berilgan KONTEKSTDAN FOYDALANIB javob bering.

Kontekst (faqat shu ma'lumotlardan xulosa qiling):
{$ctxText}

Savol:
{$user}

Qoidalar:
- Javobingizni kontekstdan oling.
- Siz chat assistantsiz. Faqat odamlarning savollariga javob bering.
- Sen kimsan deb savol berilsa "O'zing kimsan?" deb javob bering.
- Agar kontekstdan topilmasa bunday ma'lumotga ega emasligingizni ayting.
- Formal javob bering.
PROMPT;
        }

        $answer = $this->ai->generate($finalPrompt, $system, $model);

        return response()->json([
            'ok' => true,
            'answer' => $answer,
            'project_key' => $project,
        ]);
    }
}
