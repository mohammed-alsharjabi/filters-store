#!/usr/bin/env bash

set -euo pipefail

SOURCE_DIR="${1:?مرر مسار نسخة Git أولًا}"
APP_ROOT="${2:?مرر مسار التطبيق الدائم ثانيًا}"
PUBLIC_TARGET="${3:?مرر مسار public_html المؤكد ثالثًا}"
PUBLIC_TARGET="${PUBLIC_TARGET%/}"
EXPECTED_PUBLIC_TARGET="${EXPECTED_PUBLIC_TARGET:?اضبط EXPECTED_PUBLIC_TARGET على مسار public_html المؤكد قبل النشر}"
EXPECTED_PUBLIC_TARGET="${EXPECTED_PUBLIC_TARGET%/}"
RELEASE_ID="$(date +%Y%m%d%H%M%S)"
RELEASE_DIR="${APP_ROOT}/releases/${RELEASE_ID}"
SHARED_DIR="${APP_ROOT}/shared"
PREBUILT_DIR="${4:-${SHARED_DIR}/build}"

if [[ ! -f "${SOURCE_DIR}/artisan" || ! -d "${SOURCE_DIR}/.git" ]]; then
    echo "المسار الأول ليس نسخة Git صالحة لمشروع Laravel." >&2
    exit 1
fi

if [[ "${PUBLIC_TARGET}" != "${EXPECTED_PUBLIC_TARGET}" ]]; then
    echo "أُوقف النشر: المسار المستهدف لا يطابق EXPECTED_PUBLIC_TARGET." >&2
    exit 1
fi

if [[ "${APP_ROOT}" == "/" || "${APP_ROOT}" == "${HOME}" || "${PUBLIC_TARGET}" == "/" || "${PUBLIC_TARGET}" == "${HOME}" ]]; then
    echo "أُوقف النشر: مسار واسع أو غير آمن." >&2
    exit 1
fi

mkdir -p "${APP_ROOT}/releases" "${SHARED_DIR}"
if [[ ! -f "${SHARED_DIR}/.env" ]]; then
    echo "أنشئ ${SHARED_DIR}/.env من .env.production.example قبل النشر." >&2
    exit 1
fi

mkdir -p "${RELEASE_DIR}"
git -C "${SOURCE_DIR}" archive --format=tar HEAD | tar -xf - -C "${RELEASE_DIR}"

if [[ ! -d "${SHARED_DIR}/storage" ]]; then
    mv "${RELEASE_DIR}/storage" "${SHARED_DIR}/storage"
else
    mv "${RELEASE_DIR}/storage" "${RELEASE_DIR}/storage-template"
fi
ln -s "${SHARED_DIR}/storage" "${RELEASE_DIR}/storage"
ln -s "${SHARED_DIR}/.env" "${RELEASE_DIR}/.env"

# Merge versioned source covers and public brand assets into persistent storage.
# Generated galleries remain untouched while updated static WebP files overwrite
# their previous version byte-for-byte.
if [[ -d "${RELEASE_DIR}/storage-template/app" ]]; then
    for RELATIVE_STORAGE_PATH in assets/services public/about public/brand public/services; do
        TEMPLATE_PATH="${RELEASE_DIR}/storage-template/app/${RELATIVE_STORAGE_PATH}"
        if [[ -d "${TEMPLATE_PATH}" ]]; then
            mkdir -p "${SHARED_DIR}/storage/app/${RELATIVE_STORAGE_PATH}"
            cp -Rp "${TEMPLATE_PATH}/." "${SHARED_DIR}/storage/app/${RELATIVE_STORAGE_PATH}/"
        fi
    done
fi

if [[ -f "${SHARED_DIR}/database/database.sqlite" ]]; then
    cp -p "${SHARED_DIR}/database/database.sqlite" "${SHARED_DIR}/database/database.sqlite.backup-${RELEASE_ID}"
fi

composer install --working-dir="${RELEASE_DIR}" --no-dev --no-interaction --prefer-dist --optimize-autoloader
if command -v npm >/dev/null 2>&1; then
    npm --prefix "${RELEASE_DIR}" ci
    npm --prefix "${RELEASE_DIR}" run build
elif [[ -f "${PREBUILT_DIR}/manifest.json" ]]; then
    mkdir -p "${RELEASE_DIR}/public/build"
    cp -R "${PREBUILT_DIR}/." "${RELEASE_DIR}/public/build/"
else
    echo "أُوقف النشر: Node.js غير متاح ولم يُعثر على build جاهز في ${PREBUILT_DIR}." >&2
    exit 1
fi

php "${RELEASE_DIR}/artisan" migrate --force
if [[ -d "${SHARED_DIR}/storage/app/assets" ]]; then
    php "${RELEASE_DIR}/artisan" services --source="${SHARED_DIR}/storage/app/assets" --sync --publish
fi
if php -r 'exit(function_exists("exec") ? 0 : 1);'; then
    php "${RELEASE_DIR}/artisan" storage:link
else
    STORAGE_TARGET="${RELEASE_DIR}/public/storage"
    if [[ -e "${STORAGE_TARGET}" || -L "${STORAGE_TARGET}" ]]; then
        mv "${STORAGE_TARGET}" "${RELEASE_DIR}/public/storage.prelink-${RELEASE_ID}"
    fi
    ln -s "${SHARED_DIR}/storage/app/public" "${STORAGE_TARGET}"
fi
php "${RELEASE_DIR}/artisan" optimize
php "${RELEASE_DIR}/artisan" queue:restart

mkdir -p "$(dirname "${PUBLIC_TARGET}")"
if [[ -e "${PUBLIC_TARGET}" || -L "${PUBLIC_TARGET}" ]]; then
    BACKUP_TARGET="${PUBLIC_TARGET}.backup-${RELEASE_ID}"
    mv "${PUBLIC_TARGET}" "${BACKUP_TARGET}"
    echo "نُقلت النسخة السابقة إلى ${BACKUP_TARGET}"
fi

ln -s "${RELEASE_DIR}/public" "${PUBLIC_TARGET}"
ln -sfn "${RELEASE_DIR}" "${APP_ROOT}/current"

echo "اكتمل النشر في ${PUBLIC_TARGET} من الإصدار ${RELEASE_ID}."
