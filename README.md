# OKGV

**Open Kleingarten Verwaltung**

Die freie Verwaltungssoftware für Kleingartenvereine.

OKGV ist eine moderne, sichere und selbsthostbare Laravel-Anwendung. Eine Installation verwaltet genau einen Verein und besitzt eine eigene Datenbank, Benutzerbasis und Dateablage.

## Aktueller Stand

Die Basisversion `0.2.0` wird während der Bauphase mit einer fortlaufenden vierten Stelle ergänzt. Der aktuelle Entwicklungsstand wird in `VERSION` geführt.

- Laravel 13 und PHP 8.3+
- Bootstrap 5 und Alpine.js
- Login, Logout und Passwort-Reset
- Rollen- und Policy-Grundlage
- Auditlog-Basis
- Security-Header
- MariaDB-Unterstützung
- Mitgliederverwaltung mit Suche und reversibler Archivierung
- Parzellenverwaltung mit Status und Flächenangaben
- Dauerhafte Pächterhistorie mit Konfliktprüfung
- Rollenabhängige Lese- und Schreibrechte
- Wasser- und Stromzähler mit dauerhaftem Lebenszyklus
- Unveränderliche, plausibilitätsgeprüfte Zählerstände
- Revisionssichere Zählerstandkorrekturen mit optionalem Kontorecht
- Atomare Zählerwechsel und Verbrauch über mehrere Zählersegmente
- Abrechnungsperioden mit historischen Preisen und Zusatzkosten
- Reproduzierbare Rechnungen aus Flächen-, Verbrauchs- und Festkosten
- Gemeinsame Rechnungen für Haupt- und Mitpächter mit historischen Empfängerdaten
- Freigabeschutz für Rechnungen und serverseitige PDF-Ausgabe
- Konfigurierbare Preisvorlagen für wiederkehrende Kostenarten
- Verschlüsselte SEPA-Mandate und Vereinsbankdaten
- Sammellastschriften als pain.008.001.08-XML
- Zahlungsstatus und historisierte Rücklastschriften
- Pächterregistrierung mit verbindlicher Freigabe durch Vorstand oder Administrator
- E-Mail-Verifizierung nach Freigabe eines neuen Kontos
- Aufstufung freigegebener Pächterkonten zu Vorstandsmitgliedern
- Granulare Vorstandsrechte mit konfigurierbaren Rechtevorlagen
- Globale Konfiguration des sichtbaren Systemnamens
- Isoliertes Pächterportal für eigene Daten, Parzellen und Rechnungen
- Private Dokumentdownloads für Mitglieder und aktuelle Parzellen
- Prüfpflichtige Zählerstandsmeldungen mit optionalem privatem Foto
- Kompakte Navigationsgruppen für Mitglieder, Zähler und Finanzen
- Rollenabhängige Aktionspunkte für Vorgänge mit notwendiger Bearbeitung
- Persistenter heller und dunkler Darstellungsmodus

Freigegebene Rechnungen sind unveränderbar. Pächterwechsel innerhalb einer
Periode werden bis zum vollständigen Übergabeprozess bewusst nicht automatisch
abgerechnet.

## Voraussetzungen

- PHP 8.3 oder neuer mit MySQL-, XML-, Mbstring-, Curl-, Zip-, Bcmath- und Intl-Erweiterung
- Composer
- MariaDB
- Node.js 20 oder neuer
- npm

## Lokale Installation

```bash
git clone https://github.com/softwarecrash/OKGV.git
cd OKGV
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
```

Eine MariaDB-Datenbank und einen eigenen Datenbankbenutzer anlegen, anschließend die Werte in `.env` eintragen:

```bash
php artisan migrate
php artisan okgv:create-admin
php artisan serve
```

Die Anwendung ist danach standardmäßig unter `http://127.0.0.1:8000` erreichbar.
Für die Frontend-Entwicklung kann parallel `npm run dev` gestartet werden.

Pächter beantragen ihren Zugang über `/paechter-registrierung`. Ein
Administrator oder Vorstandsmitglied muss die Anfrage anschließend unter
`/registrierungsanfragen` einem aktuell eingetragenen Mitglied zuordnen und
freigeben.

Nach der Freigabe erhält der Pächter einen zeitlich begrenzten
Bestätigungslink. Für echten E-Mail-Versand müssen in `.env` mindestens
`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`,
`MAIL_PASSWORD` und `MAIL_FROM_ADDRESS` gesetzt sein. Mit dem voreingestellten
`MAIL_MAILER=log` wird die Nachricht nur in `storage/logs/laravel.log`
geschrieben.

Administratoren finden unter ihrem Benutzermenü die `Rechteverwaltung` und
die `Globale Konfiguration`. Dort können registrierte Konten zum Vorstand
hochgestuft, einzelne Rechte oder Vorlagen vergeben und der sichtbare
Systemname angepasst werden.

## Entwicklung

Die frühe Entwicklung erfolgt direkt im Linux-LXC. Docker- und Deployment-Artefakte werden erst nach Fertigstellung der Kernmodule erstellt.

Die verbindliche Reihenfolge steht in [PHASE_PLAN.md](PHASE_PLAN.md), die
Fachspezifikation in [PROJECT_SPEC.md](PROJECT_SPEC.md), Arbeitsregeln in
[AGENTS.md](AGENTS.md) und der aktuelle Fortschritt in [TODO.md](TODO.md).
Der projektweite Programmierstil ist in [AGENT_CODE_STYLE.md](AGENT_CODE_STYLE.md) verbindlich dokumentiert.

## Lizenz

OKGV ist Open Source und steht unter der MIT-Lizenz.
