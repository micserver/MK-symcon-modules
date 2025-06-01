<?php

declare(strict_types=1);

class LeuchtmittelSzenenMK extends IPSModule
{
    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyInteger('MQTTConfiguratorID', 41847);
        $this->RegisterPropertyString('DeviceTopics', '[]');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
    }

    public function GetConfigurationForm()
    {
        $options = $this->GetMQTTTopicsWithLeuchtmittel();

        $this->SendDebug("FormOptions", json_encode($options), 0);

        return json_encode([
            'elements' => [
                [
                    'type'    => 'ExpansionPanel',
                    'caption' => 'Leuchtmittel-Auswahl',
                    'items'   => [
                        [
                            'type'     => 'Select',
                            'name'     => 'DeviceTopics',
                            'caption'  => 'Verfügbare Leuchtmittel',
                            'multiple' => true,
                            'options'  => $options
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
        $configuratorID = $this->ReadPropertyInteger('MQTTConfiguratorID');
        $this->SendDebug('GetMQTTTopics', 'MQTT Konfigurator ID: ' . $configuratorID, 0);

        if (!IPS_InstanceExists($configuratorID)) {
            $this->SendDebug('GetMQTTTopics', 'Konfigurator existiert nicht', 0);
            return [];
        }

        $configurator = IPS_GetConfiguration($configuratorID);
        $this->SendDebug('MQTT Configurator Config', $configurator, 0);

        // Annahme: im Konfigurationsstring steht eine JSON-Struktur mit allen Topics
        $data = json_decode($config, true);

        if (!is_array($data)) {
            $this->SendDebug('GetMQTTTopics', 'Konfigurationsdaten konnten nicht als Array gelesen werden', 0);
            return [];
        }

        //$children = IPS_GetChildrenIDs($configuratorID);
        //$this->SendDebug('GetMQTTTopics', 'Anzahl Children: ' . count($children), 0);

        $topics = [];

        // Beispiel: je nachdem wie das JSON aufgebaut ist, 
        // musst du ggf. den Pfad zu den Topics anpassen:
        if (isset($data['Topics']) && is_array($data['Topics'])) {
            foreach ($data['Topics'] as $topic) {
                // Prüfen, ob 'Leuchtmittel' im Topic vorkommt
                if (stripos($topic, 'Leuchtmittel') !== false) {
                    $topics[] = [
                        'caption' => $topic,
                        'value'   => $topic
                    ];
                }
            }
        } else {
            $this->SendDebug('GetMQTTTopics', 'Keine "Topics" im Konfigurationsarray gefunden', 0);
        }

        $this->SendDebug('Gefilterte Topics', json_encode($topics), 0);
        return $topics;

    }

    public function SaveScene()
    {
        $this->SendDebug('SaveScene', 'Szene speichern aufgerufen', 0);
    }

    public function ApplyScene()
    {
        $this->SendDebug('ApplyScene', 'Szene anwenden aufgerufen', 0);
    }
}
