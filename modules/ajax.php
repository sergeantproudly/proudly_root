<?php

krnLoadLib('mail');
krnLoadLib('settings');

class ajax extends krn_abstract{

	function __construct($params=array()) {
		parent::__construct();
	}
	
	function GetResult(){
		if($_POST['act']&&method_exists($this,$_POST['act'])){
			echo $this->$_POST['act'];
		}
		exit;
	}	

	function GetPopup(){
		krnLoadLib('popup');
		$popupCode=$_POST['code'];
		$popup=new Popup($popupCode);
		return $popup->GetPopup();
	}
	
	function GetUploader(){
		krnLoadLib('uploader');
		$uploaderCode=$_POST['code'];
		$func='Uploader';
		$r=explode('_',$uploaderCode);
		foreach($r as $k) {
			$k{0}=strtoupper($k{0});
			$func.=$k;
		}
		if(function_exists($func))return $func();
		return false;
	}

	function Feedback(){
		$name = trim($_POST['name']);
		$tel = trim($_POST['tel']);
		$text = $_POST['text'];
		$code = $_POST['code'];

		if ($name && $tel) {				
			$form = $this->db->getRow('SELECT Title, Success FROM forms WHERE Code=?s', $code);				
			$request = '';
			if ($name) $request .= "Имя: $name\r\n";
			if ($email) $request .= "E-mail: $email\r\n";
			if ($tel) $request .= "Телефон: $tel\r\n";
			$request.='Текст:'."\r\n$text\r\n";
			$this->db->query('INSERT INTO requests SET DateTime=NOW(), Name=?s, Phone=?s, Text=?s, RefererPage=?s, IsSet=0',
			 	$name,
			 	$tel,
				str_replace('"','\"',$request),
				$_SERVER['HTTP_REFERER']
			);
				
			global $Config;
			$siteTitle=strtr(stGetSetting('SiteEmailTitle',$Config['Site']['Title']),array('«'=>'"','»'=>'"','—'=>'-'));
			$siteEmail=stGetSetting('SiteEmail',$Config['Site']['Email']);
			$adminTitle='Администратор';
			$adminEmail=stGetSetting('AdminEmail',$siteEmail);
				
			$letter['subject']=$form['Title'].' с сайта "'.$siteTitle.'"';
			$letter['html']='<b>'.$form['Title'].'</b><br/><br/>';
			$letter['html'].=str_replace("\r\n",'<br/>',$request);
			$letter['text']=strip_tags(str_replace('<br/>',"\r\n",$letter['html']));
			//SendMail($siteEmail,$siteTitle,$adminEmail,$adminTitle,$letter['Subject'],$letter['Html'],$letter['Text']);
			$mail=new Mail();
			$mail->SendMailFromSite($admin_email,$letter['subject'],$letter['html']);
										
			$json = array(
				'status' => true,
				'message' => $form['Success']
			);

		}else{
			$json = array(
				'status' => false,
				'message' => 'Серверная ошибка. При повторном возникновении, пожалуйста, обратитесь к администратору.'
			);
		}

		return json_encode($json);
	}

}

?>