#!/bin/sh
  DB='connexions'
DATE=`date +%Y.%m.%d`
FILE="${DATE}-${DB}.sql"


if [ ! -z "$1" ]; then
	PASS="--password='${1}'"
fi

echo "Backing up '${DB}' to '${FILE}':"
mysqldump --user=connexions ${PASS} --add-drop-table --extended-insert ${DB} > "${FILE}"
