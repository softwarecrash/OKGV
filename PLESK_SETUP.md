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

## Mailversand über Sendmail

Viele Webhoster stellen zusätzlich zu SMTP eine lokale Sendmail-Funktion
bereit. Das ist auf Plesk oft die einfachere Variante, wenn `localhost` wegen
TLS-Zertifikaten meckert oder für jede Subdomain kein eigener SMTP-Host
konfiguriert werden soll.

Wenn die Mailwerte in `.env` aktiv gesetzt werden, sind sie in OKGV
verbindlich und im Webinterface nur lesbar. Das ist sinnvoll, wenn ein
Hoster die Vorgaben zentral festlegt:

```dotenv
MAIL_MAILER=sendmail
MAIL_SENDMAIL_PATH="/usr/sbin/sendmail -bs -i"
MAIL_FROM_ADDRESS="noreply@deine-domain.example"
MAIL_FROM_NAME="${APP_NAME}"
```

Wer den Mailversand lieber in OKGV unter `Globale Konfiguration` pflegen
möchte, lässt die `MAIL_*` Beispielwerte in `.env` auskommentiert oder nutzt
`MAIL_MAILER=log`.

In OKGV unter `Globale Konfiguration` -> `Mailversand`:

```text
Mailversand aktivieren: ja
Versandart:             Sendmail des Webhostings
Sendmail-Pfad:          /usr/sbin/sendmail -bs -i
Absenderadresse:        noreply@deine-domain.example
Absendername:           OKGV
```

Falls der Hoster einen anderen Pfad nennt, diesen Wert eintragen. Für Sendmail
werden normalerweise kein SMTP-Benutzername, kein SMTP-Passwort, kein Host und
kein Port benötigt.

Nach einem Update mit neuer Mailkonfiguration:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

## Frontend-Build mit Plesk Node.js

Das Laravel Toolkit erzeugt `public/build` nicht automatisch. Der
Frontend-Build muss in Plesk ausdrücklich ausgeführt werden.

Wichtig: Node.js ist bei OKGV nur ein Build-Werkzeug. Die Domain darf nicht
dauerhaft als Node.js-Anwendung laufen, sonst sucht Plesk nach einer
`app.js` und Laravel startet nicht.

Im Plesk-Bereich `Node.js`:

```text
Node.js-Version: 22 LTS, 24 oder 26
Anwendungsstamm: /demo.okgv.de
Dokumentenstamm: /demo.okgv.de/public
Package Manager: npm
```

Danach:

1. `npm-Installation` ausführen.
2. `Skript ausführen` öffnen.
3. Als Skriptname `build` eintragen.
4. Prüfen, ob `public/build/manifest.json` erzeugt wurde.
5. Node.js für die Domain wieder deaktivieren.

Nicht `vite build` direkt eintragen. Das Projekt definiert in `package.json`:

```json
"scripts": {
  "build": "vite build"
}
```

Nach erfolgreichem Build muss diese Datei existieren:

```text
/demo.okgv.de/public/build/manifest.json
```

Fehlt sie, erscheint in Laravel:

```text
Vite manifest not found
```

Alternativ kann der Build in den Git-Bereitstellungsaktionen stehen:

```bash
npm ci --ignore-scripts
npm run build
php artisan migrate --force
php artisan optimize
```

Falls Plesk dort `npm` nicht findet, den Build über den Node.js-Bereich
ausführen oder lokal erzeugen und `public/build` hochladen.

### Kurzablauf nach frischem Plesk-Setup

1. Domain-Document-Root auf `/demo.okgv.de/public` setzen.
2. Node.js kurz aktivieren, nur um `npm-Installation` und das Skript `build`
   auszuführen.
3. Prüfen:

```bash
ls -la public/build/manifest.json
```

4. Node.js wieder deaktivieren.
5. Laravel über PHP/Laravel Toolkit betreiben.

Wenn nach dem Deaktivieren von Node.js der Fehler `Vite manifest not found`
erscheint, fehlt nur der Frontend-Build. Wenn stattdessen eine
Passenger-Seite erscheint und Plesk `app.js` sucht, ist Node.js noch als
Anwendungsserver aktiv.

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

### `Vite manifest not found`

Der Frontend-Build fehlt. Erzeuge `public/build` mit:

```bash
npm ci --ignore-scripts
npm run build
```

Oder lokal bauen und den Ordner `public/build` nach
`/demo.okgv.de/public/build` hochladen.

### Passenger sucht `app.js`

Plesk behandelt die Domain noch als Node.js-Anwendung. OKGV ist eine
Laravel/PHP-Anwendung. Node.js in Plesk deaktivieren und sicherstellen, dass
der Domain-Dokumentenstamm auf `public` zeigt.

### Plesk-Defaultseite erscheint

Der Document Root zeigt nicht auf `public` oder eine alte `index.html` liegt
im Document Root. Prüfen:

```text
/demo.okgv.de/public/index.php
```

Der Domain-Dokumentenstamm muss `/demo.okgv.de/public` sein.
