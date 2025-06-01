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

        $children = IPS_GetChildrenIDs($configuratorID);
        $this->SendDebug('GetMQTTTopics', 'Anzahl Children: ' . count($children), 0);

        $topics = [];

        foreach ($children as $id) {
            if (!IPS_InstanceExists($id)) {
                continue;
            }

            $instance = IPS_GetInstance($id);
            if (!isset($instance['ModuleInfo']['ModuleID']) || $instance['ModuleInfo']['ModuleID'] !== '{018EF6B5-AB94-40C6-AA53-46943E824ACF}') {
                continue;
            }

            $topic = @IPS_GetProperty($id, 'Topic');
            if ($topic === false || $topic === null || $topic === '') {
                continue;
            }

            $this->SendDebug('Topic gefunden', $topic, 0);

            if (stripos($topic, 'Leuchtmittel') !== false) {
                $topics[] = [
                    'caption' => $topic,
                    'value'   => $topic
                ];
            }
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
