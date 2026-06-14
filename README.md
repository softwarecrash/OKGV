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
- Granular berechtigte Serienmails mit Empfängergruppen und Versandhistorie
- Verschlüsselte SMTP-Konfiguration mit Testversand
- Allgemeine PDF-Briefe mit dauerhaftem Anschriften-Snapshot
- PDF-Zahlungserinnerungen für fällige offene Rechnungen
- Zentrale Dokumentenverwaltung für Verträge, Protokolle, Satzungen, Belege und Fotos
- Private Uploads mit unveränderlicher Dateiversionshistorie
- Granulare interne, Pächter- und öffentliche Dokumentfreigaben
- Nicht erratbare öffentliche Freigabelinks mit sofortiger Widerrufsmöglichkeit
- Suche, Filter und revisionsfreundliche Dokumentarchivierung
- Dreistufiges Mahnwesen für überfällige offene Rechnungen
- Unveränderliche Mahnungssnapshots mit Empfänger- und Betragshistorie
- Optionale Mahngebühren, neue Zahlungsfristen und kumulierte Forderungen
- PDF-Mahnungen und begründete, historisierte Stornierungen
- Pächterzugriff auf eigene Mahnhistorie
- Arbeitsstundenkonten je Parzelle und Abrechnungsperiode
- Automatische Arbeitsstundenkonten für zum Periodenstichtag verpachtete Parzellen
- Automatisch berechnete Fehlstunden und Strafzahlungen
- Historische Fehlstundenpositionen auf Einzel- und Gemeinschaftsrechnungen
- Arbeitseinsatztermine mit Teilnehmer- und Anwesenheitsstatus
- Automatische Übernahme bestätigter Einsatzstunden in das Jahreskonto
- Eigenständiges Arbeitseinsatzrecht für Vorstand und Gartenwart
- Globale Vereinsvorgaben für Pflichtstunden und Betrag je Fehlstunde
- Prüfpflichtige Pächtermeldungen mit optionalem privatem Foto

Freigegebene Rechnungen sind unveränderbar. Pächterwechsel innerhalb einer
Periode werden bis zum vollständigen Übergabeprozess bewusst nicht automatisch
abgerechnet.

Konten mit dem Recht `Abrechnungen und Rechnungen verwalten` finden unter
`Finanzen` die Arbeitsstundenübersicht. Zusätzlich zeigt jede
Parzellendetailansicht die periodischen Arbeitsstundenkonten; berechtigte
Konten können dort manuell anerkannte Stunden direkt pflegen. Pflichtstunden,
geleistete Stunden und
der Betrag je Fehlstunde werden pro Parzelle und Periode geführt. Die globale
Konfiguration stellt Vereinsvorgaben bereit, aus denen alle vergebenen
Parzellen beim Anlegen einer Periode automatisch ihr Konto erhalten.
Nachträgliche Pächterzuordnungen ergänzen passende bearbeitbare Perioden
ebenfalls automatisch. Fehlstunden und Strafbetrag berechnet OKGV
automatisch. Bei der nächsten
Zwischenberechnung erscheinen positive Beträge als eigene Rechnungsposition;
Änderungen an einem bereits berechneten Zwischenstand verwerfen dessen
Entwürfe und erfordern eine erneute Berechnung.

Arbeitseinsätze werden ebenfalls unter `Finanzen` innerhalb einer
Abrechnungsperiode angelegt. Nach dem Termin wird der Einsatz abgeschlossen
und die tatsächliche Teilnahme mit den geleisteten Stunden bestätigt. Nur
bestätigte Stunden abgeschlossener Einsätze fließen automatisch in das
Arbeitsstundenkonto ein. Manuell anerkannte Zusatzstunden bleiben separat
sichtbar. Eine spätere Korrektur oder Absage berechnet das Konto erneut und
löscht keine Historie.

Pächter können eigene Tätigkeiten mit Datum, Stunden, Beschreibung und
optionalem Foto melden. Erst nach Bestätigung durch ein berechtigtes
Vorstands- oder Gartenwartkonto werden die Stunden dem gemeinsamen
Parzellenkonto gutgeschrieben.

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

Für Serienmails muss zusätzlich ein Queue-Worker laufen:

```bash
php artisan queue:work --queue=default
```

Der Worker verarbeitet jeden Empfänger getrennt. Dadurch blockieren größere
Serienmails nicht den Webrequest und erfolgreiche sowie fehlgeschlagene
Zustellungen bleiben einzeln nachvollziehbar.

Pächter beantragen ihren Zugang über `/paechter-registrierung`. Ein
Administrator oder Vorstandsmitglied muss die Anfrage anschließend unter
`/registrierungsanfragen` einem aktuell eingetragenen Mitglied zuordnen und
freigeben.

Nach der Freigabe erhält der Pächter einen zeitlich begrenzten
Bestätigungslink. Bis zur Einrichtung im Abschnitt `SMTP-Einstellungen` der
`Globalen Konfiguration`
verwendet Laravel die Werte aus `.env`. Mit dem voreingestellten
`MAIL_MAILER=log` wird die Nachricht nur in `storage/logs/laravel.log`
geschrieben. Sobald die verschlüsselte SMTP-Konfiguration aktiviert ist,
verwenden Bestätigungsmails und Serienmails diese Einstellungen.

Administratoren finden unter ihrem Benutzermenü die `Rechteverwaltung` und
die `Globale Konfiguration`. Dort können registrierte Konten zum Vorstand
hochgestuft, einzelne Rechte oder Vorlagen vergeben, der sichtbare
Systemname angepasst und die verschlüsselte SMTP-Verbindung eingerichtet
oder mit einer frei wählbaren Zieladresse getestet werden.
Der SMTP-Test bestätigt die Annahme durch den konfigurierten Mailserver und
zeigt die Message-ID zur Nachverfolgung. Eine endgültige Zustellgarantie beim
Empfänger kann SMTP technisch nicht geben.

Konten mit dem Recht `Kommunikation verwalten` finden in der Hauptnavigation
den Bereich `Kommunikation`. Dort stehen Serienmails, PDF-Briefe und die
Versandhistorie bereit. Eine Zahlungserinnerung erscheint an einer Rechnung
erst nach überschrittener Fälligkeit und verändert weder Zahlungsstatus noch
Mahnstufe. Im Bereich `Mahnwesen` können berechtigte Finanzkonten anschließend
bis zu drei aufeinanderfolgende Mahnstufen mit eigener Frist und optionaler
Gebühr ausstellen. Eine weitere Stufe ist erst nach Ablauf der vorherigen
Frist möglich. Ausgestellte Mahnungen bleiben unveränderlich; fehlerhafte
Mahnungen werden begründet storniert und nicht gelöscht.

Konten mit dem Recht `Dokumente verwalten` können Dateien unter `Dokumente`
hochladen, Mitgliedern oder Parzellen zuordnen und gezielt veröffentlichen.
Erlaubt sind PDF, JPEG, PNG, WebP, TXT, DOCX und XLSX bis 20 MiB. Beim
Ersetzen einer Datei bleibt jede ältere Version erhalten. Archivieren löscht
weder Metadaten noch Dateien, beendet aber sämtliche Freigaben. Freigegebene
Rechnungen erscheinen dort als unveränderliche Systemdokumente.

## Löschbare Demo-Daten

Für manuelle Oberflächentests steht ein eigener, eindeutig markierter
Demo-Bestand bereit. Vor dem ersten Anlegen muss lokal in `.env` ein
gemeinsames Testpasswort gesetzt werden:

```dotenv
OKGV_DEMO_PASSWORD=
```

Danach werden fünf Konten mit fünf Parzellen sowie Daten für die Jahre 2024
bis 2026 angelegt:

```bash
php artisan okgv:demo-seed
```

Der Bestand enthält vier Pächterkonten, ein Vorstandsmitglied, Wasser- und
Stromzähler, einen historischen Zählerwechsel, Zählerstände,
Abrechnungsperioden, Preise, Arbeitsstunden, Arbeitseinsätze und
Pächtermeldungen. Erneutes Ausführen ersetzt ausschließlich den vorhandenen
Demo-Bestand.

Alle mit `DEMO-` markierten Testdaten können vollständig entfernt werden:

```bash
php artisan okgv:demo-purge
```

Normale Vereinsdaten werden von diesem Befehl nicht verändert. Der gewöhnliche
Befehl `php artisan db:seed` legt bewusst keine Beispieldaten an.

## Entwicklung

Die frühe Entwicklung erfolgt direkt im Linux-LXC. Docker- und Deployment-Artefakte werden erst nach Fertigstellung der Kernmodule erstellt.

Die verbindliche Reihenfolge steht in [PHASE_PLAN.md](PHASE_PLAN.md), die
Fachspezifikation in [PROJECT_SPEC.md](PROJECT_SPEC.md), Arbeitsregeln in
[AGENTS.md](AGENTS.md) und der aktuelle Fortschritt in [TODO.md](TODO.md).
Der projektweite Programmierstil ist in [AGENT_CODE_STYLE.md](AGENT_CODE_STYLE.md) verbindlich dokumentiert.

## Lizenz

OKGV ist Open Source und steht unter der MIT-Lizenz.
