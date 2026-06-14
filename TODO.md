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
- [ ] Vorgezogene Phase 12 vollständig prüfen und veröffentlichen
