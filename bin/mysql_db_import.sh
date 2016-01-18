#!/usr/bin/env sh

ROOT_PASS=
HOST=127.0.0.1

cat $1 | mysql -h "$HOST" -uroot $2
