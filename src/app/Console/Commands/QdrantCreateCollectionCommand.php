<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\QdrantService;

class QdrantCreateCollectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * php artisan qdrant:create {name?}
     */
    protected $signature = 'qdrant:create {name? : Collection nomi (ixtiyoriy)}';

    protected $description = 'Qdrant’da yangi collection yaratadi (agar mavjud bo‘lmasa)';

    public function handle(): int
    {
        $name = $this->argument('name') ?? config('services.qdrant.collection');
        $host = config('services.qdrant.host');

        if (empty($name)) {
            $this->error('❌ Collection nomi kerak! Yoki .env da QDRANT_COLLECTION qiymatini belgilang.');
            return Command::FAILURE;
        }

        $this->info("🔍 Tekshirilmoqda: {$name}");

        $collections = Http::get("{$host}/collections")->json('result.collections', []);
        $exists = collect($collections)->pluck('name')->contains($name);

        if ($exists) {
            $this->warn("⚠️  Collection [{$name}] allaqachon mavjud!");
            return Command::SUCCESS;
        }

        $service = new QdrantService($host, $name);
        $created = $service->createCollection();

        if ($created) {
            $this->info("✅ Collection [{$name}] muvaffaqiyatli yaratildi.");
        } else {
            $this->error("❌ Collection [{$name}] yaratishda xatolik yuz berdi.");
        }

        return Command::SUCCESS;
    }
}
