# Use official FrankenPHP image from Docker Hub
FROM dunglas/frankenphp:latest

# Install system dependencies for MySQL extensions
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    default-libmysqlclient-dev \
    && docker-php-ext-install pdo_mysql mysqli \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy application files
COPY . /app

# Copy Caddyfile
COPY Caddyfile /etc/caddy/Caddyfile

# Copy startup script
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Set working directory
WORKDIR /app

# Expose port (Railway will set PORT env var)
EXPOSE 8080

# Use startup script to handle PORT dynamically
CMD ["/start.sh"]

