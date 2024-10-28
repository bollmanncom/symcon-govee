<?php

declare(strict_types=1);

class GoveeConfigurator extends IPSModule
{
    public function Create()
    {
        parent::Create();
        $this->ConnectParent('{9F57DC06-0EA2-41CE-9B12-FE766033D55D}'); // GoveeIO als übergeordnetes Modul
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
    }

    public function GetConfigurationForm()
    {
        $form = [
            'elements' => [],
            'actions' => [],
            'status' => []
        ];

        // Geräte vom übergeordneten Modul (GoveeIO) abrufen
        $devices = $this->FetchDevicesFromParent();

        if ($devices) {
            $deviceList = [];
            foreach ($devices as $device) {
                // Prüfen, ob das Gerät bereits als Instanz existiert
                $existingInstanceID = $this->GetInstanceIDByDeviceID($device['device']);

                // Wenn die Instanz existiert, zeige die ID, andernfalls füge den Erzeugen-Button hinzu
                $deviceList[] = [
                    'DeviceName' => $device['deviceName'],
                    'DeviceID' => $device['device'],
                    'Model' => $device['sku'],
                    'instanceID' => $existingInstanceID,
                    'create' => [
                        'moduleID' => '{8E4E6F37-5435-431E-B058-01C253C3A021}', // GoveeDevice Modul-ID
                        'name' => $device['deviceName'],
                        'location' => ['Govee', 'Devices'], // Optional: Speicherort der Instanzen
                        'configuration' => [
                            'DeviceID' => $device['device'],
                            'DeviceModel' => $device['sku']
                        ]
                    ]
                ];
            }

            // Liste mit Geräten und dem Erzeugen-Button
            $form['actions'][] = [
                'type' => 'Configurator', // Verwenden des Configurator-Typs
                'name' => 'DeviceList',
                'caption' => 'Gefundene Geräte',
                'rowCount' => 5,
                'columns' => [
                    ['caption' => 'Name', 'name' => 'DeviceName', 'width' => '200px'],
                    ['caption' => 'ID', 'name' => 'DeviceID', 'width' => '200px'],
                    ['caption' => 'Modell', 'name' => 'Model', 'width' => '200px'],
                    [
                        'caption' => 'Instanz ID',
                        'name' => 'instanceID',
                        'width' => '100px',
                        'add' => false,
                        'visible' => true
                    ]
                ],
                'values' => $deviceList
            ];
        }

        return json_encode($form);
    }

    private function FetchDevicesFromParent()
    {
        $data = [
            'DataID' => '{E2CDD4C0-3E9F-4B4E-9D92-8C1F9B6F8B8B}', // Passende DataID für GoveeIO
        ];

        $response = $this->SendDataToParent(json_encode($data));
        return json_decode($response, true);
    }

    private function GetInstanceIDByDeviceID($deviceID)
    {
        foreach (IPS_GetInstanceListByModuleID('{8E4E6F37-5435-431E-B058-01C253C3A021}') as $id) {
            if (IPS_GetProperty($id, 'DeviceID') == $deviceID) {
                return $id;
            }
        }
        return 0;
    }
}
