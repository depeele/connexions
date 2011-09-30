#!/bin/sh
     DB='connexions'
   USER='connexions'
   PASS=$1
charSet='utf8'
collate='utf8_roman_ci'

echo "Dropping ${DB} database..."
#mysqladmin --user=root --password="${PASS}" drop ${DB}
mysql --user=root --password="${PASS}" -e \
	"DROP DATABASE ${DB};"

echo "Creating ${DB} database (charset ${charSet}, collation ${collate})..."
#mysqladmin --user=root --password="${PASS}" create ${DB}
mysql --user=root --password="${PASS}" -e \
	"CREATE DATABASE ${DB} CHARACTER SET ${charSet} COLLATE ${collate};"

echo "Grating access..."
mysql --user=root --password="${PASS}" -e \
	"GRANT ALL ON ${DB}.* TO '${USER}'@'localhost';"

echo "Creating tag tables..."
mysql -u ${USER} ${DB} < connexions.sql
