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

`parcel_tenants` verknüpft Parzellen und Mitglieder mit `starts_at`, optionalem `ends_at`, `is_primary` und optionalen Notizen. Historische Einträge werden nicht überschrieben oder gelöscht. `ends_at` darf nicht vor `starts_at` liegen. Aktive Zeiträume derselben Parzelle und desselben Mitglieds dürfen sich nicht überschneiden. Pro Parzelle ist höchstens ein aktiver Hauptpächter zulässig. Zusätzlich dürfen beliebig viele aktive Mitpächter derselben Parzelle zugeordnet sein. Haupt- und Mitpächter gelten gemeinsam als Vertragsparteien des Pachtvertrags.

#### Rechte in Phase 1

- Administrator und Vorstand dürfen Mitglieder, Parzellen und Pächterzuordnungen lesen und bearbeiten.
- Kassierer dürfen Mitglieder und deren Parzellenzuordnungen lesen, jedoch keine Stammdaten verändern.
- Wasserwart und Gartenwart dürfen Mitglieder und Parzellen einschließlich Pächterhistorie lesen, jedoch in Phase 1 nicht verändern.
- Pächter dürfen ausschließlich den eigenen Mitgliedsdatensatz, aktuell oder historisch selbst zugeordnete Parzellen und die dazugehörigen eigenen Zuordnungen lesen.
- Archivierung ist Administrator und Vorstand vorbehalten.
- Stammdaten werden nicht physisch gelöscht.

### Phase 2: Zähler

Wasser- und Stromzähler, Zählerstände und vollständig historisierte Zählerwechsel. Verbrauch muss mehrere Zähler pro Abrechnungsjahr berücksichtigen.

#### Zähler

Die Tabelle `meters` enthält:

- `parcel_id`
- Typ `water` oder `electricity`
- global eindeutige `meter_number`
- `installed_at` und optionales `removed_at`
- nichtnegative `start_reading` und optionales `end_reading` mit vier Nachkommastellen
- Status `active`, `replaced`, `removed` oder `defective`
- optionale interne Notizen

Pro Parzelle und Zählertyp ist höchstens ein aktiver Zähler zulässig. Ein Ausbau darf nicht vor dem Einbau liegen. Der Endstand darf nicht kleiner als der Startstand sein.

#### Zählerstände

Die Tabelle `meter_readings` enthält:

- `meter_id`
- nichtnegativen `reading_value` mit vier Nachkommastellen
- `reading_date`
- Quelle `board`, `tenant` oder `import`
- optionalen privaten `photo_path`
- optionale interne Notizen

Zählerstände sind append-only: Bestehende Werte werden weder bearbeitet noch gelöscht. Korrekturen werden als neuer, nachvollziehbarer Datensatz erfasst. Pro Zähler und Datum ist höchstens ein Stand zulässig. Ein Stand muss innerhalb der Einbau- und Ausbauzeit des Zählers liegen und darf chronologisch nicht kleiner als der vorherige Stand oder größer als ein bereits vorhandener späterer Stand sein.

`meter_reading_corrections` referenziert den ursprünglichen Zählerstand und
speichert den korrigierten Wert, eine verpflichtende Begründung, den
korrigierenden Benutzer und den Erfassungszeitpunkt. Auch Korrekturen sind
append-only. Mehrere aufeinanderfolgende Korrekturen bleiben erhalten; für
Berechnungen gilt jeweils der jüngste Korrekturwert. Originalwert und gesamte
Korrekturhistorie werden niemals überschrieben oder gelöscht.

Das optionale Benutzerrecht `can_correct_meter_readings` darf ausschließlich
Administrator- und Vorstandskonten zugewiesen werden. Die Rolle allein
berechtigt nicht zur Korrektur. Nur Administratoren verwalten dieses
Sonderrecht. Jede Korrektur wird zusätzlich im Auditlog erfasst.

#### Zählerwechsel

Ein Zählerwechsel wird in einer Datenbanktransaktion ausgeführt:

1. Der alte Zähler erhält `removed_at`, `end_reading` und den Status `replaced`.
2. Der neue Zähler wird mit `installed_at`, `start_reading` und Status `active` angelegt.
3. Das Wechseldatum darf nicht vor dem Einbaudatum des alten Zählers liegen.
4. Alter und neuer Zähler müssen dieselbe Parzelle und denselben Typ besitzen.
5. Der Vorgang wird im Auditlog protokolliert.

Historische Zähler werden nicht gelöscht.

#### Verbrauch

Der Verbrauch eines Zeitraums wird pro Zählersegment berechnet und anschließend summiert. Für jedes Segment gilt:

- Startwert ist der letzte Stand vor oder am Periodenbeginn, andernfalls der gespeicherte Einbaustand.
- Endwert ist der letzte Stand vor oder am Periodenende, andernfalls bei einem innerhalb der Periode ausgebauten Zähler dessen Endstand.
- Negative Differenzen sind unzulässig.

Dadurch werden mehrere Zählerwechsel innerhalb eines Abrechnungsjahres korrekt berücksichtigt.

#### Rechte in Phase 2

- Administrator und Vorstand dürfen alle Zähler, Stände und Wechsel lesen und bearbeiten.
- Wasserwart darf Wasser- und Stromzähler, Zählerstände und Wechsel verwalten.
- Kassierer darf alle Zähler und Stände lesen, jedoch nicht verändern.
- Gartenwart darf Zählerdaten lesen, jedoch nicht verändern.
- Pächter dürfen ausschließlich Zähler und Zählerstände von eigenen, aktuell oder historisch zugeordneten Parzellen lesen.
- Pächter dürfen in Phase 2 noch keine Zählerstände selbst erfassen.
- Pächter dürfen in Phase 5 eigene Zählerstände melden; fehlerhafte Meldungen
  werden später über denselben revisionssicheren Korrekturprozess berichtigt.
- Physisches Löschen und direktes Bearbeiten bestehender Zählerstände ist für
  alle Rollen ausgeschlossen.
- Administrator- und Vorstandskonten dürfen Zählerstände nur mit dem
  ausdrücklich zugewiesenen Sonderrecht revisionssicher korrigieren.

### Phase 3: Abrechnung

Abrechnungsperioden, historische Preise, flächen-, verbrauchs- und festpreisabhängige Positionen, optionale Kosten, Rechnungen und PDF-Ausgabe. Freigegebene Rechnungen sind unveränderbar.

#### Abrechnungsperioden

Eine Abrechnungsperiode besitzt eine eindeutige Bezeichnung, ein Start- und
Enddatum, ein Fälligkeitsdatum und einen Status: `draft`, `calculated`,
`approved` oder `archived`. Berechnung, Freigabe und Archivierung werden mit
eigenen Zeitstempeln dokumentiert.

Abrechnungsperioden dürfen sich zeitlich nicht überschneiden. Preise,
Zuordnungen und Periodendaten dürfen in den Status `draft` und `calculated`
geändert werden. Die Berechnung erzeugt einen reproduzierbaren,
beliebig oft erneuerbaren Zwischenstand aus Rechnungsentwürfen.

Wird ein bereits berechneter Zwischenstand geändert, werden ausschließlich
die noch nicht freigegebenen Rechnungsentwürfe samt Positionen und
Empfängersnapshots verworfen. Die Periode wechselt auditierbar zurück auf
`draft` und muss anschließend neu berechnet werden. Eine Freigabe ist nur
nach einer erfolgreichen Berechnung möglich. Ab `approved` sind Periode,
Preise, Zuordnungen und Rechnungen dauerhaft unveränderbar.

#### Historische Preise

`billing_rates` werden immer einer Abrechnungsperiode zugeordnet. Eine
Preisänderung erfolgt ausschließlich in einer neuen Periode und verändert
keine historische Rechnung.

Jeder Preis besitzt:

- einen innerhalb der Periode eindeutigen technischen Code
- eine deutsche Bezeichnung und optionale Beschreibung
- eine Berechnungsart: `fixed`, `per_sqm`, `per_kwh`, `per_m3` oder `manual`
- einen Geltungsbereich: `member`, `parcel` oder `assignment`
- einen Dezimalbetrag mit vier Nachkommastellen
- einen Aktivstatus

Vordefinierte Codes sind mindestens `LEASE_PER_SQM`, `WATER_PER_M3`,
`ELECTRICITY_PER_KWH`, `WATER_BASE_FEE`, `ELECTRICITY_BASE_FEE`,
`MEMBER_FEE` und `INSURANCE`. Verbrauchspreise werden anhand ihres Codes dem
passenden Zählertyp zugeordnet.

Der Geltungsbereich steuert die Anwendung:

- `member`: einmal je abrechnungsrelevantem Mitglied
- `parcel`: einmal je abrechnungsrelevanter Parzelle
- `assignment`: nur über eine historische Zuordnung zu Mitglied oder Parzelle

Optionale und manuelle Kosten werden über `billing_rate_assignments` genau
einem Mitglied oder einer Parzelle zugeordnet. Die Zuordnung speichert Menge
und Notizen innerhalb der Abrechnungsperiode. Eine Zuordnung darf nicht
gleichzeitig Mitglied und Parzelle referenzieren.

#### Rechnungserzeugung

Eine Abrechnung wird für Mitglieder erzeugt, die am Ende der Periode
Hauptpächter mindestens einer Parzelle sind. Die Parzellen dieses Mitglieds
werden in einer gemeinsamen Rechnung zusammengefasst. Alle am Periodenende
aktiven Haupt- und Mitpächter dieser Parzellen werden als gemeinsame
Rechnungsempfänger übernommen. Mitgliedsbezogene Kosten werden einmal für den
verantwortlichen Hauptpächter, parzellenbezogene Kosten für jede zugeordnete
Parzelle berechnet.

Automatisch berechenbare Positionen:

- Pacht und Umlagen anhand der Parzellenfläche
- Strom und Wasser anhand der historischen Zählerstände
- feste Bereitstellungs- und Mitgliedskosten
- zugeordnete Versicherungen, Sonderumlagen und Zusatzleistungen
- manuelle Positionen mit dokumentierter Menge

Pächterwechsel innerhalb einer Abrechnungsperiode werden bis zur Umsetzung
des vollständigen Übergabeprozesses in Phase 17 nicht automatisch aufgeteilt.
Die Berechnung muss solche Fälle erkennen und abbrechen, damit keine
unbeabsichtigte Zuordnung entsteht.

Rechnungsnummern werden bis zur konfigurierbaren Nummernkreisverwaltung aus
Phase 16 im Format `YYYY-NNNNN` je Kalenderjahr fortlaufend vergeben.

#### Rechnungen und Positionen

Eine Rechnung besitzt mindestens Abrechnungsperiode, Mitglied, eindeutige
Rechnungsnummer, Status `draft` oder `approved`, Rechnungsdatum,
Fälligkeitsdatum, Gesamtbetrag, Freigabezeitpunkt und freigebenden Benutzer.

`invoice_recipients` speichert für jede Vertragspartei einen unveränderlichen
Snapshot aus Mitgliedsnummer, Vorname, Nachname und Anschrift. Der
Hauptpächter bleibt als verantwortlicher Hauptempfänger gekennzeichnet. Auf
der Rechnung werden alle Vertragsparteien namentlich aufgeführt; die
Zustellanschrift stammt aus dem Snapshot des Hauptempfängers.

Rechnungspositionen speichern unveränderliche Snapshots von Code,
Bezeichnung, Berechnungsart, Menge, Einzelpreis und Positionssumme. Sie
referenzieren Preis und Parzelle nur ergänzend. Historische Rechnungen bleiben
daher auch bei späteren Stammdatenänderungen vollständig nachvollziehbar.

Freigegebene Rechnungen und ihre Positionen dürfen weder über Oberfläche noch
über Serviceklassen verändert oder gelöscht werden. Korrekturen erfolgen
später ausschließlich durch dokumentierte Folgebelege.

PDF-Dateien werden serverseitig aus dem Rechnungssnapshot erzeugt. Entwürfe
werden sichtbar als solche gekennzeichnet.

#### Rechte in Phase 3

- Administrator, Vorstand und Kassierer dürfen Abrechnungsperioden, Preise,
  Zuordnungen und Rechnungen verwalten, berechnen und freigeben.
- Wasserwart darf abrechnungsrelevante Zähler- und Verbrauchsdaten einsehen,
  aber keine Preise oder Rechnungen ändern.
- Gartenwart erhält keinen Zugriff auf Finanzdaten.
- Pächter dürfen ausschließlich freigegebene Rechnungen einsehen, in deren
  Empfängersnapshot sie als Vertragspartei enthalten sind; die
  Portaloberfläche dafür wird in Phase 5 umgesetzt.
- Jede Berechnung und Freigabe wird im Auditlog protokolliert.

### Phase 4: SEPA

Mandate, IBAN-Prüfung, Sammellastschriften, pain.008-Export, Rücklastschriften und Zahlungsstatus.

### Phase 5: Pächterportal

Eigene Daten, Parzellen, Dokumente, Rechnungen und Zähler sowie Zählerstandsmeldungen mit Foto und Freigabe.

### Phase 6: Dokumente und Kommunikation

Private Dokumentverwaltung mit Sichtbarkeiten, Serienmail, Versandhistorie sowie Brief- und PDF-Erzeugung.

### Phase 7 bis 18

Mahnwesen, Arbeitsstunden, Warteliste, Inventar, Arbeitseinsätze, CSV-Import und -Export, DSGVO, Vereinseinstellungen, Nummernkreise, Pächterwechsel und später ein Lageplan.

## Versionen

Die bisherige Basisversion `0.2.0` bleibt während der weiteren Bauphase bestehen. Veröffentlichte Entwicklungsstände erhalten eine fortlaufende vierte Stelle:

- `0.2.0.1`: erster Entwicklungsstand nach `0.2.0`
- `0.2.0.2`: zweiter Entwicklungsstand
- `0.2.0.n`: weitere Entwicklungsstände

Die vierte Stelle ist eine projektinterne Build-Nummer. Bereits veröffentlichte Tags bleiben unverändert. Eine neue dreiteilige Basisversion wird erst nach ausdrücklicher Freigabe festgelegt.
