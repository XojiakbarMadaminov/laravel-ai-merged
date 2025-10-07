# 🧠 Laravel AI RAG — Qdrant + Ollama

End-to-end **RAG (Retrieval-Augmented Generation)** stack built with **Laravel**, **Ollama** (local LLM + embeddings), **Qdrant** (vector DB) and **PostgreSQL** (relational data). This repo is tuned for local development using Docker.

---

## ✨ Features

- Chunking + embeddings with **Ollama** (`nomic-embed-text`)
- Vector search with **Qdrant** (Cosine, HNSW)
- Generation with **Ollama** chat model (e.g. `gpt-oss:20b`)
- Clean Laravel services (`QdrantService`, `QdrantRagService`) and controllers (`IngestController`, `ChatController`)
- Artisan utility: **`php artisan qdrant:create {name?}`** (creates collection iff missing)
- Dockerized: Nginx + PHP-FPM + Postgres + Redis + Qdrant + Ollama
- Persisted Ollama models via Docker **volume**

---

## 🧱 Stack

| Layer      | Tech / Service              | Notes |
|------------|-----------------------------|-------|
| API        | Laravel 12 (PHP 8.3)        | Business logic + RAG orchestration |
| LLM        | **Ollama**                  | Embeddings + chat (`11434/tcp`) |
| Vector DB  | **Qdrant**                  | Collections, vector search (`6333/tcp`) |
| Relational | PostgreSQL (pgvector image) | Used as **regular Postgres** here |
| Web        | Nginx                       | Reverse proxy (`:98`/`:498`) |
| Cache      | Redis (optional)            | Queues / caching |

---

## 🗂️ Project layout

```
.
├─ docker-compose.yml
├─ docker/
│  ├─ nginx/
│  │  └─ templates/           # Nginx templates
│  └─ logs/                   # Logs mapped from containers
├─ src/                       # Laravel app root
│  ├─ app/Services/
│  │  ├─ QdrantService.php
│  │  └─ QdrantRagService.php
│  ├─ app/Http/Controllers/
│  │  ├─ IngestController.php
│  │  └─ ChatController.php
│  ├─ app/Console/Commands/
│  │  └─ QdrantCreateCollectionCommand.php
│  └─ routes/
│     └─ api.php              # Your API routes
└─ README.md
```

---

## 🔧 Prerequisites

- Docker & Docker Compose
- ~12–16GB free disk (models & vector index can be large)
- Open ports: `98`, `498`, `11434`, `6333`, DB port if exposed

---

## ⚙️ Environment (.env)

Copy and adjust `src/.env`:

```env
# App
APP_NAME="Laravel AI RAG"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:98

# Database (inside Docker network)
DB_CONNECTION=pgsql
DB_HOST=laravel-ai-new-db
DB_PORT=5432
DB_DATABASE=ai_rag
DB_USERNAME=postgres
DB_PASSWORD=postgres

# Qdrant — use service name from docker-compose for in-network calls
QDRANT_HOST=http://qdrant:6333
QDRANT_COLLECTION=documents

# Ollama — use service name
OLLAMA_URL=http://ollama-new:11434
DEFAULT_MODEL=gpt-oss:20b
EMBED_MODEL=nomic-embed-text
```

> **Note**: Inside containers use `http://qdrant:6333` and `http://ollama-new:11434`. From the host shell you can also reach Qdrant on `http://localhost:6333` if ports are mapped.

---

## 🐳 Docker up

```bash
# From repo root
docker compose up -d --build

# Migrations (if any)
docker compose exec php-app php artisan migrate

# Pull required models into the Ollama container
docker compose exec ollama-new ollama pull nomic-embed-text
docker compose exec ollama-new ollama pull gpt-oss:20b
```
---

## 🧠 Qdrant collections

Create a collection via Artisan (uses config name by default, or pass one):
```bash
# default from QDRANT_COLLECTION
docker compose exec php-app php artisan qdrant:create

# or explicit name
docker compose exec php-app php artisan qdrant:create documents
```

Check from **host**:
```bash
curl http://localhost:6333/collections
```

Check from **php-app container**:
```bash
docker compose exec php-app curl http://qdrant:6333
```

---

## 📥 Ingest & 🔎 Search (examples)

### Ingest with service (Tinker)
```bash
docker compose exec php-app php artisan tinker
```

```php
use App\Services\QdrantRagService;
app(QdrantRagService::class)->ingestText(
  "Bu test matni. Laravel + Qdrant + Ollama ishlamoqda.",
  projectKey: 'default',
  meta: ['source' => 'tinker']
);
```

### Search with service (Tinker)
```php
use App\Services\AiService;
use App\Services\QdrantService;

$ai = app(AiService::class);
$qv = $ai->embed("Laravel nima?");

$qdrant = App\Services\QdrantService::make();
$hits = $qdrant->search($qv, 3); // with_payload enabled in service
print_r($hits);
```

> **ID format**: Points must use **UUID** or unsigned integer IDs. Ingest uses `(string) Str::uuid()` to comply.

---

## 🌐 API (typical)

Your routes may vary. Common patterns:

- `POST /api/ingest` — body: `{ text | file }` → chunks, embed, upsert to Qdrant
- `POST /api/chat` — body: `{ query }` → embed, retrieve, generate response

See `src/routes/api.php` and controllers for the exact endpoints.

---

## 🩺 Health / Diagnostics

**Qdrant**
```bash
# Host
curl http://localhost:6333
curl http://localhost:6333/collections

# From php-app
docker compose exec php-app curl http://qdrant:6333
docker compose exec php-app curl http://qdrant:6333/collections
```

**Ollama**
```bash
docker compose exec ollama-new curl http://localhost:11434/api/tags
docker compose exec ollama-new ollama list
```

**Laravel**
```bash
docker compose logs php-app --tail=200
docker compose logs nginx-server --tail=200
```

---

## 🧰 Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `model "nomic-embed-text" not found` | Model not pulled | `docker compose exec ollama-new ollama pull nomic-embed-text` |
| `content` missing in search results | Qdrant search didn’t request payload | Ensure `with_payload => true` (or `['content','meta']`) in `QdrantService::search()` |
| `Format error ... not a valid point ID` | Using `uniqid()` | Use `(string) Str::uuid()` for IDs |
| `504 Gateway Time-out` | Nginx/PHP timeouts or long model calls | Increase Nginx `fastcgi_read_timeout`, PHP `max_execution_time`, add `Http::timeout(60)`, reduce chunk size |
| `curl http://localhost:6333` works but inside php-app fails | Using `localhost` inside container | Use `http://qdrant:6333` inside Docker network |
| Models disappeared after restart | Volume not mounted or removed | Ensure volume mapping; avoid `down -v`; verify `docker volume ls` |

Timeout tuning (examples):
- **Nginx**: `fastcgi_read_timeout 300; fastcgi_connect_timeout 300; fastcgi_send_timeout 300;`
- **PHP**: `max_execution_time=300`, `request_terminate_timeout=300`
- **Laravel HTTP**: `Http::timeout(60)` for Ollama/Qdrant calls

---

## 🔒 Notes

- Postgres image is `pgvector/pgvector:pg17` but used as **normal Postgres** in this project. You don’t need pgvector features because Qdrant stores vectors.
- Ollama models are **large**; ensure volume persistence. Remove specific models with `ollama rm <name>` if you need space.

---

## 📝 License

MIT — free to use for personal & commercial projects.