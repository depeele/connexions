#!/bin/sh
PASS=$1

echo "Dropping connexionsTest database..."
mysqladmin --user=root --password="$1" drop connexionsTest

echo "Creating connexionsTest database..."
mysqladmin --user=root --password="$1" create connexionsTest

echo "Grating access..."
mysql --user=root --password="$1" mysql -e "GRANT ALL ON connexionsTest.* TO 'connexions'@'localhost';"

echo "Creating tag tables..."
mysql -u connexions connexionsTest < connexions.sql
