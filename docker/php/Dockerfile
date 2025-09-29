# Use official PHP FPM image
FROM php:8.3-fpm

# Set working directory
WORKDIR /var/www/html/src

# Arguments defined in docker-compose.yml
ARG user
ARG uid
ARG group
ARG gid

# Add PHP extension installer
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install necessary packages in a single RUN command to reduce layers
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libpq-dev \
    libhiredis-dev \
    libzip-dev \
    postgresql-client \
    zip \
    unzip \
    git \
    cron \
    supervisor \
    curl \
    && chmod +x /usr/local/bin/install-php-extensions && sync \
    && install-php-extensions gd zip pdo_pgsql redis pcntl intl exif \
    && curl -sL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy composer from official image
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Copy php-fpm configuration
#COPY /docker/php/fpm/www.conf /usr/local/etc/php-fpm.d

# Configure PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY /docker/php/php.ini "$PHP_INI_DIR/conf.d/custom.ini"

# Copy and set up crontab
COPY /docker/crontab/crontab /etc/cron.d/app-cron
RUN chmod 0644 /etc/cron.d/app-cron && crontab /etc/cron.d/app-cron

# Create non-root user and set permissions in a single step to reduce layers
RUN useradd -G www-data,root -u $uid -d /home/$user $user && \
    mkdir -p /home/$user/.composer /var/run/cron /var/log/supervisor && \
    chown -R $user:$user /home/$user /var/run/cron /var/log/supervisor && \
    chmod -R 775 /home/$user /var/run/cron /var/log/supervisor && \
    chmod gu+s /usr/sbin/cron && \
    chmod -R 775 /var/run && \
    chown -R $user:$user /var/run

# Install Supervisor
RUN apt-get update && apt-get install -y supervisor && \
    mkdir -p /var/log/supervisor && \
    chown -R $user:$user /var/log/supervisor && \
    chmod -R 775 /var/log/supervisor

# Copy Supervisor configuration
COPY /docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY /docker/supervisor/queue-worker.conf /etc/supervisor/conf.d/queue-worker.conf

# Set up user permissions
COPY /docker/scripts/permissions.sh /usr/local/bin/permissions.sh
RUN chmod +x /usr/local/bin/permissions.sh
USER root
ENTRYPOINT ["/usr/local/bin/permissions.sh"]
# Copy application code
COPY . .

# Switch to non-root user
USER $user

EXPOSE 9000

# Use ENTRYPOINT for proper init system behavior
ENTRYPOINT ["bash", "-c"]
CMD ["cron -f & supervisord -c /etc/supervisor/supervisord.conf & php-fpm"]