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
        $topics = [
            "zigbee2mqtt/Leuchtmittel/Terrasse/T0",
            "zigbee2mqtt/Leuchtmittel/Kueche/Spuele/Rechts1",
            "zigbee2mqtt/Leuchtmittel/Wohnzimmer/roteLaterne"
        ];

        $tree = $this->BuildTopicTree($topics);

        $form = json_decode(file_get_contents(__DIR__ . "/form.json"), true);
        $form['elements'][0]['items'][0]['values'] = $tree;
        return json_encode($form);
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
                        'value'   => ($i === count($parts) - 1) ? 'zigbee2mqtt/Leuchtmittel/' . implode('/', $parts) : null,
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
} und die 'form-json': {
  "elements": [
    {
      "type": "ExpansionPanel",
      "caption": "Leuchtmittel-Auswahl",
      "items": [
        {
          "type": "SelectTree",
          "name": "DeviceTopics",
          "caption": "Verfügbare Leuchtmittel",
          "multiple": true,
          "values": []
        }
      ]
    },
    {
      "type": "ExpansionPanel",
      "caption": "Szenensteuerung",
      "items": [
        {
          "type": "Button",
          "caption": "Szene speichern",
          "onClick": "SaveScene"
        },
        {
          "type": "Select",
          "name": "SceneList",
          "caption": "Gespeicherte Szenen",
          "options": []
        },
        {
          "type": "Button",
          "caption": "Szene anwenden",
          "onClick": "ApplyScene"
        }
      ]
    }
  ],
  "actions": []
} 