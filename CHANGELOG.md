# Changelog

Alle wesentlichen Änderungen an OKGV werden in dieser Datei dokumentiert.

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
