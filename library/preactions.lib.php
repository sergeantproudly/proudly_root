<?php

	class PreActions{
		
		public function __construct(){
			$this->DoActions();
		}
		
		private function DoActions(){
			global $Params;
			global $Config;
			global $Site;
			
			$Params['Db']['Link'] = new SafeMySQL(array(
		   		'host'		=> $Config['Db']['Host'],
		   		'user'		=> $Config['Db']['Login'],
		   		'pass'		=> $Config['Db']['Pswd'],
		   		'db'		=> $Config['Db']['DbName'],
		   		'charset'	=> 'utf8'
		 	));

		 	$Site = new Site();
		 	
		 	if ($_SESSION['ClientUser']['Region'] || true) {
		 		krnLoadLib('geoip');
			    $geo = new GeoIP();
			    $_SESSION['ClientUser']['Region'] = $geo->DetermineClientRegion();
		 	}
		} 
		
	}
	
	$PreActions=new PreActions();

?>