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
- Responsiver SVG-Lageplan mit klickbaren und statusfarbigen Parzellen
- Dauerhafte Pächterhistorie mit Konfliktprüfung
- Rollenabhängige Lese- und Schreibrechte
- Wasser- und Stromzähler mit dauerhaftem Lebenszyklus
- Unveränderliche, plausibilitätsgeprüfte Zählerstände
- Revisionssichere Zählerstandkorrekturen mit optionalem Kontorecht
- Atomare Zählerwechsel und Verbrauch über mehrere Zählersegmente
- Abrechnungsperioden mit historischen Preisen und Zusatzkosten
- Reproduzierbare Rechnungen aus Flächen-, Verbrauchs- und Festkosten
- Taggenaue Teilabrechnung bei Eintritten, Austritten und Pächterwechseln
- Gemeinsamer Rechnungslauf für Vorauszahlungen und Verbrauchsnachberechnungen
- Eigene Leistungszeiträume je Preis mit sichtbarer Herleitung auf Rechnung und PDF
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
- Konfigurierbare Nummernkreise für Mitglieder, Rechnungen, Mandate und Dokumente
- Isoliertes Pächterportal für eigene Daten, Parzellen und Rechnungen
- Private Dokumentdownloads für Mitglieder und aktuelle Parzellen
- Prüfpflichtige Zählerstandsmeldungen mit optionalem privatem Foto und
  zoombarer geschützter Vorschau
- Sichtbare Plausibilitätsfehler und dauerhaft nachvollziehbare
  Zählerstandsmeldungshistorie
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
- Automatische Arbeitsstundenkonten für innerhalb der Periode verpachtete Parzellen
- Taggenau anteilige Pflichtstunden bei unterjähriger Verpachtung
- Automatisch berechnete Fehlstunden und Strafzahlungen
- Historische Fehlstundenpositionen auf Einzel- und Gemeinschaftsrechnungen
- Arbeitseinsatztermine mit Teilnehmer- und Anwesenheitsstatus
- Automatische Übernahme bestätigter Einsatzstunden in das Jahreskonto
- Eigenständiges Arbeitseinsatzrecht für Vorstand und Gartenwart
- Globale Vereinsvorgaben für Pflichtstunden und Betrag je Fehlstunde
- Prüfpflichtige Pächtermeldungen mit optionalem privatem Foto
- Granular geschützte Warteliste mit Prioritäten, Status und Kontaktsuche
- Aktionshinweise für offene Wartelistenvorgänge
- Frei definierbares Vereinsinventar mit Kategorien und optionalen Anschaffungsdaten
- Transaktionale Ausgabe und Rückgabe mit dauerhafter Empfänger- und Zustandshistorie
- Granulares Inventarrecht und Aktionshinweise für überfällige Rückgaben
- Instanzweise aktivierbare Funktionsmodule für unterschiedliche SaaS-Ausbaustufen
- Serverseitig geschützte Modulrouten mit geprüften Abhängigkeiten

Freigegebene Rechnungen sind unveränderbar. Bei der Berechnung werden
Mitgliedseintritte und -austritte anhand der Mitgliedschaft, Pächterwechsel
anhand der dauerhaften Pächterhistorie taggenau berücksichtigt. Ein
Rechnungslauf kann zum Beispiel Pacht und Versicherung für das Folgejahr als
Vorauszahlung sowie Wasser und Strom für das zurückliegende Jahr als
Nachberechnung enthalten. Für eine verbrauchsgenaue Aufteilung bei
Pächterwechseln muss ein Übergabezählerstand vorhanden sein.

Konten mit dem Recht `Abrechnungen und Rechnungen verwalten` finden unter
`Finanzen` die Arbeitsstundenübersicht. Zusätzlich zeigt jede
Parzellendetailansicht die periodischen Arbeitsstundenkonten; berechtigte
Konten können dort manuell anerkannte Stunden direkt pflegen. Die jährlichen
Pflichtstunden werden anhand der tatsächlichen Belegungstage der Parzelle
anteilig berechnet; mehrere gleichzeitige Mitpächter erhöhen die Pflicht
nicht. Pflichtstunden, geleistete Stunden und
der Betrag je Fehlstunde werden pro Parzelle und Periode geführt. Die globale
Konfiguration stellt Vereinsvorgaben bereit, aus denen alle innerhalb einer
Periode vergebenen Parzellen automatisch ihr Konto erhalten.
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

### HTTPS über einen Reverse Proxy

Wird OKGV über Nginx, OpenResty oder einen anderen Reverse Proxy unter einer
HTTPS-Domain erreichbar gemacht, müssen in `.env` mindestens die öffentliche
URL und die IP des unmittelbar vorgeschalteten Proxys eingetragen werden:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://okgv.example.de
TRUSTED_PROXIES=192.0.2.10
SESSION_SECURE_COOKIE=null
```

Mehrere vertrauenswürdige Proxy-Adressen oder CIDR-Netze werden durch Kommas
getrennt. `TRUSTED_PROXIES=*` darf nur verwendet werden, wenn direkter Zugriff
auf den Anwendungsserver durch Firewall oder Netzwerk ausgeschlossen ist.
Nach Änderungen an diesen Werten muss `php artisan optimize:clear` ausgeführt
werden. Der Proxy muss `X-Forwarded-Proto`, `X-Forwarded-Host` und
`X-Forwarded-Port` korrekt setzen.

`SESSION_SECURE_COOKIE=null` lässt Laravel das Cookie automatisch an das
tatsächliche Schema anpassen. Dadurch bleibt es über HTTPS geschützt, während
ein zusätzlicher direkter HTTP-Zugang im internen Testnetz weiterhin
funktioniert. Produktive Installationen sollten ausschließlich HTTPS
bereitstellen.

Nach einer Umstellung von erzwungen sicheren Cookies auf den automatischen
Modus kann ein Browser für eine zuvor direkt per HTTP aufgerufene Adresse
noch ein unbrauchbares altes Cookie halten. In diesem Fall kann einmalig ein
neuer `SESSION_COOKIE`-Name gesetzt oder das Cookie der betroffenen Adresse
gelöscht werden.

### Funktionsmodule

Die vorhandenen Fachdaten bleiben beim Deaktivieren eines Moduls vollständig
erhalten. Nach Änderungen muss der Konfigurationscache geleert werden:

```bash
php artisan optimize:clear
```

Verfügbare Schalter:

```dotenv
OKGV_MODULE_TENANT_PORTAL=true
OKGV_MODULE_METERS=true
OKGV_MODULE_BILLING=true
OKGV_MODULE_WORK_HOURS=true
OKGV_MODULE_WORK_EVENTS=true
OKGV_MODULE_SEPA=true
OKGV_MODULE_DUNNING=true
OKGV_MODULE_DOCUMENTS=true
OKGV_MODULE_COMMUNICATION=true
OKGV_MODULE_WAITING_LIST=true
OKGV_MODULE_INVENTORY=true
OKGV_MODULE_DATA_TRANSFER=true
OKGV_PRIVACY_RETENTION_YEARS=10
```

Arbeitsstunden benötigen Abrechnung, Arbeitseinsätze benötigen
Arbeitsstunden, und SEPA sowie Mahnwesen benötigen Abrechnung. OKGV startet
bei einer ungültigen Kombination nicht, damit keine unvollständige
SaaS-Instanz betrieben wird. SMTP bleibt unabhängig vom Kommunikationsmodul
verfügbar, weil E-Mail-Verifizierung und Passwort-Reset darauf angewiesen
sind.

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
Demo-Bestand. Existiert für 2024, 2025 oder 2026 bereits eine andere
Abrechnungsperiode, wird der Vorgang vor jeder Änderung mit einem
verständlichen Hinweis abgebrochen. Abrechnungsperioden dürfen auch im
Demo-Bestand niemals zeitlich überlappen.

Alle mit `DEMO-` markierten Testdaten können vollständig entfernt werden:

```bash
php artisan okgv:demo-purge
```

Normale Vereinsdaten werden von diesem Befehl nicht verändert. Der gewöhnliche
Befehl `php artisan db:seed` legt bewusst keine Beispieldaten an.

## Datenübertragung und Backups

Konten mit dem Recht `CSV-Daten übertragen` finden im Benutzermenü den
Bereich `Datenübertragung`. Dort stehen verbindliche Importvorlagen und
Exporte für Mitglieder, Parzellen, Zähler, Zählerstände und Rechnungen
bereit. CSV-Dateien müssen UTF-8-kodiert sein. Ein Import wird vollständig
zurückgerollt, sobald eine Zeile fachlich ungültig ist.

Nur Administratoren sehen die Backup-Funktionen. Ein Backup enthält einen
MariaDB-Dump sowie private Dokumente und Nachweisfotos. Vor einem Restore
prüft OKGV Format, Version und SHA-256-Prüfsummen und erstellt automatisch
ein Sicherheitsbackup.

Die `.env` ist bewusst nicht Bestandteil des Archivs. Sie muss separat
geschützt gesichert werden. Für eine erfolgreiche Wiederherstellung ist
insbesondere derselbe `APP_KEY` erforderlich, da andernfalls verschlüsselte
SMTP- und Bankdaten nicht mehr lesbar sind. Produktive Betreiber benötigen
zusätzlich einen externen, automatisierten Backupplan; die manuelle
Serverablage allein schützt nicht gegen einen vollständigen Serverausfall.

## Datenschutz und Löschprüfung

Jedes angemeldete Mitglied findet im Benutzermenü den Bereich `Datenschutz`.
Dort kann es eine maschinenlesbare JSON-Auskunft herunterladen, eine
Löschprüfung beantragen und einzelne Kontaktdaten freiwillig für aktuell
gemeinsam eingetragene Mitpächter derselben Parzelle freigeben. Sämtliche
Freigaben sind standardmäßig deaktiviert und können jederzeit vollständig
widerrufen werden. Es gibt kein vereinsweites öffentliches
Mitgliederverzeichnis.

Vorstandsmitglieder benötigen das granulare Recht
`Datenschutzanfragen verwalten`, um fremde Auskünfte und Löschanfragen zu
prüfen. Die endgültige Pseudonymisierung bleibt Administratoren vorbehalten
und verlangt das aktuelle Passwort sowie eine ausdrückliche
Sicherheitsbestätigung.

Eine Löschprüfung berücksichtigt mindestens Archivierung, Austrittsdatum,
Pächterhistorie, offene oder noch aufzubewahrende Rechnungen, aktive
SEPA-Mandate, Dokumente und offene Inventarausgaben. Die technische
Mindestaufbewahrung wird mit `OKGV_PRIVACY_RETENTION_YEARS` konfiguriert und
beträgt standardmäßig zehn Jahre. Dieser Wert ersetzt keine rechtliche
Prüfung durch den betreibenden Verein. Sind Aufbewahrungsgründe vorhanden,
wird die Anfrage mit verständlichen Gründen blockiert. Nach erfolgreicher
Prüfung werden identifizierende Stammdaten und das Pächterkonto
pseudonymisiert; notwendige historische Fachbezüge bleiben unter einer
anonymen Referenz erhalten.

## Vereinseinstellungen und Dokumentbranding

Administratoren pflegen in der `Globalen Konfiguration` den sichtbaren
Systemnamen sowie den offiziellen Vereinsnamen, Anschrift, Ansprechpartner,
Telefon, E-Mail-Adresse, Webseite und das Vereinslogo. Der Systemname
bezeichnet die Installation; der offizielle Vereinsname erscheint als
Absender in Rechnungen und Schreiben.

Das Logo darf JPEG, PNG oder WebP mit höchstens 2 MiB sein. Es wird geprüft,
privat gespeichert und in Navigation sowie PDFs verwendet. Eine eigene
Dokumentfußzeile und E-Mail-Signatur ermöglichen Vereinsregister-,
Kontakt- oder Grußangaben ohne HTML-Ausführung.

Die Bankverbindung für Überweisungsrechnungen wird verschlüsselt gespeichert.
Sie ist bewusst vom SEPA-Lastschriftkonto und der Gläubiger-ID getrennt.
IBAN und BIC müssen bei einer späteren Änderung neu eingegeben werden; leere
Felder behalten bestehende Geheimnisse. Eine eigene Auswahl entfernt die
gesamte Rechnungsbankverbindung.

Das Standard-Zahlungsziel wird bei neuen Abrechnungsperioden vorgeschlagen.
Rechnungen, Briefe, Mahnungen und Serienmails speichern einen historischen
Snapshot ihrer Absender- und Brandingdaten. Spätere Konfigurationsänderungen
verändern daher keine bereits erzeugten Dokumente.

## Nummernkreise

Administratoren finden im Benutzermenü den Bereich `Nummernkreise`. Dort
lassen sich die Formate für Mitglieds-, Rechnungs-, Mandats- und
Dokumentnummern getrennt einstellen. `{NUMMER}` steht für die fortlaufende
Nummer, `{JAHR}` für das vierstellige Kalenderjahr.

Mindeststellen, nächster Zählerstand und ein optionaler jährlicher Neustart
sind konfigurierbar. Bereits vergebene, manuell erfasste oder importierte
Nummern bleiben unverändert und werden beim Weiterzählen übersprungen.
Mitglieder und SEPA-Mandate können beim Anlegen weiterhin bewusst eine
eigene Nummer erhalten; ein leeres Feld aktiviert die automatische Vergabe.

## Lageplan

Der Bereich `Lageplan` legt beliebig geformte Parzellen als klickbare
Polygone über ein privat gespeichertes Luft- oder Lagebild. Grün steht für
freie oder vergebene, Gelb für reservierte oder gekündigte und Rot für
gesperrte Parzellen; Nummer und Status sind zusätzlich als Text verfügbar.

Berechtigte Stammdatenverwalter laden im Bearbeitungsmodus ein eigenes oder
ausdrücklich lizenziertes JPEG-, PNG- oder WebP-Bild hoch und dokumentieren
Quelle sowie Nutzungsrecht. Danach wählen sie eine Parzelle, setzen ihre
Eckpunkte per Klick und verschieben Punkte oder die gesamte Fläche per
Drag-and-drop. Das Entfernen einer Zeichnung löscht niemals die Parzelle.
Übersicht und Editor lassen sich zwischen 100 und 400 Prozent vergrößern.
Neben den Schaltflächen steht dafür Strg und Mausrad zur Verfügung; der
vergrößerte Ausschnitt kann über Bildlaufleisten oder unmittelbar mit
gedrückter Maustaste bewegt werden. Kurze Klicks öffnen Parzellen weiterhin;
im Editor bleiben Eckpunkte und Polygonflächen direkt bearbeitbar. Die
Eckpunkt-Griffe behalten beim Zoomen ihre sichtbare Größe und verdecken daher
auch bei starker Vergrößerung nicht die Parzellenfläche.

Screenshots oder Satellitenbilder aus Google Maps dürfen nicht automatisch
als eigene Bilddatei gespeichert werden. Für Google-Satellitendaten wäre
eine gesonderte, vertragskonforme Google-Maps-API-Einbindung erforderlich.

Polygonpunkte sind Bestandteil des Parzellen-CSV-Imports und -Exports.
Frühere CSV-Dateien ohne Lageplan oder mit Rechteckkoordinaten bleiben
importierbar. Hintergrundbild und Vereinslogo werden im privaten
`association`-Ordner gesichert. Pächter sehen ausschließlich aktuell eigene
Parzellen.

## Entwicklung

Die frühe Entwicklung erfolgt direkt im Linux-LXC. Docker- und Deployment-Artefakte werden erst nach Fertigstellung der Kernmodule erstellt.

Die verbindliche Reihenfolge steht in [PHASE_PLAN.md](PHASE_PLAN.md), die
Fachspezifikation in [PROJECT_SPEC.md](PROJECT_SPEC.md), Arbeitsregeln in
[AGENTS.md](AGENTS.md) und der aktuelle Fortschritt in [TODO.md](TODO.md).
Der projektweite Programmierstil ist in [AGENT_CODE_STYLE.md](AGENT_CODE_STYLE.md) verbindlich dokumentiert.

## Lizenz

OKGV ist freie Software und steht unter der
[GNU Affero General Public License Version 3](LICENSE), kurz GNU AGPLv3.

Wer OKGV verändert und über ein Netzwerk bereitstellt, muss den
interagierenden Benutzern den vollständigen korrespondierenden Quellcode der
tatsächlich betriebenen Version gemäß Abschnitt 13 der GNU AGPLv3 anbieten.
Die Oberfläche zeigt dafür einen dauerhaften Link `Quellcode` an. Dessen Ziel
wird über folgende Umgebungsvariable festgelegt:

```dotenv
APP_SOURCE_URL=https://github.com/softwarecrash/OKGV
```

Betreiber einer veränderten Version müssen diesen Wert auf die Bezugsquelle
ihres vollständigen, zur laufenden Version passenden Quellcodes setzen.
Lizenzen eingebundener Drittanbieter-Komponenten bleiben unberührt.
