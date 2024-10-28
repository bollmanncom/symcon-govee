<?php

declare(strict_types=1);

class GoveeDevice extends IPSModule
{
    public function Create()
    {
        parent::Create();

        // Verbindung zum übergeordneten Modul herstellen
        $this->ConnectParent('{9F57DC06-0EA2-41CE-9B12-FE766033D55D}'); // Govee IO UUID

        // Eigenschaften registrieren
        $this->RegisterPropertyString('DeviceID', '');
        $this->RegisterPropertyString('DeviceModel', '');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // Verbindung zum übergeordneten Modul sicherstellen
        $this->ConnectParent('{9F57DC06-0EA2-41CE-9B12-FE766033D55D}'); // Govee IO UUID

        // Überprüfen, ob Geräte-ID und -Modell gesetzt sind
        if ($this->ReadPropertyString('DeviceID') == '' || $this->ReadPropertyString('DeviceModel') == '') {
            $this->SetStatus(200); // Status auf inaktiv setzen
            return;
        }

        // Überprüfen, ob die Variablen existieren, und falls nicht, Standardwerte setzen
        $isNewInstance = false;

        if (!@$this->GetIDForIdent('Status')) {
            $this->RegisterVariableBoolean('Status', 'Status', '~Switch', 1);
            $this->EnableAction('Status');
            $this->SetValue('Status', true); // Standard: eingeschaltet
            $isNewInstance = true;
        }

        if (!@$this->GetIDForIdent('Brightness')) {
            $this->RegisterVariableInteger('Brightness', 'Helligkeit', '~Intensity.100', 2);
            $this->EnableAction('Brightness');
            $this->SetValue('Brightness', 100); // Standardhelligkeit
            $isNewInstance = true;
        }

        if (!@$this->GetIDForIdent('Color')) {
            $this->RegisterVariableInteger('Color', 'Farbe', '~HexColor', 3);
            $this->EnableAction('Color');
            $this->SetValue('Color', 0xFFFFFF); // Standardfarbe Weiß
            $isNewInstance = true;
        }

        if (!@$this->GetIDForIdent('ColorTemperature')) {
            $this->RegisterVariableInteger('ColorTemperature', 'Farbtemperatur', '', 4);
            $this->EnableAction('ColorTemperature');
            $this->SetValue('ColorTemperature', 3000); // Standardfarbtemperatur
            $isNewInstance = true;
        }

        // Wenn die Instanz neu ist, Standardwerte auf der Govee-LED setzen
        if ($isNewInstance) {
            $this->SwitchDevice(true);
            $this->SetAllAttributesWithTemperature(3000, 100);
        } else {
            // Aktuelle Werte der Variablen abrufen
            $state = $this->GetValue('Status');
            $colorTemperature = $this->GetValue('ColorTemperature');
            $brightness = $this->GetValue('Brightness');

            // Farbvariable abrufen und in RGB-Werte umwandeln
            $color = $this->GetValue('Color');
            $red = ($color >> 16) & 0xFF;
            $green = ($color >> 8) & 0xFF;
            $blue = $color & 0xFF;

            // Entscheiden, ob Farbtemperatur oder Farbe gesetzt werden soll
            if ($red == 255 && $green == 255 && $blue == 255) {
                $this->SetAllAttributesWithTemperature($colorTemperature, $brightness);
            } else {
                $this->SetAllAttributesWithColor($brightness, $red, $green, $blue);
            }
        }
    }


    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Status':
                $this->SwitchDevice($Value);
                break;

            case 'Brightness':
                $this->SetBrightness($Value);
                break;

            case 'Color':
                $this->SetColor($Value);
                break;

            case 'ColorTemperature':
                $this->SetColorTemperature($Value);
                break;

            default:
                throw new Exception('Invalid Ident');
        }
    }

    public function SetColorTemperature(int $colorTemperature)
    {
        // Überprüfen, ob der Farbtemperaturwert im gültigen Bereich liegt (2700K - 6500K)
        if ($colorTemperature < 2700 || $colorTemperature > 6500) {
            $this->SendDebug('SetColorTemperature', 'Fehler: Farbtemperatur außerhalb des gültigen Bereichs (2700K - 6500K).', 0);
            return ['success' => false, 'error' => 'Farbtemperatur muss zwischen 2700 und 6500 Kelvin liegen'];
        }

        $capability = [
            'type' => 'devices.capabilities.color_setting',
            'instance' => 'colorTemperatureK',
            'value' => $colorTemperature,
        ];

        $result = $this->SendGoveeCommand($capability);

        if ($result['success']) {
            $this->SetValue('ColorTemperature', $colorTemperature);
        } else {
            $this->SendDebug('SetColorTemperature', 'Fehler: ' . $result['error'], 0);
        }
    }


    public function SwitchDevice(bool $State)
    {
        $capability = [
            'type' => 'devices.capabilities.on_off',
            'instance' => 'powerSwitch',
            'value' => $State ? 1 : 0,
        ];

        $result = $this->SendGoveeCommand($capability);

        if ($result['success']) {
            $this->SetValue('Status', $State);
        } else {
            $this->SendDebug('SwitchDevice', 'Fehler: ' . $result['error'], 0);
        }
    }

    public function SetBrightness(int $Brightness)
    {
        $capability = [
            'type' => 'devices.capabilities.range',
            'instance' => 'brightness',
            'value' => $Brightness,
        ];

        $result = $this->SendGoveeCommand($capability);

        if ($result['success']) {
            $this->SetValue('Brightness', $Brightness);
        } else {
            $this->SendDebug('SetBrightness', 'Fehler: ' . $result['error'], 0);
        }
    }

    public function SetColor(int $Color)
    {
        $r = ($Color >> 16) & 0xFF;
        $g = ($Color >> 8) & 0xFF;
        $b = $Color & 0xFF;
        $colorValue = (($r & 0xFF) << 16) | (($g & 0xFF) << 8) | ($b & 0xFF);

        $capability = [
            'type' => 'devices.capabilities.color_setting',
            'instance' => 'colorRgb',
            'value' => $colorValue,
        ];

        $result = $this->SendGoveeCommand($capability);

        if ($result['success']) {
            $this->SetValue('Color', $Color);
        } else {
            $this->SendDebug('SetColor', 'Fehler: ' . $result['error'], 0);
        }
    }

    public function SetAllAttributesWithColor(int $brightness, int $red, int $green, int $blue)
    {
        $state = true;

        // Array zum Sammeln aller Capabilities
        $capabilities = [];

        // Ein-/Ausschalten hinzufügen
        $capabilities[] = [
            'type' => 'devices.capabilities.on_off',
            'instance' => 'powerSwitch',
            'value' => $state ? 1 : 0,
        ];

        // Helligkeit hinzufügen (Prüfen auf gültigen Bereich)
        if ($brightness >= 1 && $brightness <= 100) {
            $capabilities[] = [
                'type' => 'devices.capabilities.range',
                'instance' => 'brightness',
                'value' => $brightness,
            ];
        } else {
            $this->SendDebug('SetAllAttributes', 'Fehler: Helligkeit außerhalb des gültigen Bereichs (1 - 100).', 0);
        }

        // RGB-Farbwerte umwandeln und hinzufügen
        $colorValue = (($red & 0xFF) << 16) | (($green & 0xFF) << 8) | ($blue & 0xFF);
        $capabilities[] = [
            'type' => 'devices.capabilities.color_setting',
            'instance' => 'colorRgb',
            'value' => $colorValue,
        ];

        // Alle Capabilities in einem einzigen Aufruf an die API senden
        $result = $this->SendGoveeCommand($capabilities);

        if ($result['success']) {
            // Wenn erfolgreich, Werte in den Statusvariablen aktualisieren
            $this->SetValue('Status', $state);
            $this->SetValue('Brightness', $brightness);

            // RGB-Farbe als Integer für die Color-Variable speichern
            $this->SetValue('Color', $colorValue);
        } else {
            $this->SendDebug('SetAllAttributes', 'Fehler: ' . $result['error'], 0);
        }
    }

    public function SetAllAttributesWithTemperature(int $brightness, int $colorTemperature)
    {
        $state = true;

        // Array zum Sammeln aller Capabilities
        $capabilities = [];

        // Ein-/Ausschalten hinzufügen
        $capabilities[] = [
            'type' => 'devices.capabilities.on_off',
            'instance' => 'powerSwitch',
            'value' => $state ? 1 : 0,
        ];

        // Helligkeit hinzufügen (Prüfen auf gültigen Bereich)
        if ($brightness >= 1 && $brightness <= 100) {
            $capabilities[] = [
                'type' => 'devices.capabilities.range',
                'instance' => 'brightness',
                'value' => $brightness,
            ];
        } else {
            $this->SendDebug('SetAllAttributes', 'Fehler: Helligkeit außerhalb des gültigen Bereichs (1 - 100).', 0);
        }

        // Farbtemperatur hinzufügen (Prüfen auf gültigen Bereich)
        if ($colorTemperature >= 2700 && $colorTemperature <= 6500) {
            $capabilities[] = [
                'type' => 'devices.capabilities.color_setting',
                'instance' => 'colorTemperatureK',
                'value' => $colorTemperature,
            ];
        } else {
            $this->SendDebug('SetAllAttributes', 'Fehler: Farbtemperatur außerhalb des gültigen Bereichs (2700K - 6500K).', 0);
        }

        // Alle Capabilities in einem einzigen Aufruf an die API senden
        $result = $this->SendGoveeCommand($capabilities);

        if ($result['success']) {
            // Wenn erfolgreich, Werte in den Statusvariablen aktualisieren
            $this->SetValue('Status', $state);
            $this->SetValue('ColorTemperature', $colorTemperature);
            $this->SetValue('Brightness', $brightness);
        } else {
            $this->SendDebug('SetAllAttributes', 'Fehler: ' . $result['error'], 0);
        }
    }

    private function SendGoveeCommand($capability)
    {
        $data = [
            'DataID' => '{5ABD644C-3C2F-34C7-9B45-68CED2830B32}',
            'DeviceID' => $this->ReadPropertyString('DeviceID'),
            'DeviceModel' => $this->ReadPropertyString('DeviceModel'),
            'Capability' => $capability,
        ];

        $this->SendDebug('SendGoveeCommand', json_encode($data), 0);

        // Senden der Daten an das übergeordnete Modul
        $jsonResult = $this->SendDataToParent(json_encode($data));

        // Prüfen, ob die Antwort leer oder fehlerhaft ist
        if ($jsonResult === false) {
            $this->SendDebug('SendGoveeCommand', 'Fehler: Kommunikation mit übergeordnetem Objekt fehlgeschlagen. Überprüfen Sie die DataID und das übergeordnete Objekt.', 0);
            return ['success' => false, 'error' => 'Kommunikation mit übergeordnetem Objekt fehlgeschlagen'];
        }

        // Antwort dekodieren und Fehler prüfen
        $decodedResult = json_decode($jsonResult, true);
        if ($decodedResult === null) {
            $this->SendDebug('SendGoveeCommand', 'Fehler: JSON-Dekodierung fehlgeschlagen. Empfangenes Ergebnis: ' . $jsonResult, 0);
            return ['success' => false, 'error' => 'JSON-Dekodierung fehlgeschlagen'];
        }

        return $decodedResult;
    }

    // Konfigurationsformular bereitstellen
    public function GetConfigurationForm()
    {
        return file_get_contents(__DIR__ . '/form.json');
    }
}
