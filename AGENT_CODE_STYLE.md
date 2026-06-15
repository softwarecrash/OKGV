# OKGV Code Style für Agents

Diese Datei ist für alle menschlichen und automatisierten Mitwirkenden verbindlich. Neue Änderungen müssen den vorhandenen Projektstil fortführen. Abweichungen benötigen einen dokumentierten technischen Grund.

## Allgemeine Formatierung

- Verwende UTF-8, LF-Zeilenenden und eine abschließende Leerzeile.
- Rücke PHP, JavaScript, SCSS und Blade mit vier Leerzeichen ein.
- Rücke YAML mit zwei Leerzeichen ein.
- Entferne nachgestellte Leerzeichen.
- Formatiere PHP mit Laravel Pint. Vor einem Commit muss `vendor/bin/pint --test` erfolgreich sein.
- Behalte Änderungen eng am jeweiligen Fachmodul. Vermische keine unabhängigen Refactorings mit einer Fachänderung.
- Verwende technische Bezeichner und Kommentare auf Englisch.
- Verwende sichtbare Texte, Validierungsfehler und Beschriftungen auf Deutsch.
- Kommentiere nur nicht offensichtliche Fach- oder Sicherheitslogik. Kommentare erklären das Warum, nicht den unmittelbar sichtbaren Code.

## Lizenzierung

- Neue OKGV-Projektdateien stehen unter `AGPL-3.0-only`.
- Verwende in Paketmetadaten den SPDX-Ausdruck `AGPL-3.0-only`.
- Bewahre Lizenz- und Urheberrechtshinweise eingebundener
  Drittanbieter-Komponenten unverändert.
- Der sichtbare Quellcode-Link muss auf den vollständigen Quellcode der
  tatsächlich betriebenen Version konfigurierbar bleiben.
- Änderungen an Lizenz, Lizenzhinweisen oder Quellcode-Angebot müssen in
  README, Projektspezifikation und Changelog dokumentiert werden.

## PHP und Laravel

- Halte PSR-4-Namensräume und die bestehende Verzeichnisstruktur ein.
- Verwende Typdeklarationen für Parameter und Rückgabewerte.
- Verwende `final` für zustandslose Services, die nicht zur Erweiterung vorgesehen sind.
- Verwende Constructor Property Promotion und `readonly` für injizierte Services.
- Verwende Enums statt frei verteilter Status- oder Typ-Strings.
- Stelle für sichtbare Enum-Werte eine deutsche `label()`-Methode bereit.
- Stelle Einheiten oder weitere feste Metadaten direkt am zugehörigen Enum bereit.
- Verwende frühe Rückgaben, um Verschachtelung gering zu halten.
- Verwende benannte Argumente, wenn mehrere gleichartige Argumente sonst schwer unterscheidbar wären.
- Verwende keine globalen Hilfsfunktionen für neue Fachlogik. Kapsle sie in klar benannte Services oder Models.
- Nutze Laravel-Fassaden und Framework-APIs in der im Projekt etablierten Form.
- Verwende `bccomp`, `bcadd` und `bcsub` für abrechnungsrelevante Dezimalwerte. Nutze dafür keine Fließkommaarithmetik.

## Schichten und Verantwortlichkeiten

### Funktionsmodule

- Prüfe vor neuen Fachbereichen, ob sie zum Kern oder zu einem Eintrag in
  `FeatureModule` gehören.
- Schaltbare Module müssen über `config/modules.php` und einen dokumentierten
  `OKGV_MODULE_*`-Wert konfigurierbar sein.
- Sichere sämtliche Modulrouten mit der Middleware `module:<key>`.
- Blende deaktivierte Module zusätzlich aus Navigation, Dashboard,
  Detailansichten, Pächterportal, Rechteauswahl und Aktionshinweisen aus.
- Ein ausgeblendeter Link ersetzt niemals die Modul-Middleware.
- Automatische Services und fachübergreifende Berechnungen müssen den
  Modulstatus ebenfalls prüfen.
- Definiere Modulabhängigkeiten zentral am `FeatureModule`-Enum. Verteile
  Abhängigkeitsregeln nicht als freie Strings über Controller oder Views.
- Deaktivieren eines Moduls darf keine Tabellen, Datensätze, Historien oder
  bereits zugewiesenen Rechte löschen.
- Modulabhängige Rechte bleiben gespeichert, werden während der Deaktivierung
  aber nicht als wirksame Berechtigung behandelt.
- SMTP bleibt Kernfunktion für Authentifizierungsmails; Serienmails und
  PDF-Briefe gehören zum Kommunikationsmodul.
- Ergänze Tests für direkte URLs, Administratorzugriff, Navigation,
  Abhängigkeiten, Datenhaltbarkeit und fachübergreifende Auswahlfelder.

### Datenübertragung und Backups

- CSV-Importe müssen vollständig transaktional sein. Eine fehlerhafte Zeile
  darf keine Teilimporte hinterlassen.
- Bestehende historische Datensätze wie Zähler und Zählerstände dürfen durch
  einen Import nicht überschrieben werden.
- CSV-Fehler nennen die konkrete Zeile und eine verständliche Korrektur.
- Backups bleiben im privaten Storage und dürfen keine `.env` oder Klartext-
  Geheimnisse ergänzen.
- Restore muss Archivpfade, Format, Version und Prüfsummen vor jeder Änderung
  prüfen und vorher ein Sicherheitsbackup erstellen.
- Vollständige Backups und Restore bleiben unabhängig von granularen
  Vorstandsrechten ausschließlich Administratoren vorbehalten.

### Datenschutz

- Personenbezogene Daten werden standardmäßig nicht mit anderen Mitgliedern
  geteilt. Jede Freigabe braucht ein ausdrückliches, feldbezogenes Opt-in.
- Prüfe bei Freigaben zusätzlich serverseitig den konkreten fachlichen Bezug,
  beispielsweise eine aktuell gemeinsam belegte Parzelle.
- Archivierung, Löschanfrage und Pseudonymisierung sind getrennte Zustände und
  dürfen in Code oder Benutzertexten nicht gleichgesetzt werden.
- Lösche historisch oder gesetzlich aufzubewahrende Fachdatensätze nicht
  stillschweigend. Dokumentiere blockierende Gründe verständlich.
- Destruktive Datenschutzaktionen bleiben Administratoren vorbehalten,
  verlangen eine erneute Passwortprüfung und werden auditiert.
- Auskunftsexporte dürfen keine Passworthashes, Sessiondaten oder unnötige
  Geheimnisse anderer Personen enthalten.

### Controller

- Controller bleiben klein und koordinieren nur Request, Policy, Service, Model und Response.
- Autorisiere jeden fachlichen Endpunkt explizit mit Policies.
- Verwende Form Requests für alle fachlichen Schreiboperationen.
- Lege komplexe oder transaktionale Geschäftslogik nicht im Controller ab.
- Lade Beziehungen gezielt und vermeide unbeabsichtigte N+1-Abfragen.
- Verwende benannte Routen und Redirects.
- Setze deutsche Flash-Meldungen nach erfolgreichen Schreiboperationen.

### Form Requests

- `authorize()` delegiert an Policies und enthält keine parallele Rechteimplementierung.
- `rules()` enthält strukturelle und feldbezogene Validierung.
- Verwende Laravel-Regelobjekte wie `Rule::enum`, `Rule::unique` und `Rule::exists`.
- Verwende `after()` nur für datenbankabhängige, fachübergreifende Validierung.
- Wiederhole kritische Konsistenzprüfungen zusätzlich im transaktionalen Service, wenn parallele Requests möglich sind.
- Begrenze Freitextfelder explizit.
- Validiere Datumsreihenfolgen und nichtnegative Dezimalwerte.

### Services

- Services kapseln fachliche Abläufe, Berechnungen und Transaktionen.
- Transaktionale Services sperren den kleinsten stabilen übergeordneten Datensatz mit `lockForUpdate()`.
- Prüfe Geschäftsbedingungen nach dem Sperren erneut.
- Wirf bei fachlich ungültigen Benutzereingaben `ValidationException::withMessages()`.
- Halte Berechnungsservices deterministisch und frei von UI-Verantwortung.
- Gib erzeugte oder veränderte Models zurück, wenn der aufrufende Controller sie für Redirects oder Auditlogs benötigt.

### Models

- Models definieren Casts, Beziehungen, lokale Scopes und kleine modellnahe Hilfsmethoden.
- Verwende Laravel-Attribute für `Fillable` und `Hidden`, wie im Projekt etabliert.
- Caste Status- und Typfelder auf Enums.
- Caste Datumsfelder auf Laravel-Datumsobjekte.
- Caste Dezimalwerte mit fester Präzision, zum Beispiel `decimal:4`.
- Benenne Beziehungen fachlich und eindeutig, etwa `readings()`, `tenancies()` oder `parcelTenancies()`.
- Verwende Query Scopes für wiederkehrende Filter wie Suche oder zeitliche Gültigkeit.
- Lege komplexe, mehrere Models betreffende Geschäftsabläufe nicht in Model Events ab.

### Policies

- Alle Berechtigungen werden serverseitig geprüft.
- Verwende `viewAny`, `view`, `create`, `update` und fachliche Fähigkeiten wie `archive` oder `replace`.
- Hinterlege sichere Standardrechte je Rolle am `UserRole`-Enum.
- Prüfe fachliche Zugriffe über Berechtigungsmethoden am `User`; eine Rolle
  allein darf einem Vorstandsmitglied keine sensiblen Rechte eröffnen.
- Neue sensible Fachbereiche erhalten einen eigenen, verständlich
  beschrifteten Berechtigungsschlüssel und werden nicht stillschweigend an
  bestehende Sammelrechte angehängt.
- Rechtevorlagen werden bei Zuweisung als Snapshot gespeichert. Änderungen an
  einer Vorlage dürfen bestehende Benutzerrechte nicht unbemerkt verändern.
- Pächterzugriffe werden immer über die eindeutige Benutzer-Mitglied-Zuordnung und die zugehörigen Fachdaten eingeschränkt.
- Ein ausgeblendeter Button ersetzt niemals eine Policy.

## Datenbank und Migrationen

- Erstelle pro fachlicher Tabelle eine eigene Migration.
- Migrationen müssen vollständig vorwärts und rückwärts ausführbar sein.
- Verwende Fremdschlüssel mit bewusst gewähltem Löschverhalten.
- Verwende `restrictOnDelete()` für unverzichtbare historische Beziehungen.
- Verwende `nullOnDelete()` nur, wenn der historische Datensatz ohne den Bezug sinnvoll erhalten bleibt.
- Lege Eindeutigkeitsregeln und häufige Suchpfade als Datenbankindizes an.
- Verwende für abrechnungs- und zählerrelevante Werte `decimal`, niemals `float` oder `double`.
- Historische Datensätze werden nicht physisch gelöscht.
- Bestehende historische Werte werden nicht überschrieben, wenn die Fachregel einen neuen Korrekturdatensatz verlangt.
- Verwende reversible Archivierung statt Löschung, wenn Aufbewahrung erforderlich ist.
- Verlasse dich bei Regeln mit Parallelitätsrisiko nicht ausschließlich auf Formvalidierung.

## Audit und Sicherheit

- Auditiere sicherheits- und fachrelevante Schreiboperationen.
- Benenne Auditaktionen als stabile englische Schlüssel im Format `bereich.aktion`, zum Beispiel `meter.replaced`.
- Speichere keine Passwörter, Bankdaten oder vollständigen personenbezogenen Änderungswerte im Auditlog.
- Speichere bei Änderungen bevorzugt nur `changed_fields`.
- Nutze verschlüsselte Audit-Metadaten für notwendige Zusatzinformationen.
- Hardcode keine Geheimnisse, Zugangsdaten oder personenbezogenen Beispieldaten.
- Sensible lokale Werte gehören ausschließlich in die ignorierte `.env`.
- Private Uploads gehören in private Storage-Verzeichnisse.
- Neue Uploadfunktionen benötigen MIME-, Größen- und Dateitypprüfung.
- Öffentliche Schreibendpunkte benötigen Rate Limiting, sobald sie eingeführt werden.
- Neue öffentlich beantragte Konten müssen ihre E-Mail-Adresse bestätigen,
  bevor sie geschützte Anwendungsbereiche verwenden dürfen.

## Historische Daten

- Historien müssen dauerhaft nachvollziehbar bleiben.
- Verwende Start- und Enddaten statt den aktuellen Zustand rückwirkend umzuschreiben.
- Verhindere überschneidende Zeiträume, wenn die Fachregel Eindeutigkeit fordert.
- Verwende Transaktionen für Wechselprozesse, die einen alten Datensatz abschließen und einen neuen anlegen.
- Append-only-Daten wie Zählerstände erhalten keine Update- oder Delete-Routen.
- Korrekturen an Append-only-Daten erfolgen als neuer, nachvollziehbarer Datensatz.

## Blade und Benutzeroberfläche

- Sichtbare Oberfläche und Meldungen sind deutsch.
- Die primären Nutzer sind häufig nichttechnische Vorstände und Pächter.
  Verwende keine unerklärten technischen Begriffe, Codes oder internen
  Statuswerte.
- Formulare müssen möglichst selbsterklärend sein. Ergänze bei Feldern mit
  Formatvorgaben, fachlichen Folgen oder ungewöhnlichen Begriffen einen kurzen
  `.form-text`-Hinweis.
- Zeige bei technischen Bezeichnern ein konkretes Beispiel und erkläre
  erlaubte Zeichen oder automatische Umwandlungen.
- Fange erwartbare Eingabefehler möglichst bereits clientseitig ab, etwa durch
  geeignete Eingabetypen, `min`, `max`, `step`, `maxlength`, Auswahlfelder
  oder kleine Alpine.js-Normalisierungen.
- Normalisiere eindeutige technische Formate während der Eingabe, wenn dies
  verlustfrei möglich ist, beispielsweise Leerzeichen zu Unterstrichen oder
  Codes zu Großbuchstaben.
- Clientseitige Eingabehilfe ersetzt niemals die identische serverseitige
  Normalisierung und Validierung im Form Request.
- Verwirf Benutzereingaben nicht stillschweigend. Automatische Änderungen
  müssen im Feld sichtbar werden oder durch einen Hilfetext erklärt sein.
- Validierungsfehler nennen das betroffene Fachproblem und möglichst die
  konkrete Lösung. Vermeide rohe Datenbank-, Regex-, Enum- oder
  Frameworkmeldungen.
- Deaktiviere oder verstecke nicht verfügbare Aktionen nicht kommentarlos,
  wenn der Grund für Nutzer relevant ist. Zeige dann einen kurzen Status- oder
  Erklärungstext.
- Riskante oder irreversible Fachaktionen benötigen eine verständliche
  Beschreibung ihrer Folgen und, sofern ein Fehlklick realistisch ist, eine
  Bestätigung.
- Leere Listen und Erstzustände enthalten einen verständlichen Hinweis und,
  falls berechtigt, einen klaren nächsten Handlungsschritt.
- Erfolgreiche Schreiboperationen bestätigen in Alltagssprache, was
  gespeichert oder verändert wurde.
- Verwende Bootstrap-5-Komponenten und die vorhandenen OKGV-Farben.
- Halte Ansichten responsiv und verwende Tabellen in `.table-responsive`.
- Verwende gemeinsame Partials oder Blade-Komponenten für wiederkehrende Formbereiche und Fehlerausgabe.
- Formulare enthalten CSRF-Schutz und bei Bedarf die korrekte HTTP-Methodensimulation.
- Zeige Aktionen nur mit `@can`, prüfe dieselbe Fähigkeit aber zusätzlich im Controller oder Form Request.
- Escape Benutzereingaben standardmäßig mit Blade.
- Falls Zeilenumbrüche dargestellt werden, verwende das bestehende Muster `{!! nl2br(e($value)) !!}`.
- Verwende keine Inline-Skripte für Fachlogik. Alpine.js ist für kleine UI-Zustände vorgesehen.
- Halte interne Notizen für Pächter unsichtbar.

### UX-Prüfung

- Prüfe neue oder geänderte Formulare aus Sicht eines Nutzers ohne
  Programmier- oder Datenbankkenntnisse.
- Teste automatische Normalisierung zusätzlich serverseitig mit direkten
  Requests, damit die Funktion auch ohne JavaScript sicher arbeitet.
- Ergänze Feature-Tests für fachlich wichtige Hilfsmechanismen,
  Validierungsgrenzen und verständliche Fehlerzustände.
- Achte bei mobilen Ansichten darauf, dass Hilfetexte, Fehlermeldungen und
  Aktionsschaltflächen ohne horizontales Scrollen verständlich bleiben.

## Routen und Benennung

- Verwende REST-Ressourcenrouten für gewöhnliche CRUD-Abläufe.
- Lass nicht erlaubte Aktionen wie `destroy` explizit weg.
- Verwende eigene benannte Routen für Fachabläufe wie Archivierung oder Zählerwechsel.
- Controller heißen nach der verwalteten Ressource oder dem Fachablauf.
- Services heißen nach ihrer Verantwortung, zum Beispiel `MeterManager` oder `ConsumptionCalculator`.
- Tests benennen das erwartete Verhalten vollständig in `snake_case`.
- Verwende englische Tabellen-, Spalten-, Klassen- und Methodennamen.
- Verwende deutsche Labels nur in der sichtbaren Oberfläche.

## Tests

- Jede neue Fachregel benötigt mindestens einen Test.
- Verwende Feature-Tests für HTTP, Policies, Validierung, Datenbankwirkung und Auditlogs.
- Verwende `RefreshDatabase`, damit Tests unabhängig bleiben.
- Erzeuge Testdaten mit Factories.
- Prüfe sowohl erlaubte als auch verbotene Rollen.
- Prüfe Pächterisolation ausdrücklich gegen fremde Datensätze.
- Prüfe bei Schreiboperationen Redirects, Validierungsfehler und Datenbankzustand.
- Prüfe historische Regeln, Randdaten und konfliktbehaftete Zeiträume.
- Prüfe, dass verbotene Update- oder Delete-Routen tatsächlich fehlen.
- Verwende feste, lesbare Testwerte für fachliche Berechnungen.
- Ergänze für jeden Vorgang, bei dem Pächter, Vorstand oder eine andere Rolle
  handeln muss, einen rollenabhängigen Aktionshinweis in der passenden
  Navigationsgruppe. Berechne Hinweise zentral und zeige sie nur Personen,
  die die Aufgabe serverseitig bearbeiten dürfen.
- Vor Veröffentlichung müssen mindestens folgende Befehle erfolgreich sein:

```bash
composer test
vendor/bin/pint --test
composer validate --strict
composer audit
npm run build
npm audit --audit-level=high
```

- Neue Migrationen werden zusätzlich mit Rollback und erneutem Migrate geprüft.
- Rollback- und Neuaufbauprüfungen dürfen niemals gegen die lokale
  Entwicklungsdatenbank laufen. Verwende dafür ausschließlich die
  isolierte Testdatenbank; auf der Entwicklungsdatenbank ist nur das
  vorwärtsgerichtete `php artisan migrate` zulässig.

## Dokumentation und Veröffentlichung

- Aktualisiere `PROJECT_SPEC.md`, bevor unklare Fachregeln implementiert werden.
- Hake abgeschlossene Aufgaben unmittelbar in `TODO.md` ab.
- Dokumentiere jede Änderung in `CHANGELOG.md`.
- Aktualisiere `README.md`, wenn Installation, Bedienung oder verfügbare Module betroffen sind.
- Erhöhe den Entwicklungsstand nach der aktuellen Regel nur an der vierten Stelle.
- Veröffentliche keine Version, solange Tests, Formatter, Builds oder Audits fehlschlagen.
- Commit-Nachrichten sind kurz, englisch und beschreiben den fachlichen Inhalt.
- Git-Tags verwenden das Format `v0.2.0.<Build>`.
