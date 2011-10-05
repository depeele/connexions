#!/bin/sh
  DB='connexions'
USER='connexions'
PASS=$1

echo "Dropping connexions database..."
mysqladmin --user=root --password="$1" drop "${DB}"

echo "Creating connexions database..."
mysqladmin --user=root --password="$1" create "${DB}"

echo "Grating access..."
mysql --user=root --password="$1" mysql -e "GRANT ALL ON ${DB}.* TO '${USER}'@'localhost';"

echo "Reloading backup..."
mysql --user=${USER} ${DB} < backup.sql
