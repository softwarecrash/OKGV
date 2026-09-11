# Produktionsupdates mit GitHub und Plesk

OKGV aktualisiert sich nicht aus der Weboberfläche heraus. Die Anwendung besitzt
absichtlich weder GitHub-Zugangsdaten noch Schreibrechte auf ihren eigenen
Quellcode. Updates werden durch Plesk aus einem freigegebenen GitHub-Release
bereitgestellt.

## Sicherheitsmodell

- `main` ist Entwicklung und darf niemals direkt an eine produktive Instanz
  gebunden werden.
- `production` enthält exakt den zuletzt freigegebenen GitHub-Release.
- Der GitHub-Workflow akzeptiert nur veröffentlichte, nicht als Vorabversion
  markierte Releases mit einer vierteiligen OKGV-Version.
- Das GitHub-Environment `production` muss mindestens eine manuelle
  Freigabe verlangen. Dadurch wird kein Release ohne ausdrückliche Entscheidung
  an Plesk ausgeliefert.
- Plesk erhält nur einen schreibgeschützten Deploy-Key für das Repository.
  Zugangsdaten gehören nie in `.env`, GitHub Actions oder dieses Repository.

## Einmalige GitHub-Konfiguration

1. Unter **Settings -> Environments** ein Environment `production` anlegen
   und mindestens einen Required Reviewer eintragen. Der Workflow wird von
   `main` manuell gestartet; eine optionale Deployment-Branch-Regel muss
   deshalb `main` erlauben.
2. Unter **Settings -> Branches** den Branch `production` schützen: direkte
   Änderungen nur für Administratoren, Pull Request oder restriktive
   Push-Regel. Der GitHub-Actions-Workflow benötigt Schreibrecht auf diesen
   Branch.
3. Für jede freigegebene Version zuerst auf GitHub unter **Releases** einen
   Release aus dem vorhandenen Tag `vX.Y.Z.N` veröffentlichen. Entwürfe und
   Pre-Releases werden bewusst abgelehnt.
4. Unter **Actions -> Promote published release to production** den Tag
   eingeben. Nach der Environment-Freigabe setzt der Workflow ausschließlich
   `production` auf genau diesen Tag.

## Einmalige Plesk-Konfiguration

1. Das Remote-Repository in Plesk auf den Branch `production` stellen,
   Document Root auf `<Anwendung>/public` setzen und automatisches Deployment
   aktivieren.
2. Den von Plesk bzw. Laravel Toolkit angezeigten Webhook unter GitHub
   **Settings -> Webhooks** eintragen. Event: `Push events`, SSL-Prüfung aktiv.
   Der Webhook darf nur Plesk benachrichtigen; er erhält keinen GitHub-Token.
3. Im Laravel Toolkit den Deployment-Ablauf aktivieren:
   Composer Production-Abhängigkeiten installieren, Node-Abhängigkeiten
   installieren und `build` ausführen.
4. Als zusätzliche Deployment-Aktion hinterlegen:

```sh
sh scripts/plesk-production-deploy.sh
```

Der Ablauf schaltet kurz in Wartung, leert Caches, führt additive Migrationen,
Administrator-Bootstrap und Laravel-Optimierung aus und schaltet die Anwendung
auch bei einem Fehler wieder hoch. Die vorhandene Datenbank wird nie geleert.

## Backups und Rollback

Plesk kopiert Dateien vor seinen zusätzlichen Aktionen. Ein OKGV-Backup aus
dem Deployment-Skript wäre daher kein Backup des alten Codes. Vor jeder
Freigabe muss ein Hoster-Snapshot oder ein manuelles OKGV-Backup vorhanden sein.
Für kritische Versionssprünge wird zusätzlich empfohlen:

1. OKGV-Backup über **Datenübertragung -> Backup** erstellen und herunterladen.
2. Datenbank- und Dateisicherung des Hosters prüfen.
3. Release promoten und Plesk-Deployment abwarten.
4. Anmeldung, Dashboard, Zählerstandsmeldung und PDF-Aufruf prüfen.

Ein Code-Rollback bedeutet: den vorherigen veröffentlichten GitHub-Release
erneut über den Workflow auf `production` promoten. Datenbankmigrationen sind
grundsätzlich vorwärtsgerichtet; ein Datenbankrollback erfolgt nur aus einem
geprüften Backup und nicht automatisch.

## Grenzen von Shared Hosting

Auf Shared Hosting gibt es keinen atomaren Releasewechsel mit Symlink und
automatischem Datenbank-Rollback. Für vollständig automatische Updates mit
Snapshot, Healthcheck und atomarem Zurückschalten ist später ein VPS/LXC- oder
Container-Betrieb erforderlich.
