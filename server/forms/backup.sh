#!/bin/bash
set -euo pipefail
DIR=/home/112books/webforms
mkdir -p "$DIR/backups"
STAMP=$(date +%Y%m%d-%H%M)

sqlite3 "$DIR/data/forms.sqlite" ".backup '$DIR/backups/forms-$STAMP.sqlite'"
chmod 600 "$DIR/backups/forms-$STAMP.sqlite"

if [ -d "$DIR/submissions" ]; then
    tar -czf "$DIR/backups/submissions-$STAMP.tar.gz" -C "$DIR" submissions
    chmod 600 "$DIR/backups/submissions-$STAMP.tar.gz"
    find "$DIR/backups" -name 'submissions-*.tar.gz' -type f -mtime +30 -delete
    if compgen -G "$DIR/backups/submissions-*.tar.gz" > /dev/null; then
        ls -1t "$DIR/backups"/submissions-*.tar.gz | tail -n +60 | xargs -r rm -f
    fi
fi

# retencio: 30 dies i maxim 60 copies
find "$DIR/backups" -name 'forms-*.sqlite' -type f -mtime +30 -delete
if compgen -G "$DIR/backups/forms-*.sqlite" > /dev/null; then
    ls -1t "$DIR/backups"/forms-*.sqlite | tail -n +60 | xargs -r rm -f
fi

echo "backup_ok $STAMP"
