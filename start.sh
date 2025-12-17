#!/bin/bash
# Read PORT from environment (Railway sets this)
PORT=${PORT:-8080}

# Update Caddyfile with the correct port
sed -i "s/:8080/:${PORT}/g" /etc/caddy/Caddyfile

# Start FrankenPHP
exec frankenphp run --config /etc/caddy/Caddyfile

