# OKGV Projektspezifikation

## Produkt

OKGV (Open Kleingarten Verwaltung) ist eine moderne, sichere und selbsthostbare Verwaltungssoftware für Kleingartenvereine mit 20 bis 500 oder mehr Parzellen.

Slogan: **Die freie Verwaltungssoftware für Kleingartenvereine.**

Domain: `okgv.de`

## Architektur

- Eine Installation verwaltet genau einen Verein.
- Die Anwendung ist nicht mandantenfähig.
- Jede Installation besitzt eine eigene Datenbank, Benutzer, Dateien, Konfiguration und Backups.
- Während der frühen Entwicklung läuft OKGV direkt im Linux-LXC.
- Docker und Produktionsdeployment beginnen erst nach Abschluss der Kernmodule.

## Technologie

- PHP 8.3 oder neuer
- Laravel
- Bootstrap 5
- Alpine.js
- MariaDB
- Node.js und npm

## Gestaltung

- Primary: `#2E7D32`
- Secondary: `#66BB6A`
- Accent: `#A5D6A7`
- Background: `#F5F7F5`
- Text: `#263238`
- Modern, minimalistisch, responsiv und mobilfähig

## Sicherheit

Pflichtbestandteile sind CSRF-Schutz, Session-Rotation, Passwort-Hashing, Form Requests, Policies, Rate Limiting, Security-Header und Auditlogs. Uploads werden nach MIME-Typ und Größe geprüft, ausführbare Dateien sind verboten und private Dokumente bleiben im privaten Storage.

Auditpflicht besteht insbesondere für Anmeldung, Benutzer-, Mitglieder- und Parzellenänderungen, Zählerwechsel, Abrechnungserstellung, Dokumentenfreigaben und SEPA-Änderungen.

## Rollen

- Administrator
- Vorstand
- Kassierer
- Wasserwart
- Gartenwart
- Pächter

Pächter dürfen ausschließlich eigene Daten sehen. Berechtigungen werden serverseitig geprüft. Bankdaten sind nur für Administrator, Vorstand und Kassierer zugänglich.

## Phasen

### Phase 0: Projektbasis

Laravel, Git, Bootstrap, Alpine.js, Login, Logout, Passwort-Reset, Dashboard, Rollenmodell, Policies, Security-Header und Auditlog-Basis.

### Phase 1: Stammdaten

Mitglieder, Parzellen und dauerhafte Pächterhistorie einschließlich CRUD, Archivierung und Suche.

#### Mitglieder

Die Tabelle `members` enthält:

- optionales, eindeutiges `user_id` zur serverseitigen Zuordnung eines Pächterkontos
- eindeutige `member_number`
- `first_name`, `last_name`, `street`, `zip`, `city`
- optionale Werte `phone`, `mobile`, `email`, `left_at` und `notes`
- `joined_at`
- Status `active`, `inactive`, `resigned` oder `archived`
- optionales `archived_at`

Archivierung ist reversibel und löscht keine Mitgliedsdaten. Ein Pächterkonto darf nur den eindeutig über `user_id` zugeordneten Mitgliedsdatensatz sehen.

#### Parzellen

Die Tabelle `parcels` enthält eine eindeutige `parcel_number`, `area_sqm`, optionale Lagebeschreibung und Notizen sowie einen der Status `free`, `assigned`, `reserved`, `terminated` oder `blocked`. Parzellen werden in Phase 1 nicht gelöscht.

#### Pächterhistorie

`parcel_tenants` verknüpft Parzellen und Mitglieder mit `starts_at`, optionalem `ends_at`, `is_primary` und optionalen Notizen. Historische Einträge werden nicht überschrieben oder gelöscht. `ends_at` darf nicht vor `starts_at` liegen. Aktive Zeiträume derselben Parzelle und desselben Mitglieds dürfen sich nicht überschneiden. Pro Parzelle ist höchstens ein aktiver Hauptpächter zulässig.

#### Rechte in Phase 1

- Administrator und Vorstand dürfen Mitglieder, Parzellen und Pächterzuordnungen lesen und bearbeiten.
- Kassierer dürfen Mitglieder und deren Parzellenzuordnungen lesen, jedoch keine Stammdaten verändern.
- Wasserwart und Gartenwart dürfen Mitglieder und Parzellen einschließlich Pächterhistorie lesen, jedoch in Phase 1 nicht verändern.
- Pächter dürfen ausschließlich den eigenen Mitgliedsdatensatz, aktuell oder historisch selbst zugeordnete Parzellen und die dazugehörigen eigenen Zuordnungen lesen.
- Archivierung ist Administrator und Vorstand vorbehalten.
- Stammdaten werden nicht physisch gelöscht.

### Phase 2: Zähler

Wasser- und Stromzähler, Zählerstände und vollständig historisierte Zählerwechsel. Verbrauch muss mehrere Zähler pro Abrechnungsjahr berücksichtigen.

### Phase 3: Abrechnung

Abrechnungsperioden, historische Preise, flächen-, verbrauchs- und festpreisabhängige Positionen, optionale Kosten, Rechnungen und PDF-Ausgabe. Freigegebene Rechnungen sind unveränderbar.

### Phase 4: SEPA

Mandate, IBAN-Prüfung, Sammellastschriften, pain.008-Export, Rücklastschriften und Zahlungsstatus.

### Phase 5: Pächterportal

Eigene Daten, Parzellen, Dokumente, Rechnungen und Zähler sowie Zählerstandsmeldungen mit Foto und Freigabe.

### Phase 6: Dokumente und Kommunikation

Private Dokumentverwaltung mit Sichtbarkeiten, Serienmail, Versandhistorie sowie Brief- und PDF-Erzeugung.

### Phase 7 bis 18

Mahnwesen, Arbeitsstunden, Warteliste, Inventar, Arbeitseinsätze, CSV-Import und -Export, DSGVO, Vereinseinstellungen, Nummernkreise, Pächterwechsel und später ein Lageplan.

## Versionen

- `0.0.1`: Projektstart
- `0.1.0`: Projektbasis
- `0.2.0`: Mitglieder und Parzellen
- `0.3.0`: Zählerverwaltung
- `0.4.0`: Abrechnung
- `0.5.0`: Pächterportal
- `0.6.0`: Dokumente
- `0.7.0`: Kommunikation
- `0.8.0`: SEPA
- `0.9.0`: Security Review
- `1.0.0`: Erste produktive Version
