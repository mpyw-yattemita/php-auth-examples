#!/bin/bash

cd "$(dirname "$0")"

trap 'killall php-fpm' INT EXIT TERM HUP
php-fpm &
sleep 0.1

h2o -c <(cat <<'EOD'
listen: 8080
file.index: ['index.php', 'index.html']
file.custom-handler:
  extension: .php
  fastcgi.connect:
    host: 127.0.0.1
    port: 9000
    type: tcp
hosts:
  "localhost:8080":
    paths:
      /:
        file.dir: .
EOD)
