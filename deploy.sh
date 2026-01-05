#!/bin/bash
# deploy.sh - Script de Deploy Automatizado para GrafiRed 3.0

set -e  # Detener en caso de error

echo "🚀 Deploy Script - GrafiRed 3.0"
echo "================================"
echo ""

# Verificar rama actual
BRANCH=$(git branch --show-current)
echo "📍 Rama actual: $BRANCH"

if [ "$BRANCH" != "main" ]; then
    echo "❌ ERROR: Debes estar en la rama 'main' para hacer deploy"
    echo "   Ejecuta: git checkout main"
    exit 1
fi

# Verificar que no haya cambios sin commit
if [ -n "$(git status --porcelain)" ]; then
    echo "❌ ERROR: Hay cambios sin commit"
    echo ""
    git status
    echo ""
    echo "Ejecuta: git add . && git commit -m 'tu mensaje'"
    exit 1
fi

# Pull últimos cambios
echo ""
echo "📥 Actualizando main desde origin..."
git pull origin main

# Obtener versión actual
CURRENT_VERSION=$(git describe --tags --abbrev=0 2>/dev/null || echo "ninguna")
echo ""
echo "📌 Versión actual: $CURRENT_VERSION"

# Solicitar nueva versión
echo ""
echo "Ingresa la nueva versión (formato: 1.0.0):"
echo "  - MAJOR.MINOR.PATCH"
echo "  - Ejemplo: 1.0.1 (hotfix), 1.1.0 (feature), 2.0.0 (breaking)"
read VERSION

if [ -z "$VERSION" ]; then
    echo "❌ ERROR: Debes ingresar una versión"
    exit 1
fi

# Validar formato de versión
if ! [[ $VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "❌ ERROR: Formato de versión inválido (debe ser X.Y.Z)"
    exit 1
fi

# Solicitar mensaje del release
echo ""
echo "Ingresa un mensaje breve del release:"
read RELEASE_MESSAGE

if [ -z "$RELEASE_MESSAGE" ]; then
    RELEASE_MESSAGE="Release v$VERSION"
fi

# Mostrar resumen
echo ""
echo "================================"
echo "📋 RESUMEN DEL DEPLOY"
echo "================================"
echo "Versión actual: $CURRENT_VERSION"
echo "Nueva versión:  v$VERSION"
echo "Mensaje:        $RELEASE_MESSAGE"
echo "Rama:           $BRANCH"
echo ""

# Confirmar
echo "¿Continuar con el deploy? (y/n)"
read CONFIRM

if [ "$CONFIRM" != "y" ] && [ "$CONFIRM" != "Y" ]; then
    echo "❌ Deploy cancelado"
    exit 0
fi

echo ""
echo "⚙️  Preparando deploy..."

# Actualizar VERSION file
echo "$VERSION" > VERSION
git add VERSION
echo "✅ VERSION file actualizado"

# Actualizar composer.json
if grep -q '"version"' composer.json; then
    sed -i "s/\"version\": \".*\"/\"version\": \"$VERSION\"/" composer.json
else
    # Agregar version si no existe
    sed -i '/"type": "project",/a\    "version": "'$VERSION'",' composer.json
fi
git add composer.json
echo "✅ composer.json actualizado"

# Commit de versión
git commit -m "chore: Bump version to v$VERSION"
echo "✅ Commit de versión creado"

# Crear tag anotado
git tag -a "v$VERSION" -m "$RELEASE_MESSAGE"
echo "✅ Tag v$VERSION creado"

# Push a origin
echo ""
echo "📤 Subiendo cambios a GitHub..."
git push origin main
git push origin "v$VERSION"

echo ""
echo "================================"
echo "✅ DEPLOY COMPLETADO"
echo "================================"
echo "🏷️  Tag:     v$VERSION"
echo "📝 Mensaje:  $RELEASE_MESSAGE"
echo "🚂 Railway desplegará automáticamente en 1-2 minutos"
echo ""
echo "📊 Monitorea el deploy en:"
echo "   https://railway.app/dashboard"
echo ""
echo "🔗 Verifica después del deploy:"
echo "   - App funcionando correctamente"
echo "   - Migraciones ejecutadas"
echo "   - Logs sin errores"
echo ""
