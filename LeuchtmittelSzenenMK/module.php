<?php

declare(strict_types=1);

class LeuchtmittelSzenenMK extends IPSModule
{
    public function Create()
    {
        // Diese Methode wird beim Erstellen der Instanz ausgeführt
        parent::Create();
    }

    public function ApplyChanges()
    {
        // Diese Methode wird aufgerufen, wenn sich Konfigurationseinstellungen ändern
        parent::ApplyChanges();
    }

    public function GetConfigurationForm()
    {
        $options = [];

        // Die ID deines MQTT-Konfigurators (muss korrekt sein)
        $mqttConfiguratorID = 41847;

        $formJson = @IPS_GetConfigurationForm($mqttConfiguratorID);
        if ($formJson === false) {
            $this->SendDebug("MQTT Konfigurator", "Keine Konfigurationsdaten gefunden", 0);
            return json_encode([
                'elements' => [],
                'actions' => []
            ]);
        }

        $configPage = json_decode($formJson, true);
        if (!isset($configPage['values']) || !is_array($configPage['values'])) {
            $this->SendDebug("MQTT Konfigurator", "Fehlerhafte Struktur in Formdaten", 0);
            return json_encode([
                'elements' => [],
                'actions' => []
            ]);
        }

        foreach ($configPage['values'] as $entry) {
            if (isset($entry['Topic']) && stripos($entry['Topic'], 'Leuchtmittel') !== false) {
                $options[] = [
                    'caption' => $entry['Topic'],
                    'value'   => $entry['Topic']
                ];
            }
        }

        $this->SendDebug("Gefundene Optionen", json_encode($options), 0);

        return json_encode([
            'elements' => [
                [
                    'type'    => 'Select',
                    'name'    => 'DeviceTopics',
                    'caption' => 'Verfügbare Leuchtmittel',
                    'options' => $options
                ]
            ],
            'actions' => []
        ]);
    }
}
