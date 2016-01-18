#!/usr/bin/env sh

ROOT_PASS=
HOST=127.0.0.1

MYSQL_DB=whatsapp2
MYSQL_USER=whatsapp
MYSQL_PASS=whatsapp_pass

# Create Database
mysql -h "$HOST" -uroot -e "CREATE DATABASE IF NOT EXISTS $MYSQL_DB"

# Create (unsafe) HelpSpot user, who can connect remotely
mysql -h "$HOST" -uroot -e "GRANT ALL PRIVILEGES ON $MYSQL_DB.* to '$MYSQL_USER'@'%' IDENTIFIED BY '$MYSQL_PASS';"
