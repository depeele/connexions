#!/bin/sh
  DB='connexions'
USER='connexions'
DATE=`date +%Y.%m.%d`
FILE="backups/${DATE}-${DB}.sql"


if [ ! -z "$1" ]; then
	PASS="--password='${1}'"
fi

echo "Backing up '${DB}' to '${FILE}':"
mysqldump --user=${USER} ${PASS} --add-drop-table --extended-insert ${DB} > "${FILE}"
