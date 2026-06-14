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

Pächter dürfen ausschließlich eigene Daten sehen. Berechtigungen werden
serverseitig geprüft. Administratorkonten besitzen vollständigen Zugriff.
Vorstandsmitglieder erhalten keine pauschalen Vollrechte, sondern
ausdrücklich zugewiesene Berechtigungen. Bankdaten sind nur für
Administratoren sowie Konten mit dem gesonderten SEPA-Recht zugänglich.

### Globale Konfiguration und Rechtevorlagen

Administratoren können den sichtbaren Systemnamen ändern. Der Name ersetzt
`OKGV` in Navigation, Seitentitel, Rechnungs-PDFs und Transaktionsmails.

Administratoren verwalten wiederverwendbare Rechtevorlagen für
Vorstandsmitglieder. Eine Vorlage enthält eine fachlich verständliche Auswahl
einzelner Rechte, insbesondere Stammdaten, Zähler, Abrechnung, Preisvorlagen,
SEPA, Registrierungsprüfung und Zählerstandprüfung. Bei Zuweisung wird ein
Snapshot der Vorlage am Benutzerkonto gespeichert. Spätere Änderungen einer
Vorlage verändern bestehende Konten nicht automatisch.

Ein freigegebenes Pächterkonto kann durch einen Administrator zum
Vorstandsmitglied hochgestuft werden. Administratorkonten dürfen in der
Oberfläche nicht herabgestuft werden. Rollen- und Rechteänderungen werden
auditiert.

## Phasen

Die verbindliche Reihenfolge und aktuelle Phasenzuordnung stehen in
`PHASE_PLAN.md`. Die folgenden Abschnitte beschreiben die fachlichen
Anforderungen; bei abweichender Nummerierung gilt `PHASE_PLAN.md`.

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

- Administratoren sowie Vorstandsmitglieder mit Stammdatenrechten dürfen
  Mitglieder, Parzellen und Pächterzuordnungen lesen oder bearbeiten.
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

Wiederkehrende Kostenarten können in `billing_rate_templates` als
konfigurierbare Preisvorlagen verwaltet werden. Administrator und Vorstand
dürfen Vorlagen anlegen und bearbeiten; Kassierer dürfen aktive Vorlagen für
Abrechnungsperioden verwenden. Eine Vorlage enthält internen Schlüssel,
Bezeichnung, Beschreibung, Berechnungsart, Geltungsbereich, optionalen
Vorschlagsbetrag und Aktivstatus.

Beim Übernehmen einer Vorlage wird ein eigenständiger `billing_rates`-Snapshot
für die gewählte Abrechnungsperiode erstellt. Die fachlichen Regeln stammen
serverseitig aus der Vorlage, während der Betrag für die Periode bestätigt
oder geändert wird. Spätere Änderungen oder die Deaktivierung einer Vorlage
verändern bereits übernommene Preise und Rechnungen niemals.

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
- Anlage und Änderung von Preisvorlagen werden im Auditlog protokolliert.

### Phase 4: SEPA

Mandate, IBAN-Prüfung, Sammellastschriften, pain.008-Export, Rücklastschriften und Zahlungsstatus.

#### Technischer Standard

OKGV erzeugt SEPA-Basislastschriften nach dem aktuell gültigen EPC SDD Core
Rulebook 2025 Version 1.1 und den Customer-to-PSP Implementation Guidelines
auf Basis von `pain.008.001.08`. Exportiert werden ausschließlich
CORE-Lastschriften in EUR.

Die Gläubiger-ID und jede Mandatsreferenz sind verpflichtend und höchstens 35
Zeichen lang. IBANs werden anhand des von SWIFT geführten IBAN-Registers
strukturell und per Prüfziffer validiert. BIC-Angaben sind optional.
Unstrukturierte Anschriften werden nicht in den Export aufgenommen; dadurch
ist der Export bereits mit der ab 15. November 2026 geltenden Einschränkung
für unstrukturierte Adressen vereinbar.

#### SEPA-Einstellungen

Die Installation besitzt genau einen Satz aus Gläubigername, Gläubiger-ID,
Vereins-IBAN, optionaler BIC und Sammelbuchungswunsch. IBAN und BIC werden
verschlüsselt gespeichert. Vollständige Bankdaten erscheinen nur im
geschützten Bearbeitungsformular und im XML-Export.

#### Mandate

`sepa_mandates` verknüpft ein Mitglied mit einer eindeutigen
Mandatsreferenz, verschlüsselter IBAN, optionaler BIC, verschlüsseltem
Kontoinhaber, Unterschriftsdatum, Gültigkeitszeitraum, Mandatsart und Status.
Mandate werden nicht gelöscht. Wiederkehrende Mandate erzeugen beim ersten
Einzug `FRST`, danach `RCUR`; einmalige Mandate erzeugen `OOFF` und werden
nach Einreichung als abgelaufen markiert.

#### Sammellastschriften

`payment_batches` bündelt freigegebene, offene Rechnungen für einen
Einzugstag. Der Sammler speichert die zum Erstellungszeitpunkt geltenden
Gläubigerdaten verschlüsselt als unveränderlichen Snapshot. Jede Position
speichert zusätzlich einen unveränderlichen Snapshot von Betrag,
Mandatsreferenz, Unterschriftsdatum, Kontoinhaber, IBAN, BIC, End-to-End-ID
und Verwendungszweck. Der Export speichert einen SHA-256-Hash des erzeugten
XML-Inhalts. Erzeugung, Export, Einreichung und Verbuchung werden auditiert.

#### Zahlungsstatus und Rücklastschriften

Der Rechnungsinhalt bleibt nach Freigabe unveränderbar. Nur der getrennte
Zahlungsstatus darf von `open` über `pending` zu `paid` wechseln. Eine
Rücklastschrift wird mit ISO-Grundcode, optionaler Erläuterung und Datum als
eigener Vorgang am Sammlerposten gespeichert; die Rechnung erhält den Status
`returned` und kann erneut bearbeitet werden. Der ursprüngliche
Lastschrift-Snapshot bleibt erhalten.

#### Rechte in Phase 4

- Administratoren sowie Konten mit dem ausdrücklichen SEPA-Recht dürfen
  SEPA-Einstellungen, Mandate, Sammler, Exporte, Zahlungsstatus und
  Rücklastschriften verwalten.
- Wasserwart, Gartenwart und Pächter erhalten keinen Zugriff auf Bankdaten
  oder SEPA-Verwaltung.
- Listen zeigen IBANs nur maskiert mit den letzten vier Stellen.
- Bankdaten und vollständige XML-Inhalte werden nicht im Auditlog gespeichert.

### Phase 5: Pächterportal

Eigene Daten, Parzellen, Dokumente, Rechnungen und Zähler sowie
Zählerstandsmeldungen mit Foto und Freigabe.

#### Registrierung und Freigabe

Pächter können öffentlich eine Registrierungsanfrage mit Vorname, Nachname,
E-Mail-Adresse, Parzellennummer und Passwort stellen. Eine Anfrage erzeugt
noch kein aktives Benutzerkonto. Das Passwort wird ausschließlich gehasht
gespeichert. Die öffentliche Route wird rate-limitiert und liefert keine
internen Mitglieds- oder Pächterdaten aus.

Administrator oder Vorstand prüfen die Anfrage und wählen eines der aktuell
eingetragenen, noch nicht mit einem Benutzerkonto verbundenen Mitglieder der
angegebenen Parzelle aus. Erst die Freigabe erzeugt ein Benutzerkonto mit der
Rolle `tenant` und verknüpft es mit diesem Mitglied. Freigabe und Ablehnung
werden mit Bearbeiter, Zeitpunkt und optionaler Begründung historisiert und
auditiert. Kassierer, Wasserwart und Gartenwart dürfen Registrierungsanfragen
nicht bearbeiten.

Nach der Freigabe wird über den in `.env` konfigurierten Laravel-Mailer eine
deutsche Bestätigungsnachricht versendet. Das neue Konto bleibt bis zur
Bestätigung der signierten, zeitlich begrenzten E-Mail-Adresse für alle
geschützten Anwendungsbereiche gesperrt. Ein neuer Bestätigungslink kann
rate-limitiert angefordert werden. Bereits vor Einführung dieser Pflicht
bestehende Konten gelten bei der Migration als bestätigt, damit kein
Administrator ausgesperrt wird.

#### Portalzugriff

Das Pächterportal zeigt ausschließlich:

- den eigenen Mitgliedsdatensatz ohne interne Notizen,
- aktuell gepachtete Parzellen und deren Zähler,
- freigegebene Rechnungen, bei denen das Mitglied als Empfänger
  historisiert ist,
- für das Mitglied oder seine aktuellen Parzellen freigegebene private
  Dokumente,
- eigene Zählerstandsmeldungen und deren Prüfstatus.

Historische oder fremde Pächterzuordnungen erweitern den Portalzugriff nicht.
Rechnungszugriff bleibt wegen der historischen Empfängersnapshots auch nach
einem späteren Pächterwechsel erhalten.

#### Dokumente im Portal

Phase 5 stellt das lesende Dokumentenmodell und geschützte Downloads bereit.
Dokumente können einem Mitglied oder einer Parzelle zugeordnet und mit der
Sichtbarkeit `tenant` freigegeben werden. Dateien liegen ausschließlich im
privaten Storage. Anlage, Bearbeitung, Dokumenttypen und allgemeine
Dokumentenverwaltung folgen in Phase 6.

#### Zählerstandsmeldungen

Ein Pächter darf für aktive Zähler seiner aktuell gepachteten Parzellen einen
Stand mit Ablesedatum, optionaler Notiz und optionalem Foto melden. Erlaubt
sind JPEG, PNG und WebP bis 8 MiB. Uploads werden anhand von MIME-Typ und
Größe geprüft, erhalten serverseitig erzeugte Dateinamen und liegen im
privaten Storage.

Eine Meldung ist zunächst `pending` und verändert die abrechnungsrelevante
Zählerhistorie nicht. Administrator, Vorstand oder Wasserwart können sie
bestätigen oder mit Begründung ablehnen. Bei Bestätigung prüft dieselbe
Geschäftslogik wie bei einer manuellen Erfassung Laufzeit, Reihenfolge und
Plausibilität und erzeugt anschließend einen Zählerstand mit Quelle
`tenant`. Meldung, Prüfung und erzeugter Zählerstand bleiben dauerhaft
verknüpft und werden auditiert. Pächter können Meldungen nach dem Absenden
nicht verändern oder löschen.

#### Navigation und Aktionshinweise

Die Hauptnavigation wird fachlich gruppiert:

- `Mitglieder` enthält Mitgliederverwaltung und Registrierungsanfragen.
- `Zähler` enthält Zählerverwaltung und Zählerstandsmeldungen.
- `Finanzen` enthält Abrechnung, Preisvorlagen, Rechnungen und SEPA.
- Rechteverwaltung und globale Konfiguration befinden sich ausschließlich im
  Benutzermenü.

Ein zentraler Aktionshinweis zeigt als leuchtender Punkt an, wenn die
angemeldete Person in einem Bereich handeln muss. Der Punkt erscheint nur,
wenn die Rolle die Aufgabe serverseitig bearbeiten darf. In Phase 5 gilt
dies für wartende Registrierungsanfragen, wartende Zählerstandsmeldungen,
abgelehnte eigene Zählerstandsmeldungen sowie offene oder zurückgegebene
eigene Rechnungen. Künftige Phasen verwenden dasselbe System für weitere
handlungsbedürftige Vorgänge.

Die Oberfläche unterstützt einen hellen und einen dunklen Darstellungsmodus.
Die Auswahl wird lokal im Browser gespeichert, gilt bereits vor dem
Seitenaufbau und kann jederzeit über einen Schalter im Benutzermenü geändert
werden. Ohne gespeicherte Auswahl wird die Systemeinstellung verwendet.

### Phase 6: Dokumentenverwaltung

#### Dokumenttypen

Die zentrale Dokumentenverwaltung unterstützt:

- Pachtvertrag,
- Übergabeprotokoll,
- Kündigung,
- Rechnung und Rechnungsbeleg,
- Satzung,
- Protokoll,
- Foto,
- Sonstiges.

Ein Dokument besitzt Titel, optionale Beschreibung, Dokumenttyp,
Sichtbarkeit, optionalen Mitglieder- und Parzellenbezug sowie
Veröffentlichungs- und Archivierungszeitpunkte. Die Oberfläche bietet Suche
und Filter nach Typ, Sichtbarkeit und Archivstatus.

#### Dateien und Versionen

Dateien liegen ausschließlich im privaten Storage und erhalten serverseitig
erzeugte Namen. Erlaubt sind PDF, JPEG, PNG, WebP, reine Textdateien sowie
DOCX und XLSX bis 20 MiB. Dateiendung und vom Server erkannter MIME-Typ müssen
zu einem erlaubten Format passen. Ausführbare, HTML-, SVG- und
makrofähige Office-Dateien sind ausgeschlossen.

Eine hochgeladene Datei wird niemals überschrieben. Beim Ersetzen entsteht
eine neue unveränderliche Dateiversion; ältere Versionen bleiben für
berechtigte Verwalter abrufbar. Metadatenänderungen, Veröffentlichung,
Archivierung und jede neue Version werden auditiert. Dokumente werden nicht
hart gelöscht.

#### Sichtbarkeit und Zuordnung

- `internal`: ausschließlich Benutzer mit Dokumentenrecht,
- `tenant`: veröffentlichte Dokumente für das zugeordnete Mitglied oder
  aktuelle Pächter der zugeordneten Parzelle,
- `public`: veröffentlichte Dokumente über einen nicht erratbaren
  Freigabelink ohne Indexierung.

Pächterdokumente benötigen mindestens einen Mitglieder- oder Parzellenbezug.
Die Zuordnung zu einem Mitglied bleibt dauerhaft persönlich; ein
Parzellenbezug gewährt Portalzugriff nur während einer aktuellen Pacht.
Öffentliche Links werden bei Rücknahme der Veröffentlichung oder
Archivierung sofort unwirksam.

#### Rechte

Dokumentenverwaltung besitzt ein eigenes granulares Recht. Administratoren
besitzen es immer. Vorstandsmitglieder erhalten es ausdrücklich oder über
eine Rechtevorlage. Nur dieses Recht erlaubt interne Dokumente, Uploads,
Metadatenänderungen, Dateiversionen, Veröffentlichung und Archivierung.
Pächter behalten ausschließlich den bereits in Phase 5 definierten
lesenden Zugriff.

#### Rechnungen

Freigegebene Rechnungen bleiben unveränderliche, aus den historischen
Rechnungssnapshots erzeugte Systemdokumente. Sie werden in der zentralen
Dokumentenübersicht verlinkt, aber nicht als veränderbare Datei dupliziert.
Zusätzliche Rechnungsbelege können als eigener Dokumenttyp hochgeladen
werden. Der Zugriff auf Systemrechnungen setzt weiterhin das
Abrechnungsrecht voraus.

### Phase 7: Kommunikation

#### Rechte

Kommunikation erhält ein eigenes Benutzerrecht. Es erlaubt Serienmails,
Versandhistorie und allgemeine PDF-Briefe. Administratoren besitzen dieses
Recht immer. Vorstandsmitglieder erhalten es nur ausdrücklich oder über eine
Rechtevorlage. Die SMTP-Konfiguration einschließlich Testversand gehört zur
globalen Konfiguration und bleibt Administratoren vorbehalten.
Zahlungserinnerungen setzen zusätzlich das Abrechnungsrecht voraus, da sie
Rechnungs- und Zahlungsdaten enthalten.

#### SMTP

SMTP-Host, Port, Schema, Benutzername, Passwort, Absenderadresse und
Absendername werden im SMTP-Abschnitt der globalen Konfiguration verwaltet.
Benutzername und Passwort werden mit Laravel verschlüsselt gespeichert und
niemals im Auditlog ausgegeben. Nur Administratoren dürfen diese Werte ändern
oder einen Testversand an eine frei eingegebene, serverseitig validierte
Zieladresse auslösen. Die Zieladresse wird auditiert, aber nicht als
Systemeinstellung gespeichert. Die Konfiguration verwendet `smtp` mit
STARTTLS-Unterstützung oder `smtps`. Testversand ist pro Benutzer auf zehn
Nachrichten pro Minute begrenzt. Bei Überschreitung bleibt die Person auf der
Konfigurationsseite und erhält eine verständliche deutsche Meldung. Eine
erfolgreiche Testmeldung bestätigt ausdrücklich nur die Annahme durch den
SMTP-Server. Die vom Transport zurückgegebene Message-ID wird für die
Nachverfolgung beim Mailanbieter auditiert.

#### Serienmails

Eine Serienmail besitzt Betreff, Nachricht, Empfängergruppe, Ersteller,
Status, Zeitpunkte und Zähler für erfolgreiche oder fehlgeschlagene
Zustellungen. Unterstützte Empfängergruppen sind:

- aktive Mitglieder,
- aktuelle Pächter,
- Administratoren und Vorstandsmitglieder,
- Empfänger offener oder zurückgegebener Rechnungen,
- aktuelle Pächter von aktiven Zählern ohne Stand im laufenden Kalenderjahr.

Vor dem Versand werden Name, E-Mail-Adresse und optionaler Mitgliedsbezug je
Empfänger als Snapshot gespeichert. Doppelte E-Mail-Adressen werden innerhalb
einer Kampagne zusammengeführt. Datensätze ohne E-Mail-Adresse werden nicht
angeschrieben. Ein Versand wird je Empfänger mit Status, Zeitpunkt und
verständlicher Fehlerangabe historisiert. Passwörter und SMTP-Geheimnisse
werden weder in der Kampagne noch im Auditlog gespeichert.

#### PDF-Briefe

Ein allgemeiner Brief speichert Mitgliedsbezug, vollständige
Empfängeranschrift, Betreff, Inhalt und Ersteller als dauerhaften Snapshot.
Die PDF-Ausgabe verändert den Brief nicht. Briefe werden in Phase 7 nicht
automatisch in die aufgeschobene Dokumentenverwaltung übernommen.

#### PDF-Zahlungserinnerungen

Für freigegebene, bereits fällige Rechnungen mit Zahlungsstatus `open` oder
`returned` kann eine PDF-Zahlungserinnerung erzeugt werden. Sie enthält
Rechnungsnummer, Fälligkeit, Betrag und historische Rechnungsempfänger.
Dieser Vorgang setzt keine Mahnstufe, berechnet keine Gebühr und verändert die
Rechnung nicht. Mahnstufen, Fristen und Mahngebühren bleiben Phase 8
vorbehalten.

### Phase 8: Mahnwesen

#### Voraussetzungen und Rechte

Mahnungen setzen das granulare Recht `Abrechnungen und Rechnungen verwalten`
voraus. Sie dürfen ausschließlich für freigegebene, überfällige Rechnungen
mit Zahlungsstatus `open` oder `returned` erstellt werden. Pächter können nur
eigene, ausgestellte Mahnungen lesen und als PDF herunterladen.

#### Mahnstufen und Fristen

Eine Rechnung unterstützt maximal drei aufeinanderfolgende Mahnstufen. Die
erste Mahnung darf nach Ablauf der ursprünglichen Rechnungsfrist erstellt
werden. Eine weitere Stufe ist erst möglich, wenn die Zahlungsfrist der
vorherigen aktiven Mahnung abgelaufen ist. Übersprungene oder doppelte Stufen
sind ausgeschlossen.

Jede Mahnung speichert:

- eindeutige Mahnnummer,
- Rechnungsbezug und Rechnungsnummer als Snapshot,
- Mahnstufe,
- Ausstellungs- und neue Fälligkeit,
- ursprünglichen Rechnungsbetrag,
- Mahngebühr,
- Gesamtforderung,
- vollständige historische Rechnungsempfänger,
- optionalen verständlichen Hinweis,
- Ersteller und Erstellungszeitpunkt.

Gebühren sind optional, nicht negativ und werden nicht rückwirkend in die
freigegebene Rechnung geschrieben. Die Gesamtforderung einer Mahnung besteht
aus dem ursprünglichen Rechnungsbetrag plus den Gebühren aller aktiven
Mahnstufen dieser Rechnung.

#### Unveränderlichkeit und Stornierung

Ausgestellte Mahnungen sind unveränderliche historische Snapshots und können
nicht gelöscht werden. Eine sachlich fehlerhafte Mahnung kann mit
Pflichtbegründung storniert werden. Stornierung beendet ihre Wirkung, erhält
aber Datensatz und PDF-Historie. Nach einer Stornierung kann dieselbe Stufe
erneut korrekt ausgestellt werden. Erstellung und Stornierung werden
auditiert.

#### Zahlung und Anzeige

Sobald die Rechnung als bezahlt markiert ist, sind keine weiteren Mahnungen
möglich. Vorhandene Mahnungen bleiben historisch sichtbar. Die
Rechnungsübersicht zeigt überfällige offene Rechnungen und die aktuelle
Mahnstufe. Berechtigte Finanzkonten erhalten hierfür einen Aktionshinweis.
Die PDF-Ausgabe enthält Rechnungsnummer, Mahnnummer, Mahnstufe, Frist,
Einzelgebühr, bisherige aktive Gebühren und Gesamtforderung.

### Phase 9: Arbeitsstunden und Strafzahlungen

#### Arbeitsstundenkonto

Für jede Parzelle kann je Abrechnungsperiode genau ein Arbeitsstundenkonto
geführt werden. Die Pflicht gilt damit einmal für den Pachtvertrag und nicht
mehrfach für jeden Mitpächter. Es speichert:

- geforderte Pflichtstunden,
- zusätzlich manuell anerkannte Stunden,
- bestätigte Stunden aus Arbeitseinsätzen,
- bestätigte Pächtermeldungen,
- die Summe aller anerkannten Stunden,
- serverseitig berechnete Fehlstunden,
- den historischen Betrag je Fehlstunde,
- den serverseitig berechneten Strafbetrag,
- optionale interne Notizen.

Fehlstunden entsprechen höchstens der positiven Differenz aus Pflichtstunden
und anerkannten Gesamtstunden. Mehrarbeit erzeugt weder negative Fehlstunden
noch eine Gutschrift. Ohne angelegtes Parzellenkonto entsteht keine
Fehlstundenposition.

Die globale Vereinskonfiguration enthält Pflichtstunden je Parzelle und
Betrag je Fehlstunde. Beim Sammelvorbereiten einer Abrechnungsperiode werden
für alle zum Periodenende vergebenen Parzellen fehlende Konten mit diesen
Werten angelegt. Die Werte werden als historischer Periodenstand kopiert.

#### Abrechnung

Bei der Berechnung einer Abrechnungsperiode werden positive Strafbeträge als
eigene Rechnungsposition `WORK_HOURS_PENALTY` übernommen. Die Position
speichert Parzelle, Pflichtstunden, geleistete Stunden, Fehlstunden,
Stundensatz und Gesamtbetrag als historischen Snapshot.

Stehen mehrere Mitglieder gemeinsam im Pachtvertrag, entsteht genau eine
Fehlstundenposition für die Parzelle auf der gemeinsamen Rechnung.
Freigegebene Rechnungen und ihre Fehlstundenpositionen bleiben unveränderlich.

#### Rechte und Änderungen

Arbeitsstunden verwenden das granulare Recht `Abrechnungen und Rechnungen
verwalten`. Pächter, Wasserwart und Gartenwart erhalten ohne dieses Recht
keinen Zugriff auf die Verwaltung.

Arbeitsstunden können nur in Entwurfsperioden und berechneten
Zwischenständen geändert werden. Eine Änderung an einem Zwischenstand
verwirft die noch nicht freigegebenen Rechnungsentwürfe auditierbar und setzt
die Periode auf Entwurf zurück. Freigegebene und archivierte Perioden sind
gesperrt. Konten werden nicht gelöscht; Korrekturen erfolgen durch
nachvollziehbare Änderungen und werden auditiert.

Offene Fehlstunden in Entwurfsperioden erzeugen für berechtigte Finanzkonten
einen Aktionshinweis.

#### Pächtermeldungen

Pächter können über ihr Portal Arbeitsstunden für eine zum Tätigkeitsdatum
selbst gepachtete Parzelle melden. Eine Meldung enthält Datum, positive
Stundenzahl, verpflichtende Tätigkeitsbeschreibung und optional ein privates
Foto als Nachweis. Erlaubt sind JPEG, PNG und WebP bis 8 MiB.

Die Meldung ist zunächst `pending` und verändert das Parzellenkonto nicht.
Konten mit dem Recht `Arbeitseinsätze verwalten` können sie bestätigen oder
mit Pflichtbegründung ablehnen. Erst eine Bestätigung übernimmt die Stunden.
Gemeinsame Pächter melden in dasselbe Parzellenkonto. Meldungen können nach
dem Absenden nicht geändert oder gelöscht werden; Prüfung, Prüfer und
Begründung bleiben dauerhaft erhalten und werden auditiert.

### Vorgezogene Phase 12: Arbeitseinsätze

Phase 12 wird auf ausdrücklichen Wunsch direkt nach Phase 9 umgesetzt, weil
bestätigte Teilnahmen die Datengrundlage der Arbeitsstundenkonten bilden.
Phase 10 und 11 bleiben fachlich unverändert offen und werden anschließend
bearbeitet.

#### Termine

Ein Arbeitseinsatz gehört genau zu einer Abrechnungsperiode und speichert
Bezeichnung, Beschreibung, Ort, Beginn, Ende, internen Hinweis, Ersteller und
Status. Beginn und Ende müssen vollständig innerhalb der Periode liegen.

Status:

- `planned`: Termin geplant, Teilnahmen können vorgemerkt werden,
- `completed`: Termin beendet, geleistete Stunden können bestätigt werden,
- `cancelled`: Termin abgesagt, seine Stunden zählen nicht.

Ein zukünftiger Termin kann nicht abgeschlossen werden. Abgesagte Einsätze
bleiben historisch erhalten und können nicht gelöscht werden.

#### Teilnehmer und Stundenübernahme

Jedes Mitglied kann einem Einsatz höchstens einmal zugeordnet werden.
Dabei wird festgehalten, für welche aktuell gepachtete Parzelle die Stunden
gelten.
Teilnahmen besitzen den Status `registered`, `confirmed` oder `absent`.
Nur `confirmed` mit einem positiven Stundenwert und nur bei einem
abgeschlossenen Einsatz wird in das Arbeitsstundenkonto übernommen.

Das Arbeitsstundenkonto trennt:

- zusätzlich manuell anerkannte Stunden,
- bestätigte Stunden aus Arbeitseinsätzen,
- bestätigte Pächtermeldungen,
- die daraus berechnete Gesamtsumme.

Korrektur, Abwesenheit oder Absage berechnen den automatischen Anteil neu.
Existiert noch kein Arbeitsstundenkonto, wird eines mit null Pflichtstunden
und null Strafsatz angelegt; Pflichtstunden und Strafsatz werden weiterhin
von der Finanzverwaltung festgelegt. Dadurch werden keine fachlichen
Standardwerte erfunden.

#### Rechte, Historie und Aktionshinweise

Das eigene granulare Recht `Arbeitseinsätze verwalten` erlaubt Termine,
Teilnehmer und Bestätigungen, aber keinen Zugriff auf Preise, Rechnungen oder
SEPA. Administrator und Vorstand erhalten das Recht standardmäßig; außerdem
erhält es der Gartenwart. Individuelle Rechtevorlagen können davon abweichen.

Änderungen sind ausschließlich in Entwurfsperioden und berechneten
Zwischenständen möglich. Abrechnungsrelevante Änderungen verwerfen
vorhandene Rechnungsentwürfe über den bestehenden sicheren
Zwischenstandsmechanismus. Freigegebene und archivierte Perioden sind auch
auf Modellebene unveränderlich. Anlage, Änderung, Teilnahmebestätigung und
Stundenübernahme werden auditiert.

Vergangene, weiterhin geplante Einsätze erzeugen für berechtigte Konten einen
Aktionshinweis, damit Abschluss oder Absage nicht vergessen werden.

### Phase 10, 11 und 13 bis 18

Warteliste, Inventar, CSV-Import und -Export, DSGVO, Vereinseinstellungen,
Nummernkreise, Pächterwechsel und später ein Lageplan.

## Versionen

Die bisherige Basisversion `0.2.0` bleibt während der weiteren Bauphase bestehen. Veröffentlichte Entwicklungsstände erhalten eine fortlaufende vierte Stelle:

- `0.2.0.1`: erster Entwicklungsstand nach `0.2.0`
- `0.2.0.2`: zweiter Entwicklungsstand
- `0.2.0.n`: weitere Entwicklungsstände

Die vierte Stelle ist eine projektinterne Build-Nummer. Bereits veröffentlichte Tags bleiben unverändert. Eine neue dreiteilige Basisversion wird erst nach ausdrücklicher Freigabe festgelegt.
