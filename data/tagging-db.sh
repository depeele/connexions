#!/bin/sh
echo "Dropping connexions database..."
mysqladmin -u root -p drop connexions

echo "Creating connexions database..."
mysqladmin -u root -p create connexions

echo "Grating access..."
mysql -u root -p mysql -e "GRANT ALL ON connexions.* TO 'connexions'@'localhost';"

echo "Creating tag tables..."
mysql -u connexions connexions < tagging.sql
