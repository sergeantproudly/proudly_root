<?php

krnLoadLib('define');
krnLoadLib('settings');

class blocks extends krn_abstract{
	
	private $blocks_info=array();
	private $forms_info=array();

	public function __construct(){
		parent::__construct();
		
		$blocks=$this->db->getAll('SELECT * FROM `text_blocks`');
		foreach($blocks as $block){
			$this->blocks_info[$block['Code']]=$block;
		}
		
		$forms=$this->db->getAll('SELECT * FROM `forms`');
		foreach($forms as $form){
			$this->forms_info[$form['Code']]=$form;
		}
	}
	
	public function GetResult(){}
	
	/** Меню - Главное меню */
	function MenuMain(){
		$element=LoadTemplate('mn_main_el');
		$content='';
		
		global $Site;
		$page=$Site->GetCurrentPage();
		
		$items=$this->db->getAll('SELECT Id, Title, Link FROM menu_items ORDER BY IF(`Order`,-1000/`Order`,0) ASC');
		foreach($items as $item){
			$linkPage='';
			if(preg_match('/([a-zA-Z0-9_]+)\/?$/',$item['Link'],$match)){
				$linkPage=$match[1];
			}
			$curr=false;
			if($linkPage && ($page==$item['Link'] || (preg_match('/'.$linkPage.'[a-zA-Z0-9_\-]*\/?$/',$page) && $item['Link']!='/'))){
				$curr=true;
			}
			$content.=strtr($element,array(
				'<%HREF%>'	=> $item['Link'],
				'<%TITLE%>'	=> $item['Title'],
				'<%CLASS%>'	=> $curr||$submn?' class="'.($curr?' active':'').'"':''
			));
		}
		
		$result=strtr(LoadTemplate('mn_main'),array(
			'<%CONTENT%>'	=> $content
		));
		return $result;
	}
	
	/** Блок - Социальные ссылки */
	function BlockSocial(){
		$element=LoadTemplate('bl_social_el');
		$content='';
		
		$socials=$this->db->getAll('SELECT Title, Link AS Href, Image FROM social ORDER BY IF(`Order`,-1000/`Order`,0) ASC');
		foreach($socials as $social){
			$social['Alt']=htmlspecialchars($social['Title'],ENT_QUOTES);
			$content.=SetAtribs($element,$social);
		}
		
		$result=SetContent(LoadTemplate('bl_social'),$content);
		return $result;
	}
	
	/** Блок - Форма */
	function BlockForm($code){
		$result=LoadTemplate($code);
		$result=strtr($result,array(
			'<%TITLE%>'	=> $this->forms_info[$code]['Title'],
			'<%TEXT%>'	=> $this->forms_info[$code]['Text'],
			'<%CODE%>'	=> $this->forms_info[$code]['Code']
		));
		return $result;
	}
	
	/** Блок - Текстовый */
	function BlockText($code){
		$result=LoadTemplate($code?$code:'bl_text');
		$result=strtr($result,array(
			'<%HEADER%>'=> '<h2>'.$this->blocks_info[$code]['Header'].'</h2>',
			'<%TITLE%>'	=> $this->blocks_info[$code]['Header'],
			'<%TEXT%>'	=> $this->blocks_info[$code]['Text']
		));
		return $result;
	}
	
	/** Блок - Город */
	function BlockCity(){	
		return ''	;
		
		$result=LoadTemplate('bl_city');
		$result=strtr($result,array(
			'<%NAME%>'	=> $_SESSION['ClientUser']['City']['Name']
		));
		return $result;
	}

}
?>