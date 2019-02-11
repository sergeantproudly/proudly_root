<?php

	define('GEOAPI_URL','http://ipgeobase.ru:7020/geo?ip=');
	
	class GeoIP{
		
		protected $db;
		protected $settings;
		
		public function __construct(){
			global $Params;
			global $Settings;
			$this->db=$Params['Db']['Link'];
			$this->settings=$Settings;
		}
		
		public function GetCityByIp($ip){
			$xml=new DOMDocument();
			if($xml->load(GEOAPI_URL.$ip)){
				return $xml->documentElement->getElementsByTagName('city')->item(0)->nodeValue;
				
			}else{
				// город не определен
				return false;
			}
		}
		
		private function GetClientIp(){
		    if (!empty($_SERVER['HTTP_CLIENT_IP'])){
		        $ip=$_SERVER['HTTP_CLIENT_IP'];
		    }elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
		        $ip=preg_replace('/,([0-9,\. ]+)$/','',$_SERVER['HTTP_X_FORWARDED_FOR']);
		    }else{
		        $ip=$_SERVER['REMOTE_ADDR'];
		    }
		    return $ip;
		}
		
		private function GetCityIdByName($city_name){
			return $this->db->getRow('SELECT * FROM cities WHERE Name="'.$city_name.'"');
		}
		
		public function DetermineClientCity(){
			$ip=$this->GetClientIp();
			if($city_name=$this->GetCityByIp($ip)){
				if($city=$this->GetCityIdByName($city_name)){
					// город найден в базе данных
					return $city;
					
				}else{
					// город не найден в базе данных
					// подставляем первый по счету
					return $this->db->getRow('SELECT Id, Name FROM cities');
				}
			}else{
				// город не найден в базе данных
				// подставляем первый по счету
				return $this->db->getRow('SELECT Id, Name FROM cities');
			}
		}
		
	}

?>