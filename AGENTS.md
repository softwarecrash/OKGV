# AGENTS.md

## Verbindlicher Code Style

- Lies vor jeder Codeänderung `AGENT_CODE_STYLE.md`.
- Die dort definierten Regeln gelten projektweit für PHP, Laravel, Blade, Datenbank, Sicherheit, Tests und Veröffentlichung.
- Neue Muster dürfen nur eingeführt werden, wenn sie einen konkreten technischen Vorteil haben und in `AGENT_CODE_STYLE.md` dokumentiert werden.

## Arbeitsregeln

- Lies vor jeder neuen Produktphase `PHASE_PLAN.md`.
- Arbeite strikt phasenweise nach `PHASE_PLAN.md`, `PROJECT_SPEC.md` und
  `TODO.md`.
- `PHASE_PLAN.md` bestimmt Reihenfolge und Phasennummer. `PROJECT_SPEC.md`
  enthält die fachlichen Details.
- Beginne keine neue Phase, bevor Datenmodell und Rechtekonzept der vorherigen Phase geprüft sind.
- Dokumentiere jede Änderung in `CHANGELOG.md`.
- Aktualisiere `TODO.md` unmittelbar nach Abschluss einer Aufgabe.
- Behalte während der Bauphase die Basisversion `0.2.0` bei.
- Erhöhe für jeden veröffentlichten Entwicklungsstand nur die vierte Stelle: `0.2.0.1`, `0.2.0.2` und so weiter.
- Ändere die dreiteilige Basisversion erst nach einer ausdrücklich beschlossenen neuen Versionierungsphase.
- Erweitere bei Unklarheiten zuerst die Spezifikation.
- Entferne keine Funktionen ohne dokumentierte Begründung.
- Hardcode keine Geheimnisse oder personenbezogenen Daten.
- Prüfe Berechtigungen immer serverseitig mit Policies oder Gates.
- Verwende Form Requests für fachliche Schreiboperationen.
- Schreibe technische Kommentare auf Englisch.
- Halte sichtbare Benutzeroberflächen deutsch.
- Gestalte die Oberfläche für nichttechnische Vereinsmitglieder
  selbsterklärend; technisches Vorwissen darf nicht vorausgesetzt werden.
- Sichere erwartbare Fehleingaben bereits während der Eingabe ab und
  normalisiere technische Formate automatisch, sofern dadurch keine
  Mehrdeutigkeit entsteht.
- Ergänze bei erklärungsbedürftigen Feldern, Statuswechseln und Fachabläufen
  kurze deutsche Hilfetexte mit konkreten Beispielen oder Auswirkungen.
- Formuliere Validierungsfehler fachlich verständlich und nenne nach
  Möglichkeit direkt die erforderliche Korrektur.
- Erstelle in frühen Entwicklungsphasen keine Docker- oder Deployment-Artefakte.
- Führe auf der lokalen Entwicklungsdatenbank niemals `migrate:fresh`,
  `migrate:refresh`, `db:wipe` oder gleichwertige löschende Datenbankbefehle
  aus. Solche Prüfungen dürfen ausschließlich gegen eine eindeutig separate
  Testdatenbank erfolgen.

## Entwicklungsreihenfolge

1. Technische Basis
2. Datenbankstruktur
3. Models und Beziehungen
4. Policies und Rechte
5. CRUD-Oberflächen
6. Komfortfunktionen
7. Tests
8. Deployment

## Qualität

- Vor Abschluss einer Aufgabe mindestens `composer test` und `npm run build` ausführen.
- Migrationen müssen vorwärts und rückwärts ausführbar sein.
- Historische Fachwerte dürfen nicht überschrieben werden.
- Sensible Uploads gehören in private Storage-Verzeichnisse.
- Neue Formulare müssen auch ohne technisches Wissen verständlich bedienbar
  sein und erwartbare Fehleingaben serverseitig absichern.
