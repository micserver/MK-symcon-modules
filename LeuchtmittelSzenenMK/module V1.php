<?php

class LeuchtmittelSzenenMK extends IPSModule
{
    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyInteger('MQTTConfiguratorID', 0);
    }

    public function GetConfigurationForm()
    {
        $topics = $this->GetMQTTTopicsWithLeuchtmittel();
        if (!is_array($topics)) {
            $topics = [];
        }

        $tree = $this->BuildTopicTree($topics);
        if (!is_array($tree)) {
            $tree = [];
        }

        IPS_LogMessage("LeuchtmittelSzenenMK", "Gefundene Topics: " . json_encode($topics));
        IPS_LogMessage("LeuchtmittelSzenenMK", "Tree-Struktur: " . json_encode($tree));

        $form = json_decode(file_get_contents(__DIR__ . "/form.json"), true);
        $form['elements'][0]['items'][0]['values'] = $tree;

        return json_encode($form);
    }

    private function GetMQTTTopicsWithLeuchtmittel(): array
    {
        $topics = [];
        $configuratorID = $this->ReadPropertyInteger('MQTTConfiguratorID');
        if ($configuratorID <= 0 || !IPS_InstanceExists($configuratorID)) {
            IPS_LogMessage("LeuchtmittelSzenenMK", "Ungültige MQTT Configurator ID: $configuratorID");
            return [];
        }

        $configuratorData = json_decode(IPS_GetConfiguration($configuratorID), true);
        if (!isset($configuratorData['Data'])) {
            IPS_LogMessage("LeuchtmittelSzenenMK", "Keine Daten im Konfigurator gefunden");
            return [];
        }

        $entries = json_decode($configuratorData['Data'], true);
        if (!is_array($entries)) {
            IPS_LogMessage("LeuchtmittelSzenenMK", "Datenformat ungültig im Konfigurator");
            return [];
        }

        foreach ($entries as $entry) {
            if (isset($entry['Topic']) && stripos($entry['Topic'], 'Leuchtmittel') !== false) {
                $topics[] = $entry['Topic'];
            }
        }

        return $topics;
    }

    private function BuildTopicTree(array $topics): array
    {
        $tree = [];

        foreach ($topics as $topic) {
            $parts = explode('/', str_replace('zigbee2mqtt/Leuchtmittel/', '', $topic));
            $current =& $tree;

            foreach ($parts as $i => $part) {
                $existing = &$this->FindChild($current, $part);

                if (!isset($existing)) {
                    $entry = [
                        'caption' => $part,
                        'value'   => ($i === count($parts) - 1) ? $topic : null,
                        'children' => []
                    ];
                    $current[] = $entry;
                    end($current);
                    $existing = &$current[key($current)];
                }

                $current =& $existing['children'];
            }
        }

        return $tree;
    }

    private function &FindChild(array &$array, string $caption)
    {
        foreach ($array as &$entry) {
            if ($entry['caption'] === $caption) {
                return $entry;
            }
        }

        $null = null;
        return $null;
    }
}
