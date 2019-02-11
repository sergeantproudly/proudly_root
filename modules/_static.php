<?php

class _static extends krn_abstract{	

	function __construct(){
		parent::__construct();
		global $Params;
		$this->static=$this->db->getRow('SELECT Id, Code, Title, Content, SeoTitle, SeoKeywords, SeoDescription FROM static_pages WHERE Code="'.$Params['Site']['Page']['Code'].'"');
		if(!$this->static){
			header('HTTP/1.1 404 Not Found');
			header('Status: 404 Not Found');
			$this->pageTitle='Page not found!';
			$this->static['Title']='';
			$this->static['Content']='Page not found!';
			
		}else{
			$this->static['Title']=stripslashes($this->static['Title']);
			$this->static['Content']=stripslashes($this->static['Content']);
			$this->pageTitle=$this->static['SeoTitle']?$this->static['SeoTitle']:$this->static['Title'];
			$this->breadCrumbs=GetBreadCrumbs(array('Главная'=>'/'),$this->pageTitle);
		}
	}	

	function GetResult(){
		$Blocks=krnLoadModuleByName('blocks');
		$this->content=$this->static['Content'];
		$result=krnLoadPageByTemplate('base_static');
		$result=strtr($result,array(
			'<%META_KEYWORDS%>'		=> $this->static['SeoKeywords'],
    		'<%META_DESCRIPTION%>'	=> $this->static['SeoDescription'],
    		'<%PAGE_TITLE%>'		=> $this->pageTitle,
    		'<%BREAD_CRUMBS%>'		=> $this->breadCrumbs,
    		'<%TITLE%>'				=> $this->static['Title'],
			'<%CONTENT%>'			=> $this->content
		));
		return $result;
	}

}

?>