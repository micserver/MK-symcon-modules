<?php
declare(strict_types=1);

class Szenensteuerung extends IPSModule
{
    public function Create()
    {
        parent::Create();

        # ---------------- Properties ----------------
        $this->RegisterPropertyString("BereichName", "Wohnzimmer");
        $this->RegisterPropertyInteger("ParentID", 0);
        $this->RegisterPropertyInteger("UebersichtVarID", 0);

        # Leuchtmittel IDs und Parameter als JSON (Array von Arrays)
        $this->RegisterPropertyString("Leuchtmittel", json_encode([]));

        # ---------------- Statusvariablen ----------------
        $this->RegisterVariableInteger("StatusSzene", "StatusSzene", "");
        $this->RegisterVariableBoolean("UebersichtEinAus", "ÜbersichtEinAus", "");

        # Event-Handling vorbereiten (für Übersicht-Button)
        $this->ConnectParent("{E6BCE2DB-5C6F-4EE0-99E1-9B06E6F3E3B0}"); // z.B. MQTT
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->EnableAction("StatusSzene");
        $this->EnableAction("UebersichtEinAus");

        # Event auf Übersicht-Variable, wenn gesetzt
        $uebersichtID = $this->ReadPropertyInteger("UebersichtVarID");
        if ($uebersichtID > 0) {
            $this->RegisterMessage($uebersichtID, VM_UPDATE);
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        switch ($Message) {
            case VM_UPDATE:
                $wert = GetValue($SenderID);
                $this->HandleUebersichtButton((bool)$wert);
                break;
        }
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case "StatusSzene":
                $this->SetzeSzene(intval($Value));
                break;
            case "UebersichtEinAus":
                $this->HandleUebersichtButton((bool)$Value);
                break;
        }
    }

    # ---------------- Szenen setzen ----------------
    public function SetzeSzene(int $szene)
    {
        $parentID = $this->ReadPropertyInteger("ParentID");
        $leuchtmittel = json_decode($this->ReadPropertyString("Leuchtmittel"), true);

        # Alles ausschalten
        $this->AllesAusschalten();

        if ($szene === 0) return;

        # Szene setzen
        foreach ($leuchtmittel as $gruppe) {
            foreach ($gruppe['ids'] as $index => $ids) {
                foreach ($ids as $i => $id) {
                    if ($id !== "none" && IPS_VariableExists($id)) {
                        $value = $gruppe['parameters'][$i][2] ?? 0;
                        RequestAction($id, $value);
                        IPS_Sleep(50);
                    }
                }
            }
        }

        # Status setzen
        SetValue($this->GetIDForIdent("StatusSzene"), $szene);

        # Übersicht aktualisieren
        SetValue($this->GetIDForIdent("UebersichtEinAus"), true);

        # Buttonfarben setzen
        $this->ButtonfarbenSetzen($szene);
    }

    public function AllesAusschalten()
    {
        $parentID = $this->ReadPropertyInteger("ParentID");

        # alle Status-Variablen ausschalten
        foreach (IPS_GetChildrenIDs($parentID) as $childID) {
            $obj = IPS_GetObject($childID);
            $parts = explode(" ", $obj['ObjectName']);
            if (isset($parts[1], $parts[2]) && strtolower($parts[2]) === 'status') {
                $target = intval($parts[1]);
                if ($target && IPS_VariableExists($target)) {
                    RequestAction($target, false);
                    IPS_Sleep(50);
                }
            }
        }

        # Status zurücksetzen
        SetValue($this->GetIDForIdent("StatusSzene"), 0);
        SetValue($this->GetIDForIdent("UebersichtEinAus"), false);

        # Buttons zurücksetzen
        $this->ButtonfarbenSetzen(0);
    }

    public function HandleUebersichtButton(bool $wert)
    {
        $szene = $wert ? 1 : 0;
        $this->SetzeSzene($szene);
    }

    public function ButtonfarbenSetzen(int $aktivSzene = 0)
    {
        $children = IPS_GetChildrenIDs($this->InstanceID);
        foreach ($children as $childID) {
            $name = IPS_GetName($childID);
            $teile = explode(" ", $name);
            $szeneNummer = intval($teile[1] ?? 0);
            $value = ($szeneNummer === $aktivSzene) ? "00AC34" : "808080";
            SetValue($childID, $value);
        }
    }
}
?>
