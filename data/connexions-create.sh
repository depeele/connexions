#!/bin/sh
     DB='connexions'
   USER='connexions'
   PASS=$1
CHARSET='utf8'
COLLATE='utf8_roman_ci'

echo "Dropping ${DB} database..."
mysql --user=root --password="${PASS}" -e \
	"DROP DATABASE ${DB};"

echo "Creating ${DB} database (charset ${CHARSET}, collation ${COLLATE})..."
mysql --user=root --password="${PASS}" -e \
	"CREATE DATABASE ${DB} CHARACTER SET ${CHARSET} COLLATE ${COLLATE};"

echo "Grating access..."
mysql --user=root --password="${PASS}" -e \
	"GRANT ALL ON ${DB}.* TO '${USER}'@'localhost';"

echo "Creating tag tables..."
mysql --user=${USER} ${DB} < connexions.sql
