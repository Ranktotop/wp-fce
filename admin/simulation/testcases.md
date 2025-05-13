
# 📋 WP_FCE Zugriffs-Testfälle

Diese Datei dokumentiert 12 Testfälle zur Überprüfung der Zugriffslogik im Fluent Community Extreme Plugin.

---

## 🔬 Testfall #1: Standardkauf mit gültiger Laufzeit

| Schritt | Beschreibung |
|--------|--------------|
| **Ereignis** | Nutzer 101 kauft Produkt 201 über CopeCart |
| **Betroffene Tabellen** | `fce_ipn_log`: Eintrag für IPN<br>`fce_product_user`: neuer Eintrag<br>`fce_product_mappings`: Produkt 201 → Space 301 |
| **Zugriff erwartet auf** | Space 301 |
| **Zugriff erlaubt?** | ✅ |
| **Grund für Entscheidung** | Produktkauf ist dokumentiert, Gültigkeit ist vorhanden, Mapping ist korrekt vorhanden |

---

## 🔬 Testfall #2: Abgelaufenes Produkt

| Schritt | Beschreibung |
|--------|--------------|
| **Ereignis** | Nutzer 102 hat Produkt 202 gekauft, aber `expiry_date` ist in der Vergangenheit |
| **Betroffene Tabellen** | `fce_product_user`: Eintrag mit abgelaufenem `expiry_date`<br>`fce_product_mappings`: Produkt 202 → Space 302 |
| **Zugriff erwartet auf** | Space 302 |
| **Zugriff erlaubt?** | ❌ |
| **Grund für Entscheidung** | Produkt ist zwar gekauft und gemappt, aber bereits abgelaufen |

---

## 🔬 Testfall #3: Admin-Override erlaubt Zugriff trotz abgelaufenem Produkt

| Schritt | Beschreibung |
|--------|--------------|
| **Ereignis** | Wie Testfall #2, aber zusätzlich ein Admin-Override „erlaube Zugriff“ für Space 302 |
| **Betroffene Tabellen** | `fce_product_user` (abgelaufen)<br>`fce_access_override`: Eintrag mit `override_allow = true` für User/Space |
| **Zugriff erwartet auf** | Space 302 |
| **Zugriff erlaubt?** | ✅ |
| **Grund für Entscheidung** | Admin-Override hat Vorrang vor Produktstatus |

---

## 🔬 Testfall #4: Admin-Override verbietet Zugriff trotz gültigem Produkt

| Schritt | Beschreibung |
|--------|--------------|
| **Ereignis** | Nutzer 103 kauft gültiges Produkt 203 → Space 303, Admin setzt expliziten Deny |
| **Betroffene Tabellen** | `fce_product_user` (gültig)<br>`fce_access_override`: `override_deny = true` |
| **Zugriff erwartet auf** | Space 303 |
| **Zugriff erlaubt?** | ❌ |
| **Grund für Entscheidung** | Admin-Override „verweigern“ hat höchste Priorität |

---

## 🔬 Testfall #5: Kein Produkt, kein Override

| Schritt | Beschreibung |
|--------|--------------|
| **Ereignis** | Nutzer 104 ist registriert, hat kein Produkt und keinen Override |
| **Betroffene Tabellen** | keine |
| **Zugriff erwartet auf** | Space 304 |
| **Zugriff erlaubt?** | ❌ |
| **Grund für Entscheidung** | Keine Quelle für Zugriff vorhanden |

---

## 🔬 Testfall #6: Gültiges Produkt, aber kein Mapping

| Schritt | Beschreibung |
|--------|--------------|
| **Ereignis** | Nutzer 105 kauft Produkt 205, aber das Produkt ist **nicht gemappt** |
| **Betroffene Tabellen** | `fce_product_user`: gültig<br>`fce_product_mappings`: kein Eintrag für Produkt 205 |
| **Zugriff erwartet auf** | Space 305 |
| **Zugriff erlaubt?** | ❌ |
| **Grund für Entscheidung** | Kein Mapping → Zugriffsziel ist nicht verbunden mit Produkt |

---

## 🔬 Testfall #7: Mehrere Produkte, eines davon gültig und gemappt

| Schritt | Beschreibung |
|--------|--------------|
| **Ereignis** | Nutzer 106 besitzt 3 Produkte:<br>– Produkt 206 (abgelaufen)<br>– Produkt 207 (ungemappt)<br>– Produkt 208 (gültig + gemappt auf Space 306) |
| **Betroffene Tabellen** | `fce_product_user`: 3 Einträge<br>`fce_product_mappings`: Produkt 208 → Space 306 |
| **Zugriff erwartet auf** | Space 306 |
| **Zugriff erlaubt?** | ✅ |
| **Grund für Entscheidung** | Mindestens ein gültiges Produkt ist korrekt gemappt |

---

## 🔬 Testfall #8: Produkt wird nach Ablauf gelöscht, aber Override bleibt

| Schritt | Beschreibung |
|--------|--------------|
| **Ereignis** | Produkt 209 läuft ab und wird aus `fce_product_user` gelöscht, Admin hat aber weiterhin Zugriff erlaubt |
| **Betroffene Tabellen** | `fce_product_user`: gelöscht<br>`fce_access_override`: `override_allow = true` |
| **Zugriff erwartet auf** | Space 307 |
| **Zugriff erlaubt?** | ✅ |
| **Grund für Entscheidung** | Admin-Zugriff bleibt bestehen, unabhängig von Produktstatus |

---

## 🔬 Testfall #9: IPN ohne Produkterstellung (ungültige IPN)

| Schritt | Beschreibung |
|--------|--------------|
| **Ereignis** | IPN wird empfangen, aber Produkt-ID fehlt oder ist unbekannt → kein Eintrag in `fce_product_user` |
| **Betroffene Tabellen** | `fce_ipn_log`: Eintrag vorhanden<br>`fce_product_user`: kein Eintrag |
| **Zugriff erwartet auf** | Space 308 |
| **Zugriff erlaubt?** | ❌ |
| **Grund für Entscheidung** | Ohne Produkt kein Zugriff – IPN allein ist nicht relevant für Access Decision |

---

## 🔬 Testfall #10: Temporärer Zugriff per Produkt, später Admin-Zugriff dauerhaft gesetzt

| Schritt | Beschreibung |
|--------|--------------|
| **Ereignis** | Nutzer 110 kauft Produkt 210 (30 Tage), danach setzt Admin dauerhaft Zugriff auf Space 310 |
| **Betroffene Tabellen** | `fce_product_user`: abgelaufen<br>`fce_access_override`: vorhanden |
| **Zugriff erwartet auf** | Space 310 |
| **Zugriff erlaubt?** | ✅ |
| **Grund für Entscheidung** | Auch nach Ablauf greift Admin-Zugriff dauerhaft weiter |

---

## 🔬 Testfall #11: Produkt nicht gemappt bei Kauf → Mapping nachträglich gesetzt

| Schritt | Beschreibung |
|--------|--------------|
| **Ereignis** | Nutzer 111 kauft Produkt 211 → Eintrag in `fce_product_user`<br>Produkt 211 ist **noch nicht gemappt** → kein Zugriff<br>Admin mappt später Produkt 211 auf Space 311 |
| **Betroffene Tabellen** | `fce_product_user`: Eintrag vorhanden, gültig<br>`fce_product_mappings`: Mapping erst nachträglich erstellt<br>`fce_ipn_log`: vorhanden |
| **Zugriff erwartet auf** | Space 311 |
| **Zugriff erlaubt?** | ✅ |
| **Grund für Entscheidung** | Das Produkt ist gültig und durch das nachträgliche Mapping wird es mit dem Space verbunden. Dadurch entsteht rückwirkend Zugriff, solange das Produkt gültig ist. |

---

## 🔬 Testfall #12: Produkt wird gelöscht, Mapping bleibt bestehen

| Schritt | Beschreibung |
|--------|--------------|
| **Ereignis** | Nutzer 112 hatte Produkt 212 mit Mapping auf Space 312<br>Produkt läuft ab → wird vom Admin **vollständig gelöscht**<br>Mapping bleibt bestehen |
| **Betroffene Tabellen** | `fce_product_user`: Eintrag gelöscht<br>`fce_product_mappings`: Eintrag bleibt bestehen<br>`fce_access_override`: nicht vorhanden |
| **Zugriff erwartet auf** | Space 312 |
| **Zugriff erlaubt?** | ❌ |
| **Grund für Entscheidung** | Ohne zugewiesenes Produkt gibt es keine gültige Quelle mehr für Zugriff. Mapping ist nur ein Verweis – ohne Besitz irrelevant. |
