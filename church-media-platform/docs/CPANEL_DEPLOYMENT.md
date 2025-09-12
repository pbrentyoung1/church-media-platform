# cPanel Git Repository Configuration

## Current Active Configuration

### Admin Interface Deployment
- **Repository Path:** `/admin/current`
- **Repository Name:** `church-media-platform`
- **Clone URL:** `https://github.com/pbrentyoung1/church-media-platform.git`
- **Branch to Deploy:** `main`
- **Document Root:** Already configured to `/admin/current/public/`
- **Live URL:** https://admin.tech.forworship.org

### Deployment Requirements Met
✅ `.cpanel.yml` file created with Laravel deployment process
✅ `package.json` updated with Vue 3 and Bootstrap dependencies  
✅ `vite.config.js` configured for Vue 3 support
✅ `.env.production` template created
✅ Repository made public for cPanel access

### Next Steps After Deployment
1. Configure PostgreSQL database in cPanel
2. Update deployed `.env` file with real database credentials
3. Test admin interface functionality
4. Set up staging environment at `/staging/current`

## Repository Status
- **Current Branch:** staging (local development)
- **Production Branch:** main (for admin.tech.forworship.org)
- **All configuration files committed and pushed**

---
**Use Repository Path:** `/admin/current` in cPanel Git Version Control
