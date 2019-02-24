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
			$this->RegisterPropertyBoolean("archive_PV_State", false);
			$this->RegisterVariableFloat("PV_currentPower", "PV current Power", "", 1);
			$this->RegisterPropertyBoolean("archive_PV_currentPower", false);
			$this->RegisterVariableString("Load_Status", "Load Status", "", 1);
			$this->RegisterPropertyBoolean("archive_Load_Status", false);			
			$this->RegisterVariableFloat("LOAD_currentPower", "LOAD current Power", "", 1);
			$this->RegisterPropertyBoolean("archive_LOAD_currentPower", false);
			$this->RegisterVariableString("Grid_Status", "Grid Status", "", 1);
			$this->RegisterPropertyBoolean("archive_Grid_Status", false);
			$this->RegisterVariableFloat("Grid_currentPower", "Grid current Power", "", 1);
			$this->RegisterPropertyBoolean("archive_Grid_currentPower", false);			
			$this->RegisterVariableString("connection_0_from", "connection 0 from", "", 1);
			$this->RegisterPropertyBoolean("archive_connection_0_from", false);
			$this->RegisterVariableString("connection_0_to", "connection 0 to", "", 1);
			$this->RegisterPropertyBoolean("archive_connection_0_to", false);
			
			$this->RegisterPropertyInteger('Interval', 300);
			
			$this->RegisterTimer("UpdateTimer", 0, 'API_RequestInfo($_IPS[\'TARGET\']);');
			#$this->RegisterTimer("UpdateTimer", 900 * 1000, 'API_RequestInfo($_IPS[\'TARGET\']);');
					
		}
    
		public function Destroy()
    		{
        		#$this->UnregisterTimer('API_UpdateStatus');
			$this->UnregisterTimer('API_RequestInfo');
   		}
		
		
		public function ApplyChanges()
		{
			
			//Never delete this line!
			parent::ApplyChanges();
		
			#$this->SetTimerInterval('API_UpdateTimer', $this->ReadPropertyInteger('Interval') * 1000);
			$this->SetTimerInterval('API_RequestInfo', $this->ReadPropertyInteger('Interval') * 1000);
			
			//Set Logging Status
			// Get ObjectID for first archive
			$archives = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}");
			$this->SendDebug("AC=> Archive ID: ",$archives[0],0);
			$this->SendDebug("AC=> PV current Power ID: ",$this->GetIDForIdent('PV_currentPower'),0);
			$this->SendDebug("AC=> archive PV current Power: ",$this->ReadPropertyBoolean('archive_PV_currentPower'),0);
			$this->SendDebug("AC=> PV State ID: ",$this->GetIDForIdent('PV_State'),0);
			$this->SendDebug("AC=> archive PV State: ",$this->ReadPropertyBoolean('archive_PV_State'),0);
			
			//Logging Status setzen
			switch ($this->ReadPropertyBoolean('archive_PV_State')){
				case true:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('PV_State'), true);
				break;
				case false:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('PV_State'), false);
				break;
			}
			switch ($this->ReadPropertyBoolean('archive_PV_currentPower')){
				case true:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('PV_currentPower'), true);
				break;
				case false:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('PV_currentPower'), false);
				break;
			}
			switch ($this->ReadPropertyBoolean('archive_Load_Status')){
				case true:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('Load_Status'), true);
				break;
				case false:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('Load_Status'), false);
				break;
			}
			switch ($this->ReadPropertyBoolean('archive_LOAD_currentPower')){
				case true:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('LOAD_currentPower'), true);
				break;
				case false:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('LOAD_currentPower'), false);
				break;
			}			
			switch ($this->ReadPropertyBoolean('archive_Grid_Status')){
				case true:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('Grid_Status'), true);
				break;
				case false:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('Grid_Status'), false);
				break;
			}
			switch ($this->ReadPropertyBoolean('archive_Grid_currentPower')){
				case true:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('Grid_currentPower'), true);
				break;
				case false:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('Grid_currentPower'), false);
				break;
			}			
			switch ($this->ReadPropertyBoolean('archive_connection_0_from')){
				case true:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('connection_0_from'), true);
				break;
				case false:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('connection_0_from'), false);
				break;
			}			
			switch ($this->ReadPropertyBoolean('archive_connection_0_to')){
				case true:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('connection_0_to'), true);
				break;
				case false:
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('connection_0_to'), false);
				break;
			}
			
			IPS_ApplyChanges($archives[0]);    

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
			$PV_currentPower=$json->siteCurrentPowerFlow->PV->currentPower*1000; // PV - Current Power
			$this->SendDebug("PV current Power: ",$PV_currentPower,0);
			$Load_Status=$json->siteCurrentPowerFlow->LOAD->status; // Load - Status
			$this->SendDebug("Load Status: ",$Load_Status,0);
			$LOAD_currentPower=$json->siteCurrentPowerFlow->LOAD->currentPower*-1000; // Load - Current Power
			$this->SendDebug("LOAD current Power: ",$LOAD_currentPower,0);
			$Grid_Status=$json->siteCurrentPowerFlow->GRID->status; // Grid - Status
			$this->SendDebug("Grid Status: ",$Grid_Status,0);
			$Grid_currentPower= $json->siteCurrentPowerFlow->GRID->currentPower*1000; // Grid - Current Power
			$this->SendDebug("Grid current Power: ",$Grid_currentPower,0);
			$connection_0_from=$json->siteCurrentPowerFlow->connections[0]->from; // Connections - From LOAD
			$this->SendDebug("connetion 0 from: ",$connection_0_from,0);
    			$connection_0_to=$json->siteCurrentPowerFlow->connections[0]->to; // Connections - From LOAD
			$this->SendDebug("connection 0 to: ",$connection_0_to,0);
			
			
			SetValue($this->GetIDForIdent("PV_currentPower"), $PV_currentPower);
			SetValue($this->GetIDForIdent("PV_State"), $PV_State);
			SetValue($this->GetIDForIdent("Load_Status"), $Load_Status);
			SetValue($this->GetIDForIdent("LOAD_currentPower"), $LOAD_currentPower);
			SetValue($this->GetIDForIdent("Grid_Status"), $Grid_Status);			
			SetValue($this->GetIDForIdent("Grid_currentPower"), $Grid_currentPower);
			SetValue($this->GetIDForIdent("connection_0_from"), $connection_0_from);
			SetValue($this->GetIDForIdent("connection_0_to"), $connection_0_to);			
			return $PV_State;
		}	
	}
?>
