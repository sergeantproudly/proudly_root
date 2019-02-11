<?php

krnLoadLib('settings');
krnLoadLib('define');

class Popup{
	
	protected $code;
	protected $db;
	protected $settings;
	protected $result=false;
	
	public function __construct($code){
		global $Params;
		global $Settings;
		$this->db=$Params['Db']['Link'];
		$this->settings=$Settings;
		$this->code=$code;
		
		$this->result=LoadTemplate('popup_'.$this->code);
		$func='Popup';
		$r=explode('_',$this->code);
		foreach($r as $k) {
			$k{0}=strtoupper($k{0});
			$func.=$k;
		}
		if(method_exists($this,$func))$this->result=$this->$func($this->result);		
	}
	
	public function GetPopup(){
		return $this->result;
	}
	
}

?>