.PHONY: build up down restart logs clean init

build:
	docker-compose build --no-cache

up:
	docker-compose up -d --remove-orphans

down:
	docker-compose down --volumes --remove-orphans

restart: down up

logs:
	docker-compose logs -f

clean:
	@echo "🧹 Cleaning up..."
	@docker stop $$(docker ps -aq) 2>/dev/null || true
	@docker rm $$(docker ps -aq) 2>/dev/null || true
	@docker volume rm $$(docker volume ls -q) 2>/dev/null || true
	@docker network prune -f
	@docker system prune -f
	@echo "✨ Clean complete"

init:
	@echo "🚀 Initializing project..."
	@mkdir -p public/assets/{css,js,images}
	@mkdir -p src/{config,controllers,models,views}
	@echo "<?php phpinfo(); ?>" > public/index.php
	@chmod -R 755 public
	@chmod 644 public/index.php
	@echo "RewriteEngine On" > public/.htaccess
	@chmod 644 public/.htaccess
	@make build
	@make up
	@echo "⏳ Waiting for services to start..."
	@sleep 5
	@echo "✅ Done! Access the app at http://localhost:8080"
	@echo "📋 Container status:"
	@docker-compose ps