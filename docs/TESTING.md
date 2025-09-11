# Testing Guide

## Local
- php artisan test
- npm run build

## Staging Smoke
- Admin login + 2FA
- Playlist CRUD
- Vimeo import
- Resi live stream visible
- Roku app: splash <2s, play <3s

## Production
- Shortened smoke: login, video play, API health

## Load/Pen
- 100 tenants, 10k videos
- Tenant isolation pen-test
