<?php
declare(strict_types=1);

class TestMK extends IPSModule
{
    public function LogEventDebug(int $VariableID)
    {
        $this->SendDebug("EventTrigger", "Event ausgelöst für Variable $VariableID", 0);
    }

    public function RequestAction($Ident, $Value)
    {
        if ($Ident == "LogEventDebug") {
            $this->LogEventDebug($Value);
        }
        if ($Ident == "TestEvent") {
            $this->TestEvent();
        }
    }

    public function Create()
    {
    $this->RegisterPropertyInteger("EventVariableID", 0);
    $this->EnableAction("LogEventDebug");
    $this->EnableAction("TestEvent");
        $this->SendDebug("Create", "EventVariableID=" . $this->ReadPropertyInteger("EventVariableID"), 0);
        parent::Create();
        // Event für konfigurierbare Variable
        $eventVarID = $this->ReadPropertyInteger("EventVariableID");
        if ($eventVarID > 0) {
            $eid = @IPS_GetObjectIDByIdent("TestMKEvent", $this->InstanceID);
            $eventScript = 'IPS_LogMessage("TestMK", "Event ausgelöst für Variable ' . $eventVarID . '");';
            if ($eid === false) {
                $eid = IPS_CreateEvent(0); // 0 = Trigger
                IPS_SetEventTrigger($eid, 1, $eventVarID); // 1 = bei Variablenänderung
                IPS_SetParent($eid, $this->InstanceID);
                IPS_SetName($eid, "TestMKEvent");
                IPS_SetEventActive($eid, true);
                IPS_SetEventScript($eid, $eventScript);
            } else {
                IPS_SetEventTrigger($eid, 1, $eventVarID);
                IPS_SetEventActive($eid, true);
                IPS_SetEventScript($eid, $eventScript);
            }
        }
    }

    public function ApplyChanges()
    {
    
    
        $eventVarID = $this->ReadPropertyInteger("EventVariableID");
        $this->SendDebug("ApplyChanges", "EventVariableID=" . $eventVarID, 0);
        // Alle Events mit Name "TestMKEvent" unterhalb der Instanz löschen
        $children = IPS_GetChildrenIDs($this->InstanceID);
        foreach ($children as $childID) {
            if (IPS_GetObject($childID)['ObjectType'] == 4) { // 4 = Event
                if (IPS_GetObject($childID)['ObjectName'] == "TestMKEvent") {
                    IPS_DeleteEvent($childID);
                    $this->SendDebug("ApplyChanges", "Doppeltes Event gelöscht: EventID=$childID", 0);
                }
            }
        }
        // Neues Event nur anlegen, wenn Variable > 0
        if ($eventVarID > 0) {
            $eid = IPS_CreateEvent(0); // 0 = Trigger
            IPS_SetParent($eid, $this->InstanceID);
            IPS_SetName($eid, "TestMKEvent");
            IPS_SetIdent($eid, "TestMKEvent");
            IPS_SetEventTrigger($eid, 1, $eventVarID);
            IPS_SetEventActive($eid, true);
            // Event-Script ruft die Debug-Funktion des Moduls per RequestAction auf
            $eventScript = 'IPS_RequestAction(' . $this->InstanceID . ', "LogEventDebug", ' . $eventVarID . ');';
            IPS_SetEventScript($eid, $eventScript);
            $this->SendDebug("ApplyChanges", "Neues Event angelegt: EventID=$eid für Variable $eventVarID", 0);
        }
        parent::ApplyChanges();
    }

    public function TestEvent()
    {
    $eventVarID = $this->ReadPropertyInteger("EventVariableID");
    $eid = @IPS_GetObjectIDByIdent("TestMKEvent", $this->InstanceID);
    $this->SendDebug("TestEvent", "EventVariableID=$eventVarID, EventID=" . ($eid !== false ? $eid : 'nicht vorhanden'), 0);
    }
}

// Wrapper-Funktion für Button
function TestMK_TestEvent($InstanceID) {
    IPS_RequestAction($InstanceID, "TestEvent", "");
}
