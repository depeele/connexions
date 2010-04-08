#!/bin/sh
PASS=$1

echo "Dropping connexions database..."
mysqladmin --user=root --password="$1" drop connexions

echo "Creating connexions database..."
mysqladmin --user=root --password="$1" create connexions

echo "Grating access..."
mysql --user=root --password="$1" mysql -e "GRANT ALL ON connexions.* TO 'connexions'@'localhost';"

echo "Creating tag tables..."
mysql -u connexions connexions < tagging.sql
