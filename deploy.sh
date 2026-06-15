#!/usr/bin/env bash
# deploy.sh — Deploy CREA a Hostinger
# Uso: bash deploy.sh
# Desde la raíz del proyecto: C:/xampp/htdocs/CREA (ejecutar en Git Bash o WSL)

set -e

SSH_KEY="${HOME}/.ssh/id_deploy"
SSH_HOST="u489236361@195.35.38.222"
REMOTE_DIR='~/domains/crea.iyemyucatan.com/public_html'

# Array para manejar correctamente rutas con espacios
SSH_OPTS=(-i "${SSH_KEY}" -p 65002)

echo "=== [1/5] Build del frontend ==="
npm run build

echo "=== [2/5] Verificaciones previas ==="
if [ -f "database/database.sqlite" ]; then
    echo "ADVERTENCIA: database/database.sqlite existe. Asegúrate de que no se incluya en el deploy."
fi

echo "=== [3/5] Empaquetando y subiendo al servidor (sin node_modules, vendor, .env, .git) ==="
tar czf - \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.env' \
    --exclude='.env.production' \
    --exclude='database/database.sqlite' \
    --exclude='storage/logs/*' \
    app bootstrap config database lang public resources routes storage/app/public \
    storage/framework artisan composer.json composer.lock \
    CHANGELOG_AUDITORIA.md PENDIENTES_AUTH.md | \
    ssh "${SSH_OPTS[@]}" $SSH_HOST "cd $REMOTE_DIR && tar xzf -"

echo "=== [4/5] Instalando dependencias en servidor ==="
ssh "${SSH_OPTS[@]}" $SSH_HOST "cd $REMOTE_DIR && composer install --optimize-autoloader --no-dev --no-interaction"

echo "=== [5/5] Migraciones, cachés y build assets ==="
ssh "${SSH_OPTS[@]}" $SSH_HOST "cd $REMOTE_DIR && \
    php artisan migrate --force && \
    php artisan db:seed --class=ActualizarModalidadesSeeder --force && \
    php artisan db:seed --class=RoleAndPermissionSeeder --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan event:cache && \
    rm -rf build && cp -r public/build/ build/ && \
    echo '✅ Deploy completado exitosamente'"

echo ""
echo "=== POST-DEPLOY: Pasos manuales requeridos ==="
echo "1. Subir .env.production al servidor como .env:"
echo "   scp -i \"${SSH_KEY}\" -P 65002 .env.production $SSH_HOST:$REMOTE_DIR/.env"
echo "   (Editar PASSWORD_CORREO_AQUI antes de subir)"
echo ""
echo "2. Configurar DocumentRoot del virtualhost crea.iyemyucatan.com"
echo "   Debe apuntar a: $REMOTE_DIR/public"
echo ""
echo "3. Configurar cron en Hostinger Panel (Cron Jobs):"
echo "   * * * * * cd $REMOTE_DIR && php artisan schedule:run >> /dev/null 2>&1"
echo ""
echo "4. Storage symlink (solo primera vez, si no existe):"
echo "   ln -sfn $REMOTE_DIR/storage/app/public $REMOTE_DIR/public/storage"
echo "   NOTA: NO usar 'php artisan storage:link' - exec() deshabilitado en Hostinger"
echo "   NOTA: build/ se copia automáticamente en paso 5 (NO es symlink, Apache no los sigue)"
echo ""
echo "5. Configurar SSL en Hostinger Panel para crea.iyemyucatan.com"
echo "6. Apuntar DNS: A record crea.iyemyucatan.com -> 195.35.38.222"
