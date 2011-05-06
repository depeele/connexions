#!/bin/sh
  DB='connexions'
PASS=$1

echo "Dropping ${DB} database..."
mysqladmin --user=root --password="$1" drop ${DB}

echo "Creating ${DB} database..."
mysqladmin --user=root --password="$1" create ${DB}

echo "Grating access..."
mysql --user=root --password="$1" mysql -e "GRANT ALL ON ${DB}.* TO '${DB}'@'localhost';"

echo "Creating tag tables..."
mysql -u ${DB} ${DB} < connexions.sql
