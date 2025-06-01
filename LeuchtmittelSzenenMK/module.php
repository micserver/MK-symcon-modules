<?php

declare(strict_types=1);

class LeuchtmittelSzenenMK extends IPSModule
{
    public function Create()
    {
        // MQTT-Konfigurator-ID hier eintragen
        $this->RegisterPropertyInteger("MQTTConfiguratorID", 41847);
        parent::Create();
    }

    public function GetConfigurationForm()
    {
        $values = $this->GetMQTTLeuchtmittelTopics();

        return json_encode([
            "elements" => [
                [
                    "type" => "ExpansionPanel",
                    "caption" => "Leuchtmittel-Auswahl",
                    "items" => [
                        [
                            "type" => "Select",
                            "name" => "DeviceTopic",
                            "caption" => "Verfügbare Leuchtmittel",
                            "options" => $values
                        ]
                    ]
                ]
            ],
            "actions" => []
        ]);
    }

    private function GetMQTTLeuchtmittelTopics(): array
    {
        $configuratorID = $this->ReadPropertyInteger("MQTTConfiguratorID");
        if (!IPS_InstanceExists($configuratorID)) {
            IPS_LogMessage("LeuchtmittelSzenenMK", "MQTT-Konfigurator mit ID $configuratorID nicht gefunden.");
            return [];
        }

        $topics = [];
        $configuratorData = json_decode(IPS_GetConfigurationForm($configuratorID), true);

        if (!isset($configuratorData['values']) || !is_array($configuratorData['values'])) {
            IPS_LogMessage("LeuchtmittelSzenenMK", "Keine gültigen values im MQTT-Konfigurator.");
            return [];
        }

        foreach ($configuratorData['values'] as $entry) {
            if (isset($entry['Topic']) && stripos($entry['Topic'], 'Leuchtmittel') !== false) {
                $topics[] = [
                    "caption" => $entry['Topic'],
                    "value"   => $entry['Topic']
                ];
            }
        }

        return $topics;
    }
}
