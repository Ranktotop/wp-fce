# FluentCommunity Extreme Add-On

Ein WordPress-Plugin zur Erweiterung des [FluentCommunity Plugins](https://fluentcommunity.co/).  
Dieses Add-On stellt eine REST-API zur Verfügung, über die externe Zahlungsanbieter wie CopeCart oder Digistore24 Mitgliederzugänge automatisch verwalten können.

---

## 🔧 Funktionen

- Zuordnung von Produkten zu Spaces und/oder Courses
- Automatische Verarbeitung von IPNs von Zahlungsanbietern
- Automatische Benutzerregistrierung (falls nicht vorhanden)
- Automatisches Hinzufügen und Entfernen von Kurs-/Space-Zugängen
- Cronjob-basierte Prüfung auf Ablauf von Mitgliedschaften
- REST-API für Anbindung externer Systeme

---

## ⚙️ Voraussetzungen

- WordPress 6.x+
- [FluentCommunity](https://fluentcommunity.co/) (muss installiert und aktiviert sein)
- PHP 8.1+
- Composer (nur für Entwicklung, `vendor/` ist im Release enthalten)

---

## 📦 Installation

1. ZIP-Datei in WordPress hochladen unter **Plugins > Installieren > Plugin hochladen**
2. Aktivieren
3. Unter **Einstellungen > FluentCommunity Extreme** konfigurieren

---

## 🔁 Automatische Updates

Dieses Plugin prüft automatisch auf neue Versionen via GitHub, sofern ein Release vorhanden ist.  
Dafür wird [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) verwendet.

---

## 🧪 Entwicklung

```bash
composer install
```

Zum Paketieren für die Produktion:

```bash
composer install --no-dev --optimize-autoloader
```

---

## 📄 Lizenz

GPL-2.0-or-later

---

## 👤 Autor

**Marc Meese**  
[https://marcmeese.com](https://marcmeese.com)
