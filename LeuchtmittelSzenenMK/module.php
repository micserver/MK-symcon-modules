<?php

class LeuchtmittelSzenenMK extends IPSModule
{
    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyString("DeviceTopics", "[]");
    }

    public function GetConfigurationForm()
    {
        $topics = $this->GetMQTTTopicsWithLeuchtmittel();

        // Debug-Ausgabe ins IP-Symcon Log
        IPS_LogMessage("SzenenMK", "Gefundene Topics: " . json_encode($topics));

        $tree = $this->BuildTopicTree($topics);

        $form = json_decode(file_get_contents(__DIR__ . "/form.json"), true);
        $form['elements'][0]['items'][0]['values'] = $tree;

        return json_encode($form);
    }

private function GetMQTTTopicsWithLeuchtmittel(): array
{
    $topics = [];
    $configuratorID = 41847;

    if (!IPS_InstanceExists($configuratorID)) {
        IPS_LogMessage("LeuchtmittelSzenenMK", "MQTT Konfigurator (ID 41847) nicht gefunden!");
        return [];
    }

    $configJSON = IPS_GetConfiguration($configuratorID);
    $config = json_decode($configJSON, true);

    if (!isset($config['Data'])) {
        IPS_LogMessage("LeuchtmittelSzenenMK", "Konfigurations-Daten nicht gefunden!");
        return [];
    }

    $data = json_decode($config['Data'], true);

    if (!isset($data['Values'])) {
        IPS_LogMessage("LeuchtmittelSzenenMK", "Keine Topics im MQTT Konfigurator gefunden!");
        return [];
    }

    foreach ($data['Values'] as $entry) {
        if (isset($entry['Topic']) && strpos($entry['Topic'], 'Leuchtmittel') !== false) {
            $topics[] = $entry['Topic'];
        }
    }

    return $topics;
}

    private function BuildTopicTree(array $topics): array
    {
        $tree = [];

        foreach ($topics as $topic) {
            $parts = explode('/', $topic);
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
