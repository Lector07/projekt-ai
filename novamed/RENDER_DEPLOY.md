# Render.com Deployment Guide

## Krok 1: Przygotowanie repozytorium

1. Zatwierdź wszystkie zmiany do Git:
```bash
cd c:\Users\Softres\Documents\my-projects\projekt-ai\novamed
git add Dockerfile .dockerignore
git commit -m "Add Dockerfile and .dockerignore for Render deployment"
git push origin main
```

## Krok 2: Utwórz Web Service na Render

1. Zaloguj się na https://render.com
2. Kliknij **New +** → **Web Service**
3. Połącz repozytorium: `Lector07/projekt-ai`
4. Wybierz branch: `main`

## Krok 3: Konfiguracja Web Service

**Ustawienia podstawowe:**
- **Name:** novamed
- **Region:** Frankfurt (EU Central) lub najbliższy region
- **Branch:** main
- **Root Directory:** `novamed`
- **Runtime:** Docker
- **Instance Type:** Free (lub Starter $7/miesiąc dla lepszej wydajności)

**Zmienne środowiskowe (Environment Variables):**

Dodaj następujące zmienne w panelu Render:

```
APP_NAME=NovaMed
APP_ENV=production
APP_KEY=base64:uBja8oX8YdFH5IpZ6f7Mx1/LEwUacjsJzyQ/H321Aoo=
APP_DEBUG=false
APP_URL=https://novamed.onrender.com

DB_CONNECTION=pgsql
DB_HOST=aws-1-eu-west-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.vlegdtrqlbbirpcbhdvr
DB_PASSWORD=8IWovpU90tV42ZKh

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

CACHE_STORE=database
QUEUE_CONNECTION=sync

LOG_CHANNEL=stderr
LOG_LEVEL=error

MAIL_MAILER=log
MAIL_FROM_ADDRESS=hello@novamed.com
MAIL_FROM_NAME=NovaMed

VITE_APP_NAME=NovaMed

REPORT_SERVICE_ENABLED=false
```

## Krok 4: Deploy

1. Kliknij **Create Web Service**
2. Render automatycznie:
   - Zbuduje obraz Docker
   - Zainstaluje zależności
   - Zbuduje assets (npm run build)
   - Uruchomi migracje i seedery
   - Uruchomi serwer na porcie przypisanym przez Render

## Krok 5: Weryfikacja

Po zakończeniu deploymentu:
1. Otwórz URL aplikacji (np. `https://novamed.onrender.com`)
2. Sprawdź czy strona się ładuje
3. Sprawdź czy API działa: `https://novamed.onrender.com/api/v1/procedures`

## Krok 6: Aktualizacja APP_URL

Po pierwszym deploymencie:
1. Skopiuj URL aplikacji z Render (np. `https://novamed-xyz.onrender.com`)
2. Zaktualizuj zmienną `APP_URL` w panelu Render
3. Kliknij **Manual Deploy** → **Clear build cache & deploy**

## Troubleshooting

### Błąd "Storage not writable"
- Render automatycznie tworzy katalogi storage
- Uprawnienia są ustawiane w Dockerfile

### Błąd połączenia z bazą
- Sprawdź czy Supabase pozwala na połączenia z IP Render
- Dodaj `0.0.0.0/0` do allowed IPs w Supabase (Settings → Database → Connection Pooling)

### Błąd 500
- Sprawdź logi w Render Dashboard → Logs
- Upewnij się, że `APP_KEY` jest ustawiony

### Migracje nie działają
- Dockerfile automatycznie uruchamia migracje przy starcie
- Jeśli chcesz uruchomić ręcznie: Render Dashboard → Shell → `php artisan migrate --force`

## Koszt

- **Free Tier:** 
  - 750 godzin/miesiąc darmowo
  - Usypia po 15 min nieaktywności
  - Pierwszy request po uśpieniu ~30s

- **Starter ($7/miesiąc):**
  - Zawsze aktywna
  - 512MB RAM
  - Lepsze dla produkcji

## Następne kroki

1. Skonfiguruj Custom Domain w Render (opcjonalnie)
2. Skonfiguruj HTTPS (automatyczne przez Render)
3. Monitoruj logi i metryki w Dashboard
4. Skonfiguruj Background Workers jeśli używasz kolejek

## Linki

- Dashboard Render: https://dashboard.render.com
- Dokumentacja Render Docker: https://render.com/docs/deploy-docker
- Supabase Dashboard: https://supabase.com/dashboard

