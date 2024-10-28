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

        $devices = $this->FetchDevicesFromParent();

        if ($devices) {
            $deviceList = [];
            foreach ($devices as $device) {
                $deviceList[] = [
                    'DeviceName' => $device['deviceName'],
                    'DeviceID' => $device['device'],
                    'Model' => $device['sku'],
                    'Create' => [
                        'moduleID' => '{8E4E6F37-5435-431E-B058-01C253C3A021}', // GoveeDevice Modul-ID
                        'configuration' => [
                            'DeviceID' => $device['device'],
                            'DeviceModel' => $device['sku']
                        ]
                    ]
                ];
            }

            $form['actions'][] = [
                'type' => 'List',
                'name' => 'DeviceList',
                'caption' => 'Gefundene Geräte',
                'rowCount' => 5,
                'columns' => [
                    ['caption' => 'Name', 'name' => 'DeviceName', 'width' => '200px'],
                    ['caption' => 'ID', 'name' => 'DeviceID', 'width' => '200px'],
                    ['caption' => 'Modell', 'name' => 'Model', 'width' => '200px'],
                    ['caption' => 'Erzeugen', 'name' => 'Create', 'width' => '100px', 'add' => true]
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
}
