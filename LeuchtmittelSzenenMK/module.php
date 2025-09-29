<?php
declare(strict_types=1);

class LeuchtmittelSzenenMK extends IPSModule
{
    public function Create()
    {
        parent::Create();

        // Bereichsname (Instanz repräsentiert einen Bereich)
        $this->RegisterPropertyString('BereichName', '');

        // Szenen werden als JSON gespeichert
        $this->RegisterPropertyString('Scenes', '[]'); // JSON-Array der Szenen
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // Alte Variablen löschen (damit bei Konfigänderung nicht zu viele übrig bleiben)
        foreach (IPS_GetChildrenIDs($this->InstanceID) as $childID) {
            $obj = IPS_GetObject($childID);
            if ($obj['ObjectType'] === OBJECTTYPE_VARIABLE) {
                IPS_DeleteVariable($childID);
            }
        }

        // Szenen auslesen
        $scenes = json_decode($this->ReadPropertyString('Scenes'), true);

        if (is_array($scenes)) {
            foreach ($scenes as $scene) {
                $ident = 'Scene_' . md5($scene['Name']);
                $this->RegisterVariableBoolean($ident, $scene['Name'], '~Switch', 0);
                $this->EnableAction($ident);
            }
        }
    }

    public function RequestAction($Ident, $Value)
    {
        if (str_starts_with($Ident, 'Scene_') && $Value) {
            $this->RunScene($Ident);

            // Schalter nach Ausführung zurücksetzen
            $this->SetValue($Ident, false);
        }
    }

    private function RunScene(string $Ident)
    {
        $scenes = json_decode($this->ReadPropertyString('Scenes'), true);
        if (!is_array($scenes)) {
            return;
        }

        foreach ($scenes as $scene) {
            if ('Scene_' . md5($scene['Name']) === $Ident) {
                // Aktionen abarbeiten
                foreach ($scene['Actions'] as $action) {
                    $varID = $action['VariableID'];
                    $value = $action['Value'];

                    if (IPS_VariableExists($varID)) {
                        $var = IPS_GetVariable($varID);
                        $type = $var['VariableType'];

                        switch ($type) {
                            case VARIABLETYPE_BOOLEAN:
                                RequestAction($varID, (bool)$value);
                                break;
                            case VARIABLETYPE_INTEGER:
                                RequestAction($varID, (int)$value);
                                break;
                            case VARIABLETYPE_FLOAT:
                                RequestAction($varID, (float)$value);
                                break;
                            case VARIABLETYPE_STRING:
                                RequestAction($varID, (string)$value);
                                break;
                        }
                    }
                }
            }
        }
    }


}

