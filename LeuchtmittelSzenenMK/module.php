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
    $configuratorID = 41847;

    if (!IPS_InstanceExists($configuratorID)) {
        $this->SendDebug("MQTT", "Konfigurator-ID nicht gefunden", 0);
        return $topics;
    }

    $config = json_decode(IPS_GetConfiguration($configuratorID), true);
    $this->SendDebug("MQTT", "Konfigurator-Konfiguration: " . print_r($config, true), 0);

    if (!isset($config['Values'])) {
        $this->SendDebug("MQTT", "Keine Values im Konfigurator", 0);
        return $topics;
    }

    $values = json_decode($config['Values'], true);
    if (!is_array($values)) {
        $this->SendDebug("MQTT", "Values konnte nicht geparst werden", 0);
        return $topics;
    }

    foreach ($values as $entry) {
        if (isset($entry['topic']) && strpos($entry['topic'], 'Leuchtmittel') !== false) {
            $topics[] = $entry['topic'];
        }
    }

    $this->SendDebug("MQTT", "Gefundene Topics: " . print_r($topics, true), 0);
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
