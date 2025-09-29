<?php
declare(strict_types=1);

class LeuchtmittelSzenenMK extends IPSModule
{
    public function Create()
    {
        parent::Create();

        // Bereich-Name speichern
        $this->RegisterPropertyString('BereichName', '');

        // Szenen als JSON-Array speichern
        $this->RegisterPropertyString('Szenen', '[]');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // Alte Variablen entfernen
        foreach (IPS_GetChildrenIDs($this->InstanceID) as $childID) {
            $obj = IPS_GetObject($childID);
            if ($obj['ObjectType'] === OBJECTTYPE_VARIABLE) {
                IPS_DeleteVariable($childID);
            }
        }

        $this->InitializeScenes();
    }

    private function InitializeScenes(): void
    {
        $bereichName = $this->ReadPropertyString('BereichName');
        $scenes = json_decode($this->ReadPropertyString('Szenen'), true);

        if (!is_array($scenes)) return;

        $updated = false;
        $addedScenes = [];

        foreach ($scenes as &$scene) {
            $sceneID = $scene['SzeneID'] ?? 0;

            // Alias automatisch setzen, falls leer
            if (empty($scene['Alias'])) {
                $scene['Alias'] = 'Szene ' . $sceneID;
                $updated = true;
            }

            $alias = $scene['Alias'];

            if (!in_array($sceneID, $addedScenes)) {
                $ident = 'Scene_' . $sceneID;
                $name = trim($bereichName . ' – ' . $alias);

                $this->RegisterVariableBoolean($ident, $name, '~Switch', 0);
                $this->EnableAction($ident);

                $addedScenes[] = $sceneID;
            }
        }

        // Wenn Aliase ergänzt wurden, Property aktualisieren
        if ($updated) {
            $this->WritePropertyString('Szenen', json_encode($scenes));
        }
    }

    public function RequestAction($Ident, $Value)
    {
        if (str_starts_with($Ident, 'Scene_') && $Value) {
            $this->RunScene($Ident);
            $this->SetValue($Ident, false); // Schalter zurücksetzen
        }
    }

    private function RunScene(string $Ident): void
    {
        $scenes = json_decode($this->ReadPropertyString('Szenen'), true);
        if (!is_array($scenes)) return;

        $sceneID = (int) str_replace('Scene_', '', $Ident);

        foreach ($scenes as $scene) {
            if (($scene['SzeneID'] ?? 0) !== $sceneID) continue;
            if (!isset($scene['Actions']) || !is_array($scene['Actions'])) continue;

            foreach ($scene['Actions'] as $action) {
                $varID = $action['InstanceID'] ?? 0;
                if (!IPS_VariableExists($varID)) continue;

                if (isset($action['Status'])) {
                    RequestAction($varID, (bool)$action['Status']);
                }
                if (isset($action['Brightness'])) {
                    RequestAction($varID, (int)$action['Brightness']);
                }
                if (isset($action['ColorTemp'])) {
                    RequestAction($varID, (int)$action['ColorTemp']); // mired
                }
            }
        }
    }
}
