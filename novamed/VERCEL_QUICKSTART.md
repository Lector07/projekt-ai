# Szybki start: Wdrożenie na Vercel

## Krok 1: Przygotuj zewnętrzną bazę danych

Wybierz jeden z dostawców:

### PlanetScale (Zalecane - darmowy tier)
1. Zarejestruj się na https://planetscale.com
2. Utwórz nową bazę danych
3. Pobierz credentials (host, username, password)

### Supabase
1. Zarejestruj się na https://supabase.com
2. Utwórz projekt
3. W Settings → Database znajdź connection string

## Krok 2: Zainstaluj Vercel CLI

```bash
npm install -g vercel
```

## Krok 3: Połącz projekt z Vercel

```bash
vercel login
vercel
```

Podczas pierwszego deployu odpowiedz na pytania:
- Set up and deploy? **Y**
- Which scope? (wybierz swoje konto)
- Link to existing project? **N**
- What's your project's name? **novamed**
- In which directory is your code located? **./**

## Krok 4: Skonfiguruj zmienne środowiskowe

W panelu Vercel (https://vercel.com/dashboard) lub przez CLI:

```bash
vercel env add APP_KEY
# Wklej wygenerowany klucz: php artisan key:generate --show

vercel env add APP_URL
# https://twoja-domena.vercel.app

vercel env add DB_CONNECTION
# mysql

vercel env add DB_HOST
# twoj-host.region.psdb.cloud

vercel env add DB_DATABASE
# nazwa_bazy

vercel env add DB_USERNAME
# username

vercel env add DB_PASSWORD
# password

vercel env add REPORT_SERVICE_ENABLED
# false
```

Lub ustaw wszystkie naraz w panelu: Settings → Environment Variables

## Krok 5: Uruchom migracje

```bash
# Pobierz zmienne środowiskowe
vercel env pull .env.production

# Uruchom migracje
php artisan migrate --force --env=production
```

## Krok 6: Deploy na produkcję

```bash
vercel --prod
```

## Gotowe! 🎉

Twoja aplikacja jest teraz dostępna pod adresem Vercel.

## Rozwiązywanie problemów

### Błąd: "Connection refused"
- Sprawdź czy dane do bazy są poprawne
- Sprawdź czy IP Vercel jest na whiteliście (dla PlanetScale nie jest wymagane)

### Błąd: "No application encryption key"
- Dodaj APP_KEY w zmiennych środowiskowych
- Użyj: `php artisan key:generate --show`

### Raporty PDF nie działają
- To normalne - serwis Java nie jest dostępny na Vercel
- Opcje:
  1. Wdróż serwis Java osobno (Railway, Heroku)
  2. Użyj biblioteki PHP (DomPDF, mPDF)
  3. Zostaw wyłączone (REPORT_SERVICE_ENABLED=false)

## Monitorowanie

- **Logi**: https://vercel.com/dashboard → Your Project → Deployments → View Function Logs
- **Analytics**: https://vercel.com/dashboard → Your Project → Analytics
- **Errors**: Sprawdź logi Laravel w storage/logs (tylko podczas buildu)

## Aktualizacja aplikacji

```bash
git push origin main
# Vercel automatycznie wykryje i wdroży zmiany
```

Lub ręcznie:

```bash
vercel --prod
```

