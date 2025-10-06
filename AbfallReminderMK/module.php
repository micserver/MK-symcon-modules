<?php
declare(strict_types=1);

class AbfallReminderMK extends IPSModule
{
    public function Create()
    {
        $this->EnableAction("FetchMails");
        // Properties immer zuerst registrieren!
        $this->RegisterPropertyInteger("IMAP_InstanzID", 0);
        $this->RegisterPropertyInteger("CacheSize", 20);
        $this->RegisterPropertyString("MailAbsender", json_encode([
            ["sender" => "noreply@awido.de"],
            ["sender" => "noreply@cubefour.de"]
        ]));
        $this->RegisterPropertyString("Abfallarten", json_encode([
            ["art" => "Biomüll"],
            ["art" => "Papiertonne"],
            ["art" => "Restmüll"],
            ["art" => "Gelber Sack"]
        ]));
        $this->RegisterPropertyString("OrtFilter", "Krombach");
        $this->RegisterPropertyString("Testdatum", "2025-10-01");
        $this->RegisterPropertyInteger("FetchInterval", 3600);
        $this->RegisterPropertyInteger("EventVariableID", 0);

        parent::Create();

        // Fehlerüberwachungs-Variablen
        $this->RegisterVariableInteger("MailTimeoutCounter", "MailTimeoutCounter", "", 40);
        $this->RegisterVariableString("MailTimeoutStatus", "MailTimeoutStatus", "", 41);

        // Variablen
        $this->RegisterVariableString("AnzeigenText", "AnzeigenText", "~TextBox", 10);
        $this->RegisterVariableString("AnzeigenHTML", "Anzeige (HTML)", "~HTMLBox", 30);
        $this->RegisterVariableBoolean("Aktiv", "Aktiv", "~Switch", 20);
        // Timer zum automatischen aufrufen
        $this->RegisterTimer("ARMK_FetchTimer", 0, 'ARMK_FetchMails($_IPS["TARGET"]);');

        // Event für konfigurierbare Variable (erst nach parent::Create())
        $eventVarID = $this->ReadPropertyInteger("EventVariableID");
        if ($eventVarID > 0) {
            $eid = @IPS_GetObjectIDByIdent("IMAPLastMessageEvent", $this->InstanceID);
            if ($eid === false) {
                $eid = IPS_CreateEvent(0); // 0 = Trigger
                IPS_SetEventTrigger($eid, 1, $eventVarID); // 1 = bei Variablenänderung
                IPS_SetParent($eid, $this->InstanceID);
                IPS_SetName($eid, "IMAPLastMessageEvent");
                IPS_SetEventActive($eid, true);
                IPS_SetEventScript($eid, 'AbfallReminderMK_ARMK_FetchMails(' . $this->InstanceID . ');');
            } else {
                IPS_SetEventTrigger($eid, 1, $eventVarID);
                IPS_SetEventActive($eid, true);
            }
        }

        // Variablen
        $this->RegisterVariableString("AnzeigenText", "AnzeigenText", "~TextBox", 10);
        $this->RegisterVariableString("AnzeigenHTML", "Anzeige (HTML)", "~HTMLBox", 30);
        $this->RegisterVariableBoolean("Aktiv", "Aktiv", "~Switch", 20);
    }
    

    public function ApplyChanges()
    {
        // Event bei Variablenänderung robust verwalten
        $eventVarID = $this->ReadPropertyInteger("EventVariableID");
        // Alle Events mit Name oder Ident "IMAPLastMessageEvent" unterhalb der Instanz löschen
        $children = IPS_GetChildrenIDs($this->InstanceID);
        foreach ($children as $childID) {
            if (IPS_GetObject($childID)['ObjectType'] == 4) { // 4 = Event
                $obj = IPS_GetObject($childID);
                if ($obj['ObjectName'] == "IMAPLastMessageEvent" || (isset($obj['ObjectIdent']) && $obj['ObjectIdent'] == "IMAPLastMessageEvent")) {
                    IPS_DeleteEvent($childID);
                    $this->SendDebug("ApplyChanges", "Altes Event gelöscht: EventID=$childID", 0);
                }
            }
        }
        // Neues Event nur anlegen, wenn Variable > 0
        if ($eventVarID > 0) {
            $eid = IPS_CreateEvent(0); // 0 = Trigger
            IPS_SetParent($eid, $this->InstanceID);
            IPS_SetName($eid, "IMAPLastMessageEvent");
            IPS_SetIdent($eid, "IMAPLastMessageEvent");
            IPS_SetEventTrigger($eid, 1, $eventVarID);
            IPS_SetEventActive($eid, true);
            // Event-Script ruft FetchMails per RequestAction auf
            $eventScript = 'IPS_RequestAction(' . $this->InstanceID . ', "FetchMails", "");';
            IPS_SetEventScript($eid, $eventScript);
            $this->SendDebug("ApplyChanges", "Neues Event angelegt: EventID=$eid für Variable $eventVarID", 0);
        }
        parent::ApplyChanges();
        // Aktion für manuelles ARMKufen wird über form.json und RequestAction behandelt
        $interval = $this->ReadPropertyInteger("FetchInterval");
        $this->SetTimerInterval("ARMK_FetchTimer", $interval * 1000);
    }

    public function RequestAction($Ident, $Value)
    {
        if ($Ident == "FetchMails") {
            $this->FetchMails();
        }
    }

    public function FetchMails()
    {
        // --- Fehlerbehandlung IMAP ---
        $e = error_get_last();
        if (!empty($e) && array_key_exists('message', $e)) {
            $Mail_Timeout_Counter = GetValue($this->GetIDForIdent("MailTimeoutCounter"));
            if ($Mail_Timeout_Counter > 3) {
                SetValue($this->GetIDForIdent("MailTimeoutStatus"), "Mail IMAP Timeout");
                $this->LogMessage("Abfall email prüfen  => Timeout Counter > 3 !", KL_ERROR);
                return;
            }
            SetValue($this->GetIDForIdent("MailTimeoutCounter"), $Mail_Timeout_Counter + 1);
            return;
        } else {
            SetValue($this->GetIDForIdent("MailTimeoutCounter"), 0);
            SetValue($this->GetIDForIdent("MailTimeoutStatus"), "OK");
        }
       
        $this->SendDebug("ARMK_FetchMails", "Start", 0);
        $imapID = $this->ReadPropertyInteger("IMAP_InstanzID");
        if ($imapID == 0 || !IPS_InstanceExists($imapID)) {
            $this->LogMessage("IMAP-Instanz nicht konfiguriert.", KL_WARNING);
            return;
        }

        $cacheSize = $this->ReadPropertyInteger("CacheSize");
        $mailSenders = json_decode($this->ReadPropertyString("MailAbsender"), true);
        $allowedSenders = array_column($mailSenders, "sender");

        $mailarray = @IMAP_GetCachedMails($imapID);
        if ($mailarray === false || count($mailarray) == 0) {
            $this->LogMessage("Keine Mails gefunden.", KL_WARNING);
            return;
        }

        $anzeige = "";
        $treffer = [];

        // Dynamischen Regex für Abfallarten bauen
        $abfallarten = json_decode($this->ReadPropertyString("Abfallarten"), true);
        $arten = array_map(function($a) { return preg_quote($a['art'], '/'); }, $abfallarten);
        $artenRegex = implode('|', $arten);

        for ($i = 0; $i < count($mailarray); $i++) {
            $sender = $mailarray[$i]['SenderAddress'] ?? '';
            if (!in_array($sender, $allowedSenders)) {
                continue;
            }
            $mailData = @IMAP_GetMailEx($imapID, $mailarray[$i]['UID']);
            if (!$mailData || !isset($mailData['Text'])) {
                continue;
            }
            $text = $mailData['Text'];
            $decoded = $this->Base64DecodeIfNeeded($text);
            if ($decoded) {
                $text = $decoded;
            }
            $text = strip_tags($text);
            $lines = explode("\n", $text);
            $this->SendDebug("Mailtext", $text, 0);
            $this->SendDebug("Lines", print_r($lines, true), 0);
            for ($k = 0; $k < count($lines); $k++) {
                $line = trim($lines[$k]);
                if (preg_match('/^(' . $artenRegex . ')\b/i', $line, $m)) {
                    for ($j = $k + 1; $j <= $k + 3 && $j < count($lines); $j++) {
                        if (preg_match('/(\d{2}\.\d{2}\.\d{4})/', $lines[$j], $d)) {
                            $treffer[] = [
                                'art' => $m[1],
                                'datum' => $d[1]
                            ];
                            $this->SendDebug("Treffer gefunden", print_r($treffer[count($treffer)-1], true), 0);
                            break;
                        }
                    }
                }
            }
        }

        // --- Ausgabe / Speicherung ---
        $gueltigeTreffer = array_filter($treffer, function($t) {
            return $this->datumpruefen($t['datum']);
        });

        if (count($gueltigeTreffer) > 0) {
            $anzeige = "";
            $html = '<div style="font-family:Roboto,Arial,sans-serif; font-size:22px; font-weight:bold; color:#000; padding:8px;">';
            $html .= '<table style="width:100%; border-collapse:collapse; color:#000; font-size:22px; font-family:Roboto,Arial,sans-serif; font-weight:bold;">';
            foreach ($gueltigeTreffer as $t) {
                $kurzdatum = '';
                if (preg_match('/^(\d{2})\.(\d{2})\.\d{4}$/', $t['datum'], $dm)) {
                    $kurzdatum = $dm[1] . '.' . $dm[2] . '.';
                } else {
                    $kurzdatum = $t['datum'];
                }
                $anzeige .= "{$t['art']}\t{$kurzdatum}\n";
                $html .= '<tr>';
                $html .= '<td style="padding:4px; font-family:Roboto,Arial,sans-serif; font-size:22px; font-weight:bold; color:#000;">' . htmlspecialchars($t['art']) . '</td>';
                $html .= '<td style="padding:4px; font-family:Roboto,Arial,sans-serif; font-size:22px; font-weight:bold; color:#000; text-align:right;">' . htmlspecialchars($kurzdatum) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table></div>';
            SetValue($this->GetIDForIdent("AnzeigenText"), $anzeige);
            SetValue($this->GetIDForIdent("AnzeigenHTML"), $html);
            SetValue($this->GetIDForIdent("Aktiv"), true);
            $this->SendDebug("Treffer", $anzeige, 0);
        } else {
            SetValue($this->GetIDForIdent("AnzeigenText"), "");
            SetValue($this->GetIDForIdent("AnzeigenHTML"), "");
            SetValue($this->GetIDForIdent("Aktiv"), false);
            $this->SendDebug("Treffer", "Keine gefunden.", 0);
        }
    }

    private function Base64DecodeIfNeeded(string $text): ?string
    {
        $text_clean = str_replace(["\r", "\n"], '', $text);

        if (preg_match('/^[A-Za-z0-9+\/=]+$/', $text_clean) && strlen($text_clean) % 4 == 0) {
            $decoded = base64_decode($text_clean, true);
            if ($decoded !== false) {
                $decoded2 = base64_decode($decoded, true);
                if ($decoded2 !== false) {
                    return $decoded2;
                }
                return $decoded;
            }
        }
        return null;
    }
    
    private function datumpruefen(string $datum): bool
    {
        $testdate = $this->ReadPropertyString("Testdatum");
        if ($testdate != "") {
            $heute = strtotime(date('Y-m-d', strtotime($testdate)));
        } else {
            $heute = strtotime(date('Y-m-d'));
        }

        $timestamp = strtotime(date('Y-m-d', strtotime($datum)));
        return ($timestamp >= $heute); // nur heute oder Zukunft
    }

}
// Wrapper-Funktion für Timer (muss  außerhalb der Klasse stehen!)
function AbfallReminderMK_ARMK_FetchMails($InstanceID) {
    IPS_RequestAction($InstanceID, "FetchMails", "");
}

