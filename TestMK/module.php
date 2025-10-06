<?php
declare(strict_types=1);

class TestMK extends IPSModule
{
    public function Create()
    {
    
    
    $this->RegisterPropertyInteger("EventVariableID", 0);
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
        $eid = @IPS_GetObjectIDByIdent("TestMKEvent", $this->InstanceID);
        if ($eid !== false && $eventVarID > 0) {
            IPS_SetEventTrigger($eid, 1, $eventVarID);
            IPS_SetEventActive($eid, true);
        } else if ($eid !== false && $eventVarID == 0) {
            IPS_SetEventActive($eid, false);
        }
        parent::ApplyChanges();
    }

    public function TestEvent()
    {
    $eventVarID = $this->ReadPropertyInteger("EventVariableID");
    $this->SendDebug("TestEvent", "EventVariableID=$eventVarID, EventID=" . ($eid !== false ? $eid : 'nicht vorhanden'), 0);
        $eid = @IPS_GetObjectIDByIdent("TestMKEvent", $this->InstanceID);
        IPS_LogMessage("TestMK", "Manueller Test: EventVariableID=$eventVarID, EventID=" . ($eid !== false ? $eid : 'nicht vorhanden'));
    }
}

// Wrapper-Funktion für Button
function TestMK_TestEvent($InstanceID) {
    IPS_RequestAction($InstanceID, "TestEvent", "");
}
