# AGENTS.md

## Arbeitsregeln

- Arbeite strikt phasenweise nach `PROJECT_SPEC.md` und `TODO.md`.
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
- Erstelle in frühen Entwicklungsphasen keine Docker- oder Deployment-Artefakte.

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
