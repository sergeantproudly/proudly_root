<?php

krnLoadLib('define');
krnLoadLib('settings');

class sitemap extends krn_abstract{
	
	function __construct(){
		parent::__construct();
	}
	
	function GetResult(){
		$pages=$this->GetPages();
		echo $this->GetSitemap($pages);
		exit;
	}
	
	function GetPages(){
		$time=time();
		
		// main
		$pages=Array(
			''	=> $time																		
		);
		
		//statics
		$items=$this->db->getAll('SELECT Code, LastModTime FROM static_pages ORDER BY IF(`Order`,-1000/`Order`,0)');
		foreach($items as $item){
			if($item['Code']!='osago' && $item['Code']!='services' && $item['Code']!='companies' && $item['Code']!='news' && $item['Code']!='statues' && $item['Code']!='interviews' && $item['Code']!='advices'){
				$pages[$item['Code']]=$item['LastModTime'];
			}			
		}
		return $pages;
	}
	
	function GetSitemap($pages){
		global $Settings;
		$siteUrl=$Settings->GetSetting('SiteUrl',$Config['Site']['Url']);
		
		$xml=new DomDocument('1.0','utf8');
		
		$urlset=$xml->appendChild($xml->createElement('urlset'));
		$urlset->setAttribute('xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
		$urlset->setAttribute('xsi:schemaLocation','http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
		$urlset->setAttribute('xmlns','http://www.sitemaps.org/schemas/sitemap/0.9');
		
		foreach($pages as $page=>$lastmodtime){
			$url=$urlset->appendChild($xml->createElement('url'));
			$loc=$url->appendChild($xml->createElement('loc'));
			$loc->appendChild($xml->createTextNode($siteUrl . (substr($siteUrl,-1)!='/'?'/':'') . $page));
			$lastmod=$url->appendChild($xml->createElement('lastmod'));
			$lastmod->appendChild($xml->createTextNode(date('c',$lastmodtime?$lastmodtime:time())));
			$changefreq=$url->appendChild($xml->createElement('changefreq'));
			$changefreq->appendChild($xml->createTextNode('daily'));
			$priority=$url->appendChild($xml->createElement('priority'));
			$priority->appendChild($xml->createTextNode('0.5'));
		}
		
		$xml->formatOutput=true;
		$xml->save('sitemap.xml');
	}
	
	function Generate(){
		$pages=$this->GetPages();
		$this->GetSitemap($pages);
	}
	
}

?>