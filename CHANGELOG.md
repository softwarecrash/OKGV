# Changelog

Alle wesentlichen Änderungen an OKGV werden in dieser Datei dokumentiert.

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
