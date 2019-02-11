<?php

krnLoadLib('settings');

class main extends krn_abstract{	

	public function __construct(){
		parent::__construct();
		$this->folder=$this->db->getRow('SELECT Title, Content, SeoTitle, SeoKeywords, SeoDescription FROM static_pages WHERE Code="main"');
		
		global $Config;
		$this->pageTitle=$this->folder['Title']?$this->folder['Title']:$this->settings->GetSetting('SiteTitle',$Config['Site']['Title']?$Config['Site']['Title']:'Главная');
	}	

	public function GetResult(){
		global $Config;
		$Blocks=krnLoadModuleByName('blocks');
		$result=krnLoadPageByTemplate('base_main');
		$result=strtr($result,array(
			'<%META_KEYWORDS%>'		=> $this->folder['Keywords']?$this->folder['Keywords']:$Config['Site']['Keywords'],
			'<%META_DESCRIPTION%>'	=> $this->folder['Description']?$this->folder['Description']:$Config['Site']['Description'],
			'<%PAGE_TITLE%>'		=> $this->pageTitle
		));
		return $result;
	}

}
?>