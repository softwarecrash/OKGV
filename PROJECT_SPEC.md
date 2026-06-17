# OKGV Projektspezifikation

## Produkt

OKGV (Open Kleingarten Verwaltung) ist eine moderne, sichere und selbsthostbare Verwaltungssoftware für Kleingartenvereine mit 20 bis 500 oder mehr Parzellen.

Slogan: **Die freie Verwaltungssoftware für Kleingartenvereine.**

Domain: `okgv.de`

## Lizenz

Der OKGV-Projektcode wird ausschließlich unter der GNU Affero General Public
License Version 3 (`AGPL-3.0-only`) veröffentlicht. Die frühere MIT-Lizenz
findet auf neue Veröffentlichungen keine Anwendung.

Jede über ein Netzwerk angebotene Instanz zeigt dauerhaft einen
Quellcode-Link. Bei unveränderten Installationen darf dieser auf das
öffentliche OKGV-Repository verweisen. Betreiber veränderter Versionen müssen
darüber den vollständigen korrespondierenden Quellcode der tatsächlich
betriebenen Version gemäß Abschnitt 13 der GNU AGPLv3 bereitstellen.
Lizenzhinweise von Abhängigkeiten und anderen Drittanbieter-Komponenten
bleiben unverändert erhalten.

## Architektur

- Eine Installation verwaltet genau einen Verein.
- Die Anwendung ist nicht mandantenfähig.
- Jede Installation besitzt eine eigene Datenbank, Benutzer, Dateien, Konfiguration und Backups.
- Während der frühen Entwicklung läuft OKGV direkt im Linux-LXC.
- Docker und Produktionsdeployment beginnen erst nach Abschluss der Kernmodule.
- Ein vorgeschalteter HTTPS-Reverse-Proxy wird ausschließlich über explizit
  konfigurierte vertrauenswürdige Proxy-IP-Adressen akzeptiert. Laravel muss
  das weitergereichte Schema für sichere Weiterleitungen, Asset-URLs und
  Session-Cookies verwenden.
- In einer öffentlich erreichbaren Testinstanz mit zusätzlichem direktem
  HTTP-Zugang im internen Netz folgt das `Secure`-Attribut des Session-Cookies
  automatisch dem erkannten Request-Schema. Produktive Installationen sollen
  ausschließlich HTTPS anbieten.

## Technologie

- PHP 8.3 oder neuer
- Laravel
- Bootstrap 5
- Alpine.js
- MariaDB
- Node.js und npm

## Entwicklungs- und Demo-Daten

Der gewöhnliche Datenbank-Seeder erzeugt keine Benutzer oder Fachdaten
automatisch. Für manuelle Oberflächentests steht ein ausdrücklich
aufzurufender Demo-Seeder bereit. Er erzeugt fünf mit `DEMO-` markierte
Parzellen mit vier Pächterkonten und einem Vorstandsmitglied sowie
zusammenhängende Testdaten für 2024 bis 2026.

Der Demo-Bestand umfasst Wasser- und Stromzähler, einen Zählerwechsel,
Zählerstände, eine offene Zählerstandsmeldung, Abrechnungsperioden,
historische Preise, Arbeitsstundenkonten, Arbeitseinsätze und
Pächtermeldungen. Ein eigener Löschbefehl entfernt ausschließlich anhand
eindeutiger Demo-Kennzeichen ermittelte Datensätze. Normale Vereinsdaten
dürfen weder beim erneuten Anlegen noch beim Entfernen verändert werden.
Der Demo-Seeder prüft vor jeder Änderung, ob für 2024 bis 2026 bereits
andere Abrechnungsperioden existieren. Bei einer zeitlichen Überschneidung
bricht er ohne Anlage von Demo-Daten ab. Damit gilt das Verbot
überschneidender Abrechnungsperioden auch für Entwicklungsdaten.

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

Freigegebene Rechnungen werden zusätzlich als PDF im privaten Storage
archiviert. Spätere Downloads verwenden bevorzugt diese archivierte Datei und
erzeugen nur für ältere Bestandsdaten ohne Archivdatei dynamisch neu.

## Rollen

- Vorstand
- Kassierer
- Wasserwart
- Gartenwart
- Pächter

Pächter dürfen ausschließlich eigene Daten sehen. Berechtigungen werden
serverseitig geprüft. Der technische Administrator ist kein Vereinsamt,
sondern ein separates Konto-Kennzeichen für Systempflege, globale
Konfiguration, Rechteverwaltung, Backups und Nummernkreise. Dieses Kennzeichen
vergibt keine automatische Einsicht in Mitglieder-, Parzellen-, Abrechnungs-,
SEPA- oder Dokumentdaten.

Ein Konto kann gleichzeitig technischer Administrator und Pächter sein. In
diesem Fall sieht es im Pächterportal ausschließlich die über `members.user_id`
verknüpften eigenen Daten. Vereinsfachrechte entstehen nur über die
Vereinsrolle und ausdrücklich zugewiesene Berechtigungen.

Vorstandsmitglieder erhalten keine pauschalen Vollrechte, sondern
ausdrücklich zugewiesene Berechtigungen. Bankdaten sind nur für Konten mit
dem gesonderten SEPA-Recht zugänglich.

### Globale Konfiguration und Rechtevorlagen

Technische Administratoren können den sichtbaren Systemnamen ändern. Der Name ersetzt
`OKGV` in Navigation, Seitentitel, Rechnungs-PDFs und Transaktionsmails.

Technische Administratoren verwalten wiederverwendbare Rechtevorlagen für
Vorstandsmitglieder. Eine Vorlage enthält eine fachlich verständliche Auswahl
einzelner Rechte, insbesondere Stammdaten, Zähler, Abrechnung, Preisvorlagen,
SEPA, Registrierungsprüfung und Zählerstandprüfung. Bei Zuweisung wird ein
Snapshot der Vorlage am Benutzerkonto gespeichert. Spätere Änderungen einer
Vorlage verändern bestehende Konten nicht automatisch.

Ein freigegebenes Pächterkonto kann durch technische Administratoren oder
Vorstandsmitglieder zum Vorstandsmitglied hochgestuft und wieder zum Pächter
zurückgestuft werden. Vorstandsmitglieder dürfen dabei keine technischen
Administratorrechte und keine individuellen Sonderrechte vergeben. Technische
Administratoren können weitere technische Administratoren ernennen oder dieses
Kennzeichen entfernen, solange mindestens ein technischer Administrator
bestehen bleibt. Rollen- und Rechteänderungen werden auditiert.

## Phasen

Die verbindliche Reihenfolge und aktuelle Phasenzuordnung stehen in
`PHASE_PLAN.md`. Die folgenden Abschnitte beschreiben die fachlichen
Anforderungen; bei abweichender Nummerierung gilt `PHASE_PLAN.md`.

### Phase 0: Projektbasis

Laravel, Git, Bootstrap, Alpine.js, Login, Logout, Passwort-Reset, Passwortänderung im eingeloggten Konto, Dashboard, Rollenmodell, Policies, Security-Header und Auditlog-Basis. Die Abmeldung erfolgt ausschließlich über ein natives, CSRF-geschütztes POST-Formular und funktioniert unabhängig von JavaScript.

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

Eine Abrechnungsperiode ist ein Rechnungslauf mit eindeutiger Bezeichnung,
Start- und Enddatum, Fälligkeitsdatum und einem Status: `draft`,
`calculated`, `approved` oder `archived`. Berechnung, Freigabe und
Archivierung werden mit eigenen Zeitstempeln dokumentiert. Der fachliche
Leistungszeitraum wird nicht pauschal aus dem Rechnungslauf abgeleitet,
sondern für jeden Preis einzeln gespeichert.

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
Vorschlagsbetrag, Abrechnungsart, Zeitanteilsregel und Aktivstatus.

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
- eine Abrechnungsart: `advance` für Vorauszahlung oder `arrears` für
  Nachberechnung
- einen eigenen Leistungsbeginn und ein eigenes Leistungsende
- die Regel, ob Ein- und Austritte taggenau anteilig berechnet werden
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

#### Vorauszahlung und Nachberechnung

Ein Rechnungslauf kann Preise mit unterschiedlichen Leistungszeiträumen
enthalten. Damit können beispielsweise Pacht, Versicherung,
Bereitstellungsgebühren und Mitgliedsbeiträge für das Folgejahr im Voraus
berechnet werden, während Strom und Wasser für das zurückliegende Jahr nach
dem tatsächlichen Verbrauch abgerechnet werden.

Vorlagen mit `advance` schlagen beim Übernehmen den um ein Jahr verschobenen
Zeitraum des Rechnungslaufs vor. Vorlagen mit `arrears` schlagen den Zeitraum
des Rechnungslaufs vor. Beide Datumswerte bleiben im konkreten Preis sichtbar
und prüfbar. Die Abrechnungsart ist zusätzlich Bestandteil des historischen
Rechnungspositions-Snapshots.

Verbrauchspreise werden nicht über einen pauschalen Zeitfaktor gekürzt. Sie
verwenden ausschließlich Zählerstände aus dem Schnitt zwischen
Leistungszeitraum und tatsächlichem Pachtzeitraum. Bei einem Pächterwechsel
ist deshalb ein Zählerstand zum Übergabedatum erforderlich, um den Verbrauch
beiden Vertragsparteien korrekt zuzuordnen.

#### Rechnungserzeugung

Eine Abrechnung wird für jedes Mitglied erzeugt, für das mindestens eine
abrechnungsrelevante Position entsteht. Mitgliedsbezogene Kosten verwenden
den Schnitt aus `joined_at`/`left_at` und dem Leistungszeitraum des Preises.
Parzellenbezogene Kosten verwenden den Schnitt aus der Hauptpächterhistorie
und dem Leistungszeitraum. Die Parzellen eines Mitglieds werden in einer
gemeinsamen Rechnung zusammengefasst. Mitpächter, deren Vertragszeitraum die
abgerechnete Hauptpacht überlappt, werden als gemeinsame Rechnungsempfänger
gespeichert.

Automatisch berechenbare Positionen:

- Pacht und Umlagen anhand der Parzellenfläche
- Strom und Wasser anhand der historischen Zählerstände
- feste Bereitstellungs- und Mitgliedskosten
- zugeordnete Versicherungen, Sonderumlagen und Zusatzleistungen
- manuelle Positionen mit dokumentierter Menge

Bei aktivierter Zeitanteilsregel werden feste, flächenbezogene und
zugeordnete Kosten taggenau berechnet. Der Faktor ist:

`abrechnungsrelevante Kalendertage / Kalendertage des Leistungszeitraums`

Start- und Endtag zählen jeweils mit. Pächterwechsel innerhalb eines
Leistungszeitraums erzeugen getrennte Teilbeträge für die jeweiligen
Hauptpächter. Mitgliedseintritt und -austritt werden unabhängig von der
Pächterhistorie behandelt. Faktor, Leistungszeitraum und tatsächlich
berücksichtigte Teilzeiträume werden in jeder Rechnungsposition gespeichert.

Die anteilige Neuberechnung gilt für Entwürfe und berechnete Zwischenstände.
Eine bereits freigegebene Vorauszahlungsrechnung bleibt unveränderbar. Wird
ein Austritt oder Pächterwechsel erst nach der Freigabe bekannt, darf die
Historie nicht umgeschrieben werden; eine spätere Gutschrift oder
Korrekturrechnung benötigt einen eigenen, noch zu implementierenden
Korrekturbeleg.

Rechnungsnummern werden über den konfigurierbaren Nummernkreis aus Phase 17
vergeben. Das Standardformat lautet `YYYY-NNNNN` und beginnt je Kalenderjahr
neu.

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

Pächter und andere Konten mit eigener Mitgliedszuordnung können im
Pächterportal eigene SEPA-Mandate hinterlegen und für zukünftige Einzüge
widerrufen. Diese Self-Service-Mandate erscheinen in der zentralen
Mandatsübersicht, werden verschlüsselt gespeichert und auditiert. Ein
Widerruf beendet keine bereits eingereichten Bankvorgänge rückwirkend.

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

Pächter, Vorstandsmitglieder, technische Helfer oder spätere Benutzer können
öffentlich eine Registrierungsanfrage mit Vorname, Nachname, E-Mail-Adresse,
optional Parzellennummer und Passwort stellen. Die Anfrage erzeugt sofort ein
Benutzerkonto mit der Rolle `tenant`, das bis zur Freigabe keine Vereinsdaten
oder Pächterdaten erhält. Das Passwort wird ausschließlich gehasht
gespeichert. Die öffentliche Route wird rate-limitiert und liefert keine
internen Mitglieds- oder Pächterdaten aus. Die E-Mail-Bestätigung wird direkt
nach der Registrierung versendet.

Administrator oder Vorstand prüfen die Anfrage. Wenn eine Parzellennummer
angegeben wurde, muss eines der aktuell eingetragenen, noch nicht mit einem
Benutzerkonto verbundenen Mitglieder dieser Parzelle ausgewählt werden. Ohne
Parzellennummer darf die Anfrage auch ohne Mitgliedsverknüpfung freigegeben
werden; Mitglieds- und Parzellenzuordnung können später ergänzt werden. Die
Freigabe aktiviert die Anfrage fachlich und markiert die E-Mail als bestätigt,
falls die externe Zustellung der Bestätigungsmail nicht funktioniert hat.
Freigabe und Ablehnung werden mit Bearbeiter, Zeitpunkt und optionaler
Begründung historisiert und auditiert. Kassierer, Wasserwart und Gartenwart
dürfen Registrierungsanfragen nicht bearbeiten.

Die Prüfübersicht bewertet bei angegebener Parzelle ausschließlich die
freigabefähigen Mitglieder dieser Parzelle anhand von E-Mail und Namen. Ohne
Parzellennummer werden noch nicht verknüpfte Mitglieder allgemein als
optionale Zuordnung angeboten. Der beste plausible Treffer wird als
unverbindliche Empfehlung markiert; eine automatische Freigabe oder globale
Zuordnung allein anhand der E-Mail findet nicht statt. Vor der
Freigabe zeigt die Oberfläche Mitglieds- und Registrierungsadresse gemeinsam.
Bei abweichenden E-Mail-Adressen entscheidet der Prüfer ausdrücklich, ob die
bisherige Kontaktadresse im Mitgliedsstammsatz erhalten bleibt oder durch die
Registrierungsadresse ersetzt wird. Die Login-Adresse des neuen Kontos ist
immer die anschließend zu bestätigende Registrierungsadresse.

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

Berechtigte Prüfer und der meldende Pächter sehen ein Foto innerhalb einer
geschützten Modalvorschau statt über einen erzwungenen Download. Die Vorschau
unterstützt Zoom und das Verschieben eines vergrößerten Ausschnitts. Das Bild
wird erst beim Öffnen geladen und beim Schließen aus dem Modal entfernt. Die
private Route liefert ausschließlich nach erfolgreicher Policy-Prüfung eine
Inline-Antwort mit korrektem MIME-Typ und `nosniff`.

Eine Meldung ist zunächst `pending` und verändert die abrechnungsrelevante
Zählerhistorie nicht. Administrator, Vorstand oder Wasserwart können sie
bestätigen oder mit Begründung ablehnen. Bei Bestätigung prüft dieselbe
Geschäftslogik wie bei einer manuellen Erfassung Laufzeit, Reihenfolge und
Plausibilität und erzeugt anschließend einen Zählerstand mit Quelle
`tenant`. Meldung, Prüfung und erzeugter Zählerstand bleiben dauerhaft
verknüpft und werden auditiert. Pächter können Meldungen nach dem Absenden
nicht verändern oder löschen.

Kann eine Meldung wegen Datum, Reihenfolge oder rückläufigem Wert nicht
bestätigt werden, muss die Prüfübersicht den konkreten Grund sichtbar
ausgeben und die betroffene Zeile markieren. Eine falsche Meldung wird
begründet abgelehnt und anschließend vom Pächter neu eingereicht. Erfolgreich
bestätigte und abgelehnte Meldungen bleiben als nachvollziehbare Historie
sichtbar.

Die Prüfübersicht zeigt zu jeder Meldung den zeitlich vorherigen wirksamen
Zählerstand mit Datum und Einheit. Korrekturen bestehender Ablesungen werden
dabei berücksichtigt; ohne frühere Ablesung dient der Einbaustand als
Vergleich. Rückläufige Meldungen werden bereits vor dem Bestätigungsversuch
deutlich markiert.

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

Eine abgelehnte eigene Zählerstandsmeldung gilt nur bis zur nächsten Meldung
desselben Pächters für denselben Zähler als offen. Sobald ein korrigierter
Stand erneut eingereicht wurde, verschwindet der Aktionshinweis unabhängig
davon, ob die neue Meldung noch geprüft wird oder bereits bestätigt ist.
Dasselbe gilt für abgelehnte Arbeitsstundenmeldungen je Pächter und Parzelle.
Bei Arbeitsstundenmeldungen kann der betroffene Pächter den Hinweis zusätzlich
als gelesen markieren, wenn keine neue Meldung nötig ist. Der Ablehnungsgrund
bleibt in beiden Fällen dauerhaft in der Historie sichtbar.
Jeder Aktionspunkt muss auf seiner Zielseite durch eine verständliche
Aufgabenbox, einen weiteren Punkt oder eine hervorgehobene Zeile eindeutig
erklärbar sein.

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

Ein Dokument besitzt eine eindeutige, automatisch vergebene Dokumentnummer,
Titel, optionale Beschreibung, Dokumenttyp,
Sichtbarkeit, optionalen Mitglieder- und Parzellenbezug sowie
Veröffentlichungs- und Archivierungszeitpunkte. Die Oberfläche bietet Suche
nach Nummer, Titel und Dateiname sowie Filter nach Typ, Sichtbarkeit und
Archivstatus.

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

#### Mailversand

SMTP-Host, Port, Schema, Benutzername, Passwort, Sendmail-Pfad,
Absenderadresse und Absendername werden im Mailversand-Abschnitt der globalen
Konfiguration verwaltet.
Benutzername und Passwort werden mit Laravel verschlüsselt gespeichert und
niemals im Auditlog ausgegeben. Nur Administratoren dürfen diese Werte ändern
oder einen Testversand an eine frei eingegebene, serverseitig validierte
Zieladresse auslösen. Die Zieladresse wird auditiert, aber nicht als
Systemeinstellung gespeichert. Die Konfiguration verwendet `smtp` mit
STARTTLS-Unterstützung, `smtps` oder bewusst unverschlüsseltes SMTP für lokale
Relays wie `localhost:25`, bei denen kein öffentliches TLS-Zertifikat
vorhanden ist. Zusätzlich kann Sendmail des Webhostings mit konfigurierbarem
Sendmail-Pfad verwendet werden, wenn der Anbieter lokale Mailzustellung
bereitstellt. Testversand ist pro Benutzer auf zehn Nachrichten pro Minute
begrenzt. Bei Überschreitung bleibt die Person auf der Konfigurationsseite
und erhält eine verständliche deutsche Meldung. Eine
erfolgreiche Testmeldung bestätigt ausdrücklich nur die Annahme durch den
konfigurierten Mailtransport. Die vom Transport zurückgegebene Message-ID wird
für die Nachverfolgung beim Mailanbieter auditiert.

Wenn `MAIL_MAILER=smtp` oder `MAIL_MAILER=sendmail` aktiv in der `.env`
gesetzt ist, gilt die Mailkonfiguration als serverseitig verwaltet. OKGV
übernimmt dann Host, Port, Schema, Sendmail-Pfad, Zugangsdaten und
Absenderdaten aus der `.env`, zeigt sie in der globalen Konfiguration nur
schreibgeschützt an und blockiert Änderungen über das Webinterface. Sind diese
Werte auskommentiert oder ist `MAIL_MAILER=log` aktiv, bleibt die
Mailkonfiguration im Webinterface bearbeitbar.

#### Serienmails

Eine Serienmail besitzt Betreff, Nachricht, Empfängergruppe, Ersteller,
Status, Zeitpunkte und Zähler für erfolgreiche oder fehlgeschlagene
Zustellungen. Unterstützte Empfängergruppen sind:

- aktive Mitglieder,
- aktuelle Pächter,
- Administratoren und Vorstandsmitglieder,
- Empfänger offener oder zurückgegebener Rechnungen,
- Pächter aktiver Zähler ohne Endstand zur letzten beendeten
  Abrechnungsperiode.

Vor dem Versand werden Name, E-Mail-Adresse und optionaler Mitgliedsbezug je
Empfänger als Snapshot gespeichert. Doppelte E-Mail-Adressen werden innerhalb
einer Kampagne zusammengeführt. Datensätze ohne E-Mail-Adresse werden nicht
angeschrieben. Ein Versand wird je Empfänger mit Status, Zeitpunkt und
verständlicher Fehlerangabe historisiert. Passwörter und SMTP-Geheimnisse
werden weder in der Kampagne noch im Auditlog gespeichert.

Die Empfängergruppe für fehlende Zählerstände wird erst nach Ende der
letzten noch bearbeitbaren Abrechnungsperiode gebildet. Sie enthält Pächter,
deren am Periodenende aktive Zähler keinen Endstand mit dem Datum des
Periodenendes besitzen. Während einer laufenden Periode bleibt diese Gruppe
leer. Die Meldung darf nach dem Stichtag eingereicht werden; maßgeblich ist
das Ablesedatum.

#### PDF-Briefe

Ein allgemeiner Brief speichert Mitgliedsbezug, vollständige
Empfängeranschrift, Betreff, Inhalt und Ersteller als dauerhaften Snapshot.
Die Auswahl eines Mitglieds befüllt Name und Anschrift im Formular sofort,
damit die verwendeten Daten vor dem Speichern sichtbar und bei Bedarf
prüfbar sind. Ohne Mitgliedsauswahl kann eine freie Anschrift eingetragen
werden.
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
noch eine Gutschrift.

Die globale Vereinskonfiguration enthält Pflichtstunden je Parzelle und
Betrag je Fehlstunde. Beim Anlegen oder zeitlichen Ändern einer
Abrechnungsperiode werden für alle innerhalb des Zeitraums wenigstens einen
Tag vergebenen Parzellen automatisch Konten angelegt. Die
Vereins-Pflichtstunden sind ein Jahreswert und werden taggenau mit dem Anteil
der belegten Kalendertage an den Kalendertagen der Abrechnungsperiode
multipliziert. Überschneidende Mitpächterzeiträume zählen je Parzelle nur
einmal. Ein lückenloser Pächterwechsel innerhalb des Jahres lässt die volle
Parzellenpflicht bestehen; Leerstandszeiten reduzieren sie.

Entsteht oder ändert sich später eine Pächterzuordnung, synchronisiert OKGV
die Konten aller passenden, bearbeitbaren Perioden automatisch. Jahreswert,
Belegungsfaktor und errechnete Pflichtstunden werden historisch gespeichert.
Eine ausdrücklich manuell geänderte Pflichtstundenzahl bleibt als
gekennzeichnete Abweichung erhalten. Freigegebene und archivierte Perioden
werden nicht überschrieben.

Die Parzellendetailansicht zeigt die Arbeitsstundenkonten aller Perioden mit
Pflichtstunden, manuell anerkannten Stunden, Arbeitseinsätzen,
Pächtermeldungen, Gesamtleistung, Fehlstunden und Fehlbetrag. Berechtigte
Finanzkonten können manuell anerkannte Stunden direkt dort ändern. Fehlende
Konten werden nicht manuell vorbereitet. Pächter gelangen von ihrer Parzelle
mit vorausgewählter Parzelle zur eigenen Arbeitsstundenmeldung. Zur
eindeutigen Unterscheidung zeigt die Parzellendetailansicht neben dem Namen
auch den vollständigen Zeitraum jeder Abrechnungsperiode.

Pächter melden geleistete Arbeitszeit in Viertelstunden von 0,25 bis
24 Stunden. Ganze Zahlen sowie deutsche Dezimaleingaben mit Komma werden
akzeptiert und serverseitig normalisiert. Unzulässige Zwischenwerte erhalten
eine verständliche Erklärung mit Eingabebeispielen.

#### Abrechnung

Bei der Berechnung einer Abrechnungsperiode werden positive Strafbeträge als
eigene Rechnungsposition `WORK_HOURS_PENALTY` übernommen. Die Position
speichert Parzelle, Pflichtstunden, Belegungsfaktor, geleistete Stunden,
Fehlstunden, Stundensatz und Gesamtbetrag als historischen Snapshot.

Stehen mehrere Mitglieder gemeinsam im Pachtvertrag, entsteht genau eine
Fehlstundenposition für die Parzelle auf der gemeinsamen Rechnung.
Wechselt der Hauptpächter innerhalb der Periode, wird der Strafbetrag nach
den jeweiligen Hauptpächtertagen auf die Rechnungen aufgeteilt.
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

Offene Fehlstunden in laufenden Entwurfsperioden sind ein normaler
Jahreszwischenstand und erzeugen keinen Aktionshinweis. Berechtigte
Vorstands- und Administrationskonten erhalten nur für noch zu prüfende
Arbeitsstundenmeldungen einen Punkt.

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

Berechtigte Vorstands- und Administrationskonten können Arbeitsstunden
stellvertretend für alle verpachteten Parzellen erfassen, damit auch Pächter
ohne eigenes Onlinekonto berücksichtigt werden können. Diese Einträge werden
direkt als bestätigt gespeichert, auditierbar als stellvertretende Erfassung
gekennzeichnet und im Parzellenkonto den bestätigten Pächtermeldungen
zugerechnet. Die Erfassungsmaske zeigt vorhandene offene Stunden der
aktuellsten bearbeitbaren Abrechnungsperiode als Orientierung an. Wenn das
erfassende Konto selbst aktuelle Parzellen besitzt, stehen diese oben in der
Auswahl, sind als eigene Parzellen markiert und werden bevorzugt
vorausgewählt.

### Vorgezogene Phase 12: Arbeitseinsätze

Phase 12 wird auf ausdrücklichen Wunsch direkt nach Phase 9 umgesetzt, weil
bestätigte Teilnahmen die Datengrundlage der Arbeitsstundenkonten bilden.
Phase 10 und 11 bleiben fachlich unverändert offen und werden anschließend
bearbeitet.

#### Termine

Die Arbeitseinsatzübersicht bietet für berechtigte Konten einen direkten
Anlegezugang. Vor dem Öffnen des Formulars wird eine bearbeitbare
Abrechnungsperiode ausgewählt. Freigegebene und archivierte Perioden werden
nicht angeboten. Ist keine bearbeitbare Periode vorhanden, erklärt die
Übersicht den fehlenden Anlegezugang verständlich.

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
Existiert wider Erwarten noch kein Arbeitsstundenkonto, wird es aus den
globalen Vereinsvorgaben und den Belegungstagen der Parzelle angelegt.
Jahreswert, Belegungsfaktor, Pflichtstunden und Strafsatz bleiben als
historischer Wert der jeweiligen Periode erhalten.

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

### Phase 10: Warteliste

Die Warteliste verwaltet Interessenten, die noch kein Mitglied und keinem
Pachtvertrag zugeordnet sind. Jeder Eintrag speichert:

- Vorname und Nachname,
- E-Mail-Adresse,
- optional Telefon und Mobilnummer,
- Eingangsdatum,
- Priorität von 1 bis 5,
- Status,
- interne Notizen.

Priorität 1 bedeutet höchste Dringlichkeit. Bei gleicher Priorität werden
ältere Eingänge zuerst angezeigt. Zulässige Status sind `waiting`,
`contacted`, `offered`, `accepted`, `withdrawn` und `rejected`. Die
Oberfläche bezeichnet sie als Wartend, Kontaktiert, Angebot unterbreitet,
Übernommen, Zurückgezogen und Abgelehnt.

Wartelisteneinträge werden nicht gelöscht. Änderungen und Statuswechsel
bleiben über Auditlogs nachvollziehbar. Abgeschlossene Einträge können über
den Statusfilter weiterhin gefunden werden. Suche ist über Name, E-Mail und
Telefon möglich.

Wegen der personenbezogenen Kontaktdaten besitzt die Warteliste das eigene
granulare Recht `Warteliste verwalten`. Administratoren besitzen es immer;
der Standardvorstand erhält es als Ausgangswert. Individuelle
Vorstandsrechte können es entziehen. Pächter und andere Rollen erhalten
keinen Zugriff ohne ausdrückliche Rechtezuweisung.

Wartende, kontaktierte und mit einem Angebot versehene Einträge gelten als
offene Vorgänge. Berechtigte Konten sehen ihre Anzahl als Aktionspunkt im
Mitglieder-Menü.

### Phase 11: Inventarverwaltung

Die Inventarverwaltung bildet frei definierbare Vereinsgegenstände ab. Dazu
zählen beispielsweise Geräte, Werkzeuge, Pumpen, Anhänger und Schlüssel,
ohne diese Kategorien technisch fest vorzugeben. Jeder Gegenstand speichert:

- eine eindeutige Inventarnummer,
- Name und frei definierbare Kategorie,
- optional Beschreibung, Standort und Seriennummer,
- Status,
- optional Anschaffungsdatum und Anschaffungskosten,
- interne Notizen.

Zulässige Status sind `available`, `issued`, `maintenance`, `retired` und
`lost`. Die Oberfläche bezeichnet sie als Verfügbar, Ausgegeben, Wartung,
Ausgemustert und Verloren. Der Status Ausgegeben wird ausschließlich durch
den Ausgabeworkflow gesetzt. Ausgemusterte Gegenstände und ihre Historie
werden nicht gelöscht.

Eine Ausgabe speichert den optional zugeordneten Mitgliedsdatensatz und
zusätzlich den Namen des Empfängers als historischen Snapshot. Dadurch bleibt
die Ausgabe auch dann verständlich, wenn sich Stammdaten später ändern.
Ausgabedatum, optionale Rückgabefrist, ausgebende Person, Zustand und Notizen
werden dauerhaft gespeichert. Bei der Rückgabe werden Rückgabedatum,
rücknehmende Person, Zustand und der anschließende Gegenstandsstatus ergänzt.
Eine offene Ausgabe kann nur einmal zurückgegeben werden; ein Gegenstand kann
nicht gleichzeitig mehrfach ausgegeben werden.

Überfällige offene Ausgaben erzeugen für berechtigte Konten einen
Aktionshinweis. Suche und Filter berücksichtigen Inventarnummer, Name,
Kategorie, Seriennummer, Standort und Status.

Das granulare Recht `Inventar verwalten` schützt sämtliche Inventardaten und
Workflows. Administratoren besitzen es immer. Standardvorstand und
Gartenwarte erhalten es als Ausgangswert; individuelle Rechtevorlagen können
es entziehen. Anlage, Änderung, Ausgabe und Rückgabe werden auditiert.

### Phase 13: Datenübertragung, Backup und Restore

Die ursprünglich getrennten Phasen 13 und 14 werden als ein gemeinsamer
Bereich `Datenübertragung` umgesetzt. Das granulare Recht
`CSV-Daten übertragen` erlaubt Vorstandsmitgliedern den Import und Export.
Vollständige Backups, Downloads, Löschung und Wiederherstellung sind
ausschließlich Administratoren erlaubt.

CSV-Importe unterstützen Mitglieder, Parzellen, Zähler und Zählerstände.
Jede Datenart besitzt eine herunterladbare UTF-8-Vorlage mit verbindlicher
Kopfzeile. Mitglieder werden anhand der Mitgliedsnummer und Parzellen anhand
der Parzellennummer neu angelegt oder aktualisiert. Zähler und Zählerstände
sind historische Datensätze und werden ausschließlich ergänzt. Vorhandene
Zählernummern oder Stände desselben Zählers am selben Datum werden nicht
überschrieben. Sämtliche Zeilen werden fachlich validiert; ein Fehler rollt
den vollständigen Import zurück. Import und Export werden ohne
personenbezogene Nutzdaten im Auditlog protokolliert.

CSV-Exporte stehen für Mitglieder, Parzellen, Zähler, effektive
Zählerstände und Rechnungen zur Verfügung. Rechnungsexporte enthalten
Empfängersnapshots und einzelne Rechnungspositionen. CSV-Dateien verwenden
UTF-8 mit BOM und Semikolon als Trennzeichen.

Ein vollständiges OKGV-Backup ist ein privates ZIP-Archiv aus MariaDB-Dump,
privaten Dokumenten, Zählerstandfotos und Arbeitsstundennachweisen. Ein
Manifest speichert Format, Anwendungsversion und SHA-256-Prüfsummen. Die
`.env` wird niemals in das Archiv aufgenommen. Insbesondere `APP_KEY`,
Datenbankzugang und SMTP-Geheimnisse müssen deshalb separat und geschützt
gesichert werden; ohne denselben `APP_KEY` bleiben verschlüsselte Werte nach
einem Restore unlesbar. Administratoren können den aktuellen `APP_KEY` im
Backup-Bereich nach Passwortbestätigung anzeigen und separat sichern. Neue
Backups speichern ausschließlich eine Prüfsumme des `APP_KEY`, damit ein
Restore mit falschem Schlüssel vor dem Überschreiben abgelehnt werden kann.

Restore akzeptiert ausschließlich unveränderte OKGV-Archive derselben
Anwendungsversion, prüft Pfade und sämtliche Prüfsummen und verlangt
Administratorpasswort sowie die Bestätigung `WIEDERHERSTELLEN`. Vor jeder
Wiederherstellung wird automatisch ein Sicherheitsbackup des aktuellen
Zustands angelegt. Backup-Erstellung, Downloadschutz, Löschung und Restore
sind serverseitig geschützt und auditiert.

### Phase 15: DSGVO

Der Datenschutzbereich ist eine Kernfunktion und kann nicht als Fachmodul
abgeschaltet werden. Mitglieder können ihre gespeicherten strukturierten
Daten als JSON-Auskunft herunterladen. Berechtigte Vorstandsmitglieder
benötigen das granulare Recht `Datenschutzanfragen verwalten`, um Auskünfte
für andere Mitglieder und Löschanfragen zu prüfen. Die endgültige
Pseudonymisierung bleibt Administratoren vorbehalten.

Löschanfragen werden dauerhaft mit Antragsteller, Prüfzeitpunkt,
Prüfvermerk, Status und konkreten Aufbewahrungsgründen dokumentiert.
Archivierung allein ist keine Löschung. Eine Pseudonymisierung ist erst
zulässig, wenn das Mitglied archiviert ist, die konfigurierte
Mindestaufbewahrung seit dem Austritt abgelaufen ist und keine aktiven
Pächterzuordnungen, offenen oder aufzubewahrenden Rechnungen, aktiven
SEPA-Mandate, mitgliedsbezogenen Dokumente oder offenen Inventarausgaben
bestehen. Interne Vereinskonten werden nicht automatisch pseudonymisiert.

Die Mindestaufbewahrung wird über `OKGV_PRIVACY_RETENTION_YEARS`
konfiguriert und beträgt technisch konservativ zehn Jahre. Sie ist keine
abschließende Rechtsberatung; der betreibende Verein muss die für seine
Unterlagen tatsächlich geltenden gesetzlichen und vertraglichen Fristen
prüfen. Nach erfolgreicher Prüfung werden Mitgliedsstammdaten,
Kommunikationsempfänger, alte Rechnungsempfänger und Bankdaten
pseudonymisiert. Historische Fachbezüge bleiben unter einer anonymen
Referenz erhalten.

Mitglieder können separat für Name, E-Mail, Telefon, Mobilnummer und
Postanschrift einwilligen, dass aktuell gemeinsam eingetragene Mitpächter
derselben Parzelle diese Angaben sehen. Jede Freigabe ist standardmäßig
deaktiviert, freiwillig, feldbezogen und jederzeit widerrufbar. Eine
vereinsweite Freigabe oder ein öffentliches Mitgliederverzeichnis ist
ausgeschlossen.

Datenauskunft, Einwilligungsänderung, Löschantrag, Prüfung und
Pseudonymisierung werden ohne unnötige personenbezogene Nutzdaten
auditiert.

### Phase 16: Vereinseinstellungen und Vorlagenbranding

Die globale Konfiguration verwaltet neben dem frei sichtbaren Systemnamen
die rechtlichen und organisatorischen Vereinsstammdaten:

- offizieller Vereinsname,
- Straße, Postleitzahl und Ort,
- Ansprechpartner,
- Telefon und E-Mail-Adresse,
- optionale Vereinswebseite,
- geprüftes Vereinslogo,
- Bankverbindung für Überweisungsrechnungen,
- Standard-Zahlungsziel,
- Dokumentfußzeile,
- E-Mail-Signatur,
- Rechtevorlage für neue Vorstandsmitglieder,
- Arbeitsstunden-Standardwerte,
- SMTP und den schreibgeschützten Modulstatus.

Der Systemname benennt die Installation in Navigation und Seitentitel. Der
offizielle Vereinsname wird als rechtlicher Absender in Dokumenten genutzt.
Beide Werte dürfen voneinander abweichen.

JPEG-, PNG- und WebP-Logos bis 2 MiB werden MIME-geprüft im privaten Storage
gespeichert und ausschließlich über eine kontrollierte Route ausgeliefert.
Wird das aktive Logo ersetzt oder entfernt, bleiben von historischen
Dokumentsnapshots referenzierte Dateien privat erhalten.

IBAN und BIC des Überweisungskontos werden verschlüsselt gespeichert und in
der Oberfläche nur maskiert dargestellt. Leere Geheimnisfelder verändern
gespeicherte Werte nicht; eine eigene Auswahl entfernt die vollständige
Bankverbindung. Dieses Rechnungsbankkonto ist fachlich vom SEPA-Konto samt
Gläubiger-ID für Lastschriften getrennt.

Das Standard-Zahlungsziel wird bei neuen Abrechnungsperioden nach Auswahl
des Enddatums vorgeschlagen, kann aber je Periode angepasst werden.
Dokumentfußzeile und E-Mail-Signatur sind reiner Text und werden vor der
Ausgabe HTML-sicher escaped.

Rechnungen, Briefe, Mahnungen und Serienmails speichern bei ihrer Erstellung
beziehungsweise Versandfreigabe einen Snapshot der Vereins- und
Bankangaben. Spätere Änderungen an Name, Anschrift, Logo, Fußzeile oder
Bankkonto verändern bereits erzeugte historische Dokumente nicht.

### Phase 17: Nummernkreise

Administratoren konfigurieren getrennte Nummernkreise für:

- Mitgliedsnummern,
- Rechnungsnummern,
- SEPA-Mandatsreferenzen,
- Dokumentnummern.

Ein Format muss den Platzhalter `{NUMMER}` enthalten und darf zusätzlich
`{JAHR}` verwenden. Die Mindeststellen der fortlaufenden Nummer, der nächste
Zählerstand und ein optionaler jährlicher Neustart sind je Nummernkreis
konfigurierbar. Bei einem jährlichen Neustart ist `{JAHR}` Pflicht, damit
keine Doppelnummern entstehen.

Die Vergabe erfolgt innerhalb einer Datenbanktransaktion mit exklusiver
Sperre des jeweiligen Nummernkreises. Bereits durch manuelle Eingabe,
CSV-Import oder frühere Versionen belegte Nummern werden übersprungen.
Dadurch sind fachlich harmlose Lücken zulässig. Eine Nummer wird niemals
nachträglich wiederverwendet oder an historischen Datensätzen verändert.

Bei neuen Mitgliedern und SEPA-Mandaten darf eine berechtigte Person
weiterhin bewusst eine eigene eindeutige Nummer eingeben. Bleibt das Feld
leer, verwendet OKGV den Nummernkreis. Rechnungen und hochgeladene Dokumente
erhalten ihre Nummer immer serverseitig. Änderungen an den Einstellungen
werden auditiert und sind ausschließlich Administratoren erlaubt.

### Phase 18: Pächterwechsel und Übergabeprozess

Ein Pächterwechsel ist ein eigenständiger, nach Abschluss unveränderlicher
Vorgang. Bearbeiten dürfen ihn Administratoren und Vorstandsmitglieder mit
dem bestehenden Recht zur Verwaltung der Stammdaten. Leseberechtigte
Stammdatenkonten sehen die Übergabehistorie; Pächter erhalten keinen Zugriff
auf interne Übergabenotizen oder Nachweise.

Der geführte Prozess erfasst:

- die Parzelle und das Übergabedatum,
- sämtliche am Vortag aktiven bisherigen Vertragsparteien,
- einen neuen Hauptpächter und optional mehrere neue Mitpächter,
- genau einen Stand für jeden am Übergabetag vorhandenen Zähler,
- optionale Übergabefotos und Übergabedokumente,
- optionale interne Notizen,
- einen Snapshot offener Forderungen der bisherigen Vertragsparteien.

Der bisherige Pachtzeitraum endet am Kalendertag vor der Übergabe. Die neuen
Pachtzeiträume beginnen am Übergabetag. Haupt- und Mitpächter werden
gemeinsam historisiert. Der neue Hauptpächter muss sich vom bisherigen
Hauptpächter unterscheiden. Künftige Übergaben werden nicht vorab
abgeschlossen, weil Zählerstände und offene Forderungen erst am tatsächlichen
Übergabetag belastbar feststehen.

Alle fachlichen Änderungen erfolgen in einer Datenbanktransaktion mit
Sperre der betroffenen Parzelle. Schlägt eine Pächterzuordnung, ein
Zählerstand oder eine Dateiablage fehl, bleibt kein teilweise ausgeführter
Wechsel zurück. Übergabezählerstände verwenden dieselben chronologischen
Plausibilitätsregeln wie reguläre Zählerstände und sind für die
Verbrauchsaufteilung maßgeblich.

Offene, vorbereitete oder zurückgegebene Forderungen werden nur als
historischer Snapshot dokumentiert. Sie verbleiben bei den bisherigen
Vertragsparteien und werden niemals auf neue Pächter übertragen.
Übergabefotos und -dokumente liegen im privaten Storage, erhalten eine
Dokumentnummer und sind Bestandteil von Backup und Restore.

Der Vorgang, seine Zählerstandsverknüpfungen und Dateinachweise können nicht
hart gelöscht oder nachträglich umgeschrieben werden. Der Abschluss erzeugt
einen Auditlog-Eintrag. Beteiligte Mitglieder finden den sie betreffenden
Übergabesnapshot in ihrem DSGVO-Auskunftsexport.

### Phase 19: Bildbasierter Polygon-Lageplan

Der Lageplan verwendet ein privat gespeichertes Luftbild, einen
Katasterauszug oder einen selbst erstellten Plan als Hintergrund. Erlaubt
sind geprüfte JPEG-, PNG- und WebP-Dateien. Quelle und Nutzungsrecht müssen
beim Upload dokumentiert und ausdrücklich bestätigt werden.

Ein Screenshot oder heruntergeladenes Satellitenbild aus Google Maps gilt
nicht automatisch als frei speicher- und weiterverwendbar. Google-
Satellitendaten dürfen nur über eine nach den Google-Maps-Bedingungen
zulässige API-Einbindung angezeigt werden. OKGV kopiert oder speichert keine
Google-Kartenkacheln.

Jede Parzelle wird als Polygon mit 3 bis 100 Punkten relativ zur tatsächlichen
Bildgröße gespeichert. Im WYSIWYG-Editor können berechtigte Benutzer:

- eine Parzelle auswählen,
- durch Klicks eine beliebige Fläche zeichnen,
- einzelne Eckpunkte per Drag-and-drop verschieben,
- die gesamte Fläche verschieben,
- den letzten Punkt zurücknehmen,
- Übersicht und Editor von 100 bis 400 Prozent zoomen,
- den vergrößerten Kartenausschnitt über Scrollleisten oder unmittelbar mit
  gedrückter Maustaste verschieben,
- nur die Fläche entfernen, ohne den Parzellendatensatz zu löschen.

Die direkte Bedienung muss zwischen kurzem Klick und tatsächlicher
Ziehbewegung unterscheiden. In der Übersicht öffnet ein kurzer Klick weiterhin
die Parzellendetails. Im Editor verschiebt freie Bildfläche die Karte, während
Eckpunkte und Polygonfläche bearbeitbar bleiben. Im Zeichenmodus dienen Klicks
auf freie Bildfläche weiterhin der Punktsetzung.

Die sichtbare Größe der kreisförmigen Eckpunkt-Griffe bleibt über alle
Zoomstufen konstant. Die Griffe dürfen bei starker Vergrößerung keine
unnötig großen Teile der Parzellenfläche verdecken.

Wird das Hintergrundbild durch ein Bild mit anderer Größe ersetzt, skaliert
OKGV bestehende Polygone proportional. Änderungen an Bild und Polygonen
werden auditiert. Frühere Rechteckkoordinaten werden einmalig verlustfrei in
vierpunktige Polygone überführt.

Die Darstellung verwendet folgende Statusgruppen:

- Grün: frei oder vergeben,
- Gelb: reserviert oder gekündigt,
- Rot: gesperrt.

Farbe ist niemals die einzige Statusinformation. Jede Fläche zeigt die
Parzellennummer und enthält eine zugängliche Textbeschreibung mit Status und
Fläche. Ein Klick öffnet die Parzellendetailansicht, sofern der angemeldete
Benutzer diese Parzelle sehen darf.

Benutzer mit Leserecht für sämtliche Stammdaten sehen den vollständigen
Lageplan. Pächter sehen ausschließlich aktuell selbst zugeordnete
Parzellen. Bearbeiten dürfen den Lageplan nur Benutzer mit bestehendem
Parzellen-Schreibrecht. Hintergrundbild und Vereinslogo sind Bestandteil
vollständiger Backups.

### Phase 12.1: Modulare Funktionsbereiche

Die Stammdatenverwaltung für Mitglieder, Parzellen, Pächterhistorie,
Benutzer, Rollen und globale Konfiguration bildet den unverzichtbaren Kern.
Folgende Funktionsbereiche können instanzweise über `.env` aktiviert oder
deaktiviert werden:

- Pächterportal und öffentliche Pächterregistrierung,
- Zählerverwaltung und Zählerstandsmeldungen,
- Abrechnung und Rechnungen,
- Arbeitsstunden,
- Arbeitseinsätze,
- SEPA,
- Mahnwesen,
- Dokumentenverwaltung,
- Kommunikation und Serienmails,
- Warteliste,
- Inventarverwaltung,
- Datenübertragung einschließlich CSV sowie manuellem Backup und Restore.

Deaktivierte Module bleiben vollständig migriert. Vorhandene Daten, Rechte
und Historien werden weder gelöscht noch verändert und stehen nach einer
erneuten Aktivierung wieder zur Verfügung. Die Modulschaltung schützt:

- direkte und öffentliche Routen,
- Navigation und Dashboard,
- Pächterportal und Detailansichten,
- Rollen- und Rechteauswahl,
- Aktionshinweise,
- automatische Geschäftslogik,
- modulabhängige Auswahlmöglichkeiten anderer Bereiche.

Ein deaktiviertes Modul antwortet auch Administratoren bei direktem
URL-Aufruf mit HTTP 404. Dies vermeidet die Offenlegung nicht gebuchter
SaaS-Funktionen. Die serverseitige Rechteprüfung bleibt zusätzlich bestehen.

Folgende Abhängigkeiten sind verbindlich:

- Arbeitsstunden benötigen Abrechnung,
- Arbeitseinsätze benötigen Arbeitsstunden,
- SEPA benötigt Abrechnung,
- Mahnwesen benötigt Abrechnung.

Ungültige Kombinationen verhindern den Anwendungsstart mit einer eindeutigen
Konfigurationsmeldung. Verbrauchspreise pro kWh oder m³ und die
Serienmail-Gruppe für fehlende Zählerstände stehen nur bei aktiver
Zählerverwaltung zur Verfügung. Die Empfängergruppe für offene Rechnungen
steht nur bei aktiver Abrechnung zur Verfügung. Bei deaktivierten
Arbeitsstunden werden weder Konten automatisch erzeugt noch
Fehlstundenpositionen berechnet.

SMTP bleibt Teil der globalen Kernkonfiguration, weil auch Passwort-Reset und
E-Mail-Verifizierung darauf angewiesen sind. Serienmails und PDF-Briefe
gehören dagegen zum schaltbaren Kommunikationsmodul.

## Versionen

Die bisherige Basisversion `0.2.0` bleibt während der weiteren Bauphase bestehen. Veröffentlichte Entwicklungsstände erhalten eine fortlaufende vierte Stelle:

- `0.2.0.1`: erster Entwicklungsstand nach `0.2.0`
- `0.2.0.2`: zweiter Entwicklungsstand
- `0.2.0.n`: weitere Entwicklungsstände

Die vierte Stelle ist eine projektinterne Build-Nummer. Bereits veröffentlichte Tags bleiben unverändert. Eine neue dreiteilige Basisversion wird erst nach ausdrücklicher Freigabe festgelegt.
