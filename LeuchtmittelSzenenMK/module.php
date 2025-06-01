<?php

declare(strict_types=1);

class LeuchtmittelSzenenMK extends IPSModule
{
    public function Create()
    {
        // Diese Zeile nicht entfernen!
        parent::Create();

        // Beispiel: eine Property zum Speichern von gewählten Geräten
        $this->RegisterPropertyString('DeviceTopics', '[]');
    }

    public function ApplyChanges()
    {
        // Diese Zeile nicht entfernen!
        parent::ApplyChanges();
    }

    public function GetConfigurationForm()
    {
        $topics = $this->GetMQTTTopicsWithLeuchtmittel();
        $options = [];

        foreach ($topics as $topic) {
            $options[] = [
                'caption' => $topic,
                'value'   => $topic
            ];
        }

        return json_encode([
            'elements' => [
                [
                    'type'    => 'ExpansionPanel',
                    'caption' => 'Leuchtmittel-Auswahl',
                    'items'   => [
                        [
                            'type'    => 'Select',
                            'name'    => 'DeviceTopics',
                            'caption' => 'Verfügbare Leuchtmittel',
                            'options' => $options,
                            'multiple' => true
                        ]
                    ]
                ],
                [
                    'type'    => 'ExpansionPanel',
                    'caption' => 'Szenensteuerung',
                    'items'   => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Szene speichern',
                            'onClick' => 'SaveScene'
                        ],
                        [
                            'type'    => 'Select',
                            'name'    => 'SceneList',
                            'caption' => 'Gespeicherte Szenen',
                            'options' => []
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Szene anwenden',
                            'onClick' => 'ApplyScene'
                        ]
                    ]
                ]
            ],
            'actions' => []
        ]);
    }

    private function GetMQTTTopicsWithLeuchtmittel(): array
    {
        $topics = [];
        $configuratorID = 41847;

        $form = @IPS_GetConfigurationForm($configuratorID);
        if ($form === false) {
            $this->SendDebug("MQTT", "Fehler beim Laden der Konfigurationsform", 0);
            return $topics;
        }

        $data = json_decode($form, true);
        if (!isset($data['values']) || !is_array($data['values'])) {
            $this->SendDebug("MQTT", "Keine gültigen Values im Konfigurator gefunden", 0);
            return $topics;
        }

        foreach ($data['values'] as $entry) {
            if (isset($entry['Topic']) && stripos($entry['Topic'], 'Leuchtmittel') !== false) {
                $topics[] = $entry['Topic'];
            }
        }

        $this->SendDebug("MQTT", "Gefundene Leuchtmittel-Themen: " . print_r($topics, true), 0);
        return $topics;
    }

    public function SaveScene()
    {
        // Szenenspeicherung implementieren
    }

    public function ApplyScene()
    {
        // Szenenanwendung implementieren
    }
}
