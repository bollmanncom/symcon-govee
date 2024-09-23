<?php

declare(strict_types=1);

class GoveeDevice extends IPSModule
{
    public function Create()
    {
        parent::Create();

        // Verbindung zum übergeordneten Modul herstellen
        $this->ConnectParent('{9f57dc06-0ea2-41ce-9b12-fe766033d55d}'); // Govee IO UUID

        // Eigenschaften registrieren
        $this->RegisterPropertyString('DeviceID', '');
        $this->RegisterPropertyString('DeviceModel', '');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // Verbindung zum übergeordneten Modul sicherstellen
        $this->ConnectParent('{9f57dc06-0ea2-41ce-9b12-fe766033d55d}'); // Govee IO UUID

        // Variablen registrieren
        $this->RegisterVariableBoolean('Status', 'Status', '~Switch', 1);
        $this->EnableAction('Status');

        $this->RegisterVariableInteger('Brightness', 'Helligkeit', '~Intensity.100', 2);
        $this->EnableAction('Brightness');

        $this->RegisterVariableInteger('Color', 'Farbe', '~HexColor', 3);
        $this->EnableAction('Color');
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

            default:
                throw new Exception('Invalid Ident');
        }
    }

    private function SwitchDevice(bool $State)
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
            'type' => 'devices.capabilities.brightness',
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
            'DataID' => '{e2cdd4c0-3e9f-4b4e-9d92-8c1f9b6f8b8b}',
            'DeviceID' => $this->ReadPropertyString('DeviceID'),
            'DeviceModel' => $this->ReadPropertyString('DeviceModel'),
            'Capability' => $capability,
        ];

        $jsonResult = $this->SendDataToParent(json_encode($data));
        return json_decode($jsonResult, true);
    }
}