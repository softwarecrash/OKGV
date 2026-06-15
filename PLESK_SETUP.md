# OKGV auf Plesk/Webhosting bereitstellen

Diese Kurzreferenz beschreibt die manuelle Demo-Installation auf einem
Plesk-Webhosting mit Git, Laravel Toolkit, PHP Composer und MariaDB.

## Pfade

Das Repository liegt eine Ebene über dem öffentlichen Webordner:

```text
Anwendung / Repository: /demo.okgv.de
Document Root:          /demo.okgv.de/public
```

Nur `public` darf öffentlich erreichbar sein. `.env`, `storage`, `vendor`,
`app`, `config` und Backups dürfen nicht im Document Root liegen.

## Wichtige `.env` Werte

In Plesk eine `.env` in `/demo.okgv.de/.env` anlegen oder bearbeiten:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://demo.okgv.de
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=DATENBANKNAME
DB_USERNAME=DATENBANKBENUTZER
DB_PASSWORD=DATENBANKPASSWORT

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
SESSION_SECURE_COOKIE=true

OKGV_ADMIN_EMAIL=admin@demo.okgv.de
OKGV_ADMIN_NAME="OKGV Administrator"
OKGV_ADMIN_PASSWORD=Demo1234!
OKGV_DEMO_PASSWORD=Demo1234!
```

`APP_KEY` nur einmal erzeugen und danach nie wieder ändern.

## Erstinstallation

Nach dem Git-Deployment und Composer-Install in Plesk:

```bash
php artisan key:generate
php artisan migrate --force
php artisan okgv:create-admin
php artisan okgv:demo-seed --force
php artisan optimize
```

Wenn `APP_KEY` bereits gesetzt ist, `key:generate` nicht erneut ausführen.

## Administrator anlegen

Wenn die `OKGV_ADMIN_*` Werte in `.env` gesetzt sind, reicht:

```bash
php artisan okgv:create-admin
```

Der Befehl legt den Administrator an oder aktualisiert ein bestehendes Konto
mit derselben E-Mail-Adresse.

Alternativ ohne `.env`:

```bash
php artisan okgv:create-admin admin@demo.okgv.de --name=Admin --password=Demo1234!
```

## Demo-Daten

Demo-Daten anlegen:

```bash
php artisan okgv:demo-seed --force
```

Demo-Daten entfernen:

```bash
php artisan okgv:demo-purge --force
```

Demo-Daten verwenden `OKGV_DEMO_PASSWORD` als Passwort für die Demo-Konten.

## Updates

Empfohlene Reihenfolge bei einem Update:

```bash
php artisan down
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --ignore-scripts
npm run build
php artisan migrate --force
php artisan optimize
php artisan up
```

Wenn Plesk Node.js nicht passend bereitstellt, `npm ci` und `npm run build`
lokal ausführen und anschließend `public/build` per Dateimanager hochladen.

## Typische Fehler

### `vendor/autoload.php` fehlt

Composer-Abhängigkeiten wurden noch nicht installiert:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

### `No application encryption key has been specified`

`APP_KEY` fehlt:

```bash
php artisan key:generate
php artisan optimize:clear
php artisan optimize
```

### Tabellen fehlen

Migrationen ausführen:

```bash
php artisan migrate --force
```

### Plesk-Defaultseite erscheint

Der Document Root zeigt nicht auf `public` oder eine alte `index.html` liegt
im Document Root. Prüfen:

```text
/demo.okgv.de/public/index.php
```

Der Domain-Dokumentenstamm muss `/demo.okgv.de/public` sein.
