# Govee Control Modul für IP-Symcon

## Beschreibung

Das **Govee Control Modul** ermöglicht die Integration und Steuerung von Govee-Geräten innerhalb von IP-Symcon. Mit diesem Modul können Sie Ihre Govee-Lampen und -Leuchten direkt aus IP-Symcon heraus steuern, einschließlich Ein-/Ausschalten, Helligkeitsanpassung und Farbsteuerung.

## Funktionsumfang

- **Govee IO Modul**: Übergeordnete Instanz zur Verwaltung des Govee API-Schlüssels und zur Kommunikation mit der Govee Cloud API.
- **Govee Device Modul**: Repräsentiert ein einzelnes Govee-Gerät und ermöglicht die Steuerung von Status, Helligkeit und Farbe.

## Voraussetzungen

- IP-Symcon Version **6.0** oder höher
- Govee-Gerät(e) mit API-Unterstützung
- Govee API-Schlüssel (erhältlich unter [Govee Developer API](https://developer.govee.com/))

## Installation

### Modul-Repository hinzufügen

1. Öffnen Sie die **IP-Symcon Management Console**.
2. Navigieren Sie zu **Kern Instanzen** > **Modules**.
3. Fügen Sie ein neues Modul hinzu und verwenden Sie folgende URL: https://github.com/bollmann-com/symcon-govee.git


4. Klicken Sie auf **OK**, um das Modul zu installieren.

## Konfiguration

### Govee IO Modul einrichten

1. **Instanz erstellen**:

- Navigieren Sie im Objektbaum zu dem Ort, an dem Sie die Instanz erstellen möchten.
- Klicken Sie mit der rechten Maustaste und wählen Sie **Objekt hinzufügen** > **Instanz**.
- Wählen Sie unter **Hersteller** das Modul **Govee IO** aus.

2. **API-Schlüssel eingeben**:

- Öffnen Sie die Eigenschaften der neu erstellten Govee IO Instanz.
- Tragen Sie Ihren persönlichen Govee API-Schlüssel in das Feld **APIKey** ein.
- Speichern Sie die Einstellungen.

### Govee Device Modul einrichten

1. **Instanz erstellen**:

- Erstellen Sie eine neue Instanz des Moduls **Govee Device** wie zuvor.

2. **Übergeordnete Instanz auswählen**:

- Stellen Sie sicher, dass die Govee Device Instanz mit der zuvor erstellten Govee IO Instanz verbunden ist. Dies sollte automatisch geschehen. Falls nicht, können Sie die übergeordnete Instanz manuell auswählen.

3. **Geräteeinstellungen konfigurieren**:

- Öffnen Sie die Eigenschaften der Govee Device Instanz.
- Geben Sie die **DeviceID** und das **DeviceModel** Ihres Govee-Geräts ein.
  - **DeviceID**: Eindeutige ID Ihres Geräts (z. B. `F2:3A:CC:8D:A2:B4:1C:CC`).
  - **DeviceModel**: Modellbezeichnung Ihres Geräts (z. B. `H600D`).
- Speichern Sie die Einstellungen.

**Hinweis**: Die DeviceID und das DeviceModel können Sie über die Govee Home App oder über die Govee API erhalten.

## Verwendung

Nach erfolgreicher Einrichtung stehen Ihnen folgende Variablen zur Verfügung:

- **Status** (`~Switch`): Schaltet das Gerät ein oder aus.
- **Helligkeit** (`~Intensity.100`): Reguliert die Helligkeit (Wertebereich 0–100 %).
- **Farbe** (`~HexColor`): Ändert die Farbe des Geräts über einen Farbwähler.

### Steuerung über die IP-Symcon Oberfläche

- **Gerät ein- oder ausschalten**:

- Setzen Sie die **Status**-Variable auf **Ein** oder **Aus**.

- **Helligkeit anpassen**:

- Ändern Sie den Wert der **Helligkeit**-Variable, um die gewünschte Helligkeit einzustellen.

- **Farbe ändern**:

- Verwenden Sie den Farbwähler der **Farbe**-Variable, um eine neue Farbe auszuwählen.

### Automatisierung und Skripte

Sie können die Variablen auch in eigenen Skripten verwenden, um Automatisierungen zu erstellen.

**Beispiel: Gerät einschalten und Farbe setzen**

```php
$instanceID = 12345; // ID Ihrer Govee Device Instanz

// Gerät einschalten
RequestAction(IPS_GetObjectIDByIdent('Status', $instanceID), true);

// Helligkeit auf 75 % setzen
RequestAction(IPS_GetObjectIDByIdent('Brightness', $instanceID), 75);

// Farbe auf Blau setzen (#0000FF)
$blueColor = hexdec('0000FF');
RequestAction(IPS_GetObjectIDByIdent('Color', $instanceID), $blueColor);

## Fehlerbehebung

### Gerät reagiert nicht:
- Überprüfen Sie die DeviceID und das DeviceModel in den Einstellungen.
- Stellen Sie sicher, dass das Gerät mit dem Internet verbunden ist.
- Prüfen Sie, ob der API-Schlüssel korrekt und gültig ist.

### Fehlermeldungen in IP-Symcon:
- Aktivieren Sie das Debugging für die Instanzen, um detaillierte Informationen zu erhalten.
- Prüfen Sie die Meldungen im IP-Symcon Meldungsfenster.

### Ungültiger API-Schlüssel:
- Stellen Sie sicher, dass Sie den korrekten API-Schlüssel von Govee verwenden.
- Beantragen Sie gegebenenfalls einen neuen Schlüssel über die Govee Developer API.

## Bekannte Probleme

### Einschränkungen der Govee API:
- Nicht alle Funktionen sind für alle Geräte verfügbar.
- Es gibt API-Aufruflimits; vermeiden Sie zu häufige Anfragen in kurzen Zeitabständen.

### Verzögerungen bei der Steuerung:
- Da die Kommunikation über die Cloud erfolgt, kann es zu leichten Verzögerungen kommen.

## Unterstützung und Kontakt

### Bei Fragen oder Problemen können Sie sich gerne an uns wenden:

- Entwickler: bollmann.com e.K.
- E-Mail: info@bollmann.com
- Webseite: https://bollmann.com

## Lizenz

Dieses Modul steht unter der MIT-Lizenz. Details finden Sie in der Datei LICENSE.

## Danksagung

IP-Symcon Community: Für die Unterstützung und Ressourcen zur Modulentwicklung.
Govee: Für die Bereitstellung der API und Dokumentation.

## Hinweis: Bitte stellen Sie sicher, dass Sie die aktuellste Version dieses Moduls verwenden und dass Sie die Dokumentation von Govee bezüglich API-Nutzung und -Beschränkungen beachten.