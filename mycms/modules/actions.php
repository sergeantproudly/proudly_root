<?php

class actions extends krn_abstract{
	
	function __construct(){
		parent::__construct();
	}
	
	function GetResult(){
	}
	
	/** System */
	function SystemMultiSelect($params){
		$storageTable=$params['storageTable'];
		$storageSelfField=$params['storageSelfField'];
		$storageField=$params['storageField'];
		$selfValue=$params['selfValue'];
		dbDoQuery('DELETE FROM `'.$storageTable.'` WHERE `'.$storageSelfField.'`="'.$selfValue.'"',__FILE__,__LINE__);
		if(isset($params['values'])){
			foreach($params['values'] as $value){
				dbDoQuery('INSERT INTO `'.$storageTable.'` SET `'.$storageSelfField.'`="'.$selfValue.'", `'.$storageField.'`="'.$value.'"',__FILE__,__LINE__);
			}
		}
	}
	
	/** New */
	function OnAddNew($newRecord){
		if(!$newRecord['Code']){
			krnLoadLib('chars');
			$code=mb_strtolower(chrTranslit($newRecord['Title']));
			$code=strtr($code,array(','=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>''));
			dbDoQuery('UPDATE news SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}
	}
	
	function OnEditNew($newRecord,$oldRecord){
		if(!$newRecord['Code']){
			krnLoadLib('chars');
			$code=mb_strtolower(chrTranslit($newRecord['Title']));
			$code=strtr($code,array(','=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>''));
			dbDoQuery('UPDATE news SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}
	}
	
	/** Statue */
	function OnAddStatue($newRecord){
		if(!$newRecord['Code']){
			krnLoadLib('chars');
			$code=mb_strtolower(chrTranslit($newRecord['Title']));
			$code=strtr($code,array(','=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>''));
			dbDoQuery('UPDATE statues SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}
	}
	
	function OnEditStatue($newRecord,$oldRecord){
		if(!$newRecord['Code']){
			krnLoadLib('chars');
			$code=mb_strtolower(chrTranslit($newRecord['Title']));
			$code=strtr($code,array(','=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>''));
			dbDoQuery('UPDATE statues SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}
	}
	
	/** Interview */
	function OnAddInterview($newRecord){
		if(!$newRecord['Code']){
			krnLoadLib('chars');
			$code=mb_strtolower(chrTranslit($newRecord['Title']));
			$code=strtr($code,array(','=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>''));
			dbDoQuery('UPDATE interviews SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}
	}
	
	function OnEditInterview($newRecord,$oldRecord){
		if(!$newRecord['Code']){
			krnLoadLib('chars');
			$code=mb_strtolower(chrTranslit($newRecord['Title']));
			$code=strtr($code,array(','=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>''));
			dbDoQuery('UPDATE interviews SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}
	}
	
	/** Advice */
	function OnAddAdvice($newRecord){
		if(!$newRecord['Code']){
			krnLoadLib('chars');
			$code=mb_strtolower(chrTranslit($newRecord['Title']));
			$code=strtr($code,array(','=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>''));
			dbDoQuery('UPDATE advices SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}
	}
	
	function OnEditAdvice($newRecord,$oldRecord){
		if(!$newRecord['Code']){
			krnLoadLib('chars');
			$code=mb_strtolower(chrTranslit($newRecord['Title']));
			$code=strtr($code,array(','=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>''));
			dbDoQuery('UPDATE advices SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}
	}
	
	/** City */
	function OnAddCity($newRecord){
		if(!$newRecord['Code']){
			krnLoadLib('chars');
			$code=mb_strtolower(chrTranslit($newRecord['Name']));
			$code=strtr($code,array(','=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>''));
			dbDoQuery('UPDATE cities SET `Code`="'.$code.'" WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}
	}
	
	function OnEditCity($newRecord,$oldRecord){
		if(!$newRecord['Code']){
			krnLoadLib('chars');
			$code=mb_strtolower(chrTranslit($newRecord['Name']));
			$code=strtr($code,array(','=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>''));
			dbDoQuery('UPDATE cities SET `Code`="'.$code.'" WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}
	}
	
	/** Company */
	function OnAddCompany($newRecord){
		if(!$newRecord['Code']){
			krnLoadLib('chars');
			$code=mb_strtolower(chrTranslit($newRecord['Title']));
			$code=strtr($code,array(','=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>''));
			dbDoQuery('UPDATE companies SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}
	}
	
	function OnEditCompany($newRecord,$oldRecord){
		if(!$newRecord['Code']){
			krnLoadLib('chars');
			$code=mb_strtolower(chrTranslit($newRecord['Title']));
			$code=strtr($code,array(','=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>''));
			dbDoQuery('UPDATE companies SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}
	}
	
	function OnDeleteCompany($oldRecord){
		dbDoQuery('DELETE FROM companies_contacts WHERE CompanyId='.$oldRecord['Id'],__FILE__,__LINE__);
		dbDoQuery('DELETE FROM companies_licenses WHERE CompanyId='.$oldRecord['Id'],__FILE__,__LINE__);
	}
	
	/** Service */
	function OnAddService($newRecord){
		if(!$newRecord['Code']){
			krnLoadLib('chars');
			$code=mb_strtolower(chrTranslit($newRecord['Title']));
			$code=strtr($code,array(','=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>''));
			dbDoQuery('UPDATE services SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}
	}
	
	function OnEditService($newRecord,$oldRecord){
		if(!$newRecord['Code']){
			krnLoadLib('chars');
			$code=mb_strtolower(chrTranslit($newRecord['Title']));
			$code=strtr($code,array(','=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>''));
			dbDoQuery('UPDATE services SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}
	}
	
	/** Static pages */
	function OnAddStaticPage($newRecord){
		if(!$newRecord['Code']){
			krnLoadLib('chars');
			$code=mb_strtolower(chrTranslit($newRecord['Title']));
			$code=strtr($code,array(','=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>''));
			dbDoQuery('UPDATE static_pages SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}else{
			dbDoQuery('UPDATE static_pages SET LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}		
	}
	
	function OnEditStaticPage($newRecord,$oldRecord){
		if(!$newRecord['Code']){
			krnLoadLib('chars');
			$code=mb_strtolower(chrTranslit($newRecord['Title']));
			$code=strtr($code,array(','=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>''));
			dbDoQuery('UPDATE static_pages SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}else{
			dbDoQuery('UPDATE static_pages SET LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		}	
	}
	
	/** Users */
	function OnAddUser($newRecord){
		krnLoadSiteLib('phpbb3_auth');
		$params=array(
			'email'				=> $newRecord['Email'],
			'password'			=> $newRecord['Password_src'],
			'password_repeat'	=> $newRecord['Password_src']
		);
		$phpbb = new My_phpbblib();
		$phpbb_userid=$phpbb->registration($params);
		dbDoQuery('UPDATE users SET PhpbbId='.$phpbb_userid.' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
	}
	
	function OnEditUser($newRecord,$oldRecord){
		// если забанили пользователя
		if(!$oldRecord['IsBanned'] && $newRecord['IsBanned']){
			$data=$newRecord;
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($newRecord['Email'],'user_ban',$data);
			
		}elseif($oldRecord['IsBanned'] && !$newRecord['IsBanned']){
			$data=$newRecord;
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($newRecord['Email'],'user_unban',$data);
		}
	}
	
	function OnDeleteUser($oldRecord){
		dbDoQuery('DELETE FROM `forum_users` WHERE `user_id`='.$oldRecord['PhpbbId'],__FILE__,__LINE__);
	}
	
	/** User Comment Advices */
	function OnEditUserCommentAdvice($newRecord,$oldRecord){		
		// если забанили комментарий
		if($oldRecord['IsActive'] && !$newRecord['IsActive']){
			$user=dbGetRecordFromDb('SELECT * FROM users WHERE Id='.$newRecord['UserId'],__FILE__,__LINE__);
			$material=dbGetRecordFromDb('SELECT * FROM advices WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
			$data=$newRecord;
			
			dbDoQuery('UPDATE advices SET CommentsCount=CommentsCount-1 WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($user['Email'],'comment_ban',$data);
		
		// если разбанили комментарий	
		}elseif(!$oldRecord['IsActive'] && $newRecord['IsActive']){
			$user=dbGetRecordFromDb('SELECT * FROM users WHERE Id='.$newRecord['UserId'],__FILE__,__LINE__);
			$material=dbGetRecordFromDb('SELECT * FROM advices WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
			$data=$newRecord;
			
			dbDoQuery('UPDATE advices SET CommentsCount=CommentsCount+1 WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($user['Email'],'comment_unban',$data);
		}
	}
	
	/** User Comment News */
	function OnEditUserCommentNew($newRecord,$oldRecord){
		// если забанили комментарий
		if($oldRecord['IsActive'] && !$newRecord['IsActive']){
			$user=dbGetRecordFromDb('SELECT * FROM users WHERE Id='.$newRecord['UserId'],__FILE__,__LINE__);
			$material=dbGetRecordFromDb('SELECT * FROM news WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
			$data=$newRecord;
			
			dbDoQuery('UPDATE news SET CommentsCount=CommentsCount-1 WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($user['Email'],'comment_ban',$data);
		
		// если разбанили комментарий	
		}elseif(!$oldRecord['IsActive'] && $newRecord['IsActive']){
			$user=dbGetRecordFromDb('SELECT * FROM users WHERE Id='.$newRecord['UserId'],__FILE__,__LINE__);
			$material=dbGetRecordFromDb('SELECT * FROM news WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
			$data=$newRecord;
			
			dbDoQuery('UPDATE news SET CommentsCount=CommentsCount+1 WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($user['Email'],'comment_unban',$data);
		}
	}
	
	/** User Comment Statues */
	function OnEditUserCommentStatue($newRecord,$oldRecord){
		// если забанили комментарий
		if($oldRecord['IsActive'] && !$newRecord['IsActive']){
			$user=dbGetRecordFromDb('SELECT * FROM users WHERE Id='.$newRecord['UserId'],__FILE__,__LINE__);
			$material=dbGetRecordFromDb('SELECT * FROM statues WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
			$data=$newRecord;
			
			dbDoQuery('UPDATE statues SET CommentsCount=CommentsCount-1 WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($user['Email'],'comment_ban',$data);
		
		// если разбанили комментарий	
		}elseif(!$oldRecord['IsActive'] && $newRecord['IsActive']){
			$user=dbGetRecordFromDb('SELECT * FROM users WHERE Id='.$newRecord['UserId'],__FILE__,__LINE__);
			$material=dbGetRecordFromDb('SELECT * FROM statues WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
			$data=$newRecord;
			
			dbDoQuery('UPDATE statues SET CommentsCount=CommentsCount+1 WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($user['Email'],'comment_unban',$data);
		}
	}
	
	/** User Comment Interviews */
	function OnEditUserCommentInterview($newRecord,$oldRecord){
		// если забанили комментарий
		if($oldRecord['IsActive'] && !$newRecord['IsActive']){
			$user=dbGetRecordFromDb('SELECT * FROM users WHERE Id='.$newRecord['UserId'],__FILE__,__LINE__);
			$material=dbGetRecordFromDb('SELECT * FROM interviews WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
			$data=$newRecord;
			
			dbDoQuery('UPDATE interviews SET CommentsCount=CommentsCount-1 WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($user['Email'],'comment_ban',$data);
		
		// если разбанили комментарий	
		}elseif(!$oldRecord['IsActive'] && $newRecord['IsActive']){
			$user=dbGetRecordFromDb('SELECT * FROM users WHERE Id='.$newRecord['UserId'],__FILE__,__LINE__);
			$material=dbGetRecordFromDb('SELECT * FROM interviews WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
			$data=$newRecord;
			
			dbDoQuery('UPDATE interviews SET CommentsCount=CommentsCount+1 WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($user['Email'],'comment_unban',$data);
		}
	}
	
	/** User Comment Interviews */
	function OnEditUserCommentReview($newRecord,$oldRecord){
		// если забанили комментарий
		if($oldRecord['IsActive'] && !$newRecord['IsActive']){
			$user=dbGetRecordFromDb('SELECT * FROM users WHERE Id='.$newRecord['UserId'],__FILE__,__LINE__);
			$material=dbGetRecordFromDb('SELECT * FROM companies_reviews WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
			$data=$newRecord;
			
			dbDoQuery('UPDATE companies_reviews SET CommentsCount=CommentsCount-1 WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($user['Email'],'comment_ban',$data);
		
		// если разбанили комментарий	
		}elseif(!$oldRecord['IsActive'] && $newRecord['IsActive']){
			$user=dbGetRecordFromDb('SELECT * FROM users WHERE Id='.$newRecord['UserId'],__FILE__,__LINE__);
			$material=dbGetRecordFromDb('SELECT * FROM companies_reviews WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
			$data=$newRecord;
			
			dbDoQuery('UPDATE companies_reviews SET CommentsCount=CommentsCount+1 WHERE Id='.$newRecord['MaterialId'],__FILE__,__LINE__);
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($user['Email'],'comment_unban',$data);
		}
	}
	
	/** Company Reviews */
	function OnEditCompanyReview($newRecord,$oldRecord){
		// если забанили отзыв
		if($oldRecord['IsActive'] && !$newRecord['IsActive']){
			$user=dbGetRecordFromDb('SELECT * FROM users WHERE Id='.$newRecord['UserId'],__FILE__,__LINE__);
			$company=dbGetRecordFromDb('SELECT * FROM companies WHERE Id='.$newRecord['CompanyId'],__FILE__,__LINE__);
			$data=$newRecord;
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($user['Email'],'review_ban',$data);
		
		// если разбанили отзыв	
		}elseif(!$oldRecord['IsActive'] && $newRecord['IsActive']){
			$user=dbGetRecordFromDb('SELECT * FROM users WHERE Id='.$newRecord['UserId'],__FILE__,__LINE__);
			$company=dbGetRecordFromDb('SELECT * FROM companies WHERE Id='.$newRecord['CompanyId'],__FILE__,__LINE__);
			$data=$newRecord;
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($user['Email'],'review_unban',$data);
		}
	}
	
	/** Forum Topics */
	function OnEditForumTopic($newRecord,$oldRecord){
		// если забанили топик
		if($oldRecord['topic_visibility'] && !$newRecord['topic_visibility']){
			$user=dbGetRecordFromDb('SELECT * FROM users WHERE PhpbbId='.$newRecord['topic_poster'],__FILE__,__LINE__);
			$data=$newRecord;
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($user['Email'],'forum_topic_ban',$data);
			
		}elseif(!$oldRecord['topic_visibility'] && $newRecord['topic_visibility']){
			$user=dbGetRecordFromDb('SELECT * FROM users WHERE PhpbbId='.$newRecord['topic_poster'],__FILE__,__LINE__);
			$data=$newRecord;
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($user['Email'],'forum_topic_unban',$data);
		}
	}
	
	/** Forum Posts */
	function OnEditForumPost($newRecord,$oldRecord){
		// если забанили пост
		if($oldRecord['post_visibility'] && !$newRecord['post_visibility']){
			$user=dbGetRecordFromDb('SELECT * FROM users WHERE PhpbbId='.$newRecord['poster_id'],__FILE__,__LINE__);
			$data=$newRecord;
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($user['Email'],'forum_post_ban',$data);
			
		}elseif(!$oldRecord['post_visibility'] && $newRecord['post_visibility']){
			$user=dbGetRecordFromDb('SELECT * FROM users WHERE PhpbbId='.$newRecord['poster_id'],__FILE__,__LINE__);
			$data=$newRecord;
						
			krnLoadLib('mail');
			$mail=new Mail();
			$mail->SendMailTemplateFromSite($user['Email'],'forum_post_unban',$data);
		}
	}
}

?>