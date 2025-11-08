# Pliki dodane dla Vercel

## Utworzone pliki:

### 1. `vercel.json` ⭐
Główny plik konfiguracyjny Vercel z:
- Konfiguracją buildów
- Routingiem dla Laravel
- Ustawieniami środowiska produkcyjnego
- Optymalizacją cache dla assets

### 2. `api/index.php`
Punkt wejścia dla Vercel - przekierowuje żądania do Laravel `public/index.php`

### 3. `.vercelignore`
Lista plików wykluczonych z deploymentu (node_modules, vendor, testy, itp.)

### 4. `.env.production.example`
Przykładowy plik zmiennych środowiskowych dla produkcji Vercel z:
- Konfiguracją bazy danych
- Wyłączonym debug mode
- Ustawieniami cache i session
- **Wyłączonym serwisem raportów** (REPORT_SERVICE_ENABLED=false)

### 5. `build.sh`
Skrypt build dla Vercel (opcjonalny)

### 6. `VERCEL_DEPLOYMENT.md` 📖
Szczegółowa dokumentacja zawierająca:
- Ważne ostrzeżenia o limitacjach Vercel dla Laravel
- Zalecane alternatywy
- Wymagania przed wdrożeniem
- Instrukcje krok po kroku
- Lista tego co działa i co nie działa
- Rozwiązanie problemu z serwisem raportów

### 7. `VERCEL_QUICKSTART.md` 🚀
Szybki przewodnik wdrożenia z:
- Krokami konfiguracji bazy danych
- Komendami Vercel CLI
- Konfiguracją zmiennych środowiskowych
- Rozwiązywaniem problemów

## Zmodyfikowane pliki:

### 1. `app/Http/Controllers/Api/V1/Admin/AdminAppointmentController.php`
- ✅ Dodano sprawdzanie czy serwis raportów jest włączony
- ✅ Pobieranie URL serwisu z konfiguracji
- ✅ Graceful handling gdy serwis jest niedostępny

### 2. `config/services.php`
- ✅ Dodano konfigurację `report` z opcjami:
  - `enabled` - włącz/wyłącz serwis
  - `url` - adres URL serwisu

### 3. `package.json`
- ✅ Dodano skrypt `build:vercel` dla buildu na Vercel

### 4. `.env.example`
- ✅ Dodano zmienne:
  - `REPORT_SERVICE_ENABLED`
  - `REPORT_SERVICE_URL`

## ⚠️ WAŻNE UWAGI

### Serwis raportów PDF
Twoja aplikacja używa zewnętrznego serwisu Java na `localhost:8080` do generowania raportów. 
**To nie będzie działać na Vercel!**

#### Rozwiązania:
1. **Wdróż serwis Java osobno** (Railway, Heroku, AWS) i ustaw URL w `.env`
2. **Użyj biblioteki PHP** do PDF (DomPDF, mPDF, Snappy)
3. **Zostaw wyłączone** na Vercel (ustaw `REPORT_SERVICE_ENABLED=false`)

### Baza danych
- SQLite nie będzie działać na Vercel
- Musisz użyć zewnętrznej bazy: PlanetScale, Supabase, Railway

### Pliki
- Nie można przechowywać plików lokalnie na Vercel
- Użyj AWS S3, DigitalOcean Spaces lub Cloudinary

### Kolejki i Cron
- Queue jobs nie będą działać
- Scheduled tasks nie będą działać
- Rozważ użycie zewnętrznych serwisów (Quirrel, Inngest)

## Następne kroki:

1. **Przeczytaj** `VERCEL_QUICKSTART.md` dla szybkiego wdrożenia
2. **Przeczytaj** `VERCEL_DEPLOYMENT.md` dla pełnej dokumentacji
3. **Przygotuj** zewnętrzną bazę danych (PlanetScale, Supabase)
4. **Zdecyduj** co zrobić z serwisem raportów
5. **Uruchom** `vercel` aby wdrożyć aplikację

## Testowanie lokalne:

```bash
# Zainstaluj Vercel CLI
npm install -g vercel

# Testuj lokalnie z konfiguracją Vercel
vercel dev
```

## Deploy:

```bash
# Development
vercel

# Production
vercel --prod
```

## Wsparcie:

Jeśli aplikacja wymaga pełnej funkcjonalności Laravel (pliki, kolejki, cron, lokalne usługi),
rozważ inne platformy:
- Laravel Forge + DigitalOcean
- Laravel Vapor (AWS)
- Railway.app
- Heroku

---

Powodzenia z wdrożeniem! 🚀

