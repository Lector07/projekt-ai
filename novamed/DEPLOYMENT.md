# NovaMed - Vercel Deployment Guide

## Overview

This guide explains how to deploy the NovaMed Laravel + Vue.js application to Vercel with Supabase PostgreSQL database.

## Prerequisites

- Vercel CLI installed: `npm install -g vercel`
- Vercel account with project created
- Supabase PostgreSQL database set up
- Node.js 20+ and npm installed

## Configuration Files

### 1. `vercel.json`

The Vercel configuration is set up to:
- Run the PHP serverless function via `vercel-php@0.7.4`
- Serve static assets from `public/` directory
- Automatically build frontend assets during deployment
- Route API requests to the PHP function
- Serve Vite-built assets from `/build/` path

**Key settings:**
- `outputDirectory`: `"public"` - Static files served from public directory
- Auto-detects framework to run `npm run vercel-build`
- Routes static assets (JS, CSS, images) with proper cache headers
- Routes `/api/*` and other paths to Laravel PHP function

### 2. `package.json`

**Build script:**
```json
"vercel-build": "vite build"
```

This script is automatically detected and run by Vercel during deployment. It:
- Builds Vue.js frontend with Vite
- Generates optimized, code-split bundles
- Creates manifest at `public/build/.vite/manifest.json`
- Outputs to `public/build/` directory

### 3. `vite.config.ts`

**Build configuration:**
- Uses `laravel-vite-plugin` for Laravel integration
- Output directory: `public/build`
- Manifest enabled for production asset loading
- Manual chunk splitting for better caching:
  - **vendor**: Core Vue libraries (vue, vue-router, @vueuse/core)
  - **ui-lib**: UI component libraries (radix-vue, reka-ui)
  - **primevue**: PrimeVue components
  - **charts**: Chart.js library
  - **utils**: Utility libraries (axios, vuedraggable, lucide-vue-next)

## Deployment Process

### 1. Deploy to Vercel

```bash
cd novamed
vercel
```

For production deployment:
```bash
vercel --prod
```

### 2. Set Environment Variables

Configure these secrets in Vercel (via CLI or Dashboard):

**Database (Supabase PostgreSQL):**
```bash
vercel env add DB_CONNECTION pgsql production
vercel env add DB_HOST aws-1-eu-west-1.pooler.supabase.com production
vercel env add DB_PORT 5432 production
vercel env add DB_DATABASE postgres production
vercel env add DB_USERNAME postgres.vlegdtrqlbbirpcbhdvr production
vercel env add DB_PASSWORD YOUR_PASSWORD production
```

**Laravel:**
```bash
vercel env add APP_KEY YOUR_APP_KEY production
vercel env add APP_ENV production production
vercel env add APP_DEBUG false production
vercel env add APP_URL https://your-deployment-url.vercel.app production
```

### 3. Build Process on Vercel

When you deploy, Vercel automatically:

1. **Installs dependencies:**
   ```bash
   npm ci
   composer install --no-dev --optimize-autoloader
   ```

2. **Builds frontend assets:**
   ```bash
   npm run vercel-build  # Runs: vite build
   ```

3. **Creates serverless function:**
   - Bundles `api/index.php` with PHP runtime
   - Includes Laravel framework and application code

4. **Deploys static assets:**
   - Serves files from `public/` directory
   - Assets in `public/build/` served with cache headers

## Asset Serving

### Static Asset URLs

Built assets are served from `/build/` path:
- JavaScript: `/build/assets/app-[hash].js`
- CSS: `/build/assets/app-[hash].css`
- Fonts: `/build/assets/primeicons-[hash].woff2`

### Manifest File

Laravel uses the Vite manifest to resolve asset paths:
- Location: `public/build/.vite/manifest.json`
- Generated during build process
- Contains mappings of source files to hashed output files

## Routing

Vercel routes are configured in this order:

1. **Static build assets:** `/build/*` → `public/build/*`
2. **Static files:** `/*.{js,css,svg,png,jpg,...}` → `public/*`
3. **API routes:** `/api/*` → Laravel PHP function
4. **All other routes:** `/*` → Laravel PHP function (for Vue Router)

## Bundle Optimization

The build is optimized with manual chunk splitting:

- **Main app chunk:** ~1.1 MB (294 kB gzipped)
- **Vendor chunk:** ~147 kB (58 kB gzipped)
- **UI library chunk:** ~304 kB (86 kB gzipped)
- **Charts chunk:** ~207 kB (71 kB gzipped)
- **Utils chunk:** ~1,061 kB (225 kB gzipped)

Benefits:
- Better browser caching (vendor code cached separately)
- Faster initial page load
- Smaller chunks = faster downloads

## Troubleshooting

### "Vite manifest not found" error

**Cause:** Build didn't run or failed during deployment

**Solution:**
1. Check Vercel build logs for errors
2. Verify `vercel-build` script runs successfully locally:
   ```bash
   npm run vercel-build
   ```
3. Ensure `public/build/` is not in `.vercelignore`

### Static assets return 404

**Cause:** Routing configuration issue

**Solution:**
- Verify `outputDirectory: "public"` in `vercel.json`
- Check that build assets exist in `public/build/`
- Review route order in `vercel.json`

### Build fails on Vercel

**Cause:** Missing dependencies or Node version mismatch

**Solution:**
1. Add `.nvmrc` or `.node-version` file if needed
2. Check that all dependencies are in `package.json`
3. Review Vercel build logs for specific errors

## Database Migration

To run migrations on Supabase:

1. **Set up local environment:**
   ```bash
   cp .env.production.example .env
   # Edit .env with your Supabase credentials
   ```

2. **Run migrations:**
   ```bash
   php artisan migrate --force
   ```

3. **Seed database (optional):**
   ```bash
   php artisan db:seed --force
   ```

## Cache Configuration

Laravel caching is configured for serverless:
- **Config cache:** `/tmp/config.php`
- **Routes cache:** `/tmp/routes.php`
- **Views cache:** `/tmp/views`
- **Cache driver:** `array` (in-memory for serverless)
- **Session driver:** `cookie` (stateless for serverless)

## Performance Tips

1. **Enable route caching** (if not using closures):
   ```bash
   php artisan route:cache
   ```

2. **Use CDN for static assets** (optional):
   - Configure `ASSET_URL` environment variable
   - Use Vercel's built-in CDN

3. **Monitor build times:**
   - Vite build typically takes 10-15 seconds
   - Composer install ~30-60 seconds
   - Total deployment ~2-3 minutes

## Support

- Vercel Documentation: https://vercel.com/docs
- Laravel Vite Plugin: https://laravel.com/docs/vite
- Supabase: https://supabase.com/docs

## Version Information

- Laravel: 12.10.2
- Vue: 3.5.21
- Vite: 7.1.7
- Node: 20.x
- PHP: 8.3+
- laravel-vite-plugin: 2.0.1
- vercel-php: 0.7.4
