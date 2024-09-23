<?php

declare(strict_types=1);

class GoveeIO extends IPSModule
{
    public function Create()
    {
        // Diese Zeile nicht löschen
        parent::Create();

        // Eigenschaft für den API-Schlüssel registrieren
        $this->RegisterPropertyString('APIKey', '');
    }

    public function ApplyChanges()
    {
        // Diese Zeile nicht löschen
        parent::ApplyChanges();

        // API-Schlüssel aus den Moduleigenschaften lesen
        $apiKey = $this->ReadPropertyString('APIKey');

        // Überprüfen, ob der API-Schlüssel gesetzt ist
        if ($apiKey == '') {
            $this->LogMessage('API-Schlüssel ist nicht gesetzt!', KL_WARNING);
            return;
        }

        // Log-Meldung zur Überprüfung
        $this->LogMessage("GoveeIO: API-Schlüssel gesetzt auf: " . $apiKey, KL_MESSAGE);
    }

    // Methode, um den API-Schlüssel für untergeordnete Module bereitzustellen
    public function GetAPIKey()
    {
        return $this->ReadPropertyString('APIKey');
    }

    // Methode, um Daten von untergeordneten Modulen zu empfangen
    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString, true);

        // Prüfen, ob die DataID übereinstimmt
        if ($data['DataID'] !== '{E2CDD4C0-3E9F-4B4E-9D92-8C1F9B6F8B8B}') {
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

    // Konfigurationsformular bereitstellen
    public function GetConfigurationForm()
    {
        return file_get_contents(__DIR__ . '/form.json');
    }
}
