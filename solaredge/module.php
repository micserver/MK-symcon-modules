<?
  // Klassendefinition
	class solaredge extends IPSModule
	{
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			
			$this->RegisterPropertyInteger("SourceVariable", 0);
			$this->RegisterPropertyString("Formula", "\$Value/10*sin(30)*pi()");
			
			$this->RegisterPropertyString("API_Key", "deinAPIKey");
			$this->RegisterPropertyString("ID", "deineID");
			
			$this->RegisterVariableFloat("GridPower", "GridPower", "", 0);
			#$this->RegisterVariableFloat("Value", "Value", "", 0);
			
			$this->RegisterTimer("UpdateTimer", 900 * 1000, 'API_RequestInfo($_IPS[\'TARGET\']);');
		}
	
		public function ApplyChanges()
		{
			
			//Never delete this line!
			parent::ApplyChanges();
				
			//Create our trigger
			#if(IPS_VariableExists($this->ReadPropertyInteger("SourceVariable"))) {
			#	$eid = @IPS_GetObjectIDByIdent("SourceTrigger", $this->InstanceID);
			#	if($eid === false) {
			#		$eid = IPS_CreateEvent(0 /* Trigger */);
			#		IPS_SetParent($eid, $this->InstanceID);
			#		IPS_SetIdent($eid, "SourceTrigger");
			#		IPS_SetName($eid, "Trigger for #".$this->ReadPropertyInteger("SourceVariable"));
			#	}
			#	IPS_SetEventTrigger($eid, 0, $this->ReadPropertyInteger("SourceVariable"));
			#	IPS_SetEventScript($eid, "SetValue(IPS_GetObjectIDByIdent(\"Value\", \$_IPS['TARGET']), API_Calculate(\$_IPS['TARGET'], \$_IPS['VALUE']));");
			#	IPS_SetEventActive($eid, true);
			#}
			
		}
	
		/**
		* This function will be available automatically after the module is imported with the module control.
		* Using the custom prefix this function will be callable from PHP and JSON-RPC through:
		*
		* API_RequestInfo($id);
		*
		*/
		
		#public function Calculate(float $Value)
		#{
			
		#	eval("\$Value = " . $this->ReadPropertyString("Formula") . ";");
			
		#	return $Value;
		
		#}
		public function RequestInfo()
		{

			$apikey = $this->ReadPropertyString("API_Key");
			$id = $this->ReadPropertyString("ID");
			$this->SendDebug("API_Key: ",$apikey,0);
			$this->SendDebug("ID: ",$id,0);
			$gridpower = 17;
			SetValue($this->GetIDForIdent("GridPower"), $gridpower);
		}
	
	}
?>
