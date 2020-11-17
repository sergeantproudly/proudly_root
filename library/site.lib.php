<?php
    
    class Site {	

    	protected $db;
		protected $settings;

		public function __construct() {
			global $Params;
			global $Settings;
			$this->db = $Params['Db']['Link'];
			$this->settings = $Settings;
		}

		function GetCurrentPage(){
			$page=false;
			if(preg_match('/\/([a-zA-Z0-9_\-]+)\/?$/',$_SERVER['REQUEST_URI'],$match)){
				$page=$match[1];
			}elseif(preg_match('/\/$/',$_SERVER['REQUEST_URI'])){
				$page='/';
			}
			return $page;
		}
		
		function SetLinks($html){
			$result=preg_replace('~<a +href="(?!http[s]?://)([^\>]+)~i','<a href="/$1',$html);
			return strtr($result,array(
				'<a href="//'		=> '<a href="/',
				'<a href="/#'		=> '<a href="#',
				'<a href="/tel:'	=> '<a href="tel:',
				'<a href="/mailto'	=> '<a href="mailto' 
			));
		}

		function GetContactData() {
			return $this->db->getRow('SELECT * FROM contacts WHERE RegionId = ?i OR RegionId = 0 ORDER BY RegionId DESC', $_SESSION['ClientUser']['Region']['Id']);
		}

		function GetPage(){
			krnLoadLib('settings');
			global $krnModule;
			$Blocks=krnLoadModuleByName('blocks');
			$Main=krnLoadModuleByName('main');

			$result=strtr($krnModule->GetResult(),array(
		    	'<%META_KEYWORDS%>'			=> $Config['Site']['Keywords'],
		    	'<%META_DESCRIPTION%>'		=> $Config['Site']['Description'],
		    	'<%META_IMAGE%>'			=> '',
		    	'<%PAGE_TITLE%>'			=> stGetSetting('SiteTitle',$Config['Site']['Title']),
		    	'<%SITE_URL%>'				=> stGetSetting('SiteUrl',$Config['Site']['Url']),
		    	'<%SITE_EMAIL%>'			=> stGetSetting('SiteEmail',$Config['Site']['Email']),
		    	'<%SITE_TITLE%>'			=> stGetSetting('SiteTitle',$Config['Site']['Title']),
		    	'<%META_VERIFICATION%>'		=> stGetSetting('MetaVerification'),
		    	'<%YANDEX_METRIKA%>'		=> stGetSetting('YandexMetrika'),
		    	'<%MN_MAIN%>'				=> $Blocks->MenuMain(),
		    	'<%BL_CITY%>'				=> $Blocks->BlockCity(),
		    	'<%BREAD_CRUMBS%>'			=> '',
		    	'<%CONSULTANT%>'			=> stGetSetting('ConsultantCode'),
		    	'<%ANALYTICS%>'				=> stGetSetting('AnalyticsCode'),
		    	'<%BL_SOCIAL%>'				=> $Blocks->BlockSocial(),
		    	'<%VERSION%>'				=> stGetSetting('AssetsVersion') ? '?v1.' . stGetSetting('AssetsVersion') : '',
			));

			$contacts = $this->GetContactData();
			$result = strtr($result, array(
				'<%TEL%>'		=> $contacts['Tel'],
				'<%TELLINK%>'	=> preg_replace('/[^\d\+]/', '', $contacts['Tel']),
				'<%ADDRESS%>'	=> $contacts['Address'],
				'<%EMAIL%>'		=> $contacts['Email'] ?: stGetSetting('SiteEmail',$Config['Site']['Email'])
			));

			return $this->SetLinks($result);
		}	

	}
	
?>