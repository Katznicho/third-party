# Deployment Guide

This project uses GitHub Actions to automatically deploy to Hostinger when code is pushed to specific branches.

## Workflows

### 1. `deploy.yml`
- **Triggers**: Pushes to `main` branch
- **Purpose**: Production deployment

### 2. `deploy-demo.yml`
- **Triggers**: Pushes to `demo` branch
- **Purpose**: Demo/staging deployment

## Setup Instructions

### 1. Configure GitHub Secrets

Go to your GitHub repository → Settings → Secrets and variables → Actions, and add the following secrets:

- **FTP_USERNAME**: Your Hostinger FTP username (e.g., `u242329769`)
- **FTP_PASSWORD**: Your Hostinger FTP password
- **FTP_SERVER**: Your Hostinger FTP server hostname (e.g., `fr-int-web1427.hosting.controlpanel.net`)

### 2. Deployment Path

The workflows are configured to deploy to:
```
/home/u242329769/domains/vendor.kashtre.com/public_html
```

If your deployment path is different, update the `DEPLOY_DIR` variable in both workflow files.

### 3. What Gets Deployed

The workflow will:
1. ✅ Checkout the code
2. ✅ Install Composer dependencies
3. ✅ Copy files to the server (excluding `.env`, `.htaccess`, `node_modules`, `vendor`, etc.)
4. ✅ Set proper permissions on `storage` and `bootstrap/cache`
5. ✅ Create storage symlink
6. ✅ Install Composer dependencies on the server
7. ✅ Run database migrations
8. ✅ Clear and cache Laravel configurations

### 4. Excluded Files

The following files/directories are excluded from deployment:
- `.env` (environment configuration)
- `.htaccess` (server configuration)
- `.git` (version control)
- `.github` (GitHub Actions)
- `node_modules` (npm dependencies)
- `vendor` (Composer dependencies - installed on server)
- `storage/logs/*` (log files)
- `storage/framework/cache/*` (cache files)
- `storage/framework/sessions/*` (session files)
- `storage/framework/views/*` (compiled views)

### 5. Server Requirements

Make sure your Hostinger server has:
- PHP 8.2 or higher
- Composer installed
- Required PHP extensions: `mbstring`, `xml`, `bcmath`, `ctype`, `json`, `tokenizer`, `curl`, `pdo`, `pdo_mysql`
- SSH access enabled
- Proper permissions for the deployment directory

### 6. First-Time Setup

Before the first deployment, make sure to:

1. **Create `.env` file on the server** with your production database credentials and other environment variables
2. **Set up the database** on Hostinger
3. **Configure the web server** to point to the `public` directory

### 7. Manual Deployment

If you need to deploy manually, you can:

```bash
# SSH into your server
ssh -p 65002 u242329769@fr-int-web1427.hosting.controlpanel.net

# Navigate to the project directory
cd /home/u242329769/domains/vendor.kashtre.com/public_html

# Pull latest changes (if using git on server)
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:clear
```

## Troubleshooting

### Deployment Fails

1. Check GitHub Actions logs for specific error messages
2. Verify all secrets are correctly set
3. Ensure SSH access is working: `ssh -p 65002 username@server`
4. Check server disk space: `df -h`
5. Verify PHP version: `php -v`
6. Check Composer is installed: `composer --version`

### Migration Errors

If migrations fail:
- Check database connection in `.env`
- Verify database user has proper permissions
- Review migration files for syntax errors
- Check Laravel logs: `storage/logs/laravel.log`

### Permission Errors

If you see permission errors:
```bash
chmod -R 755 storage bootstrap/cache
chown -R u242329769:u242329769 storage bootstrap/cache
```

## Notes

- The workflow uses `rsync` to efficiently sync files
- Composer dependencies are installed on the server to avoid uploading the `vendor` directory
- Migrations run automatically with `--force` flag (no confirmation prompt)
- All caches are cleared and rebuilt after deployment
