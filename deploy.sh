#!/bin/bash

# Script de déploiement avec gestion de version
# Usage: ./deploy.sh [version] [environment]

set -e

# Configuration
VERSION=${1:-"auto"}
ENVIRONMENT=${2:-"production"}
APP_NAME="COLOC-CMU"

echo "🚀 Déploiement de $APP_NAME"
echo "Environment: $ENVIRONMENT"
echo "Version: $VERSION"

# Fonction pour incrémenter automatiquement la version
auto_increment_version() {
    if [ -f "VERSION" ]; then
        CURRENT_VERSION=$(cat VERSION)
        echo "Version actuelle: $CURRENT_VERSION"
        
        # Incrémenter la version patch
        IFS='.' read -ra VERSION_PARTS <<< "$CURRENT_VERSION"
        MAJOR=${VERSION_PARTS[0]}
        MINOR=${VERSION_PARTS[1]}
        PATCH=${VERSION_PARTS[2]}
        
        NEW_PATCH=$((PATCH + 1))
        NEW_VERSION="$MAJOR.$MINOR.$NEW_PATCH"
        
        echo "Nouvelle version: $NEW_VERSION"
        echo "$NEW_VERSION" > VERSION
        VERSION=$NEW_VERSION
    else
        echo "1.0.0" > VERSION
        VERSION="1.0.0"
    fi
}

# Gestion de la version
if [ "$VERSION" = "auto" ]; then
    auto_increment_version
else
    echo "$VERSION" > VERSION
fi

echo "📝 Version définie: $VERSION"

# Mise à jour du git commit dans le fichier de version
if [ -d ".git" ]; then
    GIT_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
    echo "Git commit: $GIT_COMMIT"
fi

# Build de l'application
echo "🔨 Build de l'application..."
npm run build

# Optimisation Laravel
echo "⚡ Optimisation Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Affichage des informations de déploiement
echo ""
echo "✅ Déploiement terminé!"
echo "📊 Informations de version:"
echo "   - Version: $VERSION"
echo "   - Environment: $ENVIRONMENT"
echo "   - Git Commit: ${GIT_COMMIT:-unknown}"
echo "   - Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""
echo "🌐 L'application est prête avec la version $VERSION"
echo "   La version sera visible sur la page de login"
