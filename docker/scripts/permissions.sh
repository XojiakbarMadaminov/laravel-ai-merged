#!/bin/bash

# Change ownership, excluding .git folder
find . -not -path "./.git*" -exec chown gitlab-runner:www-data {} \;

# Change permissions for directories, excluding .git
find . -type d -not -path "./.git*" -exec chmod 755 {} \;

# Change permissions for files, excluding .git
find . -type f -not -path "./.git*" -exec chmod 664 {} \;

# Special permissions for Laravel directories
chmod -R 775 storage bootstrap/cache
chmod 775 artisan

# Execute CMD as the final process
exec "$@"