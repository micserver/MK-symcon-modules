<?php
declare(strict_types=1);

class AbfallReminderMK extends IPSModule
{
    public function Create()
    {
        $this->LogMessage("Modul wurde geladen!", KL_NOTIFY);
    {
        parent::Create();

        // Eigenschaften aus der form.json
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

        // Variablen
        $this->RegisterVariableString("AnzeigenText", "AnzeigenText", "~TextBox", 10);
        $this->RegisterVariableBoolean("Aktiv", "Aktiv", "~Switch", 20);

    // Timer zum automatischen aufrufen
    $this->RegisterTimer("ARMK_FetchTimer", 0, 'ARMK_FetchMails($_IPS["TARGET"]);');
    }
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
    // Aktion für manuelles ARMKufen wird über form.json und RequestAction behandelt
    $interval = $this->ReadPropertyInteger("FetchInterval");
    $this->SetTimerInterval("ARMK_FetchTimer", $interval * 1000);
    }

    public function FetchMails()
    {
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

            // --- Parsing des Klartexts ---
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
            foreach ($gueltigeTreffer as $t) {
                $anzeige .= "{$t['art']}\t=>  {$t['datum']}\n";
            }

            SetValue($this->GetIDForIdent("AnzeigenText"), $anzeige);
            SetValue($this->GetIDForIdent("Aktiv"), true);

            $this->SendDebug("Treffer", $anzeige, 0);
        } else {
            SetValue($this->GetIDForIdent("AnzeigenText"), "");
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
            $heute = strtotime($testdate);
        } else {
            $heute = time();
        }

        $timestamp = strtotime($datum);
        return ($timestamp >= $heute - 86400); // nicht älter als 1 Tag
    }
}

// Wrapper-Funktion für Timer (muss außerhalb der Klasse stehen!)
function AbfallReminderMK_ARMK_FetchMails($InstanceID) {
    IPS_RequestAction($InstanceID, "FetchMails", "");
}

