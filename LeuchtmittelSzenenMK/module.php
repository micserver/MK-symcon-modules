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
        $config = @IPS_GetConfigurationForm(41847);
        $this->SendDebug("RAW", $config, 0);

        $options = [];

        if ($config !== false) {
            $data = json_decode($config, true);
            $this->SendDebug("Decoded Data", print_r($data, true), 0);

        // Zusätzliche Prüfung und Debug-Ausgabe
        if (isset($data['values'])) {
            $this->SendDebug('Check isset($data[values])', 'JA', 0);
        } else {
            $this->SendDebug('Check isset($data[values])', 'NEIN', 0);
        }

        if (is_array($data['values'])) {
            $this->SendDebug('Check is_array($data[values])', 'JA', 0);
        } else {
            $this->SendDebug('Check is_array($data[values])', 'NEIN', 0);
        }

        if (isset($data['values']) && is_array($data['values'])) {
            $this->SendDebug('MQTT Configurator', 'values count: ' . count($data['values']), 0);
            foreach ($data['values'] as $entry) {
                if (isset($entry['Topic']) && str_contains($entry['Topic'], 'Leuchtmittel')) {
                    $options[] = [
                        'caption' => $entry['Topic'],
                        'value'   => $entry['Topic']
                    ];
                    $this->SendDebug('Gefiltertes Topic', $entry['Topic'], 0);
                }
            }
        }
    } else {
        $this->SendDebug("MQTT Config", "Keine Daten", 0);
    }

    $this->SendDebug("FormOptions", json_encode($options), 0);

    return json_encode([
        'elements' => [
            [
                'type'    => 'Select',
                'name'    => 'DeviceTopics',
                'caption' => 'Leuchtmittel-Auswahl',
                'options' => $options
            ]
        ],
        'actions' => []
    ]);
}
}