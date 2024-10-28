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
    public function ForwardData($JSONString)
    {
        $this->SendDebug('Govee IO', $JSONString, 0);

        // JSON-Daten in ein assoziatives Array dekodieren
        $data = json_decode($JSONString, true);

        // Überprüfen, ob die DataID übereinstimmt
        if ($data['DataID'] === '{5ABD644C-3C2F-34C7-9B45-68CED2830B32}') {
            // Debugging: Ausgabe der empfangenen Daten
            $this->SendDebug('ReceiveData', 'Empfangene Daten: ' . print_r($data, true), 0);

            // Verarbeiten der empfangenen Daten
            $result = $this->SendAPIRequest($data);

            // Rückgabewert im JSON-Format codieren und zurücksenden
            return json_encode($result);
        } 
        else if ($data['DataID'] === '{E2CDD4C0-3E9F-4B4E-9D92-8C1F9B6F8B8B}') {
            // FetchDevices wird aufgerufen, um die Geräteliste abzurufen
            $devices = $this->FetchDevices();
    
            // Die Geräte als JSON-codierte Antwort zurückgeben
            return json_encode($devices);
        } else {
            // Debugging: Fehlermeldung bei falscher DataID
            $this->SendDebug('ReceiveData', 'Fehler: Ungültige DataID empfangen', 0);
            return json_encode(['success' => false, 'error' => 'Ungültige DataID empfangen']);
        }
    }


    private function SendAPIRequest($data)
    {
        // Implementieren Sie hier die Kommunikation mit der Govee API
        $apiKey = $this->ReadPropertyString('APIKey');
        $deviceId = $data['DeviceID'];
        $deviceModel = $data['DeviceModel'];
        $capabilities = $data['Capability'];

        // Sicherstellen, dass $capabilities ein Array ist
        if (!$this->isArrayOfAssociativeArrays($capabilities)) {
            $capabilities = [$capabilities]; // In ein Array "verpacken", falls es keine Liste ist
        }

        $reqId = 0;

        foreach ($capabilities as $capability) {
            $reqId++;
            $requestId = (string) ($reqId); // Hochzählende requestId pro Eintrag

            // Aufbau des Anfragekörpers für diese Capability
            $requestBody = [
                'requestId' => $requestId,
                'payload' => [
                    'device' => $deviceId,
                    'sku' => $deviceModel,
                    'capability' => $capability,
                ],
            ];

            $this->SendDebug('Request body', json_encode($requestBody), 0);
        
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

            // Anfrage ausführen und Antwort verarbeiten
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error = 'cURL Fehler: ' . curl_error($ch);
                $this->SendDebug('SendAPIRequest', $error, 0);
                curl_close($ch);
                return ['success' => false, 'error' => $error];
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $dataResponse = json_decode($response, true);
            curl_close($ch);

            if ($httpCode != 200 || (isset($dataResponse['status']) && $dataResponse['status'] != 200)) {
                $error = isset($dataResponse['message']) ? $dataResponse['message'] : 'Unbekannter Fehler';
                $this->SendDebug('SendAPIRequest', 'Fehler beim Senden des Befehls: ' . $error, 0);
                return ['success' => false, 'error' => $error];
            }
        }

        // Wenn alle Anfragen erfolgreich waren
        return ['success' => true];
    }

    private function isArrayOfAssociativeArrays($data)
    {
        // Prüfen, ob $data ein Array ist
        if (!is_array($data)) {
            return false;
        }

        // Prüfen, ob jedes Element im Array ein assoziatives Array ist
        foreach ($data as $item) {
            if (!is_array($item) || array_keys($item) === range(0, count($item) - 1)) {
                // Wenn $item kein Array oder ein numerisch indiziertes Array ist, return false
                return false;
            }
        }

        return true; // Es handelt sich um ein Array von assoziativen Arrays
    }

    public function FetchDevices()
    {
        $apiKey = $this->ReadPropertyString('APIKey');
    
        if (empty($apiKey)) {
            $this->SendDebug('FetchDevices', 'API-Key ist nicht gesetzt', 0);
            return [];
        }
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://openapi.api.govee.com/router/api/v1/user/devices');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Govee-API-Key: ' . $apiKey,
            'Content-Type: application/json',
        ]);
    
        $response = curl_exec($ch);
    
        if (curl_errno($ch)) {
            $error = 'cURL Fehler: ' . curl_error($ch);
            $this->SendDebug('FetchDevices', $error, 0);
            curl_close($ch);
            return [];
        }
    
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    
        if ($httpCode != 200) {
            $this->SendDebug('FetchDevices', 'HTTP-Fehlercode: ' . $httpCode, 0);
            return [];
        }
    
        $data = json_decode($response, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->SendDebug('FetchDevices', 'JSON-Dekodierungsfehler: ' . json_last_error_msg(), 0);
            return [];
        }
    
        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        } else {
            $this->SendDebug('FetchDevices', 'Keine Geräte gefunden oder ungültiges Format', 0);
            return [];
        }
    }
   

    // Konfigurationsformular bereitstellen
    public function GetConfigurationForm()
    {
        return file_get_contents(__DIR__ . '/form.json');
    }
}
