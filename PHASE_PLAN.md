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
- [x] Wiederholbaren und vollständig löschbaren Demo-Datenbestand bereitstellen

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
- [x] Taggenaue Abrechnung bei Ein- und Austritten
- [x] Vorauszahlungen und Verbrauchsnachberechnungen in einem Rechnungslauf
- [x] Eigene Leistungszeiträume je Preis

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
- [x] Globale Vereinsvorgaben und automatische Parzellenkonten
- [x] Pflichtstunden bei unterjähriger Verpachtung taggenau anteilig berechnen
- [x] Fehlstundenbetrag bei Pächterwechsel nach Hauptpächtertagen aufteilen
- [x] Prüfpflichtige Pächtermeldungen mit optionalem Foto
- [x] Arbeitsstundenübersicht und Direkterfassung in der Parzellendetailansicht

## Phase 10: Warteliste

- [x] Datenmodell und Statuskonzept spezifiziert
- [x] Warteliste
- [x] Eigenes granulares Recht
- [x] Suche, Filter und Prioritätssortierung
- [x] Auditlog und Aktionshinweis

## Phase 11: Inventarverwaltung

- [x] Inventarverwaltung
- [x] Geräte, Schlüssel, Anhänger und weitere frei definierbare Inventargegenstände
- [x] Optionale Anschaffungsdaten, Kosten, Seriennummern und Standorte
- [x] Ausgabe, Rückgabe und dauerhafte Historie
- [x] Granulares Recht und Aktionshinweis für überfällige Rückgaben

## Phase 12: Arbeitseinsätze

- [x] Auf ausdrücklichen Wunsch direkt nach Phase 9 vorgezogen
- [x] Arbeitseinsätze
- [x] Terminverwaltung
- [x] Stundenübernahme
- [x] Direkter Anlegezugang aus der Arbeitseinsatzübersicht
- [x] Bearbeitbare Abrechnungsperiode beim Anlegen auswählen

## Phase 12.1: Modularisierung

- [x] Funktionsbereiche wie Arbeitseinsätze, Inventar, Warteliste, Arbeitsstunden und SEPA modularisieren und konfigurierbar aktivier- oder deaktivierbar machen, damit später unterschiedliche Ausbaustufen möglich sind
- [x] Abhängigkeiten zentral prüfen und ungültige Kombinationen ablehnen
- [x] Routen, Navigation, Rechte, Aktionshinweise und Fachlogik absichern
- [x] Daten und Rechte deaktivierter Module dauerhaft erhalten

## Phase 13: Datenübertragung

- [x] Die bisherigen Phasen 13 und 14 zu einer gemeinsamen Phase zusammengeführt
- [x] CSV-Import für Mitglieder, Parzellen, Zähler und Zählerstände
- [x] CSV-Export für Mitglieder, Parzellen, Zähler, Zählerstände und Rechnungen
- [x] Vollständige manuelle Backups und Wiederherstellung

## Phase 14: In Phase 13 zusammengeführt

- [x] CSV-Export als Bestandteil der Datenübertragung umgesetzt

## Phase 15: DSGVO

- [x] DSGVO-Funktionen
- [x] Funktion zur einstellung ob andere pächter meine daten und welche sehen dürfen (opt in)

## Phase 16: Vereinseinstellungen

- [x] Vereinseinstellungen

## Phase 16: PDF / Rechnungs vorlagen

- [x] Individualisierbare rechnungen, pdf, mail mit eigenem logo, fußzeile uws

## Phase 17: Nummernkreise

- [x] Konfigurierbare Mitgliedsnummern
- [x] Konfigurierbare Rechnungsnummern
- [x] Konfigurierbare SEPA-Mandatsreferenzen
- [x] Konfigurierbare Dokumentnummern
- [x] Platzhalter für Jahr und fortlaufende Nummer
- [x] Optionaler jährlicher Neustart
- [x] Transaktionssichere Vergabe mit Kollisionsprüfung
- [x] Historische und manuell vergebene Nummern unverändert erhalten

## Phase 18: Pächterwechsel

- [ ] Pächterwechsel
- [ ] Übergabeprozess

## Phase 19: Lageplan

- [x] Privates Luft- oder Lagebild mit dokumentiertem Nutzungsrecht
- [x] WYSIWYG-Polygoneditor für beliebige Parzellenformen
- [x] Eckpunkte und gesamte Flächen per Drag-and-drop verschieben
- [x] Validierte Polygonpunkte relativ zur Bildgröße
- [x] Statusfarben mit zusätzlicher Textkennzeichnung
- [x] Klickbare Parzellen mit geschützten Detaillinks
- [x] Liste noch nicht platzierter Parzellen
- [x] Pächterisolation und bestehende Stammdatenrechte
- [x] Polygonpunkte in Parzellen-CSV-Import und -Export
- [x] Hintergrundbild in Backup und Restore

## Phase 20: Deployment

- [ ] Deployment
- [ ] Docker
- [ ] Docker Compose
- [ ] Automatisierte Zeitpläne, externe Backupziele und Aufbewahrungsstrategie
- [ ] Produktionsdokumentation

## Nächster Schritt

Die Phasen 0 bis 17 sowie die auf Wunsch vorgezogene Phase 19 sind
abgeschlossen. Phase 18 mit Pächterwechsel und Übergabeprozess bleibt offen.
Danach folgt Phase 20 mit Deployment und Produktionsdokumentation.
