<?php

	class PreActions{
		
		public function __construct(){
			$this->DoActions();
		}
		
		private function DoActions(){
			global $Params;
			global $Config;
			
			$Params['Db']['Link']=new SafeMySQL(array(
		   		'host'		=> $Config['Db']['Host'],
		   		'user'		=> $Config['Db']['Login'],
		   		'pass'		=> $Config['Db']['Pswd'],
		   		'db'		=> $Config['Db']['DbName'],
		   		'charset'	=> 'utf8'
		 	));
		 	
		 	/*
		 	if(!$_SESSION['ClientUser']['City']){
		 		krnLoadLib('geoip');
			    $geo=new GeoIP();
			    $_SESSION['ClientUser']['City']=$geo->DetermineClientCity();
		 	}
		 	*/
		} 
		
	}
	
	$PreActions=new PreActions();

?>