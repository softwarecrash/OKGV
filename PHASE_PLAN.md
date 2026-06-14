# OKGV Phasenplan

Diese Datei ist die verbindliche Reihenfolge für die weitere Entwicklung von
OKGV. `PROJECT_SPEC.md` enthält die fachlichen Details. Bei abweichender
Phasennummerierung bestimmt diese Datei die Zuordnung und Reihenfolge.

Es wird immer nur eine Phase gleichzeitig bearbeitet. Vor Beginn einer neuen
Phase müssen Datenmodell, Rechtekonzept, offene Tests und Dokumentation der
vorherigen Phase geprüft sein.

## Status

- `[x]` abgeschlossen
- `[ ]` noch nicht begonnen oder nicht vollständig abgeschlossen

## Projektweite UX-Regeln

- [x] Fachbereiche in kompakten Navigationsgruppen zusammenfassen
- [x] Leuchtenden Aktionspunkt für rollenabhängig offene Aufgaben verwenden
- [x] Aktionspunkte in zukünftigen Phasen für jeden notwendigen Benutzer-
  oder Vorstandsschritt ergänzen
- [x] Hellen und dunklen Darstellungsmodus bereitstellen
- [x] Vorstandsrechte granular und über konfigurierbare Vorlagen vergeben
- [x] Globalen Systemnamen konfigurierbar machen

## Phase 0: Projektbasis

- [x] Projektbasis
- [x] Authentifizierung
- [x] Rollenmodell
- [x] Policies
- [x] Auditlog

## Phase 1: Stammdaten

- [x] Mitgliederverwaltung
- [x] Parzellenverwaltung
- [x] Pächterhistorie

## Phase 2: Zählerverwaltung

- [x] Wasserzähler
- [x] Stromzähler
- [x] Zählerstände
- [x] Zählerwechsel
- [x] Historische Zähler

## Phase 3: Abrechnungssystem

- [x] Abrechnungssystem
- [x] Historische Preise
- [x] Pacht pro m²
- [x] Strom pro kWh
- [x] Wasser pro m³
- [x] Bereitstellungsgebühren
- [x] Mitgliedsbeiträge
- [x] Umlagen
- [x] Versicherungen
- [x] PDF-Rechnungen
- [x] Konfigurierbare Preisvorlagen

## Phase 4: SEPA

- [x] SEPA-Mandate
- [x] pain.008-XML-Export
- [x] Rücklastschriften
- [x] Zahlungsstatus

## Phase 5: Pächterportal

- [x] Pächterportal
- [x] Pächter können sich mit ihrer Parzellennummer registrieren
- [x] Zuordnung muss durch Vorstand oder Administrator bestätigt werden
- [x] Eigene Rechnungen
- [x] Eigene Dokumente
- [x] Eigene Zählerstände
- [x] Zählerstände mit Foto melden
- [x] Neue Konten nach Freigabe per E-Mail verifizieren
- [x] Freigegebene Pächterkonten administrativ zum Vorstand hochstufen

## Phase 6: Dokumentenverwaltung

- [x] Nach der vorgezogenen Phase 7 wieder aufgenommen
- [x] Dokumentenverwaltung
- [x] Verträge
- [x] Übergabeprotokolle
- [x] Satzungen
- [x] Rechnungen
- [x] Fotos

## Phase 7: Kommunikation

- [x] Datenmodell und Rechtekonzept vollständig spezifizieren
- [x] Serienmails mit Empfängergruppen und Versandhistorie
- [x] Verschlüsselte SMTP-Konfiguration und Testversand
- [x] PDF-Briefe mit Empfänger-Snapshot
- [x] PDF-Zahlungserinnerungen ohne Mahnstufe oder Mahngebühr
- [x] Auditlogs, Tests und Bedienhinweise

## Phase 8: Mahnwesen

- [x] Mahnwesen

## Phase 9: Arbeitsstunden

- [x] Arbeitsstunden
- [x] Strafzahlungen
- [x] Parzellenbezogene Konten statt Einzelkonten je Mitpächter
- [x] Globale Vereinsvorgaben und Sammelvorbereitung
- [x] Prüfpflichtige Pächtermeldungen mit optionalem Foto

## Phase 10: Warteliste

- [ ] Warteliste

## Phase 11: Inventarverwaltung

- [ ] Inventarverwaltung
- [ ] Geräte
- [ ] Schlüssel
- [ ] Anhänger
- [ ] Vereinsinventar

## Phase 12: Arbeitseinsätze

- [x] Auf ausdrücklichen Wunsch direkt nach Phase 9 vorgezogen
- [x] Arbeitseinsätze
- [x] Terminverwaltung
- [x] Stundenübernahme

## Phase 13: CSV-Import

- [ ] CSV-Import

## Phase 14: CSV-Export

- [ ] CSV-Export

## Phase 15: DSGVO

- [ ] DSGVO-Funktionen

## Phase 16: Vereinseinstellungen

- [ ] Vereinseinstellungen

## Phase 17: Nummernkreise

- [ ] Nummernkreise

## Phase 18: Pächterwechsel

- [ ] Pächterwechsel
- [ ] Übergabeprozess

## Phase 19: Lageplan

- [ ] SVG-Lageplan

## Phase 20: Deployment

- [ ] Deployment
- [ ] Docker
- [ ] Docker Compose
- [ ] Backupstrategie
- [ ] Produktionsdokumentation

## Nächster Schritt

Die Phasen 0 bis 9 sowie die fachlich zu Phase 9 gehörende, vorgezogene
Phase 12 sind abgeschlossen. Als Nächstes folgt Phase 10: Warteliste.
