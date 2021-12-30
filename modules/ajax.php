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
			$request .= 'Текст:'."\r\n$text\r\n";
			$this->db->query('INSERT INTO requests SET DateTime=NOW(), Name=?s, Phone=?s, Text=?s, RefererPage=?s, IsSet=0',
			 	$name,
			 	$tel,
				str_replace('"','\"',$request),
				$_SERVER['HTTP_REFERER']
			);
				
			global $Config;
			$siteTitle = strtr(stGetSetting('SiteEmailTitle', $Config['Site']['Title']), array('«'=>'"','»'=>'"','—'=>'-'));
			$siteEmail = stGetSetting('SiteEmail', $Config['Site']['Email']);
			$adminTitle = 'Администратор';
			$adminEmail = stGetSetting('AdminEmail', $siteEmail);
				
			$letter['subject'] = $form['Title'].' с сайта "'.$siteTitle.'"';
			$letter['html'] = '<b>'.$form['Title'].'</b><br/><br/>';
			$letter['html'] .= str_replace("\r\n",'<br/>',$request);
			$mail=new Mail();
			$mail->SendMailFromSite($adminEmail, $letter['subject'], $letter['html']);
										
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

	function MassEmail(){
		$emailTemplate = LoadTemplate('email/email_ny_2022');
		if ($emailTemplate) {
			$mail = new Mail();

			$subject = 'Proudly — С Наступающим 2022! 🎉';
			$this->db->query('INSERT INTO email_campaigns SET Title = ?s, DateTime = NOW(), Finished = 0', $subject);
			$campaignId = $this->db->insertId();

			$subscribersList = $this->db->getAll('SELECT Id, Email FROM email_contacts WHERE Deleted = 0 ORDER BY IF (`Order`, -1000/`Order`, 0)');
			foreach ($subscribersList as $subscriber) {
				$mail->SendMailFromSite($subscriber['Email'], $subject, strtr($emailTemplate, array(
					'<%C1%>' => $campaignId,
					'<%C2%>' => $subscriber['Id'],
				)));
				$this->db->query('INSERT INTO rel_campaign_contacts SET CampaignId = ?i, ContactId = ?i, DateTime = NOW()', $campaignId, $subscriber['Id']);
				sleep(5);
			}
			$this->db->query('UPDATE email_campaigns SET Finished = 1 WHERE Id = ?i', $campaignId);

			$status = 'SUCCESS: E-mail has sended';
		} else {
			$status = 'ERROR: Template not found';
		}			

		return $status;
	}

	function EmailCountView() {
		$campaignId = $_GET['c1'];
		$contactId = $_GET['c2'];
		$this->db->query('UPDATE rel_campaign_contacts SET Seen = 1 WHERE CampaignId = ?i AND ContactId = ?i', $campaignId, $contactId);

		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
	}

	function EmailRedirect() {
		$campaignId = $_GET['c1'];
		$contactId = $_GET['c2'];

		$this->db->query('UPDATE rel_campaign_contacts SET LinkVisited = 1 WHERE CampaignId = ?i AND ContactId = ?i', $campaignId, $contactId);

		__Redirect('https://proudly.ru/');
		return true;
	}

	function Unsubscribe() {
		$contactId = $_GET['id'];

		$this->db->query('UPDATE email_contacts SET Deleted = 1 WHERE Id = ?i', $contactId);
		return 'Вы успешно отписались от рассылки';
	}

}

?>