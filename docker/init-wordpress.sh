#!/bin/sh
# Скачивает ядро WordPress в volume (/var/www/html), если его ещё нет.
# wp-config.php — symlink на хостовый файл из ./www (mount /var/www/wp-config-host).
set -eu

WEBROOT="${WEBROOT:-/var/www/html}"
CONFIG_SRC="${CONFIG_SRC:-/var/www/wp-config-host/wp-config.php}"
cd "$WEBROOT"

if [ -f index.php ] && [ -d wp-includes ]; then
	echo "WordPress already present in ${WEBROOT}, skip download."
else
	echo "Downloading WordPress into ${WEBROOT}..."
	tmp="$(mktemp -d)"
	trap 'rm -rf "$tmp"' EXIT

	curl -fsSL "https://wordpress.org/latest.tar.gz" -o "$tmp/wordpress.tar.gz"
	tar -xzf "$tmp/wordpress.tar.gz" -C "$tmp"
	cp -a "$tmp/wordpress"/. "$WEBROOT"/

	if id www-data >/dev/null 2>&1; then
		chown -R www-data:www-data "$WEBROOT" 2>/dev/null || true
	fi

	echo "WordPress core ready."
	trap - EXIT
	rm -rf "$tmp"
fi

# Всегда обновляем ссылку на хостовый wp-config.php (без file-mount в volume — так надёжнее на Docker Desktop/Windows)
if [ ! -f "$CONFIG_SRC" ]; then
	echo "ERROR: ${CONFIG_SRC} not found. Expected host file www/wp-config.php" >&2
	exit 1
fi
rm -f "$WEBROOT/wp-config.php"
ln -s "$CONFIG_SRC" "$WEBROOT/wp-config.php"
echo "Linked wp-config.php -> ${CONFIG_SRC}"
