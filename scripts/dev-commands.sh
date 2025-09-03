#!/bin/bash

# Shise-Cal Development Helper Commands
# Collection of useful commands for development

COMPOSE_FILE="docker-compose.dev.yml"

case "$1" in
    "start")
        echo "🚀 Starting development environment..."
        docker-compose -f $COMPOSE_FILE up -d
        ;;
    "stop")
        echo "🛑 Stopping development environment..."
        docker-compose -f $COMPOSE_FILE down
        ;;
    "restart")
        echo "🔄 Restarting development environment..."
        docker-compose -f $COMPOSE_FILE restart
        ;;
    "logs")
        echo "📋 Showing logs..."
        docker-compose -f $COMPOSE_FILE logs -f ${2:-}
        ;;
    "shell")
        echo "🐚 Opening shell in app container..."
        docker-compose -f $COMPOSE_FILE exec app bash
        ;;
    "test")
        echo "🧪 Running tests..."
        docker-compose -f $COMPOSE_FILE exec app php artisan test ${2:-}
        ;;
    "migrate")
        echo "🗄️  Running migrations..."
        docker-compose -f $COMPOSE_FILE exec app php artisan migrate ${2:-}
        ;;
    "seed")
        echo "🌱 Seeding database..."
        docker-compose -f $COMPOSE_FILE exec app php artisan db:seed ${2:-}
        ;;
    "fresh")
        echo "🔄 Fresh migration with seeding..."
        docker-compose -f $COMPOSE_FILE exec app php artisan migrate:fresh --seed
        ;;
    "composer")
        echo "📦 Running composer..."
        docker-compose -f $COMPOSE_FILE exec app composer ${2:-install}
        ;;
    "npm")
        echo "📦 Running npm..."
        docker-compose -f $COMPOSE_FILE exec node npm ${2:-install}
        ;;
    "artisan")
        echo "🎨 Running artisan command..."
        docker-compose -f $COMPOSE_FILE exec app php artisan ${2:-list}
        ;;
    "clear")
        echo "🧹 Clearing caches..."
        docker-compose -f $COMPOSE_FILE exec app php artisan optimize:clear
        ;;
    "build")
        echo "🔨 Building assets..."
        docker-compose -f $COMPOSE_FILE exec node npm run build
        ;;
    "dev")
        echo "🎨 Starting asset development server..."
        docker-compose -f $COMPOSE_FILE exec node npm run dev
        ;;
    "status")
        echo "📊 Container status..."
        docker-compose -f $COMPOSE_FILE ps
        ;;
    "clean")
        echo "🧹 Cleaning up containers and volumes..."
        docker-compose -f $COMPOSE_FILE down -v --remove-orphans
        docker system prune -f
        ;;
    *)
        echo "🛠️  Shise-Cal Development Commands"
        echo ""
        echo "Usage: $0 <command> [options]"
        echo ""
        echo "Available commands:"
        echo "  start          Start development environment"
        echo "  stop           Stop development environment"
        echo "  restart        Restart development environment"
        echo "  logs [service] Show logs (optionally for specific service)"
        echo "  shell          Open bash shell in app container"
        echo "  test [options] Run PHPUnit tests"
        echo "  migrate [opts] Run database migrations"
        echo "  seed [class]   Seed database"
        echo "  fresh          Fresh migration with seeding"
        echo "  composer [cmd] Run composer command"
        echo "  npm [cmd]      Run npm command"
        echo "  artisan [cmd]  Run artisan command"
        echo "  clear          Clear all caches"
        echo "  build          Build frontend assets"
        echo "  dev            Start asset development server"
        echo "  status         Show container status"
        echo "  clean          Clean up containers and volumes"
        echo ""
        echo "Examples:"
        echo "  $0 start"
        echo "  $0 test --coverage"
        echo "  $0 logs app"
        echo "  $0 artisan make:controller TestController"
        ;;
esac