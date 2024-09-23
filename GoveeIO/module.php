<?php

declare(strict_types=1);

class GoveeIO extends IPSModule
{
    public function Create()
    {
        parent::Create();

        // Eigenschaft für den API-Schlüssel registrieren
        $this->RegisterPropertyString('APIKey', '');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
    }

    // Methode, um den API-Schlüssel für untergeordnete Module bereitzustellen
    public function GetAPIKey()
    {
        return $this->ReadPropertyString('APIKey');
    }

    // Methode, um Daten von untergeordneten Modulen zu empfangen
    public function ForwardData($JSONString)
    {
        $data = json_decode($JSONString, true);

        // Prüfen, ob die DataID übereinstimmt
        if ($data['DataID'] !== '{e2cdd4c0-3e9f-4b4e-9d92-8c1f9b6f8b8b}') {
            $this->SendDebug('ForwardData', 'Ungültige DataID erhalten', 0);
            return json_encode(['success' => false, 'error' => 'Invalid DataID']);
        }

        // API-Aufruf hier implementieren
        $result = $this->SendAPIRequest($data);

        // Ergebnis zurückgeben
        return json_encode($result);
    }

    private function SendAPIRequest($data)
    {
        // Implementieren Sie hier die Kommunikation mit der Govee API
        $apiKey = $this->ReadPropertyString('APIKey');
        $deviceId = $data['DeviceID'];
        $deviceModel = $data['DeviceModel'];
        $capability = $data['Capability'];

        // Generieren einer eindeutigen requestId
        $requestId = uniqid();

        // Aufbau des Anfragekörpers
        $requestBody = [
            'requestId' => $requestId,
            'payload' => [
                'device' => $deviceId,
                'sku' => $deviceModel,
                'capability' => $capability,
            ],
        ];

        // JSON kodieren
        $jsonBody = json_encode($requestBody);

        // cURL-Anfrage vorbereiten
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://openapi.api.govee.com/router/api/v1/device/control');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Govee-API-Key: ' . $apiKey,
            'Content-Type: application/json',
        ]);

        // Anfrage ausführen
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = 'cURL Fehler: ' . curl_error($ch);
            $this->SendDebug('SendAPIRequest', $error, 0);
            curl_close($ch);
            return ['success' => false, 'error' => $error];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $dataResponse = json_decode($response, true);

        if ($httpCode != 200 || (isset($dataResponse['status']) && $dataResponse['status'] != 200)) {
            $error = isset($dataResponse['message']) ? $dataResponse['message'] : 'Unbekannter Fehler';
            $this->SendDebug('SendAPIRequest', 'Fehler beim Senden des Befehls: ' . $error, 0);
            return ['success' => false, 'error' => $error];
        }

        return ['success' => true];
    }
}
