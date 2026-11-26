#!/usr/bin/env bash
# ==============================================================================
# KNOTELLE Production Automated Database Backup & Maintenance Script
# ==============================================================================
# Recommended Cron Schedule (daily at 02:00 AM):
# 0 2 * * * /home/ubuntu/knotelle/docker/backup.sh >> /home/ubuntu/knotelle/backups/backup.log 2>&1
# ==============================================================================

set -euo pipefail

BACKUP_DIR="/home/ubuntu/knotelle/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/knotelle_db_${TIMESTAMP}.sql.gz"
RETENTION_DAYS=14

mkdir -p "${BACKUP_DIR}"

echo "[$(date -u +"%Y-%m-%dT%H:%M:%SZ")] Starting automated database backup..."

# 1. Perform compressed MariaDB dump
if docker exec knotelle-db mysqldump \
    --single-transaction \
    --quick \
    --default-character-set=utf8mb4 \
    -u knottele_user \
    -pKnotelleLive2026! \
    knottele | gzip > "${BACKUP_FILE}"; then
    
    FILESIZE=$(ls -lh "${BACKUP_FILE}" | awk '{print $5}')
    echo "[$(date -u +"%Y-%m-%dT%H:%M:%SZ")] SUCCESS: Backup created at ${BACKUP_FILE} (${FILESIZE})"
else
    echo "[$(date -u +"%Y-%m-%dT%H:%M:%SZ")] ERROR: mysqldump failed!"
    exit 1
fi

# 2. Prune old database backups older than RETENTION_DAYS
echo "[$(date -u +"%Y-%m-%dT%H:%M:%SZ")] Cleaning backups older than ${RETENTION_DAYS} days..."
find "${BACKUP_DIR}" -type f -name "knotelle_db_*.sql.gz" -mtime +${RETENTION_DAYS} -delete

# 3. Prune expired Laravel database sessions (> 72 hours)
echo "[$(date -u +"%Y-%m-%dT%H:%M:%SZ")] Pruning expired sessions from MariaDB..."
docker exec knotelle-app php artisan session:prune --hours=72 || true

echo "[$(date -u +"%Y-%m-%dT%H:%M:%SZ")] Maintenance tasks completed successfully."
