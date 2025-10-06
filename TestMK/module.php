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
        $eventNeedsUpdate = true;
        if ($eid !== false) {
            $eventInfo = IPS_GetEvent($eid);
            // Prüfen, ob das Event bereits auf die gewünschte Variable zeigt
            if ($eventVarID > 0 && $eventInfo['TriggerValue'] == $eventVarID && $eventInfo['TriggerType'] == 1) {
                $eventNeedsUpdate = false;
                $this->SendDebug("ApplyChanges", "Event existiert und zeigt bereits auf Variable $eventVarID (EventID=$eid)", 0);
                // Event aktivieren und Script aktualisieren (falls nötig)
                IPS_SetEventActive($eid, true);
                $eventScript = 'IPS_LogMessage("TestMK", "Event ausgelöst für Variable ' . $eventVarID . '");';
                IPS_SetEventScript($eid, $eventScript);
            } else {
                // Event löschen, wenn Variable geändert oder auf 0 gesetzt
                IPS_DeleteEvent($eid);
                $this->SendDebug("ApplyChanges", "Altes Event gelöscht: EventID=$eid", 0);
            }
        }
        // Neues Event nur anlegen, wenn Variable > 0 und kein passendes Event existiert
        if ($eventVarID > 0 && $eventNeedsUpdate) {
            $eid = IPS_CreateEvent(0); // 0 = Trigger
            IPS_SetParent($eid, $this->InstanceID);
            IPS_SetName($eid, "TestMKEvent");
            IPS_SetEventTrigger($eid, 1, $eventVarID);
            IPS_SetEventActive($eid, true);
            $eventScript = 'IPS_LogMessage("TestMK", "Event ausgelöst für Variable ' . $eventVarID . '");';
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
        IPS_LogMessage("TestMK", "Manueller Test: EventVariableID=$eventVarID, EventID=" . ($eid !== false ? $eid : 'nicht vorhanden'));
    }
}

// Wrapper-Funktion für Button
function TestMK_TestEvent($InstanceID) {
    IPS_RequestAction($InstanceID, "TestEvent", "");
}
