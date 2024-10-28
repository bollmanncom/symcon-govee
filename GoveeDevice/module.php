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

        // Variablen registrieren
        $this->RegisterVariableBoolean('Status', 'Status', '~Switch', 1);
        $this->EnableAction('Status');

        $this->RegisterVariableInteger('Brightness', 'Helligkeit', '~Intensity.100', 2);
        $this->EnableAction('Brightness');

        $this->RegisterVariableInteger('Color', 'Farbe', '~HexColor', 3);
        $this->EnableAction('Color');

        $this->RegisterVariableInteger('ColorTemperature', 'Farbtemperatur', '', 4);
        $this->EnableAction('ColorTemperature');
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

    private function SetColorTemperature(int $colorTemperature)
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

    private function SetBrightness(int $Brightness)
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

    private function SetColor(int $Color)
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

    private function SendGoveeCommand($capability)
    {
        $data = [
            'DataID' => '{5ABD644C-3C2F-34C7-9B45-68CED2830B32}',
            'DeviceID' => $this->ReadPropertyString('DeviceID'),
            'DeviceModel' => $this->ReadPropertyString('DeviceModel'),
            'Capability' => $capability,
        ];

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
