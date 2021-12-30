<?php

krnLoadLib('define');

class main extends krn_abstract{
	
	function __construct(){
		parent::__construct();
	}
	
	function GetResult(){
		$result=$this->GetContent();

		return $result;
	}
	
	function GetContent(){
		$records=krnLoadModuleByName('records2');
		$documents='<div class="inner-wrapper">'.$records->BrowseDocuments().'</div>';
		
		if($_SESSION['User']['Status']!=PERMISSION_MASK_MODERATOR){
			$records=krnLoadModuleByName('records');
			$documents.='<div class="inner-wrapper">'.$records->BrowseDocuments().'</div>';
		}
		
		return $this->GetStatistics();		
		//return '';
	}
	
	function GetStatistics(){
		$stats['ContactsTotal']=dbGetValueFromDb('SELECT COUNT(Id) FROM email_contacts',__FILE__,__LINE__);
		$stats['Campaign']=dbGetRecordFromDb('SELECT * FROM email_campaigns WHERE Finished = 1 ORDER BY DateTime DESC',__FILE__,__LINE__);
		$stats['SeenCount']=dbGetValueFromDb('SELECT COUNT(Id) FROM rel_campaign_contacts WHERE CampaignId = ' . $stats['Campaign']['Id'] . ' AND Seen = 1',__FILE__,__LINE__);	
		$stats['LinkVisitedCount']=dbGetValueFromDb('SELECT COUNT(Id) FROM rel_campaign_contacts WHERE CampaignId = ' . $stats['Campaign']['Id'] . ' AND LinkVisited = 1',__FILE__,__LINE__);		
		
		$result=LoadTemplate('main');
		$result=strtr($result,array(
			'<%DOCUMENTS%>'	=> $documents,
			'<%VALUE1%>'	=> $stats['ContactsTotal'],
			'<%UNIT1%>'		=> '',
			'<%TITLE1%>'	=> 'Контактов всего',
			'<%VALUE2%>'	=> $stats['ContactsTotal'],
			'<%UNIT2%>'		=> '',
			'<%TITLE2%>'	=> 'Отправлено',
			'<%VALUE3%>'	=> $stats['SeenCount'],
			'<%UNIT3%>'		=> '',
			'<%TITLE3%>'	=> 'Прочитано',
			'<%VALUE4%>'	=> $stats['LinkVisitedCount'],
			'<%UNIT4%>'		=> '',
			'<%TITLE4%>'	=> 'Переходы',
		));
		return $result;
	}
	
}

?>