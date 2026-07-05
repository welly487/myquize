#!/bin/bash
set -e

# Render 會透過 PORT 環境變數告訴容器要監聽哪個 port，預設值只是保險
: "${PORT:=80}"

sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf
sed -i "s/80/${PORT}/g" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
