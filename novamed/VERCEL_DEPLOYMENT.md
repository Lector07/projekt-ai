# Wdrożenie NovaMed na Vercel

## ⚠️ Ważne uwagi

Vercel **nie jest optymalnym rozwiązaniem** dla pełnowartościowej aplikacji Laravel z następujących powodów:

1. **Brak trwałego filesystemu** - pliki przesłane przez użytkowników zostaną usunięte po każdym deploymencie
2. **Brak obsługi SQLite** - wymaga zewnętrznej bazy danych (MySQL/PostgreSQL)
3. **Limit czasu wykonania** - 10 sekund dla darmowych kont, 60 sekund dla Pro
4. **Brak wsparcia dla kolejek** - queue workers nie będą działać
5. **Brak wsparcia dla cron jobs** - scheduled tasks nie będą działać
6. **Cold starts** - pierwsze żądanie może być wolne

## Zalecane alternatywy

- **Laravel Forge** + DigitalOcean/AWS/Linode
- **Laravel Vapor** (serverless na AWS)
- **Railway.app** (świetne dla Laravel)
- **Heroku** (z dodatkami dla bazy danych)
- **PlanetScale** (dla bazy danych) + Vercel (tylko dla frontend)

## Wymagania przed wdrożeniem

1. **Zewnętrzna baza danych MySQL/PostgreSQL**
   - PlanetScale (darmowy tier)
   - Supabase (darmowy tier)
   - Railway (darmowy trial)
   - Amazon RDS
   - DigitalOcean Managed Database

2. **Zewnętrzne storage dla plików**
   - AWS S3
   - DigitalOcean Spaces
   - Cloudinary

## Kroki wdrożenia

### 1. Przygotowanie projektu

```bash
# Build aplikacji Vue/Vite
npm install
npm run build

# Wygeneruj APP_KEY jeśli nie masz
php artisan key:generate --show
```

### 2. Konfiguracja Vercel (przez CLI)

```bash
# Zainstaluj Vercel CLI
npm i -g vercel

# Zaloguj się
vercel login

# Deploy
vercel
```

### 3. Konfiguracja zmiennych środowiskowych w Vercel

W panelu Vercel (Settings → Environment Variables) dodaj:

```
APP_NAME=NovaMed
APP_ENV=production
APP_KEY=base64:TWOJ_WYGENEROWANY_KLUCZ
APP_DEBUG=false
APP_URL=https://twoja-domena.vercel.app

DB_CONNECTION=mysql
DB_HOST=twoj-host-bazy-danych
DB_PORT=3306
DB_DATABASE=nazwa_bazy
DB_USERNAME=uzytkownik
DB_PASSWORD=haslo

SESSION_DRIVER=cookie
CACHE_STORE=array
QUEUE_CONNECTION=sync

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=twoj-klucz
AWS_SECRET_ACCESS_KEY=twoj-sekret
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=twoj-bucket
```

### 4. Migracje bazy danych

Musisz uruchomić migracje lokalnie lub przez terminal Vercel:

```bash
vercel env pull .env.production
php artisan migrate --force
```

### 5. Build Commands

W ustawieniach projektu Vercel:

- **Build Command**: `npm install && npm run build && composer install --no-dev --optimize-autoloader`
- **Output Directory**: `public`
- **Install Command**: `npm install`

## Ograniczenia na Vercel

### Co NIE będzie działać:

1. ❌ Przesyłanie i przechowywanie plików lokalnie
2. ❌ Queue jobs (wysyłanie emaili w tle)
3. ❌ Scheduled tasks (cron)
4. ❌ SQLite database
5. ❌ Cache (Redis/Memcached) - trzeba użyć zewnętrznego
6. ❌ Session w plikach - użyj cookie lub database
7. ❌ **Serwis raportów na localhost:8080** - musisz wdrożyć go osobno

### Co BĘDZIE działać:

1. ✅ Routing i kontrolery
2. ✅ Blade/Vue views
3. ✅ API endpoints
4. ✅ Middleware i autoryzacja
5. ✅ Eloquent ORM (z zewnętrzną bazą)
6. ✅ Static assets (CSS, JS, images)

## Rozwiązanie problemu z serwisem raportów

Twoja aplikacja używa lokalnego serwisu Java na `http://localhost:8080` do generowania raportów PDF. Na Vercel to nie będzie działać.

### Opcje:

1. **Wdróż serwis Java osobno** (np. na Railway, Heroku, AWS)
   - Zaktualizuj URL w `.env`: `REPORT_SERVICE_URL=https://twoj-serwis-java.railway.app`

2. **Użyj zewnętrznej biblioteki PHP** do generowania PDF
   - DomPDF
   - Snappy (wkhtmltopdf)
   - mPDF

3. **Wyłącz funkcję raportów na Vercel**
   - Dodaj warunek w kontrolerze sprawdzający `REPORT_SERVICE_ENABLED`

## Testowanie lokalne

Możesz przetestować konfigurację Vercel lokalnie:

```bash
vercel dev
```

## Deployment

```bash
# Development
vercel

# Production
vercel --prod
```

## Monitorowanie

- Logi: https://vercel.com/dashboard → Your Project → Logs
- Analytics: https://vercel.com/dashboard → Your Project → Analytics

## Wsparcie

Jeśli napotkasz problemy, rozważ migrację na platformę lepiej przystosowaną do Laravel.

