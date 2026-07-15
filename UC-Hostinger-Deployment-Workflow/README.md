# UC Hostinger Deployment Workflow Package

Copy the contents of this package into the root of the Unclad Collection repository.

Important files:

- `.htaccess` — required Hostinger root rewrite
- `deployment/HOSTINGER-DEPLOYMENT.md` — complete deployment guide
- `deployment/PRE-DEPLOY.ps1` — local Windows pre-deployment helper
- `deployment/POST-DEPLOY.sh` — Hostinger post-deployment helper
- `deployment/.env.staging.example` — staging environment template
- `deployment/.env.production.example` — production environment template
- `deployment/FIRST-SERVER-SETUP.md` — initial server checklist
- `deployment/DEPLOYMENT-CHECKLIST.md` — repeat release checklist
- `deployment/TROUBLESHOOTING.md` — known Hostinger fixes
- `deployment/CRON-JOBS.md` — scheduler and queue configuration
- `deployment/ROLLBACK.md` — rollback process

Do not copy either example environment file to `.env` without replacing every placeholder.
Never commit the real `.env`.
