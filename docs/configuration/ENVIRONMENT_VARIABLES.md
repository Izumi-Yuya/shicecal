# Environment Variables Configuration

This document describes all environment variables used in the Shise-Cal application.

## Application Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_NAME` | "Shise-Cal" | Application name |
| `APP_ENV` | local | Application environment (local, testing, production) |
| `APP_KEY` | - | Application encryption key (generate with `php artisan key:generate`) |
| `APP_DEBUG` | true | Enable debug mode (set to false in production) |
| `APP_URL` | http://localhost | Application base URL |

## Logging Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `LOG_CHANNEL` | stack | Default logging channel |
| `LOG_DEPRECATIONS_CHANNEL` | null | Channel for deprecation warnings |
| `LOG_LEVEL` | debug | Minimum log level |

## Database Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_CONNECTION` | mysql | Database driver |
| `DB_HOST` | 127.0.0.1 | Database host |
| `DB_PORT` | 3306 | Database port |
| `DB_DATABASE` | shisecal | Database name |
| `DB_USERNAME` | root | Database username |
| `DB_PASSWORD` | - | Database password |

## Cache and Session Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `CACHE_DRIVER` | file | Cache driver (file, redis, database) |
| `SESSION_DRIVER` | file | Session driver |
| `SESSION_LIFETIME` | 120 | Session lifetime in minutes |
| `QUEUE_CONNECTION` | sync | Queue driver |

## File Storage Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `FILESYSTEM_DISK` | local | Default filesystem disk (local, s3) |

## Mail Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `MAIL_MAILER` | smtp | Mail driver |
| `MAIL_HOST` | mailpit | SMTP host |
| `MAIL_PORT` | 1025 | SMTP port |
| `MAIL_USERNAME` | null | SMTP username |
| `MAIL_PASSWORD` | null | SMTP password |
| `MAIL_ENCRYPTION` | null | SMTP encryption (tls, ssl) |
| `MAIL_FROM_ADDRESS` | noreply@shisecal.local | Default from address |
| `MAIL_FROM_NAME` | "${APP_NAME}" | Default from name |

## AWS Configuration (Optional)

These variables are only needed if using AWS services (S3 for file storage, SES for email).

| Variable | Default | Description |
|----------|---------|-------------|
| `AWS_ACCESS_KEY_ID` | - | AWS access key |
| `AWS_SECRET_ACCESS_KEY` | - | AWS secret key |
| `AWS_DEFAULT_REGION` | us-east-1 | AWS region |
| `AWS_BUCKET` | - | S3 bucket name |
| `AWS_ENDPOINT` | - | Custom S3 endpoint (for MinIO, etc.) |
| `AWS_USE_PATH_STYLE_ENDPOINT` | false | Use path-style S3 URLs |

## Facility System Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `APPROVAL_SYSTEM_ENABLED` | true | Enable approval workflow |
| `FILE_UPLOAD_MAX_SIZE` | 10240 | Max file upload size in KB |
| `ALLOWED_IP_ADDRESSES` | - | Comma-separated list of allowed IPs |

## Environment-Specific Examples

### Local Development
```env
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
FILESYSTEM_DISK=local
MAIL_MAILER=log
```

### Testing
```env
APP_ENV=testing
APP_DEBUG=true
DB_CONNECTION=sqlite
DB_DATABASE=database/testing.sqlite
FILESYSTEM_DISK=local
MAIL_MAILER=array
```

### Production
```env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
FILESYSTEM_DISK=s3
MAIL_MAILER=ses
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## Security Notes

1. **Never commit `.env` files** to version control
2. **Generate a unique `APP_KEY`** for each environment
3. **Use strong database passwords** in production
4. **Set `APP_DEBUG=false`** in production
5. **Restrict `ALLOWED_IP_ADDRESSES`** if needed
6. **Use HTTPS** in production (`APP_URL` should use https://)