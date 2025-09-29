env-up:
	docker compose -f docker-compose.yml up -d
env-down:
	docker compose -f docker-compose.yml down
env-restart:
	docker compose -f docker-compose.yml down && docker compose -f docker-compose.yml up -d
env-build:
	docker compose -f docker-compose.yml build
env-build-no-cache:
	docker compose -f docker-compose.yml build --no-cache
env-rebuild:
	docker compose -f docker-compose.yml down && docker compose -f docker-compose.yml up -d --build

app-bash:
	docker exec -it $(shell grep ^APP_CONTAINER_NAME= .env | cut -d '=' -f2) bash

container-list:
	docker container ls --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
