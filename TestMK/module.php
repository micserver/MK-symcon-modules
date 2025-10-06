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
    }

    public function ApplyChanges()
    {
        $eventVarID = $this->ReadPropertyInteger("EventVariableID");
        $this->SendDebug("ApplyChanges", "EventVariableID=" . $eventVarID, 0);
        // MessageSink: Variable für VM_UPDATE registrieren
        $this->UnregisterAllMessages();
        if ($eventVarID > 0 && IPS_VariableExists($eventVarID)) {
            $this->RegisterMessage($eventVarID, 10603); // 10603 = VM_UPDATE
            $this->SendDebug("ApplyChanges", "MessageSink für Variable $eventVarID registriert", 0);
        }
        parent::ApplyChanges();
    }

    private function UnregisterAllMessages()
    {
        $children = IPS_GetChildrenIDs($this->InstanceID);
        foreach ($children as $childID) {
            if (IPS_GetObject($childID)['ObjectType'] == 2) { // 2 = Variable
                $this->UnregisterMessage($childID, 10603);
            }
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        if ($Message == 10603) { // VM_UPDATE
            $this->SendDebug("MessageSink", "Variable $SenderID geändert: Neuer Wert=" . GetValue($SenderID), 0);
            // Hier kannst du beliebige Reaktionen einbauen
        }
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
