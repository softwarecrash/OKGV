# Changelog

Alle wesentlichen Änderungen an OKGV werden in dieser Datei dokumentiert.

## [0.2.0.18] - 2026-06-14

### Phase 6

- Zentrale Dokumentenverwaltung für Pachtverträge, Übergabeprotokolle,
  Kündigungen, Rechnungsbelege, Satzungen, Protokolle, Fotos und sonstige
  Dokumente umgesetzt.
- Suche sowie Filter nach Dokumenttyp, Sichtbarkeit und Archivstatus ergänzt.
- Dokumente können Mitgliedern und Parzellen zugeordnet und intern, für
  Pächter oder über einen nicht erratbaren öffentlichen Link freigegeben
  werden.
- Jede ersetzte Datei erzeugt eine unveränderliche Version; ältere Dateien
  bleiben abrufbar und werden niemals überschrieben.
- Archivierung beendet sämtliche Freigaben, erhält aber Metadaten und alle
  Dateiversionen.
- Freigegebene Rechnungen werden als unveränderliche Systemdokumente in der
  Dokumentenübersicht verlinkt.

### Security

- Eigenständiges, granular zuweisbares Recht `Dokumente verwalten`
  eingeführt.
- Uploads liegen im privaten Storage, verwenden serverseitig erzeugte
  Dateinamen und sind auf erlaubte Endungen, MIME-Typen und 20 MiB begrenzt.
- Ausführbare Dateien, HTML, SVG und makrofähige Office-Dateien sind
  ausgeschlossen.
- Öffentliche Freigaben verwenden zufällige 64-stellige Tokens, werden nicht
  indexiert und erlöschen beim Widerruf oder Archivieren sofort.
- Erstellung, Änderung, neue Dateiversionen und Archivierung werden ohne
  Dateiinhalte im Auditlog protokolliert.
- Migrationen wurden vorwärts und rückwärts isoliert geprüft und anschließend
  ausschließlich mit `php artisan migrate` auf MariaDB angewendet; vorhandene
  Bestandszahlen blieben unverändert.

### Tests

- Rechteisolation, sichere Uploadprüfung, Pächterzuordnung,
  Versionsbeständigkeit, öffentliche Links und Archivierungswiderruf werden
  durch Feature-Tests abgedeckt.
- Insgesamt bestehen 83 Tests mit 448 Assertions.
- Entwicklungsstand auf `0.2.0.18` erhöht.

## [0.2.0.17] - 2026-06-14

### Phase 7

- Phase 6 auf ausdrücklichen Wunsch aufgeschoben und Phase 7 Kommunikation
  vollständig umgesetzt.
- Serienmails für aktive Mitglieder, aktuelle Pächter, Vorstand, Empfänger
  offener Rechnungen und fehlende Zählerstände ergänzt.
- Empfänger werden vor Versand dedupliziert und mit Name, E-Mail-Adresse,
  Mitgliedsbezug und Zustellstatus historisiert.
- Versand pro Empfänger als Queue-Job umgesetzt, damit größere Verteiler den
  Webrequest nicht blockieren.
- Verschlüsselte SMTP-Konfiguration mit SMTP/STARTTLS oder SMTPS,
  Absenderdaten und rate-limitiertem Testversand ergänzt.
- Allgemeine PDF-Briefe mit dauerhaftem Empfänger- und Anschriften-Snapshot
  hinzugefügt.
- PDF-Zahlungserinnerungen für fällige offene oder zurückgegebene Rechnungen
  ergänzt, ohne Mahnstufe, Mahngebühr oder Rechnungsänderung.

### Security

- Eigenständiges, granular zuweisbares Recht `Kommunikation verwalten`
  eingeführt.
- SMTP-Benutzername und Passwort werden verschlüsselt gespeichert, nicht
  vorausgefüllt und nicht im Auditlog protokolliert.
- Zahlungserinnerungen setzen zusätzlich das Abrechnungsrecht und eine
  tatsächlich überschrittene Fälligkeit voraus.
- Verbindliche Agent-Regel ergänzt, die `migrate:fresh`, `migrate:refresh`,
  `db:wipe` und vergleichbare Befehle auf der Entwicklungsdatenbank verbietet.
- Neue Tabellen wurden ausschließlich mit vorwärtsgerichtetem
  `php artisan migrate` ergänzt; vorhandene Bestandszahlen blieben unverändert.

### Tests

- SMTP-Verschlüsselung, Rechteisolation, Empfänger-Deduplizierung,
  Versandhistorie, Brief-Snapshots und Zahlungserinnerungen werden durch
  Feature-Tests abgedeckt.
- Insgesamt bestehen 78 Tests mit 413 Assertions.
- Entwicklungsstand auf `0.2.0.17` erhöht.
- Entwicklungsstand `0.2.0.17` auf GitHub veröffentlicht.

## [0.2.0.16] - 2026-06-14

### Changed

- Entbehrliche Hilfetexte unter E-Mail-Adresse und Passwort aus der
  Anmeldemaske entfernt.

### Added

- Barrierearmen Augenschalter zum Anzeigen und erneuten Verbergen des
  eingegebenen Passworts ergänzt.

### Tests

- Darstellung des Passwortschalters und Entfernung der Hilfetexte durch einen
  Feature-Test abgesichert.
- Entwicklungsstand auf `0.2.0.16` erhöht.
- Entwicklungsstand `0.2.0.16` auf GitHub veröffentlicht.

## [0.2.0.15] - 2026-06-14

### Added

- Freigegebene Pächterkonten können durch Administratoren zu
  Vorstandsmitgliedern hochgestuft werden.
- Granulare Benutzerrechte für Stammdaten, Zähler, Abrechnung, Preisvorlagen,
  SEPA, Registrierungsprüfung und Zählerstandprüfung ergänzt.
- Konfigurierbare Rechtevorlagen mit zurückhaltender Standardvorlage für
  Vorstandsmitglieder hinzugefügt.
- Globale Konfiguration für den sichtbaren Systemnamen und die
  Standard-Rechtevorlage ergänzt.
- Deutsche E-Mail-Verifizierung mit signiertem, zeitlich begrenztem Link nach
  Freigabe einer Registrierungsanfrage aktiviert.

### Changed

- Sämtliche bestehenden Policies und Aktionshinweise prüfen nun
  benutzerspezifische Rechte statt pauschaler Vorstandsrechte.
- Systemname wird in Navigation, Anmeldung, Dashboard, Rechnungs-PDFs und
  Transaktionsmails dynamisch verwendet.
- Bestehende Konten werden bei Einführung der E-Mail-Pflicht einmalig als
  bestätigt übernommen, damit kein bestehender Zugang gesperrt wird.

### Security

- SEPA- und Abrechnungszugriff werden Vorstandsmitgliedern nicht mehr
  automatisch durch die Rolle gewährt.
- Rechtevorlagen werden bei der Zuweisung als Snapshot gespeichert; spätere
  Vorlagenänderungen erweitern bestehende Konten nicht unbemerkt.
- Unbestätigte Konten können ausschließlich die Verifizierungsstrecke und
  Abmeldung verwenden.
- Rollen- und Rechteänderungen sowie globale Konfigurationsänderungen werden
  auditiert.

### Tests

- Aufstufung, Rechteisolation, Vorlagen-Snapshots, Systemname,
  Verifizierungsversand und Zugriffssperre werden durch Feature-Tests
  abgedeckt.
- Entwicklungsstand auf `0.2.0.15` erhöht.
- Entwicklungsstand `0.2.0.15` auf GitHub veröffentlicht.

## [0.2.0.14] - 2026-06-14

### Changed

- Hauptnavigation in die kompakten Gruppen `Mitglieder`, `Zähler` und
  `Finanzen` gegliedert.
- Abrechnung, Preisvorlagen, Rechnungen und SEPA unter `Finanzen`
  zusammengeführt.
- Registrierungsanfragen unter `Mitglieder` und Zählerstandsmeldungen unter
  `Zähler` eingeordnet.
- Rechteverwaltung aus der Hauptnavigation in das persönliche Benutzermenü
  verschoben.
- Hellen und dunklen Darstellungsmodus mit lokaler, persistenter Auswahl
  ergänzt.

### Added

- Zentrales rollenabhängiges Aktionshinweis-System mit pulsierendem Punkt
  hinzugefügt.
- Aktionspunkte für wartende Registrierungen, wartende oder abgelehnte
  Zählerstandsmeldungen sowie offene Pächterrechnungen aktiviert.
- Projektweite Regel ergänzt, nach der zukünftige handlungsbedürftige
  Vorgänge ebenfalls einen Aktionspunkt erhalten.

### Security

- Theme-Initialisierung als lokale, CSP-konforme JavaScript-Datei umgesetzt.
- Aktionspunkte werden nur aus Datensätzen berechnet, die für die jeweilige
  Rolle tatsächlich handlungsrelevant sind.

### Tests

- Navigationsgruppen, Rollenfilter, Aktionszahlen, Rechteverwaltung und
  Theme-Schalter werden durch Feature-Tests abgedeckt.
- Entwicklungsstand auf `0.2.0.14` erhöht.
- Entwicklungsstand `0.2.0.14` auf GitHub veröffentlicht.

## [0.2.0.13] - 2026-06-14

### Phase 5

- Rate-limitierte Pächterregistrierung mit Parzellennummer hinzugefügt.
- Freigabe- und Ablehnungsworkflow für Administrator und Vorstand
  implementiert.
- Pächterkonten werden erst nach Prüfung gegen einen aktiven Pachtvertrag
  erstellt und mit genau einem Mitglied verknüpft.
- Pächterportal für eigene Mitgliedsdaten, aktuelle Parzellen, aktive Zähler,
  freigegebene Rechnungen und Dokumente hinzugefügt.
- Zählerstandsmeldungen mit optionalem Foto und Prüfstatus implementiert.
- Bestätigung und Ablehnung durch Administrator, Vorstand oder Wasserwart
  ergänzt; erst bestätigte Meldungen erzeugen einen offiziellen Zählerstand.
- Lesendes Dokumentenmodell als Grundlage für die Verwaltung in Phase 6
  hinzugefügt.

### Security

- Registrierungskennwörter werden gehasht und nach Bearbeitung der Anfrage
  aus dem Anfragedatensatz entfernt.
- Ehemalige Pächter verlieren den Zugriff auf Parzellen und Zähler mit Ende
  ihrer Pächterzuordnung.
- Zählerfotos und Dokumente liegen im privaten Storage und werden nur über
  Policy-geschützte Download-Routen ausgeliefert.
- Foto-Uploads sind auf JPEG, PNG und WebP bis 8 MiB beschränkt;
  ausführbare Uploads werden abgewiesen.
- Registrierungen, Freigaben, Ablehnungen und Zählerstandsmeldungen werden
  auditiert, ohne Passwörter oder Dateiinhalte zu protokollieren.

### Tests

- Registrierung, Rollenrechte, aktive Pachtzuordnung, Fremdzugriff,
  Dokumentisolation, Uploadprüfung und Ablesefreigabe werden durch
  Feature-Tests abgedeckt.
- Entwicklungsstand auf `0.2.0.13` erhöht.
- Entwicklungsstand `0.2.0.13` auf GitHub veröffentlicht.

## [0.2.0.12] - 2026-06-14

### Phase 4

- Verschlüsselte SEPA-Einstellungen für Gläubiger-ID und Vereinskonto
  hinzugefügt.
- Mandatsverwaltung mit Gültigkeit, Status, Einmal- und Folgelastschriften
  sowie SWIFT-registerbasierter IBAN-Prüfung implementiert.
- Freigegebene Rechnungen um einen getrennten Zahlungsstatus ergänzt.
- Sammellastschriften mit verschlüsselten, unveränderlichen Gläubiger-,
  Mandats- und Rechnungs-Snapshots implementiert.
- pain.008.001.08-Export für SEPA CORE in EUR mit FRST-, RCUR- und
  OOFF-Sequenzen hinzugefügt.
- SHA-256-Prüfsumme, Export-, Einreichungs- und Buchungsstatus sowie
  vollständige Auditierung ergänzt.
- XML-Downloads als CSRF-geschützte POST-Aktion umgesetzt und
  Statusübergänge serverseitig gegen verfrühte Verbuchungen abgesichert.
- Rücklastschriften mit ISO-Grundcode, Datum und optionaler Erläuterung
  historisiert; betroffene Rechnungen werden wieder geöffnet.
- Bankdaten werden in Listen maskiert und sind ausschließlich für
  Administrator, Vorstand und Kassierer zugänglich.
- Deutsche, selbsterklärende Oberflächen für Einstellungen, Mandate,
  Sammellastschriften und Rückgaben hinzugefügt.

### Security

- IBAN, BIC, Kontoinhaber und Banksnapshots werden verschlüsselt gespeichert.
- Bankdaten und XML-Inhalte werden nicht in Audit-Metadaten geschrieben.
- Serverseitige Policies schließen Wasserwart, Gartenwart und Pächter von
  sämtlichen SEPA-Daten aus.

### Tests

- Rollen, Validierung, Verschlüsselung, Maskierung, XML-Inhalt,
  Zahlungsstatus und Rücklastschriften werden durch Feature-Tests abgedeckt.
- Entwicklungsstand auf `0.2.0.12` erhöht.
- Entwicklungsstand `0.2.0.12` auf GitHub veröffentlicht.

## [0.2.0.11] - 2026-06-14

### Added

- Konfigurierbare Preisvorlagen für wiederkehrende Kostenarten hinzugefügt.
- Administrator und Vorstand können Vorlagen mit Schlüssel, Bezeichnung,
  Berechnungsart, Geltungsbereich, Beschreibung und optionalem
  Vorschlagsbetrag verwalten.
- Finanzrollen können aktive Vorlagen beim Anlegen eines Periodenpreises
  auswählen und müssen anschließend nur den aktuellen Betrag prüfen oder
  ändern.
- Vorlagen können deaktiviert werden, ohne bereits verwendete Preise zu
  beeinflussen.
- Anlage und Änderung von Vorlagen werden im Auditlog dokumentiert.

### Security

- Vorlagenwerte werden bei der Übernahme serverseitig geladen; manipulierte
  Formularwerte können Berechnungsart oder Geltungsbereich nicht verändern.
- Jede Übernahme erzeugt einen eigenständigen historischen Snapshot in der
  Abrechnungsperiode. Spätere Vorlagenänderungen verändern keine bestehenden
  Preise oder Rechnungen.

### Tests

- Rollenrechte, Codenormalisierung, sichere Vorlagenübernahme,
  periodenspezifische Beträge und Historienstabilität werden durch
  Feature-Tests abgedeckt.
- Entwicklungsstand auf `0.2.0.11` erhöht.
- Entwicklungsstand `0.2.0.11` auf GitHub veröffentlicht.

## [0.2.0.10] - 2026-06-14

### Documentation

- `PHASE_PLAN.md` als verbindlichen Entwicklungsfahrplan mit den Phasen 0 bis
  20 hinzugefügt.
- Bereits umgesetzte Phasen 0 bis 3 vollständig als abgeschlossen markiert.
- SEPA als nächste, ausschließlich zu bearbeitende Produktphase festgelegt.
- `AGENTS.md` und `TODO.md` mit dem neuen Phasenplan verknüpft.
- Deployment, Docker, Backupstrategie und Produktionsdokumentation bleiben
  ausdrücklich bis Phase 20 zurückgestellt.
- Entwicklungsstand auf `0.2.0.10` erhöht.
- Entwicklungsstand `0.2.0.10` auf GitHub veröffentlicht.

## [0.2.0.9] - 2026-06-14

### Changed

- „Zwischenstand berechnen“ kann vor der Rechnungsfreigabe beliebig oft
  ausgeführt werden.
- Abrechnungsperioden, Preise und Preiszuordnungen bleiben im berechneten
  Zwischenstand bearbeitbar.
- Änderungen an einem berechneten Zwischenstand verwerfen ausschließlich
  dessen nicht freigegebene Rechnungsentwürfe und setzen die Periode auf
  `draft` zurück.
- Das Verwerfen eines Zwischenstands wird mit Anlass und Anzahl der
  betroffenen Entwürfe im Auditlog dokumentiert.
- Oberfläche und PDF kennzeichnen nicht freigegebene Rechnungen eindeutig als
  veränderlichen Zwischenstand.
- Erst die Rechnungsfreigabe sperrt Periode, Preise, Zuordnungen und
  Rechnungssnapshots dauerhaft.

### Tests

- Wiederholte Berechnung sowie das sichere Zurücksetzen nach Preisänderungen
  werden durch Feature-Tests abgedeckt.
- Entwicklungsstand auf `0.2.0.9` erhöht.
- Entwicklungsstand `0.2.0.9` auf GitHub veröffentlicht.

## [0.2.0.8] - 2026-06-14

### Changed

- Bestehende Formulare für Authentifizierung, Mitglieder, Parzellen,
  Pächterzuordnungen, Zähler, Ablesungen und Abrechnung um verständliche
  Hinweise, Beispiele und Angaben zur Sichtbarkeit ergänzt.
- Technische Formularbegriffe in der Abrechnung verständlicher benannt und
  Berechnungsarten sowie Geltungsbereiche direkt an der Eingabe erklärt.
- Leere Listen und Historien zeigen nun eine sinnvolle nächste Handlung.
- Dashboard um direkte, berechtigungsabhängige Zugänge zu allen vorhandenen
  Modulen erweitert.

### Security

- Zählerwechsel, Rechnungsfreigabe, Neuberechnung, Archivierung,
  Preiszuordnungen und Sonderrechte zeigen ihre Folgen vor dem Speichern an.
- Zähler- und Rechnungshistorien erklären deutlich, welche Originalwerte
  unveränderlich bleiben.

### Fixed

- Zentrale deutsche Validierungsmeldungen verwenden verständliche Feldnamen
  statt technischer Laravel-Bezeichner.
- Deutsch ist auch ohne lokale Umgebungsdatei und in der Testumgebung die
  verbindliche Standardsprache.
- Feature-Tests sichern Hilfetexte, Historienhinweise und deutsche
  Validierungsfehler ab.
- Entwicklungsstand auf `0.2.0.8` erhöht.
- Entwicklungsstand `0.2.0.8` auf GitHub veröffentlicht.

## [0.2.0.7] - 2026-06-14

### Documentation

- `AGENTS.md` verpflichtet alle Mitwirkenden zu selbsterklärenden deutschen
  Oberflächen, automatischer Eingabehilfe und verständlichen Fehlermeldungen.
- `AGENT_CODE_STYLE.md` beschreibt verbindliche UX-Regeln für Hilfetexte,
  Formatnormalisierung, riskante Aktionen, leere Zustände und UX-Tests.
- Entwicklungsstand auf `0.2.0.7` erhöht.
- Entwicklungsstand `0.2.0.7` auf GitHub veröffentlicht.

## [0.2.0.6] - 2026-06-14

### Fixed

- Leerzeichen im technischen Code eines Abrechnungspreises werden während der
  Eingabe und serverseitig automatisch durch Unterstriche ersetzt.
- Entwicklungsstand auf `0.2.0.6` erhöht.
- Entwicklungsstand `0.2.0.6` auf GitHub veröffentlicht.

## [0.2.0.5] - 2026-06-14

### Added

- Revisionssicheres Konzept für Zählerstandkorrekturen mit unverändertem
  Originalwert und vollständiger Korrekturhistorie spezifiziert.
- Optionales Sonderrecht für Administrator- und Vorstandskonten festgelegt.
- Migrationen, Model und Beziehungen für das optionale Kontorecht und
  append-only Zählerstandkorrekturen hinzugefügt.
- Plausibilitätsprüfung und Verbrauchsberechnung verwenden den jeweils
  jüngsten wirksamen Korrekturwert.
- Transaktionaler, auditierter Korrekturservice und administrative
  Rechtezuweisung implementiert.
- Deutsche Oberflächen für die Vergabe des Sonderrechts, Erfassung von
  Korrekturen und Anzeige der vollständigen Korrekturhistorie ergänzt.
- Tests für explizite Rechtevergabe, Rollenbegrenzung, unveränderte
  Originalwerte, Auditlog, Append-only-Schutz und korrigierte
  Verbrauchsberechnung ergänzt.
- Rollback der ursprünglichen Rollen-Migration entfernt den Rollenindex nun
  vor der Spalte.
- Entwicklungsstand auf `0.2.0.5` erhöht.
- Entwicklungsstand `0.2.0.5` auf GitHub veröffentlicht.

## [0.2.0.4] - 2026-06-14

### Fixed

- Mehrere aktive Haupt- und Mitpächter einer Parzelle werden als gemeinsame,
  historisierte Rechnungsempfänger modelliert.
- Rechnungserzeugung, HTML-Ansicht, PDF und Pächterrechte berücksichtigen alle
  aktiven Vertragsparteien der abgerechneten Parzellen.
- Bestehende Rechnungen erhalten bei der Migration automatisch einen Snapshot
  ihres bisherigen Hauptempfängers.
- Eine separate SQLite-Testumgebung verhindert, dass Artisan-Testmigrationen
  die lokale MariaDB-Entwicklungsdatenbank berühren.
- Entwicklungsstand auf `0.2.0.4` erhöht.
- Entwicklungsstand `0.2.0.4` auf GitHub veröffentlicht.

## [0.2.0.3] - 2026-06-14

### Phase 3

- Datenmodell für Abrechnungsperioden, historische Preise, optionale
  Zuordnungen, Rechnungen und unveränderliche Rechnungssnapshots spezifiziert
- Berechnungsregeln, Schutz historischer Daten und Rollenrechte festgelegt
- Phase 3 in einzeln prüfbare Umsetzungsschritte aufgeteilt
- Reversible Migrationen für Abrechnungsperioden, Preise, Preiszuordnungen,
  Rechnungen und Rechnungssnapshot-Positionen hinzugefügt
- Typisierte Status-, Berechnungs- und Geltungsbereich-Enums sowie Models,
  Factories und Beziehungen für die Abrechnung ergänzt
- Änderungen und Löschungen freigegebener Rechnungen und ihrer Positionen auf
  Model-Ebene gesperrt
- Transaktionale Periodenverwaltung mit Überschneidungsprüfung, Berechnung,
  Freigabe und Archivierung implementiert
- Rechnungsberechnung für Mitglieds-, Flächen-, Verbrauchs- und
  Zuordnungskosten mit exakter Dezimalarithmetik ergänzt
- Pächterwechsel innerhalb einer Periode blockieren die automatische
  Abrechnung und Berechnung sowie Freigabe werden auditiert
- Policies und Form Requests für Finanzrollen, Pächterisolation,
  Periodenzeiträume, Preise und exklusive Mitglied-/Parzellenzuordnungen
  ergänzt
- Controller und geschützte Routen für Perioden, Preise, Zuordnungen,
  Berechnung, Freigabe, Archivierung und Rechnungen hinzugefügt
- Responsive deutsche Verwaltungsoberflächen für Abrechnungsperioden, Preise,
  Zuordnungen und Rechnungen in die Hauptnavigation integriert
- Serverseitige PDF-Erzeugung mit Dompdf 3.1 aus unveränderlichen
  Rechnungssnapshots ergänzt; Entwürfe werden deutlich gekennzeichnet
- Tests für Finanzrollen, Pächterisolation, Periodenkonflikte, exakte
  Rechnungsberechnung, Mehrzählerverbrauch, Pächterwechsel, Unveränderlichkeit,
  Auditlogs und PDF-Ausgabe ergänzt
- Entfernen von Preisen und Preiszuordnungen wird ebenfalls im Auditlog
  dokumentiert
- Statussperren werden unabhängig vom globalen Administratorrecht direkt an
  den Änderungsendpunkten durchgesetzt
- Entwicklungsstand auf `0.2.0.3` erhöht
- Entwicklungsstand `0.2.0.3` auf GitHub veröffentlicht

## [0.2.0.2] - 2026-06-14

### Entwicklung

- Verbindliche projektweite Stilrichtlinie `AGENT_CODE_STYLE.md` hinzugefügt
- Regeln für PHP, Laravel-Schichten, Datenbank, Historien, Sicherheit, Blade, Tests und Veröffentlichung dokumentiert
- `AGENTS.md` verpflichtet alle Agents zur Anwendung und Pflege der Stilrichtlinie
- Entwicklungsstand `0.2.0.2` auf GitHub veröffentlicht

## [0.2.0.1] - 2026-06-14

### Entwicklung

- Versionierung auf die feste Basis `0.2.0` mit fortlaufender vierter Build-Stelle umgestellt
- Bestehende Git-Tags bleiben unverändert
- Datenmodell, Historienregeln, Verbrauchsberechnung und Rechtekonzept der Zählerverwaltung spezifiziert
- Migrationen für Zähler und unveränderliche, datumsbezogen eindeutige Zählerstände hinzugefügt
- Zähler- und Zählerstandmodels, Factories und Parzellenbeziehungen hinzugefügt
- Transaktionale Zähleranlage, atomare Zählerwechsel und append-only Zählerstände implementiert
- Segmentierte Verbrauchsberechnung über mehrere Zähler eines Zeitraums implementiert
- Rollenbasierte Policies, Form Requests und deutsche Zähleroberflächen hinzugefügt
- Eigener, auditierter Zählerwechselprozess und append-only Ableseerfassung ergänzt
- Tests für Rollenrechte, Pächterisolation, Aktivzähler, Ableseplausibilität, Wechsel und Mehrzählerverbrauch ergänzt
- Historische Zähler sind nicht reaktivierbar; Wechsel respektieren vorhandene spätere und letzte Ablesungen
- Phase 2 mit 25 Tests, 109 Assertions, Migration-Rollback und Sicherheits-Audits geprüft
- Entwicklungsstand `0.2.0.1` auf GitHub veröffentlicht

## [0.2.0] - 2026-06-14

### Phase 1

- Datenmodell für Mitglieder, Parzellen und dauerhafte Pächterhistorie präzisiert
- Reversible Archivierung und eindeutige Verknüpfung von Pächterkonten mit Mitgliedern festgelegt
- Rollenabhängige Lese-, Schreib- und Archivrechte für Stammdaten festgelegt
- Migrationen für Mitglieder, Parzellen und indexierte Pächterhistorie hinzugefügt
- Status-Enums, Models, Factories und Beziehungen für Phase-1-Stammdaten hinzugefügt
- Rollenbasierte Policies und validierende Form Requests einschließlich Zeitraumkonflikten hinzugefügt
- Deutsche CRUD-Oberflächen für Mitglieder, Parzellen und Pächterhistorie hinzugefügt
- Suche, Statusfilter und reversible Mitgliederarchivierung hinzugefügt
- Auditlogs für Änderungen an Mitgliedern, Parzellen und Pächterzuordnungen ergänzt
- Feature-Tests für CRUD, Rollenrechte, Suche, Archivierung, Auditlogs, Historienkonflikte und Pächterisolation ergänzt
- Parzellenbezogene Transaktionssperren verhindern konkurrierende, überschneidende Pächterzuordnungen
- Version `0.2.0` auf GitHub veröffentlicht

## [0.1.0] - 2026-06-14

### Hinzugefügt

- Laravel-13-Projektbasis für lokale Linux-LXC-Entwicklung
- Bootstrap-5-Layout und Alpine.js
- Login, Logout, Passwort-Reset und geschütztes Dashboard
- Interaktiver Artisan-Befehl `okgv:create-admin` für den ersten Administrator
- Rollenmodell für Administrator, Vorstand, Kassierer, Wasserwart, Gartenwart und Pächter
- Policy-Grundlage für Benutzerzugriffe
- Verschlüsselte Auditlog-Metadaten für Login, Logout und fehlgeschlagene Anmeldungen
- Security-Header einschließlich Content Security Policy
- Deutsches Branding und vorbereitete deutsche Authentifizierungsoberfläche
- MariaDB-Konfiguration ohne veröffentlichte Zugangsdaten
- Projektregeln, Spezifikation, Aufgabenliste und Versionsdatei
- Feature-Tests für Authentifizierung, Registrierungsschutz, Security-Header, Auditlogs und Policies

### Sicherheit

- Öffentliche Registrierung deaktiviert
- Serverseitige Autorisierungsgrundlage eingerichtet
- CSRF-, Session- und Passwortschutz über Laravel aktiviert

### Entwicklung

- Git-Repository und Remote `softwarecrash/OKGV` eingerichtet
- Version `0.1.0` mit repositorygebundenem Deploy Key auf GitHub veröffentlicht
- Docker und Deployment-Artefakte gemäß Entwicklungsstrategie zurückgestellt
- Verwundbare, ungenutzte Frontend-Entwicklungsabhängigkeiten entfernt
