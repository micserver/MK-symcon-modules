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

        $options = array_map(function ($topic) {
            return [
                'caption' => $topic,
                'value'   => $topic
            ];
        }, $topics);

        $form = json_decode(file_get_contents(__DIR__ . "/form.json"), true);
        $form['elements'][0]['items'][0]['options'] = $options;
        return json_encode($form);
    }

    private function GetMQTTTopicsWithLeuchtmittel(): array
    {
        $topics = [];
        $rootID = 41847; // MQTT-Konfigurator-ID
        IPS_LogMessage("SzenenMK", "ID: " . $rootID);
        $this->CollectTopicsRecursive($rootID, $topics);

        return array_filter($topics, function ($topic) {
            return stripos($topic, 'Leuchtmittel') !== false;
        });
    }

    private function CollectTopicsRecursive(int $parentID, array &$topics)
    {
        foreach (IPS_GetChildrenIDs($parentID) as $childID) {
            $object = IPS_GetObject($childID);
            print_r($object);
            if (isset($object['ObjectName']) && $object['ObjectName'] !== '') {
                $fullPath = $this->GetFullTopicPath($childID);
                if ($fullPath !== '') {
                    $topics[] = $fullPath;
                }
            }
            $this->CollectTopicsRecursive($childID, $topics);
        }
    }

    private function GetFullTopicPath(int $objectID): string
    {
        $parts = [];
        while ($objectID != 0) {
            $object = IPS_GetObject($objectID);
            if (trim($object['ObjectName']) === '') {
                break;
            }
            array_unshift($parts, $object['ObjectName']);
            $objectID = $object['ParentID'];
        }
        return implode('/', $parts);
    }
}
