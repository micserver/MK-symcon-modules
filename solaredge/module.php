<?
  // Klassendefinition
	class solaredge extends IPSModule
	{
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();		

			$this->RegisterPropertyString("API_Key", "4TBYELPQL0BSZADT4AJJQ89ASHL010E2");
			$this->RegisterPropertyString("ID", "487010");
			#$this->RegisterPropertyString("API_Key", "deinAPIKey");
			#$this->RegisterPropertyString("ID", "deineID");
			
			$this->RegisterVariableString("PV_State", "PV State", "", 0);
			$this->RegisterPropertyBoolean("archive_PV_Status", false);
			$this->RegisterVariableFloat("GridPower", "GridPower", "", 1);
			$this->RegisterPropertyBoolean("archive_GridPower", false);
						
			$this->RegisterTimer("UpdateTimer", 900 * 1000, 'API_RequestInfo($_IPS[\'TARGET\']);');
		}
	
		public function ApplyChanges()
		{
			
			//Never delete this line!
			parent::ApplyChanges();
			
			//Set Logging Status
			// Get ObjectID for first archive
			$archives = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}");
			$this->SendDebug("Archive ID: ",$archives[0],0);
			$this->SendDebug("GridPower ID: ",$this->GetIDForIdent('GridPower'),0);
			$this->SendDebug("PV State: ",$this->GetIDForIdent('PV_State'),0);
			
			//Logging Status setzen
			switch ($this->ReadPropertyBoolean('archive_PV_Status')){
				case true:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('PV_State'), true);	
				break;
				case false:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('PV_State'), false);	
				break;
			}
			if ($this->ReadPropertyBoolean('archive_GridPower')){
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('GridPower'), true);
			} else {
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('GridPower'), false);
			}
			    
			$this->SendDebug("Archive PV Status: ",$this->ReadPropertyBoolean('archive_PV_Status'),0);
			$this->SendDebug("Archive GridPower: ",$this->ReadPropertyBoolean('archive_GridPower'),0);
		}
	
		/**
		* This function will be available automatically after the module is imported with the module control.
		* Using the custom prefix this function will be callable from PHP and JSON-RPC through:
		*
		* API_RequestInfo($id);
		*
		*/
		
		public function RequestInfo()
		{

			$apikey = $this->ReadPropertyString("API_Key");
			$id = $this->ReadPropertyString("ID");
			$this->SendDebug("API_Key: ",$apikey,0);
			$this->SendDebug("ID: ",$id,0);
			
			// PV-Anlage abfragen
			$content = Sys_GetURLContent("https://monitoringapi.solaredge.com/site/".$id."/currentPowerFlow?api_key=".$apikey );
			$json=json_decode($content);
			
			$PV_State=$json->siteCurrentPowerFlow->PV->status; // PV - State
			$this->SendDebug("PV State: ",$PV_State,0);
			$Gridpower=$json->siteCurrentPowerFlow->PV->currentPower*1000; // PV - Current Power
			$this->SendDebug("Grid Power: ",$Gridpower,0);
			
			SetValue($this->GetIDForIdent("GridPower"), $Gridpower);
			SetValue($this->GetIDForIdent("PV_State"), $PV_State);
			return $PV_State;
		}
		
	}
?>
