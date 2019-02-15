<?
  // Klassendefinition
	class solaredge extends IPSModule
	{
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();		

			#$apikey = "4TBYELPQL0BSZADT4AJJQ89ASHL010E2"; 
			#$ID = "487010";
			
			$this->RegisterPropertyString("API_Key", "deinAPIKey");
			$this->RegisterPropertyString("ID", "deineID");
			
			$this->RegisterVariableString("PV_Status", "PV_Status", "", 0);
			$this->RegisterVariableFloat("GridPower", "GridPower", "", 1);
						
			$this->RegisterTimer("UpdateTimer", 900 * 1000, 'API_RequestInfo($_IPS[\'TARGET\']);');
		}
	
		public function ApplyChanges()
		{
			
			//Never delete this line!
			parent::ApplyChanges();
			
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
			
			$PV_Status=$json->siteCurrentPowerFlow->PV->status; // PV - Status
			$this->SendDebug("PV Status: ",$PV_Status,0);
			$Gridpower=$json->siteCurrentPowerFlow->PV->currentPower*1000; // PV - Current Power
			$this->SendDebug("Grid Power: ",$Gridpower,0);
			
			SetValue($this->GetIDForIdent("GridPower"), $Gridpower);
			SetValue($this->GetIDForIdent("PV_Status"), $PV_Status);
			return $Gridpower;
		}
		
	}
?>
