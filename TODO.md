# TODO

Der verbindliche Gesamtfahrplan mit den Phasen 0 bis 20 steht in
`PHASE_PLAN.md`. In dieser Datei werden die jeweils aktive Phase und
projektweite Einzelaufgaben detailliert geführt.

## Phase 0: Projektbasis

- [x] Entwicklungsumgebung prüfen
- [x] PHP, Composer, Node.js, npm, Git und MariaDB installieren
- [x] Laravel installieren
- [x] Git-Repository initialisieren und GitHub-Remote einrichten
- [x] Bootstrap-Layout vorbereiten
- [x] Alpine.js integrieren
- [x] Login und Logout einrichten
- [x] Passwort-Reset einrichten
- [x] Öffentliche Registrierung deaktivieren
- [x] Befehl zum Erstellen des ersten Administrators ergänzen
- [x] Dashboard erstellen
- [x] Rollenmodell anlegen
- [x] Policy-Grundlage anlegen
- [x] Auditlog-Basis für Authentifizierungsereignisse anlegen
- [x] Security-Header ergänzen
- [x] Deutsche Oberfläche und OKGV-Branding vorbereiten
- [x] Projektdokumentation erstellen
- [x] Version auf 0.1.0 erhöhen
- [x] Phase 0 vollständig testen
- [x] Version 0.1.0 auf GitHub veröffentlichen

## Phase 1: Stammdaten

- [x] Datenmodell für Mitglieder, Parzellen und Pächterhistorie spezifizieren
- [x] Rechtekonzept der Stammdaten vollständig prüfen
- [x] Migrationen erstellen
- [x] Models und Beziehungen erstellen
- [x] Policies und Form Requests erstellen
- [x] CRUD-Oberflächen erstellen
- [x] Suche und Archivierung erstellen
- [x] Tests erstellen
- [x] Phase 1 vollständig prüfen
- [x] Version 0.2.0 auf GitHub veröffentlichen

Weitere Phasen werden vor Beginn aus `PROJECT_SPEC.md` detailliert.

## Phase 2: Zählerverwaltung

- [x] Datenmodell für Zähler, Zählerstände und Zählerwechsel spezifizieren
- [x] Rechtekonzept der Zählerverwaltung vollständig prüfen
- [x] Migrationen erstellen
- [x] Models und Beziehungen erstellen
- [x] Verbrauchsberechnung über mehrere Zähler implementieren
- [x] Policies und Form Requests erstellen
- [x] CRUD-Oberflächen für Zähler und Zählerstände erstellen
- [x] Historisierten Zählerwechsel implementieren
- [x] Tests erstellen
- [x] Phase 2 vollständig prüfen
- [x] Entwicklungsstand 0.2.0.1 veröffentlichen
- [x] Optionales Benutzerrecht für Zählerstandkorrekturen ergänzen
- [x] Append-only Korrekturhistorie und effektive Werte implementieren
- [x] Rechteverwaltung und Korrekturoberfläche ergänzen
- [x] Korrekturworkflow und Verbrauchsberechnung testen
- [x] Entwicklungsstand 0.2.0.5 veröffentlichen

## Phase 3: Abrechnung

- [x] Datenmodell und Rechtekonzept spezifizieren
- [x] Abrechnungsperioden und historische Preise migrieren
- [x] Optionale Preiszuordnungen migrieren
- [x] Rechnungen und unveränderliche Positionen migrieren
- [x] Models, Enums und Beziehungen implementieren
- [x] Berechnung und Statusübergänge als Services implementieren
- [x] Policies und Form Requests implementieren
- [x] Deutsche Verwaltungsoberflächen implementieren
- [x] PDF-Ausgabe implementieren
- [x] Auditlog für Berechnung und Freigabe ergänzen
- [x] Feature- und Unit-Tests ergänzen
- [x] Sicherheitsprüfungen und Asset-Build ausführen
- [x] Entwicklungsstand 0.2.0.3 veröffentlichen
- [x] Mehrere Vertragsparteien als Rechnungsempfänger historisieren
- [x] Gemeinsame Rechnungsdarstellung und Empfängerrechte testen
- [x] Entwicklungsstand 0.2.0.4 veröffentlichen
- [x] Technische Preiscodes bei der Eingabe automatisch normalisieren
- [x] Entwicklungsstand 0.2.0.6 veröffentlichen
- [x] Wiederholbare Rechnungszwischenstände ermöglichen
- [x] Veraltete Rechnungsentwürfe bei Änderungen sicher und auditierbar verwerfen
- [x] Abrechnung erst mit der Rechnungsfreigabe dauerhaft sperren
- [x] Wiederholte Berechnung und Rücksetzung durch Tests absichern
- [x] Entwicklungsstand 0.2.0.9 veröffentlichen
- [x] Konfigurierbare Preisvorlagen spezifizieren
- [x] Vorlagenverwaltung für Administrator und Vorstand implementieren
- [x] Aktive Vorlagen für Finanzrollen auswählbar machen
- [x] Periodenpreis als unveränderlichen Snapshot der Vorlage übernehmen
- [x] Betrag bei der Übernahme periodenspezifisch anpassbar machen
- [x] Rechte, Manipulationsschutz und Historienstabilität testen
- [x] Entwicklungsstand 0.2.0.11 veröffentlichen

## Projektweite Standards

- [x] Projektlizenz von MIT auf GNU AGPLv3 umstellen
- [x] Paketmetadaten auf `AGPL-3.0-only` aktualisieren
- [x] Lizenzanforderungen in README, Projektspezifikation und Agent-Regeln dokumentieren
- [x] Konfigurierbaren Quellcode-Link für Netzwerkbereitstellungen ergänzen
- [x] Entwicklungsstand 0.2.0.41 veröffentlichen
- [x] Verbindliche Code-Style-Richtlinie für Agents erstellen
- [x] Code-Style-Richtlinie aus `AGENTS.md` referenzieren
- [x] Entwicklungsstand 0.2.0.2 veröffentlichen
- [x] Verbindliche Regeln für selbsterklärende und fehlertolerante Formulare ergänzen
- [x] Entwicklungsstand 0.2.0.7 veröffentlichen
- [x] Bestehende Authentifizierungs- und Verwaltungsoberflächen auf verständliche Hilfetexte prüfen
- [x] Formulare für Stammdaten, Zähler und Abrechnung selbsterklärend überarbeiten
- [x] Deutsche, feldbezogene Validierungsmeldungen zentral ergänzen
- [x] Riskante Aktionen mit verständlichen Folgen und Bestätigungen absichern
- [x] Leere Zustände mit einer klaren nächsten Handlung ergänzen
- [x] Projektweite UX-Regeln durch Feature-Tests absichern
- [x] Entwicklungsstand 0.2.0.8 veröffentlichen
- [x] Verbindlichen Phasenplan 0 bis 20 als eigene Arbeitsdatei anlegen
- [x] Abgeschlossene Phasen 0 bis 3 im Phasenplan markieren
- [x] Phasenplan in den Agent-Arbeitsregeln verankern
- [x] Entwicklungsstand 0.2.0.10 veröffentlichen
- [x] Navigation in Mitglieder, Zähler und Finanzen gruppieren
- [x] Zentrales rollenabhängiges Aktionshinweis-System implementieren
- [x] Rechteverwaltung in das Benutzermenü verschieben
- [x] Persistenten Hell-/Dunkelmodus implementieren
- [x] Navigation, Aktionshinweise und Theme-Umschaltung testen
- [x] Entwicklungsstand 0.2.0.14 veröffentlichen
- [x] Granulare Berechtigungsschlüssel für Vorstandsmitglieder einführen
- [x] Aufstufung freigegebener Pächterkonten zum Vorstand ermöglichen
- [x] Konfigurierbare Rechtevorlagen mit sicherem Snapshot-Verhalten ergänzen
- [x] Globale Konfiguration für Systemname und Standardvorlage ergänzen
- [x] E-Mail-Verifizierung nach Freigabe einer Registrierung aktivieren
- [x] Bestehende Policies auf benutzerspezifische Rechte umstellen
- [x] Rollen-, Vorlagen-, Branding- und Verifizierungstests ergänzen
- [x] Entwicklungsstand 0.2.0.15 veröffentlichen
- [x] Passwort-Sichtbarkeitsschalter in der Anmeldemaske ergänzen
- [x] Entbehrliche Hilfetexte aus der Anmeldemaske entfernen
- [x] Entwicklungsstand 0.2.0.16 veröffentlichen

## Phase 4: SEPA

- [x] Aktuelle EPC-, ISO-20022-, SWIFT- und Bundesbank-Vorgaben prüfen
- [x] Datenmodell und Rechtekonzept vollständig spezifizieren
- [x] Verschlüsselte SEPA-Einstellungen implementieren
- [x] Mandatsverwaltung mit IBAN- und Gläubiger-ID-Prüfung implementieren
- [x] Zahlungsstatus an freigegebenen Rechnungen ergänzen
- [x] Sammellastschriften mit unveränderlichen Snapshots implementieren
- [x] pain.008.001.08-XML-Export implementieren
- [x] Export-Hash und Auditlogs ergänzen
- [x] Einreichungs- und Buchungsstatus implementieren
- [x] Rücklastschriften mit ISO-Grundcode historisieren
- [x] Bankdaten in Listen maskieren und serverseitig schützen
- [x] Rollen-, Verschlüsselungs-, XML- und Workflowtests ergänzen
- [x] Migration vorwärts und rückwärts prüfen
- [x] Phase 4 vollständig prüfen
- [x] Entwicklungsstand 0.2.0.12 veröffentlichen

## Phase 5: Pächterportal

- [x] Datenmodell und Rechtekonzept vollständig spezifizieren
- [x] Öffentliche, rate-limitierte Registrierungsanfragen implementieren
- [x] Freigabe und Ablehnung durch Administrator oder Vorstand implementieren
- [x] Sichere Benutzer- und Mitgliedszuordnung gegen aktive Pachtverträge prüfen
- [x] Pächterdashboard für eigene Daten und aktuelle Parzellen implementieren
- [x] Eigene freigegebene Rechnungen in das Portal integrieren
- [x] Lesenden Dokumentenzugriff mit privaten Downloads vorbereiten
- [x] Zählerstandsmeldungen mit privatem Foto-Upload implementieren
- [x] Bestätigung und Ablehnung durch Vorstand oder Wasserwart implementieren
- [x] Auditlogs für Registrierung und Zählerstandsmeldungen ergänzen
- [x] Fremdzugriff, Uploads und Statusübergänge testen
- [x] Migration vorwärts und rückwärts prüfen
- [x] Phase 5 vollständig prüfen
- [x] Entwicklungsstand 0.2.0.13 veröffentlichen

## Phase 7: Kommunikation

- [x] Phase 6 auf ausdrücklichen Wunsch als aufgeschoben dokumentieren
- [x] Datenmodell und Rechtekonzept der Kommunikation spezifizieren
- [x] Granulares Kommunikationsrecht ergänzen
- [x] Verschlüsselte SMTP-Konfiguration und Testversand implementieren
- [x] Serienmails und Empfängergruppen implementieren
- [x] Versandhistorie und Auditlogs implementieren
- [x] Allgemeine PDF-Briefe implementieren
- [x] PDF-Zahlungserinnerungen ohne Mahnstufen implementieren
- [x] Feature- und Sicherheitstests ergänzen
- [x] Migration ausschließlich nicht löschend auf Entwicklungsdatenbank anwenden
- [x] Phase 7 vollständig prüfen und veröffentlichen

## Phase 6: Dokumentenverwaltung

- [x] Phase 6 nach der vorgezogenen Phase 7 wieder aufnehmen
- [x] Datenmodell, Dateiversionierung und Rechtekonzept spezifizieren
- [x] Granulares Dokumentenrecht ergänzen
- [x] Dokumenttypen, Suche und Filter implementieren
- [x] Sichere private Uploads und unveränderliche Dateiversionen implementieren
- [x] Mitglieder-, Parzellen- und Sichtbarkeitszuordnung implementieren
- [x] Veröffentlichung, öffentliche Freigabelinks und Archivierung implementieren
- [x] Rechnungen in der Dokumentenübersicht verlinken
- [x] Auditlogs und deutschsprachige Bedienhinweise ergänzen
- [x] Feature-, Rechte- und Uploadtests ergänzen
- [x] Migration ausschließlich nicht löschend auf Entwicklungsdatenbank anwenden
- [x] Phase 6 vollständig prüfen und veröffentlichen

## Globale Konfiguration

- [x] SMTP-Einstellungen aus Kommunikation in die globale Konfiguration verschieben
- [x] Berechtigungen, Navigation und Tests der SMTP-Integration prüfen
- [x] Entwicklungsstand veröffentlichen

## Phase 8: Mahnwesen

- [x] Datenmodell, Mahnstufen, Fristen und Rechtekonzept spezifizieren
- [x] Unveränderliche Mahnungssnapshots und Empfängerhistorie implementieren
- [x] Sequentielle Mahnstufen und Fristprüfung implementieren
- [x] Optionale Mahngebühren und kumulierte Gesamtforderung implementieren
- [x] Stornierung mit Pflichtbegründung und Auditlog implementieren
- [x] Mahnungsübersicht und Rechnungsintegration implementieren
- [x] PDF-Mahnungen implementieren
- [x] Pächterzugriff auf eigene Mahnungen implementieren
- [x] Aktionshinweis für überfällige Rechnungen ergänzen
- [x] Feature-, Rechte-, PDF- und Unveränderlichkeitstests ergänzen
- [x] Migration vorwärts und rückwärts isoliert prüfen
- [x] Migration ausschließlich nicht löschend auf Entwicklungsdatenbank anwenden
- [x] Phase 8 vollständig prüfen und veröffentlichen

## Phase 9: Arbeitsstunden

- [x] Datenmodell, Berechnung und Rechtekonzept spezifizieren
- [x] Arbeitsstundenkonten je Mitglied und Abrechnungsperiode implementieren
- [x] Fehlstunden und Strafbeträge ausschließlich serverseitig berechnen
- [x] Verwaltung und verständliche Bedienhinweise implementieren
- [x] Änderungen berechneter Zwischenstände sicher zurücksetzen
- [x] Strafzahlungen als historische Rechnungspositionen übernehmen
- [x] Gemeinsame Rechnungen mehrerer Vertragspartner berücksichtigen
- [x] Auditlogs und Aktionshinweise ergänzen
- [x] Feature- und Rechtetests ergänzen
- [x] Migration vorwärts und rückwärts isoliert prüfen
- [x] Migration ausschließlich nicht löschend auf Entwicklungsdatenbank anwenden
- [x] Gesamttests, Formatter und Asset-Build ausführen
- [x] Phase 9 vollständig prüfen und veröffentlichen

## Phase 10: Warteliste

- [x] Datenmodell, Status und Prioritätsregel spezifizieren
- [x] Eigenes granulares Wartelistenrecht ergänzen
- [x] Anlegen, Anzeigen und Bearbeiten ohne Löschfunktion implementieren
- [x] Suche nach Name und Kontaktdaten implementieren
- [x] Status- und Prioritätsfilter implementieren
- [x] Standardsortierung nach Priorität und Eingangsdatum implementieren
- [x] Auditlogs ohne unnötige Kontaktdaten ergänzen
- [x] Aktionshinweis im Mitglieder-Menü ergänzen
- [x] Bedienhinweise und leere Zustände ergänzen
- [x] Feature-, Rechte-, Validierungs- und Navigationstests ergänzen
- [x] Migration vorwärts und rückwärts auf MariaDB prüfen
- [x] Gesamttests, Formatter, Build und Audits ausführen
- [x] Phase 10 vollständig prüfen
- [x] Entwicklungsstand veröffentlichen

## Phase 11: Inventarverwaltung

- [x] Datenmodell, Status und Rechtekonzept spezifizieren
- [x] Frei definierbare Gegenstände und Kategorien implementieren
- [x] Optionale Anschaffungsdaten, Kosten, Seriennummern und Standorte ergänzen
- [x] Eigenes granulares Inventarrecht ergänzen
- [x] Suche sowie Status- und Kategoriefilter implementieren
- [x] Transaktionale Ausgabe mit Mitglieds- und Namenssnapshot implementieren
- [x] Einmalige Rückgabe mit Folgestatus implementieren
- [x] Ausgabe- und Rückgabehistorie dauerhaft schützen
- [x] Auditlogs und Aktionshinweise für überfällige Rückgaben ergänzen
- [x] Bedienhinweise und leere Zustände ergänzen
- [x] Feature-, Rechte-, Validierungs- und Historientests ergänzen
- [x] Migration vorwärts und rückwärts isoliert prüfen
- [x] Migration ausschließlich nicht löschend auf Entwicklungsdatenbank anwenden
- [x] Gesamttests, Formatter, Build und Audits ausführen
- [x] Phase 11 vollständig prüfen
- [x] Entwicklungsstand veröffentlichen

## Phase 12.1: Modularisierung

- [x] Kernfunktionen und schaltbare Fachmodule festlegen
- [x] Zentrale `.env`-Konfiguration für alle vorhandenen Fachmodule ergänzen
- [x] Verbindliche Modulabhängigkeiten implementieren
- [x] Ungültige Konfigurationen beim Anwendungsstart ablehnen
- [x] Modul-Middleware für direkte und öffentliche Routen implementieren
- [x] Navigation, Dashboard und Pächterportal modulabhängig machen
- [x] Parzellen-, Abrechnungs- und Konfigurationsansichten anpassen
- [x] Rechte deaktivierter Module ausblenden, aber dauerhaft erhalten
- [x] Aktionshinweise deaktivierter Module unterdrücken
- [x] Automatische Arbeitsstundenkonten und Fehlstundenpositionen abschalten
- [x] Verbrauchspreise an das Zählermodul koppeln
- [x] modulabhängige Serienmail-Empfängergruppen absichern
- [x] Modulstatus in der globalen Konfiguration anzeigen
- [x] Datenhaltbarkeit bei Deaktivierung und Reaktivierung testen
- [x] Direktzugriffe einschließlich Administratorzugriff testen
- [x] Abhängigkeiten und manipulierte Requests testen
- [x] Gesamttests, Formatter, Build und Audits ausführen
- [x] Phase 12.1 vollständig prüfen
- [x] Entwicklungsstand veröffentlichen

## Phase 13 und 14: Datenübertragung, Backup und Restore

- [x] CSV-Import und CSV-Export zu einer gemeinsamen Phase zusammenführen
- [x] Datenmodell und Rechtekonzept vollständig spezifizieren
- [x] Schaltbares Modul und granulares CSV-Recht ergänzen
- [x] UTF-8-Importvorlagen für Mitglieder, Parzellen, Zähler und Zählerstände bereitstellen
- [x] Transaktionalen Import mit verständlichen Zeilenfehlern implementieren
- [x] Mitglieder und Parzellen anhand ihrer Fachnummer sicher aktualisieren
- [x] Zähler und Zählerstände ausschließlich historisch ergänzen
- [x] CSV-Export für Mitglieder, Parzellen, Zähler, Zählerstände und Rechnungen implementieren
- [x] Private ZIP-Backups aus MariaDB-Dump und privaten Dateien implementieren
- [x] Manifest, Versionsprüfung und SHA-256-Prüfsummen ergänzen
- [x] Administratorpasswort, Bestätigungsphrase und Sicherheitsbackup vor Restore erzwingen
- [x] Backup-Download und Löschung serverseitig schützen
- [x] Auditlogs ohne fachliche Klartextdaten ergänzen
- [x] Rechte-, Import-, Export-, Backup- und Modulprüfungen ergänzen
- [x] Gesamttests, Formatter, Build und Audits ausführen
- [x] Phase 13 und 14 vollständig prüfen
- [x] Entwicklungsstand veröffentlichen

## Phase 15: DSGVO

- [x] Datenmodell und Rechtekonzept für Datenschutzfunktionen spezifizieren
- [x] Granulares Recht zur Verwaltung von Datenschutzanfragen ergänzen
- [x] Persönlichen maschinenlesbaren Auskunftsexport implementieren
- [x] Öffentliche Datenschutzinformationen ergänzen
- [x] Löschanträge mit dokumentierter Aufbewahrungsprüfung implementieren
- [x] Konfigurierbare Mindestaufbewahrung und Pseudonymisierung ergänzen
- [x] Aktive Verträge, offene Rechnungen, SEPA, Dokumente und Ausgaben als Löschhindernisse prüfen
- [x] Freiwillige feldbezogene Datenfreigabe für aktuelle Mitpächter ergänzen
- [x] Freigaben standardmäßig deaktivieren und jederzeit widerrufbar machen
- [x] Auditlog für Export, Freigaben, Löschprüfung und Pseudonymisierung ergänzen
- [x] Migration auf MariaDB vorwärts und rückwärts prüfen
- [x] Fach-, Rechte- und Sicherheitstests ergänzen
- [x] Gesamttests, Formatter, Build und Audits ausführen
- [x] Phase 15 vollständig prüfen
- [x] Entwicklungsstand 0.2.0.43 veröffentlichen

## Phase 16: Vereinseinstellungen und Vorlagenbranding

- [x] Bestehende globale, SMTP- und SEPA-Konfiguration abgleichen
- [x] Offiziellen Vereinsnamen, Anschrift und Ansprechpartner ergänzen
- [x] Telefon, E-Mail-Adresse und Vereinswebseite ergänzen
- [x] Sicher validierten Upload für JPEG-, PNG- und WebP-Logos ergänzen
- [x] Vereinslogo privat speichern und kontrolliert ausliefern
- [x] Verschlüsselte Bankverbindung für Rechnungen ergänzen
- [x] Rechnungsbankverbindung klar vom SEPA-Lastschriftkonto trennen
- [x] Standard-Zahlungsziel für neue Abrechnungsperioden ergänzen
- [x] Konfigurierbare Dokumentfußzeile und E-Mail-Signatur ergänzen
- [x] Vereinsdaten, Logo und Bankverbindung in PDFs verwenden
- [x] Serienmails mit sicher dargestellter Vereinssignatur versehen
- [x] Absenderdaten für Rechnungen, Briefe, Mahnungen und Serienmails historisieren
- [x] Historische Logos bei Konfigurationswechseln erhalten
- [x] Datenschutzinformationen um den verantwortlichen Verein ergänzen
- [x] Administratorrechte und Auditlog beibehalten
- [x] Migrationen auf MariaDB vorwärts und rückwärts prüfen
- [x] Fach-, Verschlüsselungs-, Upload- und Snapshot-Tests ergänzen
- [x] Gesamttests, Formatter, Build und Audits ausführen
- [x] Phase 16 vollständig prüfen
- [x] Entwicklungsstand 0.2.0.44 veröffentlichen

## Phase 17: Nummernkreise

- [x] Bestehende Nummernvergaben und Eindeutigkeitsregeln prüfen
- [x] Nummernkreise für Mitglieder, Rechnungen, SEPA-Mandate und Dokumente spezifizieren
- [x] Formatplatzhalter `{JAHR}` und `{NUMMER}` implementieren
- [x] Mindeststellen, nächsten Zählerstand und optionalen Jahresneustart konfigurierbar machen
- [x] Vergabe mit Datenbanksperre und Kollisionsprüfung transaktionssicher umsetzen
- [x] Manuelle und importierte Fachnummern weiterhin zulassen
- [x] Bereits vergebene und historische Nummern unverändert erhalten
- [x] Automatische Mitgliedsnummer bei leerem Anlegefeld ergänzen
- [x] Automatische Mandatsreferenz bei leerem Anlegefeld ergänzen
- [x] Rechnungsberechnung auf den konfigurierbaren Nummernkreis umstellen
- [x] Dokumentnummern ergänzen und bestehende Dokumente rückwirkend nummerieren
- [x] Administrationsoberfläche mit Vorschau und Bedienhinweisen ergänzen
- [x] Änderungen der Nummernkreise auditieren
- [x] Rechte-, Validierungs-, Kollisions- und Integrationstests ergänzen
- [x] Migration ausschließlich nicht löschend auf Entwicklungsdatenbank anwenden
- [x] Gesamttests, Formatter, Build und Audits ausführen
- [x] Phase 17 vollständig prüfen
- [x] Entwicklungsstand 0.2.0.45 veröffentlichen

## Vorgezogene Phase 12: Arbeitseinsätze

- [x] Vorziehen von Phase 12 direkt nach Phase 9 dokumentieren
- [x] Datenmodell, Status und Rechtekonzept spezifizieren
- [x] Granulares Recht für Arbeitseinsätze ergänzen
- [x] Terminverwaltung innerhalb einer Abrechnungsperiode implementieren
- [x] Teilnehmerstatus und bestätigte Stunden implementieren
- [x] Manuelle und automatisch übernommene Stunden getrennt speichern
- [x] Arbeitsstundenkonten bei Änderungen sicher neu berechnen
- [x] Absagen und Abwesenheiten ohne Löschen historisieren
- [x] Auditlogs und Aktionshinweise ergänzen
- [x] Bedienhinweise und Navigation ergänzen
- [x] Feature- und Rechtetests ergänzen
- [x] Migration vorwärts und rückwärts isoliert prüfen
- [x] Migration ausschließlich nicht löschend auf Entwicklungsdatenbank anwenden
- [x] Gesamttests, Formatter und Asset-Build ausführen
- [x] Vorgezogene Phase 12 vollständig prüfen und veröffentlichen

## Nachbesserung Phase 9 und 12: Parzellenkonten

- [x] Arbeitsstundenpflicht auf Parzellen statt Mitglieder umstellen
- [x] Sichere Migrationsstrategie für bestehende Konten implementieren
- [x] Globale Pflichtstunden und Fehlstundensatz ergänzen
- [x] Konten aller vergebenen Parzellen aus Vereinsvorgaben erzeugen
- [x] Manuelle, Einsatz- und Pächtermeldungsstunden getrennt summieren
- [x] Arbeitseinsatzteilnahmen einer Parzelle zuordnen
- [x] Rechnungsposition je Parzelle statt je Mitpächter erzeugen
- [x] Pächterformular mit Tätigkeitsbeschreibung und privatem Foto ergänzen
- [x] Bestätigungs- und Ablehnungsworkflow implementieren
- [x] Gemeinschaftspacht auf gemeinsames Parzellenkonto testen
- [x] Migration isoliert vorwärts und rückwärts prüfen
- [x] Migration ausschließlich nicht löschend auf MariaDB anwenden
- [x] Gesamttests, Formatter, Build und Audits ausführen
- [x] Nachbesserung vollständig prüfen und veröffentlichen

## Entwicklungswerkzeug: Löschbarer Demo-Bestand

- [x] Vier Pächterkonten und ein Vorstandsmitglied anlegen
- [x] Fünf eindeutig markierte Demo-Parzellen zuordnen
- [x] Drei Jahre Abrechnungsperioden und historische Preise anlegen
- [x] Wasser- und Stromzähler mit Ständen und Zählerwechsel anlegen
- [x] Arbeitsstunden, Arbeitseinsätze und Pächtermeldungen anlegen
- [x] Offene Zählerstandsmeldung für den Prüfablauf anlegen
- [x] Wiederholbaren Seed-Befehl implementieren
- [x] Selektive vollständige Löschroutine implementieren
- [x] Schutz vorhandener Entwicklungsdaten im Seed-Purge-Seed-Zyklus prüfen
- [x] Demo-Zugänge lokal dokumentieren
- [x] Gesamttests, Formatter, Build und Audits ausführen
- [x] Demo-Bestand vollständig prüfen und veröffentlichen

## Nachbesserung Phase 9: Parzellendetail und Meldeseite

- [x] Arbeitsstundenkonten in der Parzellendetailansicht darstellen
- [x] Manuell anerkannte Stunden dort direkt bearbeitbar machen
- [x] Fehlende Konten mit vorausgewählter Parzelle anlegen
- [x] Pächter zur vorausgewählten Arbeitsstundenmeldung führen
- [x] Meldeseite gegen Konten ohne Mitgliedsverknüpfung absichern
- [x] Leere Parzellenzuordnung verständlich erklären
- [x] Regressionstests ergänzen
- [x] Gesamttests, Formatter, Build und Audits ausführen
- [x] Nachbesserung vollständig prüfen und veröffentlichen

## Nachbesserung Phase 12: Arbeitseinsatz anlegen

- [x] Anlegebutton in der Arbeitseinsatzübersicht ergänzen
- [x] Bearbeitbare Abrechnungsperiode auswählbar machen
- [x] Zustand ohne bearbeitbare Periode erklären
- [x] Sicheren Abbrechen-Link für Gartenwarte ergänzen
- [x] Administrator- und Gartenwartzugriff testen
- [x] Gesamttests, Formatter, Build und Audits ausführen
- [x] Nachbesserung vollständig prüfen und veröffentlichen

## Nachbesserung Phase 9: Automatische Parzellenkonten

- [x] Ursache der widersprüchlichen Kontostände zweier 2026-Perioden prüfen
- [x] Arbeitsstundenkonten beim Anlegen einer Abrechnungsperiode automatisch erzeugen
- [x] Konten bei späteren passenden Pächterzuordnungen automatisch ergänzen
- [x] Leere Synchronisation ohne Zurücksetzen eines Zwischenstands absichern
- [x] Manuelle Sammelvorbereitung aus der Oberfläche entfernen
- [x] Periodenzeiträume in der Parzellendetailansicht anzeigen
- [x] Demo- und Bestandsperiode 2026 ohne Datenbanklöschung berichtigen
- [x] Regressionstests und Projektdokumentation ergänzen
- [x] Gesamttests, Formatter und Asset-Build ausführen
- [x] Nachbesserung vollständig prüfen
- [x] Entwicklungsstand veröffentlichen

## Nachbesserung Projektbasis: HTTPS-Reverse-Proxy

- [x] Ursache der blockierten HTTP-Assets hinter dem HTTPS-Proxy ermitteln
- [x] Vertrauenswürdige Proxy-Adressen konfigurierbar machen
- [x] Öffentliche URL, Produktionsmodus und sichere Cookies lokal konfigurieren
- [x] HTTPS-Weiterleitungen und Asset-URLs über die öffentliche Domain prüfen
- [x] Reverse-Proxy-Konfiguration verständlich dokumentieren
- [x] Regressionstest für weitergereichtes HTTPS-Schema ergänzen
- [x] Gesamttests, Formatter, Build und Audits ausführen
- [x] Nachbesserung vollständig prüfen
- [x] Entwicklungsstand veröffentlichen

## Nachbesserung Projektbasis: 419 bei direktem HTTP-Zugang

- [x] Betroffenen Rechteverwaltungs-POST über die LAN-IP reproduzieren
- [x] Sicheres Cookie als Ursache des Sessionverlusts bestätigen
- [x] Cookie-Sicherheit automatisch an HTTP oder HTTPS anpassen
- [x] Öffentliche HTTPS-Cookies weiterhin mit `Secure` prüfen
- [x] Login und Rechteformular über die direkte LAN-IP prüfen
- [x] Regressionstest für beide Request-Schemata ergänzen
- [x] Gesamttests, Formatter, Build und Audits ausführen
- [x] Nachbesserung vollständig prüfen
- [x] Entwicklungsstand veröffentlichen

## Nachbesserung Demo-Bestand: Perioden und Anmeldung

- [x] Überschneidende echte und Demo-Abrechnungsperioden als Ursache bestätigen
- [x] Demo-Seed vor Überschneidungen mit bestehenden Perioden schützen
- [x] Web-Anmeldung aller fünf Demo-Konten automatisiert prüfen
- [x] Administrator vor dem lokalen Datenbankreset unverändert sichern
- [x] Entwicklungsdatenbank frisch migrieren und Administrator wiederherstellen
- [x] Demo-Bestand mit genau einer Periode je Jahr neu erzeugen
- [x] Rollen, Parzellenzuordnungen, Passwörter und Periodenüberschneidungen prüfen
- [x] Gesamttests, Formatter, Build und Audits ausführen
- [x] Nachbesserung vollständig prüfen
- [x] Entwicklungsstand veröffentlichen

## Nachbesserung Phase 3: Zeitanteile und Leistungszeiträume

- [x] Bestehende Behandlung von Eintritten, Austritten und Pächterwechseln prüfen
- [x] Abrechnungsperiode als Rechnungslauf und Preise mit eigenem Leistungszeitraum spezifizieren
- [x] Abrechnungsart Vorauszahlung und Nachberechnung ergänzen
- [x] Taggenaue Zeitanteilsregel für Mitglieds- und Parzellenkosten ergänzen
- [x] Pächterwechsel in getrennte Hauptpächteranteile aufteilen
- [x] Verbrauch auf den tatsächlichen Pacht- und Leistungszeitraum begrenzen
- [x] Preisvorlagen um Abrechnungsart und Zeitanteilsregel erweitern
- [x] Leistungszeitraum und Zeitanteil in Oberfläche, Rechnung und PDF anzeigen
- [x] Demo-Preise auf Vorauszahlung und Nachberechnung umstellen
- [x] Migration auf MariaDB vorwärts und rückwärts prüfen
- [x] Fachtests für Eintritt, Austritt, Pächterwechsel und gemischten Rechnungslauf ergänzen
- [x] Gesamttests, Formatter, Build und Audits ausführen
- [x] Nachbesserung vollständig prüfen
- [x] Entwicklungsstand veröffentlichen
- [ ] Eigenständige Gutschriften/Korrekturbelege für Änderungen nach Rechnungsfreigabe umsetzen

## Nachbesserung Phase 9: Anteilige Pflichtstunden

- [x] Jährliche Pflichtstunden und Belegungsfaktor historisch speichern
- [x] Belegungstage einer Parzelle ohne Doppelzählung von Mitpächtern ermitteln
- [x] Pflichtstunden bei unterjährigem Eintritt und Austritt taggenau berechnen
- [x] Lückenlosen Pächterwechsel als volle Parzellenbelegung behandeln
- [x] Manuelle Pflichtstundenabweichungen vor automatischem Überschreiben schützen
- [x] Alte und neue Parzelle bei einer geänderten Pächterzuordnung synchronisieren
- [x] Fehlstundenbetrag bei Pächterwechsel nach Hauptpächtertagen aufteilen
- [x] Belegungsanteil und manuelle Abweichung in der Oberfläche erklären
- [x] Fachtests für Teiljahr, Wechsel, manuelle Abweichung und Rechnungsaufteilung ergänzen
- [x] Migration auf MariaDB vorwärts und rückwärts prüfen
- [x] Gesamttests, Formatter, Build und Audits ausführen
- [x] Nachbesserung vollständig prüfen
- [x] Entwicklungsstand veröffentlichen
