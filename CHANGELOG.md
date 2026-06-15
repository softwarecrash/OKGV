# Changelog

Alle wesentlichen Änderungen an OKGV werden in dieser Datei dokumentiert.

## [0.2.0.55] - 2026-06-15

### Vorheriger Zählerstand in der Prüfübersicht

- Die Übersicht der Zählerstandsmeldungen zeigt den vorherigen wirksamen
  Zählerstand mit Datum und Einheit direkt neben dem gemeldeten Wert.
- Historisierte Korrekturen werden bei der Ermittlung des Vergleichswerts
  berücksichtigt.
- Existiert noch keine Ablesung, wird der Einbaustand des Zählers verwendet.
- Ein gemeldeter Wert unterhalb des vorherigen Stands wird bereits vor der
  Bestätigung rot und mit einem verständlichen Hinweis markiert.
- Insgesamt bestehen 188 Tests mit 1.097 Assertions.
- Composer- und npm-Abhängigkeiten weisen keine bekannten
  Sicherheitswarnungen auf.
- Entwicklungsstand auf `0.2.0.55` erhöht.
- Entwicklungsstand `0.2.0.55` auf GitHub veröffentlicht.

## [0.2.0.54] - 2026-06-15

### Rückmeldung beim Bestätigen von Zählerständen

- Plausibilitätsfehler beim Bestätigen einer Zählerstandsmeldung werden nun
  sichtbar oberhalb der Tabelle erklärt.
- Die betroffene Meldung wird rot markiert, statt nach dem Neuladen scheinbar
  unverändert ohne Rückmeldung stehen zu bleiben.
- Der Hinweis erklärt, dass falsche Meldungen begründet abgelehnt und vom
  Pächter neu eingereicht werden müssen.
- Die Übersicht erläutert, dass erfolgreich bearbeitete Meldungen aus
  Nachvollziehbarkeitsgründen weiterhin als Historie sichtbar bleiben.
- Die Demo-Zählerstandsmeldung verwendet künftig einen Wert oberhalb des
  bereits vorhandenen letzten Stands.
- Die bestehende private Meldung `#5` wurde nicht verändert: Ihr Wert `205`
  liegt unter dem vorhandenen Stand `206` und muss daher abgelehnt werden.
- Insgesamt bestehen 188 Tests mit 1.094 Assertions.
- Composer- und npm-Abhängigkeiten weisen keine bekannten
  Sicherheitswarnungen auf.
- Entwicklungsstand auf `0.2.0.54` erhöht.
- Entwicklungsstand `0.2.0.54` auf GitHub veröffentlicht.

## [0.2.0.53] - 2026-06-15

### Zählerstandsfotos prüfen

- Private Fotos aus Zählerstandsmeldungen werden bei der Prüfung nicht mehr
  als Download geöffnet.
- Die geschützte Fotoroute liefert zulässige Bilddateien mit Inline-
  Darstellung, privatem Cache-Header und `nosniff` aus.
- Ein Bootstrap-Modal zeigt das Foto innerhalb der Meldungsübersicht und lädt
  es erst beim Öffnen.
- Die Vorschau lässt sich von 100 bis 500 Prozent vergrößern, über Strg und
  Mausrad zoomen und mit gedrückter Maustaste verschieben.
- Beim Schließen des Modals wird die private Bildquelle wieder entfernt.
- Bestehende Policy-Prüfungen verhindern weiterhin jeden Zugriff auf fremde
  Meldungsfotos.
- Insgesamt bestehen 188 Tests mit 1.090 Assertions.
- Composer- und npm-Abhängigkeiten weisen keine bekannten
  Sicherheitswarnungen auf.
- Entwicklungsstand auf `0.2.0.53` erhöht.
- Entwicklungsstand `0.2.0.53` auf GitHub veröffentlicht.

## [0.2.0.52] - 2026-06-15

### Konstante Editor-Griffe

- Die kreisförmigen Eckpunkt-Griffe im Polygoneditor behalten unabhängig von
  der Zoomstufe dieselbe sichtbare Größe.
- Der SVG-Radius wird gegenläufig zur aktuellen Vergrößerung berechnet, damit
  die Griffe bei 400 Prozent nicht große Teile der Parzelle verdecken.
- Konturbreite und Bedienbarkeit der Griffe bleiben erhalten.
- Insgesamt bestehen 188 Tests mit 1.076 Assertions.
- Composer- und npm-Abhängigkeiten weisen keine bekannten
  Sicherheitswarnungen auf.
- Entwicklungsstand auf `0.2.0.52` erhöht.
- Entwicklungsstand `0.2.0.52` auf GitHub veröffentlicht.

## [0.2.0.51] - 2026-06-15

### Direkte Lageplanbedienung

- Der zusätzliche Schalter `Karte verschieben` wurde wieder entfernt.
- Vergrößerte Lagepläne lassen sich nun unmittelbar mit gedrückter Maustaste
  greifen und verschieben.
- Ein kurzer Klick auf eine Parzelle öffnet in der Übersicht weiterhin die
  Detailansicht; erst eine tatsächliche Ziehbewegung unterdrückt den Klick.
- Im Editor verschiebt Ziehen auf freier Bildfläche den Kartenausschnitt,
  während Eckpunkte und die ausgewählte Polygonfläche direkt bearbeitbar
  bleiben.
- Im aktiven Zeichenmodus bleiben Klicks auf freie Bildfläche ausschließlich
  für neue Polygonpunkte reserviert.
- Insgesamt bestehen 188 Tests mit 1.075 Assertions.
- Composer- und npm-Abhängigkeiten weisen keine bekannten
  Sicherheitswarnungen auf.
- Entwicklungsstand auf `0.2.0.51` erhöht.
- Entwicklungsstand `0.2.0.51` auf GitHub veröffentlicht.

## [0.2.0.50] - 2026-06-15

### Lageplan mit der Maus verschieben

- Übersicht und Polygoneditor besitzen einen eindeutigen Modus
  `Karte verschieben`.
- Im aktiven Modus lässt sich der vergrößerte Kartenausschnitt mit gedrückter
  linker Maustaste greifen und in alle Richtungen ziehen.
- Parzellenlinks und Polygonbearbeitung werden während des Verschiebens
  gesperrt, damit keine unbeabsichtigte Navigation oder Datenänderung erfolgt.
- Scrollleisten bleiben parallel zum Ziehen mit der Maus nutzbar.
- Pointer Events ermöglichen dieselbe Bedienung auch auf Touch-Geräten.
- Insgesamt bestehen 188 Tests mit 1.075 Assertions.
- Composer- und npm-Abhängigkeiten weisen keine bekannten
  Sicherheitswarnungen auf.
- Entwicklungsstand auf `0.2.0.50` erhöht.
- Entwicklungsstand `0.2.0.50` auf GitHub veröffentlicht.

## [0.2.0.49] - 2026-06-15

### Lageplan-Zoom

- Lageplanübersicht und Polygoneditor besitzen Zoomschaltflächen von 100 bis
  400 Prozent sowie eine Funktion zum Einpassen.
- Strg und Mausrad vergrößern oder verkleinern den Kartenausschnitt an der
  aktuellen Mausposition.
- Vergrößerte Karten können innerhalb eines begrenzten Ansichtsbereichs über
  die Bildlaufleisten verschoben werden.
- Zoom und Verschiebung wirken ausschließlich auf die Browserdarstellung;
  gespeicherte Hintergrundbilder, Polygone und Fachdaten bleiben unverändert.
- Insgesamt bestehen 188 Tests mit 1.071 Assertions.
- Composer- und npm-Abhängigkeiten weisen keine bekannten
  Sicherheitswarnungen auf.
- Entwicklungsstand auf `0.2.0.49` erhöht.
- Entwicklungsstand `0.2.0.49` auf GitHub veröffentlicht.

## [0.2.0.48] - 2026-06-15

### Korrekturen

- Das PHP-Uploadlimit der Entwicklungsumgebung wurde an das im Lageplan
  ausgewiesene Dateilimit angepasst.
- Fehlgeschlagene Datei-Uploads zeigen nun eine verständliche deutsche
  Meldung statt `validation.uploaded`.
- Insgesamt bestehen 188 Tests mit 1.063 Assertions.
- Entwicklungsstand auf `0.2.0.48` erhöht.
- Entwicklungsstand `0.2.0.48` auf GitHub veröffentlicht.

## [0.2.0.47] - 2026-06-15

### Nachbesserung Phase 19: Luftbild und WYSIWYG-Polygoneditor

- Der einfache Rechteckplan wurde durch einen bildbasierten
  WYSIWYG-Polygoneditor ersetzt.
- Administratoren und berechtigte Stammdatenverwalter können ein privates
  JPEG-, PNG- oder WebP-Luftbild beziehungsweise einen Lageplan bis 15 MiB
  hinterlegen.
- Quelle und Nutzungsrecht des Hintergrundbilds müssen dokumentiert und beim
  Upload ausdrücklich bestätigt werden.
- Die Oberfläche weist darauf hin, dass Google-Maps-Satellitenbilder nicht
  automatisch als eigene Bilddateien gespeichert werden dürfen und eine
  vertragskonforme API-Einbindung benötigen.
- Parzellen können mit 3 bis 100 Punkten beliebig geformt werden.
- Ein WYSIWYG-Editor unterstützt Punktsetzung, Rückgängig, das Ziehen
  einzelner Eckpunkte und das Verschieben der gesamten Fläche.
- Das Entfernen einer Zeichnung löscht ausschließlich das Polygon; Mitglied,
  Pächterhistorie, Parzelle und alle Fachdaten bleiben erhalten.
- Beim Austausch des Hintergrundbilds werden vorhandene Polygone proportional
  auf die neue Bildgröße skaliert.
- Die Anzeige legt statusfarbige, klickbare Polygone über das private
  Hintergrundbild und behält Pächterisolation sowie Detaillinks bei.
- Frühere Rechteckdaten wurden additiv in vierpunktige Polygone überführt.
- Nach der Migration bestehen weiterhin 6 Benutzer, 6 Parzellen und alle
  5 Demo-Parzellen; alle Demo-Flächen besitzen ein Polygon.
- CSV-Import und -Export verwenden Polygonpunkte. Frühere fünfspaltige und
  neunspaltige Parzellen-CSV-Dateien bleiben importierbar.
- Vollständige Backups sichern nun den privaten `association`-Ordner mit
  Vereinslogo und Lageplanbild.
- Hintergrundwechsel und Polygonänderungen werden auditiert.
- Unlesbare oder außerhalb von 400 × 300 bis 12000 × 12000 Pixel liegende
  Hintergrundbilder werden mit einer verständlichen Formularmeldung
  abgewiesen.
- Die additive Migration wurde isoliert vorwärts, rückwärts und erneut
  vorwärts geprüft sowie ausschließlich vorwärts auf MariaDB angewendet.
- Insgesamt bestehen 187 Tests mit 1.061 Assertions.
- Composer- und npm-Abhängigkeiten weisen keine bekannten
  Sicherheitswarnungen auf.
- Entwicklungsstand auf `0.2.0.47` erhöht.
- Entwicklungsstand `0.2.0.47` auf GitHub veröffentlicht.

## [0.2.0.46] - 2026-06-15

### Phase 19: SVG-Lageplan

- Phase 19 wurde auf ausdrücklichen Wunsch vor Phase 18 umgesetzt; der
  Pächterwechsel aus Phase 18 bleibt weiterhin offen.
- Parzellen können mit X- und Y-Position sowie Breite und Höhe auf einer
  festen Zeichenfläche von `1200 × 800` Einheiten platziert werden.
- Der responsive Lageplan wird vollständig serverseitig aus validierten
  Stammdaten erzeugt und führt keine hochgeladenen SVG-Fragmente aus.
- Grün kennzeichnet freie oder vergebene, Gelb reservierte oder gekündigte
  und Rot gesperrte Parzellen.
- Parzellennummer, Status und Fläche stehen zusätzlich als zugänglicher Text
  bereit; ein Klick öffnet die geschützte Parzellendetailansicht.
- Noch nicht platzierte Parzellen werden gesondert aufgeführt und können von
  berechtigten Stammdatenverwaltern direkt bearbeitet werden.
- Unvollständige Rechtecke und Flächen außerhalb der Zeichenfläche werden
  mit verständlichen Meldungen abgelehnt.
- Pächter sehen auf dem Lageplan ausschließlich aktuell selbst zugeordnete
  Parzellen; bestehende Policies bleiben vollständig wirksam.
- Navigation, Parzellenliste und Parzellendetailansicht wurden mit dem
  Lageplan verknüpft.
- Parzellen-CSV-Import und -Export enthalten die vier Lageplanwerte.
- Frühere fünfspaltige Parzellen-CSV-Dateien bleiben importierbar und werden
  ohne Lageplanposition übernommen.
- Der löschbare Demo-Bestand erhält fünf Beispielpositionen; die bestehenden
  Demo-Parzellen der Entwicklungsinstanz wurden ohne Neuaufbau platziert.
- Die Migration wurde isoliert vorwärts, rückwärts und erneut vorwärts
  geprüft sowie ausschließlich vorwärts auf MariaDB angewendet.
- Insgesamt bestehen 184 Tests mit 1.039 Assertions.
- Composer- und npm-Abhängigkeiten weisen keine bekannten
  Sicherheitswarnungen auf.
- Entwicklungsstand auf `0.2.0.46` erhöht.
- Entwicklungsstand `0.2.0.46` auf GitHub veröffentlicht.

## [0.2.0.45] - 2026-06-15

### Phase 17: Nummernkreise

- Mitgliedsnummern, Rechnungsnummern, SEPA-Mandatsreferenzen und
  Dokumentnummern besitzen getrennt konfigurierbare Nummernkreise.
- Formate unterstützen die verständlichen Platzhalter `{JAHR}` und
  `{NUMMER}` sowie eine einstellbare Mindeststellenzahl.
- Leerzeichen in Formaten werden automatisch durch Bindestriche ersetzt;
  unbekannte Platzhalter erhalten eine verständliche Validierungsmeldung.
- Der nächste Zählerstand und ein optionaler jährlicher Neustart können
  ausschließlich von Administratoren gepflegt werden.
- Die Vergabe erfolgt transaktionssicher mit Datenbanksperre und zusätzlicher
  Kollisionsprüfung gegen die jeweilige eindeutige Fachspalte.
- Manuell vergebene, importierte und historische Nummern bleiben unverändert
  und werden von der automatischen Vergabe übersprungen.
- Neue Mitglieder und SEPA-Mandate erhalten bei leerem Nummernfeld
  automatisch die nächste Nummer; eine bewusste manuelle Eingabe bleibt
  möglich.
- Rechnungsberechnung und Dokumentupload verwenden nun ebenfalls den
  zentralen Nummerngenerator.
- Dokumente besitzen eine sichtbare und durchsuchbare Dokumentnummer;
  vorhandene Dokumente werden bei der Migration ohne Löschung nachnummeriert.
- Eine Administrationsoberfläche zeigt Vorschauen und erklärt Platzhalter,
  Jahreswechsel sowie zulässige Nummernlücken.
- Änderungen an Nummernkreisen werden auditiert.
- Beide Migrationen wurden isoliert vorwärts, rückwärts und erneut vorwärts
  geprüft sowie ausschließlich vorwärts und ohne Löschung auf die
  MariaDB-Entwicklungsdatenbank angewendet.
- Insgesamt bestehen 179 Tests mit 1.013 Assertions.
- Composer- und npm-Abhängigkeiten weisen keine bekannten
  Sicherheitswarnungen auf.
- Entwicklungsstand auf `0.2.0.45` erhöht.
- Entwicklungsstand `0.2.0.45` auf GitHub veröffentlicht.

## [0.2.0.44] - 2026-06-15

### Phase 16: Vereinseinstellungen und Vorlagenbranding

- Die globale Konfiguration wurde um offiziellen Vereinsnamen, Anschrift,
  Ansprechpartner, Telefon, E-Mail-Adresse und Webseite erweitert.
- Der sichtbare Systemname und der rechtliche Vereinsname sind getrennt
  konfigurierbar.
- JPEG-, PNG- und WebP-Vereinslogos bis 2 MiB werden MIME-geprüft im privaten
  Storage gespeichert und kontrolliert ausgeliefert.
- Das aktive Logo erscheint in Navigation, Rechnungen, Briefen,
  Zahlungserinnerungen und Mahnungen.
- Eine eigene Dokumentfußzeile und E-Mail-Signatur können als sicher
  dargestellter Text gepflegt werden.
- Das Überweisungskonto für Rechnungen wurde von den SEPA-Lastschriftdaten
  getrennt; IBAN und BIC werden verschlüsselt und in Formularen nicht im
  Klartext angezeigt.
- Leere Bankfelder behalten vorhandene Geheimnisse, während eine eigene
  Auswahl die vollständige Rechnungsbankverbindung entfernt.
- Ein Standard-Zahlungsziel wird bei neuen Abrechnungsperioden automatisch
  nach dem gewählten Enddatum vorgeschlagen.
- Rechnungen, Briefe, Mahnungen und Serienmails speichern historische
  Absendersnapshots einschließlich Logo, Fußzeile und Bankverbindung.
- Spätere Konfigurationsänderungen verändern bereits erzeugte Dokumente
  nicht; historische Logodateien bleiben privat erhalten.
- Die Datenschutzinformationen zeigen den verantwortlichen Verein mit
  Kontaktanschrift.
- Beide Migrationen wurden auf MariaDB erfolgreich vorwärts, rückwärts und
  erneut vorwärts ausgeführt.
- Insgesamt bestehen 175 Tests mit 990 Assertions.
- Composer- und npm-Abhängigkeiten weisen keine bekannten
  Sicherheitswarnungen auf.
- Entwicklungsstand auf `0.2.0.44` erhöht.
- Entwicklungsstand `0.2.0.44` auf GitHub veröffentlicht.

## [0.2.0.43] - 2026-06-15

### Phase 15: DSGVO

- Ein zentraler Datenschutzbereich mit öffentlicher Datenschutzinformation
  und persönlichem, maschinenlesbarem JSON-Auskunftsexport wurde ergänzt.
- Pächter können Name, E-Mail, Telefon, Mobilnummer und Anschrift einzeln und
  freiwillig für aktuell gemeinsam eingetragene Mitpächter derselben Parzelle
  freigeben.
- Sämtliche Datenfreigaben sind standardmäßig deaktiviert und jederzeit
  vollständig widerrufbar; ein vereinsweites Mitgliederverzeichnis entsteht
  nicht.
- Das granulare Recht `Datenschutzanfragen verwalten` erlaubt berechtigten
  Vorstandsmitgliedern fremde Auskünfte und Löschanfragen zu bearbeiten.
- Löschanträge speichern Antragsteller, Status, Prüfvermerk,
  Aufbewahrungsgründe und Abschluss nachvollziehbar.
- Die automatische Löschprüfung berücksichtigt Archivierung, Austritt,
  Pächterhistorie, Rechnungen, SEPA-Mandate, Dokumente, Inventarausgaben und
  interne Vereinskonten.
- Die technische Mindestaufbewahrung ist über
  `OKGV_PRIVACY_RETENTION_YEARS` konfigurierbar und ersetzt ausdrücklich keine
  rechtliche Prüfung des betreibenden Vereins.
- Eine endgültige Pseudonymisierung bleibt Administratoren vorbehalten,
  verlangt Passwort und Sicherheitsbestätigung und erhält notwendige
  historische Fachbezüge unter einer anonymen Referenz.
- Auskunft, Freigabeänderung, Löschantrag, Prüfung und Pseudonymisierung
  werden auditiert.
- Die Migration wurde auf MariaDB erfolgreich vorwärts, rückwärts und erneut
  vorwärts ausgeführt.
- Insgesamt bestehen 169 Tests mit 963 Assertions.
- Composer- und npm-Abhängigkeiten weisen keine bekannten
  Sicherheitswarnungen auf.
- Entwicklungsstand auf `0.2.0.43` erhöht.
- Entwicklungsstand `0.2.0.43` auf GitHub veröffentlicht.

## [0.2.0.42] - 2026-06-15

### Phase 13 und 14: Datenübertragung, Backup und Restore

- CSV-Import und CSV-Export wurden zu einem gemeinsamen, schaltbaren
  Funktionsbereich `Datenübertragung` zusammengeführt.
- Das granulare Recht `CSV-Daten übertragen` kann Vorstandsmitgliedern
  zugewiesen werden; vollständige Backups und Restore bleiben ausschließlich
  Administratoren vorbehalten.
- UTF-8-Importvorlagen und transaktionale Importe für Mitglieder, Parzellen,
  Zähler und Zählerstände wurden ergänzt.
- Bestehende Mitglieder und Parzellen werden über ihre Fachnummer
  aktualisiert; historische Zähler und Zählerstände werden niemals
  überschrieben.
- Fehler nennen die betroffene CSV-Zeile und rollen den gesamten Import
  zurück.
- Exporte stehen für Mitglieder, Parzellen, Zähler, effektive Zählerstände
  und Rechnungen einschließlich Empfängern und Positionen bereit.
- Private ZIP-Backups enthalten MariaDB-Dump, Dokumente und Nachweisfotos
  sowie ein versionsgebundenes Manifest mit SHA-256-Prüfsummen.
- Die `.env` und ihre Geheimnisse werden nicht in Backups aufgenommen und
  müssen insbesondere wegen des benötigten `APP_KEY` separat gesichert werden.
- Restore prüft Archivpfade, Version und Prüfsummen, verlangt das aktuelle
  Administratorpasswort und die Bestätigung `WIEDERHERSTELLEN` und erstellt
  vorab automatisch ein Sicherheitsbackup.
- Import, Export, Backup-Erstellung, Löschung und Wiederherstellung werden
  auditiert.
- Ein realer MariaDB-Dump der Entwicklungsinstanz wurde als privates
  OKGV-Backup erzeugt und erfolgreich auf ZIP-Integrität sowie Dateirechte
  `0600` geprüft.
- Insgesamt bestehen 163 Tests mit 929 Assertions.
- Entwicklungsstand auf `0.2.0.42` erhöht.
- Entwicklungsstand `0.2.0.42` auf GitHub veröffentlicht.

## [0.2.0.41] - 2026-06-15

### Lizenzierung

- Die bisherige MIT-Lizenz des OKGV-Projektcodes wurde entfernt.
- OKGV wird ab diesem Entwicklungsstand unter der GNU Affero General Public
  License Version 3 (`AGPL-3.0-only`) veröffentlicht.
- `LICENSE`, Composer- und npm-Paketmetadaten wurden entsprechend
  aktualisiert.
- README, Projektspezifikation und Agent-Regeln dokumentieren die GNU AGPLv3
  sowie den unveränderten Fortbestand von Drittanbieter-Lizenzhinweisen.
- Die Benutzeroberfläche bietet dauerhaft einen Quellcode-Link an, dessen
  Ziel über `APP_SOURCE_URL` auf den Quellcode der tatsächlich betriebenen
  Version gesetzt werden kann.
- Ein Arbeitseinsatztest verwendet nun ein festes, zur Abrechnungsperiode
  passendes Mitgliedseintrittsdatum und ist dadurch nicht mehr zufallsabhängig.
- Insgesamt bestehen 154 Tests mit 887 Assertions.
- Entwicklungsstand auf `0.2.0.41` erhöht.
- Entwicklungsstand `0.2.0.41` auf GitHub veröffentlicht.

## [0.2.0.40] - 2026-06-15

### Phase 12.1: Modularisierung

- Pächterportal, Zähler, Abrechnung, Arbeitsstunden, Arbeitseinsätze, SEPA,
  Mahnwesen, Dokumente, Kommunikation, Warteliste und Inventar können
  instanzweise über `.env` aktiviert oder deaktiviert werden.
- Eine zentrale Moduldefinition stellt deutsche Namen, Status und
  Abhängigkeiten bereit.
- Arbeitsstunden benötigen Abrechnung, Arbeitseinsätze benötigen
  Arbeitsstunden und SEPA sowie Mahnwesen benötigen Abrechnung.
- Ungültige Modulkombinationen verhindern den Anwendungsstart mit einer
  eindeutigen technischen Fehlermeldung.
- Modul-Middleware schützt alle direkten und öffentlichen Fachrouten auch vor
  Administratorzugriffen; deaktivierte Bereiche antworten mit HTTP 404.
- Navigation, Dashboard, Pächterportal, Parzellendetails, globale
  Konfiguration, Rechteauswahl und Aktionshinweise beachten den Modulstatus.
- Deaktivierte Module behalten Tabellen, Fachdaten, Historien,
  Rechtezuweisungen und Rechtevorlagen unverändert.
- Bei deaktivierten Arbeitsstunden werden keine Konten synchronisiert und
  keine Fehlstundenpositionen in Rechnungen aufgenommen.
- Verbrauchspreise und ihre Vorlagen stehen nur mit aktiver
  Zählerverwaltung zur Verfügung.
- Serienmail-Gruppen für offene Rechnungen und fehlende Zählerstände richten
  sich nach den zugehörigen Modulen.
- SMTP bleibt als Kernfunktion für Passwort-Reset und E-Mail-Verifizierung
  unabhängig vom Kommunikationsmodul konfigurierbar.
- Die globale Konfiguration zeigt den aktuellen Modulstatus schreibgeschützt
  an.
- Direkte URLs, Abhängigkeiten, Datenhaltbarkeit, Rechteerhalt,
  Fachberechnungen und manipulierte Requests sind durch Regressionstests
  abgesichert.
- Insgesamt bestehen 153 Tests mit 883 Assertions.
- Entwicklungsstand auf `0.2.0.40` erhöht.
- Entwicklungsstand `0.2.0.40` auf GitHub veröffentlicht.

## [0.2.0.39] - 2026-06-15

### Phase 11: Inventarverwaltung

- Frei definierbare Vereinsgegenstände können mit Inventarnummer, Kategorie,
  Beschreibung, Standort, Seriennummer und Status verwaltet werden.
- Anschaffungsdatum und Anschaffungskosten sind optionale Angaben.
- Ausgaben speichern Mitgliedszuordnung, Empfängername, Ausgabezeitpunkt,
  optionale Rückgabefrist, Zustand und Bearbeiter dauerhaft.
- Bei Mitgliedern wird der historische Empfängername serverseitig aus den
  Stammdaten übernommen; archivierte Mitglieder sind nicht auswählbar.
- Rückgaben schließen eine Ausgabe genau einmal ab und setzen den Gegenstand
  wahlweise auf Verfügbar, Wartung oder Verloren.
- Transaktionen und Datensperren verhindern parallele Mehrfachausgaben und
  doppelte Rückgaben.
- Gegenstände und Ausgaben besitzen keine Löschroute und können auch auf
  Modellebene nicht gelöscht werden.
- Suche nach Inventarnummer, Name, Kategorie, Seriennummer und Standort sowie
  Status- und Kategoriefilter wurden ergänzt.
- Überfällige offene Ausgaben erscheinen als Aktionshinweis.
- Das neue granulare Recht `Inventar verwalten` schützt Oberfläche und
  Workflows; Administratoren besitzen es immer, Vorstand und Gartenwart
  erhalten es als Standardrecht.
- Anlage, Änderung, Ausgabe und Rückgabe werden auditiert.
- Der Phasenplan enthält die zusätzlich vorgesehene Phase 12.1 zur späteren
  Modularisierung und Aktivierbarkeit einzelner Funktionsbereiche.
- Migrationen wurden isoliert vorwärts, rückwärts und erneut vorwärts geprüft
  und anschließend ausschließlich vorwärts auf MariaDB angewendet.
- Insgesamt bestehen 143 Tests mit 843 Assertions.
- Entwicklungsstand auf `0.2.0.39` erhöht.
- Entwicklungsstand `0.2.0.39` auf GitHub veröffentlicht.

## [0.2.0.38] - 2026-06-15

### Session und CSRF

- Session- und CSRF-Cookies folgen auf der Testinstanz automatisch dem
  tatsächlichen Request-Schema.
- Über die öffentliche HTTPS-Domain bleiben Cookies mit `Secure` geschützt.
- Beim direkten HTTP-Zugang im internen Netz werden Cookies wieder an den
  Server gesendet, sodass Formulare wie die Rechteverwaltung nicht mehr mit
  HTTP 419 abbrechen.
- Der vollständige LAN-Ablauf aus Anmeldung, Rechteverwaltung und
  Formular-POST wurde ohne Änderung vorhandener Benutzerrechte geprüft.
- Ein Regressionstest deckt das unterschiedliche Cookie-Verhalten für HTTP
  und weitergereichtes HTTPS ab.
- Der Session-Cookie-Name der laufenden Testinstanz wurde einmalig geändert,
  damit ein altes, unbrauchbares `Secure`-Cookie der LAN-IP den nächsten
  Login nicht blockiert.
- Insgesamt bestehen 136 Tests mit 797 Assertions.
- Entwicklungsstand auf `0.2.0.38` erhöht.
- Entwicklungsstand `0.2.0.38` auf GitHub veröffentlicht.

## [0.2.0.37] - 2026-06-15

### Phase 10: Warteliste

- Interessenten können mit Name, E-Mail, Telefon, Mobilnummer, Eingangsdatum,
  Priorität, Status und internen Notizen verwaltet werden.
- Priorität 1 bis 5 und das Eingangsdatum bestimmen die Standardsortierung.
- Suche nach Name und Kontaktdaten sowie Status- und Prioritätsfilter
  erleichtern die Bearbeitung größerer Listen.
- Wartend, Kontaktiert und Angebot unterbreitet gelten als offene Vorgänge;
  abgeschlossene Einträge bleiben über die Filter dauerhaft auffindbar.
- Einträge werden nicht gelöscht. Anlage, Änderungen und Statuswechsel werden
  ohne unnötige Kontaktdaten auditiert.
- Das eigene Recht `Warteliste verwalten` schützt die personenbezogenen
  Interessentendaten und kann Vorstandsmitgliedern granular zugewiesen werden.
- Berechtigte Konten sehen offene Wartelistenvorgänge als Aktionspunkt im
  Mitglieder-Menü.
- Migrationen wurden auf MariaDB vorwärts und rückwärts geprüft.
- Insgesamt bestehen 135 Tests mit 793 Assertions.
- Entwicklungsstand auf `0.2.0.37` erhöht.
- Entwicklungsstand `0.2.0.37` auf GitHub veröffentlicht.

## [0.2.0.36] - 2026-06-15

### HTTPS-Reverse-Proxy

- Vertrauenswürdige Reverse-Proxy-Adressen können über `TRUSTED_PROXIES`
  explizit konfiguriert werden.
- Laravel berücksichtigt das weitergereichte HTTPS-Schema dadurch für
  Weiterleitungen, Vite-Assets und weitere erzeugte URLs.
- Die Beispielkonfiguration dokumentiert sichere Session-Cookies hinter
  HTTPS.
- Die öffentliche Testinstanz verwendet Produktionsmodus, deaktivierte
  Fehlerdetails, ihre HTTPS-Adresse und ausschließlich den unmittelbar
  vorgeschalteten Proxy als Vertrauensanker.
- Die öffentliche Loginseite wurde auf HTTPS-Weiterleitung sowie HTTPS-URLs
  für CSS, JavaScript und Navigation geprüft.
- Ein Regressionstest deckt die Schemaerkennung über einen
  vertrauenswürdigen Proxy ab.
- Insgesamt bestehen 128 Tests mit 758 Assertions.
- Entwicklungsstand auf `0.2.0.36` erhöht.
- Entwicklungsstand `0.2.0.36` auf GitHub veröffentlicht.

## [0.2.0.35] - 2026-06-15

### Anteilige Arbeitsstunden

- Die globalen Pflichtstunden gelten als Jahreswert je Parzelle und werden bei
  unterjähriger Verpachtung taggenau nach belegten Kalendertagen berechnet.
- Gleichzeitige Mitpächter werden nicht doppelt gezählt; ein lückenloser
  Pächterwechsel erhält die volle Jahrespflicht der Parzelle.
- Jahreswert, Belegungsfaktor und eine mögliche manuelle Abweichung werden
  historisch am Arbeitsstundenkonto gespeichert.
- Änderungen an Pächterzeiträumen synchronisieren betroffene bearbeitbare
  Abrechnungsperioden; manuelle Sonderwerte bleiben dabei erhalten.
- Fehlstundenbeträge werden bei einem Hauptpächterwechsel nach den jeweiligen
  Belegungstagen auf die Rechnungen aufgeteilt.
- Oberfläche und globale Konfiguration erklären die automatische
  Zeitanteilsberechnung und kennzeichnen manuell abweichende Pflichtstunden.
- Fachtests decken Teiljahre, lückenlose Wechsel, manuelle Abweichungen und
  die Rechnungsaufteilung ab.
- Migration wurde auf MariaDB vorwärts und rückwärts geprüft.
- Insgesamt bestehen 127 Tests mit 756 Assertions.
- Entwicklungsstand auf `0.2.0.35` erhöht.
- Entwicklungsstand `0.2.0.35` auf GitHub veröffentlicht.

## [0.2.0.34] - 2026-06-15

### Abrechnungszeiträume

- Abrechnungsperioden dienen jetzt als Rechnungslauf; jeder Preis speichert
  einen eigenen historischen Leistungszeitraum.
- Preise und Preisvorlagen unterscheiden zwischen Vorauszahlung und
  Nachberechnung.
- Vorauszahlungsvorlagen schlagen das Folgejahr vor, während
  Nachberechnungsvorlagen den Zeitraum des Rechnungslaufs verwenden.
- Pacht, Versicherung, Beiträge und andere feste oder flächenbezogene Kosten
  können bei Ein- und Austritten taggenau anteilig berechnet werden.

### Pächterwechsel und Verbrauch

- Pächterwechsel blockieren die Abrechnung nicht mehr. Parzellenkosten werden
  anhand der dauerhaften Hauptpächterhistorie auf die jeweiligen
  Nutzungszeiträume verteilt.
- Mitgliedskosten verwenden unabhängig davon Eintritts- und Austrittsdatum
  des Mitglieds.
- Strom- und Wasserverbrauch werden auf den Schnitt aus Leistungszeitraum und
  tatsächlichem Pachtzeitraum begrenzt.
- Leistungszeitraum, Abrechnungsart, Zeitanteilsfaktor und verwendete
  Teilzeiträume werden in Rechnungspositionen historisch gespeichert und in
  Oberfläche sowie PDF angezeigt.

### Demo-Daten

- Demo-Pacht und Demo-Mitgliedsbeiträge werden als Vorauszahlung für das
  Folgejahr angelegt.
- Demo-Wasser und Demo-Strom werden als verbrauchsabhängige Nachberechnung des
  laufenden Jahres angelegt.
- Migration wurde auf MariaDB vorwärts und rückwärts geprüft.
- Insgesamt bestehen 123 Tests mit 742 Assertions.
- Entwicklungsstand auf `0.2.0.34` erhöht.
- Entwicklungsstand `0.2.0.34` auf GitHub veröffentlicht.

## [0.2.0.33] - 2026-06-14

### Demo-Daten

- Der Demo-Seeder prüft vor jeder Änderung, ob seine Perioden 2024 bis 2026
  eine vorhandene, nicht zum Demo-Bestand gehörende Abrechnungsperiode
  überschneiden.
- Bei einer Überschneidung bricht der Seed mit einem verständlichen Fehler
  ab und legt keine Demo-Daten an.
- Die Anmeldung des Vorstands- und aller vier Pächterkonten wird über das
  echte Web-Loginformular als Regressionstest geprüft.

### Lokaler Datenreset

- Die Entwicklungsdatenbank wurde auf ausdrücklichen Wunsch vollständig neu
  migriert; der bestehende Administrator blieb mit ID und Passwort-Hash
  unverändert erhalten.
- Der neue Bestand enthält ausschließlich einen Administrator, fünf
  Demo-Konten, fünf Demo-Parzellen und je eine nicht überlappende
  Abrechnungsperiode für 2024, 2025 und 2026.
- Alle dokumentierten Administrator- und Demo-Zugangsdaten wurden direkt
  gegen die neue Datenbank validiert.
- Insgesamt bestehen 120 Tests mit 727 Assertions.
- Entwicklungsstand auf `0.2.0.33` erhöht.
- Entwicklungsstand `0.2.0.33` auf GitHub veröffentlicht.

## [0.2.0.32] - 2026-06-14

### Arbeitsstundenkonten

- Arbeitsstundenkonten werden beim Anlegen oder zeitlichen Ändern einer
  Abrechnungsperiode automatisch für alle am Periodenende verpachteten
  Parzellen eingerichtet.
- Neue oder geänderte Pächterzuordnungen ergänzen fehlende Konten passender,
  bearbeitbarer Abrechnungsperioden automatisch.
- Die manuelle Aktion `Parzellenkonten vorbereiten` und die zusätzliche
  Einzelanlage fehlender Konten wurden aus der Oberfläche entfernt.
- Ein Synchronisationslauf ohne fehlende Konten verwirft keinen bereits
  berechneten Zwischenstand mehr.
- Die Parzellendetailansicht zeigt zusätzlich den vollständigen Zeitraum
  jedes Arbeitsstundenkontos, damit ähnlich benannte Perioden eindeutig
  bleiben.

### Datenkorrektur

- Die normale `Abrechnung 2026` enthält nun Konten für alle sechs am
  Periodenende verpachteten Parzellen und wurde wegen der ergänzten
  Berechnungsgrundlagen korrekt in den Entwurfsstatus zurückgesetzt.
- Die `DEMO Abrechnung 2026` enthält wieder ausschließlich die fünf
  markierten Demo-Parzellen; andere Entwicklungsdaten wurden nicht gelöscht.
- Automatische Kontoerzeugung, nachträgliche Pächterzuordnung und der Erhalt
  berechneter Zwischenstände sind durch Regressionstests abgedeckt.
- Perioden- beziehungsweise Pächteränderung und automatische Kontoanlage
  werden gemeinsam in einer Datenbanktransaktion gespeichert.
- Insgesamt bestehen 118 Tests mit 682 Assertions.
- Entwicklungsstand auf `0.2.0.32` erhöht.
- Entwicklungsstand `0.2.0.32` auf GitHub veröffentlicht.

## [0.2.0.31] - 2026-06-14

### Arbeitseinsätze

- Direkten Button `Arbeitseinsatz anlegen` in der
  Arbeitseinsatzübersicht ergänzt.
- Beim Anlegen kann eine bearbeitbare Abrechnungsperiode direkt aus einem
  übersichtlichen Auswahlmenü gewählt werden.
- Freigegebene und archivierte Perioden werden nicht als Ziel angeboten.
- Ist keine bearbeitbare Periode vorhanden, zeigt die Übersicht eine
  verständliche Erklärung.
- Der Abbrechen-Link im Anlegeformular führt zurück zur
  Arbeitseinsatzübersicht und funktioniert damit auch für Gartenwarte ohne
  Abrechnungsrecht.
- Administrator- und Gartenwartzugriff sowie der Zustand ohne bearbeitbare
  Periode sind durch Feature-Tests abgedeckt.
- Insgesamt bestehen 116 Tests mit 670 Assertions.
- Entwicklungsstand auf `0.2.0.31` erhöht.
- Entwicklungsstand `0.2.0.31` auf GitHub veröffentlicht.

## [0.2.0.30] - 2026-06-14

### Löschbarer Demo-Bestand

- Expliziten Befehl `php artisan okgv:demo-seed` für einen wiederholbaren,
  zusammenhängenden Testbestand ergänzt.
- Vier Pächterkonten und ein Vorstandsmitglied mit jeweils eigener
  `DEMO-`-Parzelle angelegt.
- Für 2024 bis 2026 Abrechnungsperioden, historische Preise,
  Arbeitsstundenkonten, Arbeitseinsätze und Pächtermeldungen ergänzt.
- Wasser- und Stromzähler mit fortlaufenden Zählerständen, historischem
  Zählerwechsel und offener Zählerstandsmeldung angelegt.
- Das gemeinsame Demo-Passwort wird ausschließlich aus der lokalen
  `OKGV_DEMO_PASSWORD`-Konfiguration gelesen und nicht veröffentlicht.

### Sichere Entfernung

- `php artisan okgv:demo-purge` entfernt ausschließlich über eindeutige
  Demo-Kennzeichen ermittelte Konten und Fachdaten.
- Erneutes Anlegen ersetzt nur den bestehenden Demo-Bestand und erzeugt
  keine Duplikate.
- Der Standardbefehl `php artisan db:seed` legt bewusst keine Benutzer oder
  Beispieldaten mehr an.
- Ein Seed-Purge-Seed-Zyklus auf MariaDB bestätigte, dass vorhandene
  Benutzer-, Mitglieder-, Parzellen-, Zähler-, Zählerstands- und
  Periodendaten unverändert bleiben.
- Wiederholbarkeit, selektive Löschung, abgeleitete Rechnungs-, Mahn- und
  Dokumentdaten sowie private Dateien sind durch Feature-Tests abgedeckt.
- Insgesamt bestehen 115 Tests mit 663 Assertions.
- Entwicklungsstand auf `0.2.0.30` erhöht.
- Entwicklungsstand `0.2.0.30` auf GitHub veröffentlicht.

## [0.2.0.29] - 2026-06-14

### Parzellendetail

- Arbeitsstundenkonten aller Abrechnungsperioden in die
  Parzellendetailansicht aufgenommen.
- Pflichtstunden, manuelle Stunden, bestätigte Arbeitseinsätze,
  Pächtermeldungen, Gesamtstunden, Fehlstunden und Fehlbetrag werden getrennt
  und nachvollziehbar dargestellt.
- Berechtigte Finanzkonten können manuell anerkannte Stunden direkt in der
  Parzellenansicht speichern.
- Fehlende Konten bearbeitbarer Perioden lassen sich mit vorausgewählter
  Parzelle anlegen und übernehmen die globalen Vereinsvorgaben.
- Pächter gelangen von ihrer Parzelle und aus dem Portal mit vorausgewählter
  Parzelle zur Arbeitsstundenmeldung.

### Fehlerbehebung

- `/arbeitsstunden-melden` erzeugt bei Administratoren oder anderen Konten
  ohne passende Pächterverknüpfung keinen Serverfehler mehr.
- Nicht passende Konten werden mit einem verständlichen Hinweis zur
  Arbeitsstundenverwaltung weitergeleitet.
- Pächter ohne aktuell zugeordnete Parzelle erhalten auf der Meldeseite eine
  verständliche Erklärung statt eines nicht nutzbaren Formulars.
- Die Meldeschaltfläche wird nur für tatsächlich verknüpfte Pächterkonten
  angeboten.
- Parzellenansicht, direkte Erfassung, sichere Weiterleitung und leere
  Zuordnungen sind durch Feature-Tests abgedeckt.
- Insgesamt bestehen 113 Tests mit 636 Assertions.
- Entwicklungsstand auf `0.2.0.29` erhöht.
- Entwicklungsstand `0.2.0.29` auf GitHub veröffentlicht.

## [0.2.0.28] - 2026-06-14

### Arbeitsstunden je Parzelle

- Arbeitsstundenkonten von einzelnen Mitgliedern auf genau ein gemeinsames
  Konto je Parzelle und Abrechnungsperiode umgestellt.
- Globale Vereinsvorgaben für Pflichtstunden und Betrag je Fehlstunde in der
  globalen Konfiguration ergänzt.
- Alle zum Periodenende vergebenen Parzellen können gesammelt mit diesen
  Vorgaben vorbereitet werden; bestehende historische Konten werden dabei
  nicht überschrieben.
- Manuell anerkannte Stunden, bestätigte Arbeitseinsätze und freigegebene
  Pächtermeldungen werden getrennt ausgewiesen und gemeinsam berechnet.
- Fehlstunden und Strafzahlungen werden nur einmal je Parzelle berechnet,
  auch wenn mehrere Personen im Pachtvertrag stehen.
- Arbeitseinsatzteilnahmen sind nun zusätzlich der betroffenen Parzelle
  zugeordnet.

### Pächtermeldungen

- Pächter können geleistete Arbeitsstunden mit Datum, Tätigkeitsbeschreibung
  und optionalem Foto selbst einreichen.
- Meldungen werden erst nach Bestätigung durch eine berechtigte Person dem
  gemeinsamen Parzellenkonto gutgeschrieben.
- Ablehnungen benötigen eine Begründung und bleiben für den einreichenden
  Pächter nachvollziehbar.
- Nachweisfotos werden ausschließlich im privaten Speicher abgelegt und sind
  nur für den Einreicher sowie berechtigte Prüfer abrufbar.
- Offene Meldungen erzeugen einen rollenabhängigen Aktionshinweis.

### Migration und Tests

- Bestehende mitgliederbezogene Konten und Einsatzteilnahmen werden nur bei
  eindeutiger historischer Parzellenzuordnung migriert; uneindeutige Daten
  brechen die Migration mit einer verständlichen Fehlermeldung ab.
- Migration vorwärts und rückwärts isoliert geprüft und anschließend
  ausschließlich mit `php artisan migrate` auf MariaDB angewendet;
  vorhandene Benutzer-, Mitglieder- und Periodendaten blieben unverändert.
- Parzellenkonten, globale Vorgaben, Gemeinschaftspacht, Pächtermeldungen,
  Prüfung, Ablehnung, Unveränderlichkeit und private Fotonachweise sind durch
  Feature-Tests abgedeckt.
- Insgesamt bestehen 110 Tests mit 620 Assertions.
- Entwicklungsstand auf `0.2.0.28` erhöht.
- Entwicklungsstand `0.2.0.28` auf GitHub veröffentlicht.

## [0.2.0.27] - 2026-06-14

### Vorgezogene Phase 12

- Phase 12 auf ausdrücklichen Wunsch direkt nach Phase 9 umgesetzt, da
  Arbeitseinsätze die Datengrundlage der Arbeitsstundenkonten bilden.
- Terminverwaltung mit Bezeichnung, Ort, Zeitraum, Beschreibung,
  Abrechnungsperiode und den Status `Geplant`, `Abgeschlossen` und
  `Abgesagt` ergänzt.
- Mitglieder können als angemeldet, bestätigt oder abwesend geführt werden.
- Nur bestätigte Teilnahmen abgeschlossener Einsätze werden automatisch in
  das Arbeitsstundenkonto übernommen.
- Manuell anerkannte Stunden und Einsatzstunden werden getrennt gespeichert
  und als nachvollziehbare Gesamtsumme dargestellt.
- Korrekturen, Abwesenheit und Absage berechnen betroffene
  Arbeitsstundenkonten automatisch neu.
- Fehlende Arbeitsstundenkonten werden ohne erfundene Pflichtstunden oder
  Strafsätze sicher angelegt.
- Vergangene, noch geplante Einsätze erzeugen einen rollenabhängigen
  Aktionspunkt.

### Rechte und Sicherheit

- Eigenständiges Recht `Arbeitseinsätze verwalten` eingeführt; Vorstand und
  Gartenwart erhalten es standardmäßig, ohne dadurch Finanzrechte zu
  bekommen.
- Termine müssen vollständig innerhalb ihrer Abrechnungsperiode liegen;
  zukünftige Termine können nicht abgeschlossen werden.
- Teilnahmen können erst nach Abschluss mit einem positiven Stundenwert
  bestätigt werden.
- Änderungen an berechneten Zwischenständen verwerfen sicher und auditierbar
  die noch nicht freigegebenen Rechnungsentwürfe.
- Arbeitseinsätze und Teilnehmer werden nicht gelöscht. Freigegebene oder
  archivierte Perioden sind zusätzlich auf Modellebene unveränderlich.
- Migration vorwärts und rückwärts isoliert geprüft und anschließend
  ausschließlich mit `php artisan migrate` auf MariaDB angewendet;
  vorhandene Benutzer-, Mitglieder- und Periodendaten blieben unverändert.

### Tests

- Stundenübernahme, manuelle Zusatzstunden, Abwesenheit, Absage,
  Kontoerstellung, Zwischenstandsrücksetzung, Rechte, Aktionspunkte und
  Unveränderlichkeit sind durch Feature-Tests abgedeckt.
- Insgesamt bestehen 105 Tests mit 592 Assertions.
- Entwicklungsstand auf `0.2.0.27` erhöht.
- Entwicklungsstand `0.2.0.27` auf GitHub veröffentlicht.

## [0.2.0.26] - 2026-06-14

### Phase 9

- Arbeitsstundenkonten je Mitglied und Abrechnungsperiode umgesetzt.
- Pflichtstunden, geleistete Stunden, Fehlstunden, Betrag je Fehlstunde und
  Strafzahlung werden übersichtlich im Finanzbereich verwaltet.
- Fehlstunden und Strafbeträge werden ausschließlich serverseitig berechnet;
  Mehrarbeit erzeugt keine negative Forderung.
- Positive Strafbeträge werden mit Stundenmenge und historischem Stundensatz
  als eigene Rechnungsposition übernommen.
- Bei gemeinsamen Pachtverträgen erscheinen die Fehlstunden aller
  Vertragspartner einzeln auf derselben Rechnung.
- Offene Fehlstunden in Entwurfsperioden erzeugen einen rollenabhängigen
  Aktionspunkt im Finanzbereich.

### Security

- Arbeitsstunden verwenden das granulare Abrechnungsrecht und sind für
  unberechtigte Konten serverseitig gesperrt.
- Änderungen an berechneten Zwischenständen verwerfen nur die noch nicht
  freigegebenen Rechnungsentwürfe und werden auditiert.
- Arbeitsstunden freigegebener oder archivierter Perioden sind auch auf
  Modellebene unveränderlich; Datensätze können nicht gelöscht werden.
- Die Migration wurde vorwärts und rückwärts isoliert geprüft und
  anschließend ausschließlich mit `php artisan migrate` auf MariaDB
  angewendet; vorhandene Bestandszahlen blieben unverändert.

### Tests

- Berechnung, Übererfüllung, Zwischenstandsrücksetzung, Unveränderlichkeit,
  Gemeinschaftsrechnungen, Rechte und Aktionspunkte werden durch
  Feature-Tests abgedeckt.
- Insgesamt bestehen 96 Tests mit 559 Assertions.
- Entwicklungsstand auf `0.2.0.26` erhöht.
- Entwicklungsstand `0.2.0.26` auf GitHub veröffentlicht.

## [0.2.0.25] - 2026-06-14

### Phase 8

- Dreistufiges Mahnwesen für freigegebene, überfällige und offene oder
  zurückgegebene Rechnungen umgesetzt.
- Mahnstufen werden lückenlos und erst nach Ablauf der vorherigen Frist
  ausgestellt.
- Jede Mahnung speichert Rechnungsnummer, Beträge, Empfänger, Frist, Gebühr,
  Gesamtforderung und Ersteller als unveränderlichen Snapshot.
- Optionale Gebühren werden über alle aktiven Mahnstufen kumuliert, ohne die
  freigegebene Rechnung rückwirkend zu verändern.
- PDF-Mahnungen mit Mahnstufe, Frist, Gebührenübersicht und Gesamtforderung
  ergänzt.
- Mahnübersicht, Rechnungsintegration und Pächterzugriff auf eigene Mahnungen
  hinzugefügt.
- Überfällige, tatsächlich mahnfähige Rechnungen erzeugen einen
  rollenabhängigen Aktionspunkt im Finanzbereich.

### Security

- Mahnungen verwenden das bestehende granulare Abrechnungsrecht.
- Bezahlte, noch nicht fällige oder nicht freigegebene Rechnungen können
  serverseitig nicht gemahnt werden.
- Ausgestellte Mahnungen können weder verändert noch gelöscht werden.
- Nur die höchste aktive Mahnstufe kann mit Pflichtbegründung storniert
  werden; Erstellung und Stornierung werden auditiert.
- Die Migration wurde vorwärts und rückwärts isoliert geprüft und
  anschließend ausschließlich mit `php artisan migrate` auf MariaDB
  angewendet; vorhandene Bestandszahlen blieben unverändert.

### Tests

- Mahnstufen, Fristsperren, Gebührenkumulation, Stornierung,
  Unveränderlichkeit, Rechteisolation, Pächterzugriff, PDF und Aktionspunkte
  werden durch Feature-Tests abgedeckt.
- Insgesamt bestehen 90 Tests mit 533 Assertions.
- Entwicklungsstand auf `0.2.0.25` erhöht.
- Entwicklungsstand `0.2.0.25` auf GitHub veröffentlicht.

## [0.2.0.24] - 2026-06-14

### Fixed

- Erfolgsmeldung des SMTP-Tests unterscheidet nun klar zwischen Annahme durch
  den SMTP-Server und endgültiger Zustellung beim Empfänger.
- Hinweis auf mögliche Verzögerung und Spamordner ergänzt.
- Vom SMTP-Transport zurückgegebene Message-ID wird zur Nachverfolgung beim
  Mailanbieter im verschlüsselten Auditlog gespeichert und angezeigt.

### Diagnosis

- Für die verwendete Absender-Subdomain `eigene-scholle.okgv.de` fehlen
  derzeit eigene SPF- und erkennbare DKIM-DNS-Einträge.
- Die letzten angenommenen Testnachrichten gingen an Gmail; fehlende
  Absenderauthentifizierung kann dort zu Spam-Einstufung oder Ablehnung nach
  der SMTP-Annahme führen.

### Tests

- Präzise Annahmemeldung und Audit-Metadaten der Testmail werden durch
  Feature-Tests abgedeckt.
- Insgesamt bestehen 85 Tests mit 486 Assertions.
- Entwicklungsstand auf `0.2.0.24` erhöht.
- Entwicklungsstand `0.2.0.24` auf GitHub veröffentlicht.

## [0.2.0.23] - 2026-06-14

### Fixed

- Zu strenges SMTP-Testlimit von drei Versuchen in zehn Minuten ersetzt.
- Pro Benutzer sind nun zehn Testmails pro Minute möglich.
- Beim Überschreiten erfolgt eine Rückleitung zum SMTP-Formular mit
  verständlicher deutscher Meldung statt einer allgemeinen 429-Seite.

### Tests

- Zulässige Testversuche und benutzerfreundliche Begrenzungsmeldung werden
  durch einen Feature-Test abgedeckt.
- Insgesamt bestehen 85 Tests mit 485 Assertions.
- Entwicklungsstand auf `0.2.0.23` erhöht.
- Entwicklungsstand `0.2.0.23` auf GitHub veröffentlicht.

## [0.2.0.22] - 2026-06-14

### Changed

- SMTP-Feldbezeichnung `Neuer Benutzername` zu `Benutzername` korrigiert.
- Irreführenden Hinweis zum Leerlassen des Benutzerfeldes entfernt.
- Der gespeicherte SMTP-Benutzername wird beim Bearbeiten wieder im Feld
  angezeigt; ausschließlich das Passwort bleibt maskiert.

### Tests

- Sichtbarkeit des Benutzernamens und Maskierung des Passworts werden durch
  einen Feature-Test abgesichert.
- Entwicklungsstand auf `0.2.0.22` erhöht.
- Entwicklungsstand `0.2.0.22` auf GitHub veröffentlicht.

## [0.2.0.21] - 2026-06-14

### Changed

- Zieladresse für SMTP-Testmails kann in der globalen Konfiguration frei
  eingegeben werden.
- Das Feld wird serverseitig als E-Mail-Adresse validiert und mit der
  Administratoradresse sinnvoll vorausgefüllt.

### Security

- Die Testadresse wird im Auditlog nachvollziehbar gespeichert, aber nicht
  als globale Einstellung übernommen.
- Rate-Limit und ausschließliches Administratorrecht bleiben unverändert.

### Tests

- Freie Zieladresse, erfolgreicher Testversand und Ablehnung ungültiger
  Adressen werden durch Feature-Tests abgedeckt.
- Insgesamt bestehen 84 Tests mit 460 Assertions.
- Entwicklungsstand auf `0.2.0.21` erhöht.
- Entwicklungsstand `0.2.0.21` auf GitHub veröffentlicht.

## [0.2.0.20] - 2026-06-14

### Changed

- Feldbezeichnung `Neues Passwort` in der SMTP-Konfiguration verständlicher
  zu `Passwort` geändert.
- Entwicklungsstand auf `0.2.0.20` erhöht.
- Entwicklungsstand `0.2.0.20` auf GitHub veröffentlicht.

## [0.2.0.19] - 2026-06-14

### Changed

- SMTP-Einstellungen und Testversand als eigenen Abschnitt in die globale
  Konfiguration verschoben.
- Separaten SMTP-Menüpunkt aus dem Bereich Kommunikation entfernt.
- Das Kommunikationsrecht umfasst weiterhin Serienmails, Versandhistorie und
  PDF-Briefe, aber keine Änderung globaler Serverzugangsdaten.

### Security

- SMTP-Konfiguration und Testversand sind ausschließlich Administratoren
  zugänglich.
- Verschlüsselte Zugangsdaten werden weiterhin weder vorausgefüllt noch im
  Auditlog ausgegeben.

### Tests

- Einbindung in die globale Konfiguration, Geheimnismaskierung und
  Administratorzugriff werden durch Feature-Tests abgedeckt.
- Insgesamt bestehen 83 Tests mit 450 Assertions.
- Entwicklungsstand auf `0.2.0.19` erhöht.
- Entwicklungsstand `0.2.0.19` auf GitHub veröffentlicht.

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
- Entwicklungsstand `0.2.0.18` auf GitHub veröffentlicht.

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
