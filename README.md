# ELIAS  Beyond the Profile

Persönliche Website von Elias Ems als klassische PHP-Seite: **PHP, MySQL, HTML, CSS und JavaScript**, ohne Build-Schritt und ohne Node.js auf dem Server. Damit läuft sie auf günstigem oder kostenlosem Webspace wie **InfinityFree**.

Inhalt: Hero, About, The Journey, Endurance (Sportgalerie), Projekte mit Detailseiten, der KI-Assistent „Ask Elias“, Kontakt und das Gästebuch als letzte Sektion. Moderation unter `/admin/`.

## Ordner

```
htdocs/                  ← dieser Inhalt kommt auf den Webspace
  index.php              Startseite
  project.php            Projekt-Detailseiten (/projects/<name>)
  404.php, sitemap.php, robots.php, favicon.svg, .htaccess
  admin/                 Moderation des Gästebuchs
  api/                   guestbook.php, report.php, ask.php, admin.php
  assets/css/style.css   das komplette Design
  assets/js/             Scroll-Effekte, Menü, Galerie, Ask, Gästebuch
  assets/fonts/          Schrift Geist (OFL-Lizenz liegt daneben)
  assets/images/         Bilder (Sport, Projekte, Vorschaubild)
  app/                   PHP-Code, Konfiguration und Inhalte (vom Web aus gesperrt)
    .env                 ← deine Zugangsdaten (nie auf GitHub, Vorlage: .env.example)
    config.php           liest die Werte aus .env, enthält selbst keine Geheimnisse
    data/                ← hier pflegst du die Inhalte
  database/schema.sql    Tabellen für das Gästebuch (vom Web aus gesperrt)
    app/.env.example       Vorlage für app/.env
.htaccess                leitet beim lokalen Start mit XAMPP alles nach htdocs/ weiter
```

## Auf InfinityFree veröffentlichen

1. **Konto und Website anlegen** auf https://infinityfree.com. Du bekommst eine Adresse wie `deinname.infinityfreeapp.com` (oder nimmst eine eigene Domain).
2. **SSL einschalten:** im Control Panel unter „SSL Certificates“ ein kostenloses Zertifikat ausstellen lassen, damit die Seite über `https://` läuft.
3. **Datenbank anlegen:** Control Panel → „MySQL Databases“ → neue Datenbank erstellen. Notiere dir **MySQL Hostname**, **Datenbankname**, **Benutzername** und **Passwort** (steht unter „MySQL Details“ bzw. „Account Details“).
4. **Tabellen anlegen:** bei der Datenbank auf „phpMyAdmin“ → links die Datenbank wählen → Reiter „Importieren“ → Datei `htdocs/database/schema.sql` auswählen → „OK“.
5. **Konfiguration ausfüllen:** `htdocs/app/.env.example` nach `htdocs/app/.env` kopieren und eintragen:
   - `SITE_URL`: deine Adresse, z. B. `https://deinname.infinityfreeapp.com`
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`: die Werte aus Schritt 3, `DB_CREATE_IF_MISSING=false`
   - `ADMIN_PASSWORD_HASH`: der Hash deines Admin-Passworts (siehe „Admin-Passwort“)
   - `IP_SALT`: irgendein langer Zufallstext, z. B. 40 Zeichen aus einem Passwortgenerator
   - `AI_API_KEY`: optional, siehe „KI-Assistent“

   Werte mit Sonderzeichen (z. B. der Hash) in einfache Anführungszeichen setzen: `ADMIN_PASSWORD_HASH='$2y$12$...'`.
6. **Hochladen:** Control Panel → „Online File Manager“ (oder ein FTP-Programm wie FileZilla mit den FTP-Daten aus dem Panel). Den **Inhalt** des Ordners `htdocs` in den Ordner `htdocs` auf dem Server hochladen, also so, dass `index.php` direkt in `htdocs` liegt. Die Datei `.htaccess` muss mit hoch (im File Manager eventuell „versteckte Dateien anzeigen“ aktivieren).
7. **Testen:** Seite öffnen, Kommentar schreiben (er erscheint sofort), die Seite in einem anderen Browser oder am Handy neu laden. Der Kommentar muss dort erscheinen. Danach unter `https://deine-adresse/admin/` anmelden und ihn wieder löschen.

Neue DNS-Einträge und Zertifikate brauchen bei InfinityFree manchmal bis zu ein paar Stunden.

**Beim Aktualisieren** nur die geänderten Dateien hochladen und `app/.env` auf dem Server nicht überschreiben (dort stehen die Zugangsdaten von InfinityFree, lokal die von XAMPP).

**GitHub:** `.env` steht in `.gitignore` und wird nie hochgeladen. Alles andere, auch `config.php`, enthält keine Geheimnisse.

### Was InfinityFree besonders macht

- InfinityFree prüft jeden Besucher mit einem kurzen JavaScript-Test. Echte Browser merken davon nichts, und die Anfragen der Seite an ihre eigenen PHP-Dateien funktionieren. Fremde Programme, Webhooks oder Apps können die API dagegen nicht aufrufen. Das ist für diese Seite in Ordnung.
- URLs mit dem Wort „chat“ werden dort blockiert. Deshalb heißt der Endpunkt des Assistenten `api/ask.php`. Bitte nichts in „chat“ umbenennen.
- E-Mail-Versand per PHP gibt es dort nicht; die Seite braucht ihn auch nicht.
- Die Datenbank ist nur vom Webspace aus erreichbar, nicht von deinem PC.

## Lokal starten mit XAMPP

Der Projektordner liegt in `C:\xampp\htdocs\Elias-Website`.

1. Diesen Ordner nach `C:\xampp\htdocs\Elias-Website` kopieren.
2. `htdocs/app/.env.example` nach `htdocs/app/.env` kopieren (die Vorlage hat schon die passenden XAMPP-Werte).
3. **XAMPP Control Panel** öffnen und bei **Apache** und **MySQL** auf „Start“ klicken.
4. Im Browser **http://localhost/Elias-Website/** öffnen.
3. Beenden im XAMPP Control Panel mit „Stop“.

Der Admin-Bereich unter `/admin/` ist aus, bis du in `htdocs/app/.env` einen `ADMIN_PASSWORD_HASH` einträgst (siehe „Admin-Passwort“). Gästebuch und die restliche Seite laufen auch ohne.

Beim ersten Aufruf legt die Seite die Datenbank `elias` mit allen Tabellen selbst an (nur lokal, eingestellt in `htdocs/app/.env` mit `DB_CREATE_IF_MISSING=true`). Änderungen an Dateien siehst du sofort nach dem Neuladen im Browser.

Die `.htaccess` im Projektordner leitet alle Aufrufe nach `htdocs/` weiter. Deshalb steht `htdocs` nicht in der Adresse, und README und die anderen Dateien außerhalb von htdocs/ sind vom Browser aus nicht erreichbar. Die Seite erkennt selbst, dass sie in einem Unterordner liegt; auf InfinityFree (direkt im Web-Root) funktionieren dieselben Dateien ohne Änderung.

Alle Zugangsdaten stehen in `htdocs/app/.env`. Die Seite funktioniert damit auch vom Handy im selben WLAN (über die IP-Adresse des PCs, z. B. `http://10.234.100.168/Elias-Website/`).

Hat dein MySQL-Benutzer `root` ein Passwort, trage es in `.env` bei `DB_PASS` ein. Startet Apache nicht, ist meist Port 80 durch ein anderes Programm belegt; das zeigt das XAMPP Control Panel an.

## Inhalte pflegen

Alle persönlichen Inhalte stehen in `htdocs/app/data/`. Die Dateien sind PHP, aber wie Listen aufgebaut: Text zwischen `'...'` ändern, Einträge kopieren. Ein Apostroph im Text wird als `\'` geschrieben, oder der Text steht in `"..."`.

| Datei | Inhalt |
| --- | --- |
| `profile.php` | Name, Headline, About-Texte, Social Links, Navigation, optionales Hero-Bild |
| `projects.php` | Projekte mit Status, Technologien, Links, Bildern |
| `timeline.php` | Einträge für „The Journey“ |
| `sport.php` | Sportfotos, Screenshots, Statistiken |
| `knowledge.php` | Wissen und Beispielfragen des KI-Assistenten |

**Social Links:** in `profile.php` bei `SOCIAL_LINKS` die leeren `'url' => ''` füllen. Leere Einträge erscheinen als „Not linked yet“.

**Fotos:** Datei nach `htdocs/assets/images/sport/` hochladen und in `sport.php` beim Eintrag `'image' => null` ersetzen durch
`'image' => ['src' => '/assets/images/sport/datei.jpg', 'width' => 1600, 'height' => 2000]` (echte Pixelgröße) und einen `'alt'`-Text schreiben. Vor dem Hochladen von Strava-Screenshots Wohnadresse, Start- und Zielpunkte in Wohnungsnähe und Namen anderer Personen wegschneiden.

**Projekte:** in `projects.php` einen Eintrag kopieren und anpassen. `status` nur setzen, wenn er stimmt. Was funktioniert, gehört in `implemented`, Ideen in `planned`.

Nach Änderungen die Datei erneut hochladen. Ein Tippfehler in einer PHP-Datei (z. B. ein fehlendes Komma) führt zu einer leeren Seite; dann die letzte Änderung prüfen.

## KI-Assistent einrichten (Ask Elias)

1. Bei https://openrouter.ai (oder einem anderen OpenAI-kompatiblen Anbieter) einen API-Schlüssel erstellen.
2. **Beim Anbieter ein Ausgabenlimit setzen.** Das ist die wichtigste Kostenbremse.
3. In `.env` bei `AI_API_KEY` den Schlüssel eintragen, optional `AI_MODEL` ändern.

Der Assistent kennt nur, was in `knowledge.php` steht, und alles dort kann jedem Besucher erzählt werden. Begrenzungen: Fragen höchstens 500 Zeichen, nur die letzten 8 Nachrichten werden mitgeschickt, Antworten höchstens 350 Tokens, 20 Sekunden Timeout, pro Besucher 8 Fragen in 5 Minuten und 40 pro Tag, dazu `daily_limit` für alle zusammen. Chatverläufe werden nicht gespeichert.

## Admin-Bereich

`https://deine-adresse/admin/` (lokal: http://localhost/Elias-Website/admin/) öffnen und mit deinem Admin-Passwort anmelden. Oben wählst du den Bereich:

- **Comments:** Neue Kommentare erscheinen sofort, ohne Freigabe. **Unpublish** blendet einen aus, **Delete** löscht ihn endgültig (zweimal klicken: „Delete for good“). Unter **Reported** stehen alle gemeldeten Kommentare mit Anzahl und Grund der Meldungen; eine rote Zahl an „Comments“ zeigt, wie viele es gibt. Besucher können Kommentare mit dem Herz liken (eins pro Besucher), die Anzahl siehst du auch im Admin.
  Wenn ein KI-Schlüssel (`AI_API_KEY`) gesetzt ist, prüft die KI jeden neuen Kommentar beim Absenden und blendet Beleidigungen, Hassrede, Spam o. Ä. sofort aus (Status `rejected`, nur im Admin sichtbar). Ältere, noch ungeprüfte Kommentare werden nach und nach im Hintergrund nachgeprüft. Ist die KI nicht erreichbar, wird der Kommentar normal veröffentlicht und später nachgeprüft.
- **Texts:** Rolle neben dem Namen, die großen Wörter oben, Intro, About-Texte, Kacheln und Interessen, die Links bei „Say hello“ und die Texte im Sport-Bereich (inklusive Zitat).
- **Strava:** Aktivitäten-Karten hinzufügen, bearbeiten, umsortieren (↑ ↓) und entfernen (✕), dazu „Strava in numbers“ und die Kacheln oben im Sport-Bereich.
- **Ask Elias:** Beispielfragen und das Wissen des Assistenten (Themen mit Fakten und Plänen). Die Regeln des Assistenten bleiben fest im Code.

Jeder Abschnitt hat **Save** und **Reset to original**. Eingaben werden schon im Browser geprüft und auf dem Server noch einmal vollständig. Gespeicherte Änderungen liegen in der Datenbank (Tabelle `site_content`) und sind sofort online. Die Dateien in `app/data/` bleiben die Ausgangsversion: „Reset to original“ holt sie zurück, und ein neuer Upload der Dateien überschreibt deine Änderungen im Admin nicht. Projekte und Journey werden weiter in `app/data/` gepflegt.

**Admin-Passwort:** In `.env` steht nur ein Hash (`ADMIN_PASSWORD_HASH`), nicht das Passwort selbst. Für ein neues Passwort in einer Eingabeaufforderung ausführen:

```
C:\xampp\php\php.exe -r "echo password_hash('NEUES-PASSWORT', PASSWORD_DEFAULT);"
```

Das Ergebnis in einfachen Anführungszeichen bei `ADMIN_PASSWORD_HASH` eintragen. Nach 5 falschen Versuchen ist die Anmeldung 15 Minuten gesperrt.

## Sicherheit

Umgesetzt:

- Zugangsdaten nur in `app/.env` (nicht in Git); der Ordner `app/` und `database/` sowie alle Dateien, die mit einem Punkt beginnen, sind per `.htaccess` gesperrt.
- Das Admin-Passwort ist nur als bcrypt-Hash gespeichert; nach 5 Fehlversuchen ist die Anmeldung 15 Minuten gesperrt.
- Alle Datenbankzugriffe mit Prepared Statements (PDO), keine zusammengesetzten SQL-Befehle mit Eingaben.
- Jede Ausgabe in HTML wird escaped; Kommentare werden im Browser als Text eingefügt, nie als HTML.
- Serverseitige Prüfung aller Eingaben (Name 1–40, Nachricht 1–500 Zeichen nach dem Trimmen), Größenlimit für jede Anfrage.
- Neue Kommentare sind sofort `published`; per Meldung und unter `/admin/` lassen sie sich ausblenden oder löschen.
- Rate Limiting in der Datenbank, Honeypot-Feld, Mindestzeit bis zum Absenden, höchstens ein Link, keine doppelten Nachrichten, eine Meldung pro Besucher und Kommentar.
- IP-Adressen werden nur als gesalzener Hash gespeichert.
- Moderation über eine PHP-Session (Cookie `HttpOnly`, `SameSite=Strict`, über HTTPS `Secure`), neue Session-ID beim Anmelden, CSRF-Token und Origin-Prüfung bei jeder Änderung, 5 Anmeldeversuche pro 15 Minuten.
- Sicherheitsheader, darunter eine Content-Security-Policy, die nur Skripte von der eigenen Seite erlaubt.
- Schrift, Skripte und Bilder liegen auf dem eigenen Webspace; es werden keine Daten an Google o. Ä. übertragen. Nur Fragen an „Ask Elias“ gehen an den KI-Anbieter.

Verbleibende Risiken:

- **Ein einzelnes Admin-Passwort**, ohne zweiten Faktor.
- **Limits hängen an der IP-Adresse.** Wer viele Adressen nutzt, umgeht sie; die Moderation und das Tageslimit fangen das ab. Falls InfinityFree alle Besucher mit derselben Adresse anliefert, teilen sie sich ein Limit; dann in `.env` `TRUST_FORWARDED_FOR` prüfen.
- **Kein CAPTCHA** im Gästebuch.
- **Prompt Injection:** Der Assistent hat Regeln, aber Sprachmodelle lassen sich überreden. Deshalb steht in `knowledge.php` nichts Privates.
- Kostenloses Hosting hat Grenzen bei Rechenzeit und Aufrufen pro Tag; bei sehr vielen Besuchern kann die Seite zeitweise gesperrt werden.

## Noch von dir zu ergänzen

- [ ] Sportfotos und Strava-Screenshots ergänzen (`sport.php` oder im Admin)
- [ ] Vorschaubilder und Screenshots für die Projekte
- [ ] Impressum/Offenlegung und Datenschutzerklärung (Gästebuch, IP-Hash, Weitergabe von Fragen an einen KI-Anbieter). Was in Österreich genau nötig ist, bitte selbst klären; das hier ist keine Rechtsberatung.

Auf der Seite, im Assistenten und in den Metadaten steht keine Altersangabe. Bitte auch beim Ergänzen keine eintragen.

