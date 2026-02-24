#!/bin/sh
sudo apt install apache2 libapache2-mod-php;

sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
-keyout server.key \
-out server.crt
