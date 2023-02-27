<?php

class Settings{
	
	private $db;
	private $all_settings_arr=array();
	
	public function __construct(){
		global $Params;
		$this->db=$Params['Db']['Link'];
		$this->LoadAllSettings();
	}
	
	public function LoadAllSettings(){
		$this->all_settings_arr=array();
		$settings=$this->db->getAll('SELECT `Code`, `Value` FROM `settings`');
		foreach($settings as $setting){
			$this->all_settings_arr[$setting['Code']]=$setting['Value'];
		}
	}
	
	public function GetSetting($code,$default=''){
		if(!$this->all_settings_arr)$this->LoadAllSettings();
		foreach($this->all_settings_arr as $c=>$v){
			if($c==$code)return $v;
		}
		return $default;
	}
	
	public function SetSetting($code, $value, $force_create = false, $title = false){
		if(!$this->all_settings_arr)$this->LoadAllSettings();
		if (!in_array($code, array_keys($this->all_settings_arr)) && $force_create) {
			$this->db->query('INSERT INTO `settings` SET `Value` = ?s, `Code` = ?s' . ($title ? ', `Title` = ?s' : ''), $value, $code);
			$this->all_settings_arr[$code] = $value;
			
		} else {
			$this->db->query('UPDATE `settings` SET `Value`=?s WHERE `Code`=?s', $value, $code);
			foreach($this->all_settings_arr as $c=>$v){
				if($c==$code){
					$this->all_settings_arr[$c]=$value;
					return true;
				}
			}
		}
		return false;
	}
}

$GLOBALS['Settings']=new Settings();

function stGetSetting($code,$default=''){
	global $Settings;
	return $Settings->GetSetting($code,$default);
}
function stSetSetting($code, $value, $force_create = false, $title = false){
	global $Settings;
	return $Settings->SetSetting($code, $value, $force_create, $title);
}

?>