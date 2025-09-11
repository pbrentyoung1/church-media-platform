# Environment Setup

## Local
- Laravel Valet
- DBngin (Postgres)
- Mailpit
- Redis

## Staging / Production
- Hosting.com reseller account
- Separate Postgres DBs
- Cron: * * * * * php artisan schedule:run
- Secrets via .env.staging / .env.production
