<?php

krnLoadLib('mail');
krnLoadLib('settings');
// krnLoadLib('amo'); // интеграция с amoCRM отключена

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

	// вложения формы: до 5 файлов, 10 МБ на файл, 25 МБ суммарно
	const FEEDBACK_MAX_FILES = 5;
	const FEEDBACK_MAX_FILE_SIZE = 10485760;
	const FEEDBACK_MAX_TOTAL_SIZE = 26214400;
	const FEEDBACK_MAIL_ATTACH_LIMIT = 5242880;

	// допустимые расширения => допустимые типы содержимого (по finfo)
	private function FeedbackAllowedTypes(){
		$ole = array('application/msword', 'application/vnd.ms-office', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/CDFV2', 'application/x-ole-storage', 'application/octet-stream');
		$ooxml = array('application/zip', 'application/x-zip-compressed', 'application/octet-stream', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats-officedocument.presentationml.presentation');
		$text = array('text/plain', 'text/csv', 'application/csv', 'text/x-csv', 'application/x-empty', 'inode/x-empty');
		return array(
			'pdf' => array('application/pdf'),
			'doc' => $ole, 'xls' => $ole, 'ppt' => $ole,
			'docx' => $ooxml, 'xlsx' => $ooxml, 'pptx' => $ooxml,
			'png' => array('image/png'),
			'webp' => array('image/webp'),
			'svg' => array('image/svg+xml', 'image/svg', 'text/xml', 'text/plain', 'application/xml'),
			'jpg' => array('image/jpeg'), 'jpeg' => array('image/jpeg'),
			'txt' => $text, 'csv' => $text,
			'rtf' => array('text/rtf', 'application/rtf', 'text/plain'),
		);
	}

	private static function FeedbackTranslitMap(){
		return array('а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya');
	}

	// проверяет и сохраняет загруженные файлы; возвращает array('files' => [...], 'error' => '...')
	private function FeedbackSaveFiles(){
		$result = array('files' => array(), 'error' => '');
		if (empty($_FILES['files']) || !is_array($_FILES['files']['name'])) return $result;

		$list = array();
		foreach ($_FILES['files']['name'] as $i => $origName) {
			if ($_FILES['files']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
			$list[] = array(
				'name' => (string)$origName,
				'tmp' => $_FILES['files']['tmp_name'][$i],
				'size' => (int)$_FILES['files']['size'][$i],
				'error' => $_FILES['files']['error'][$i],
			);
		}
		if (!$list) return $result;

		if (count($list) > self::FEEDBACK_MAX_FILES) {
			$result['error'] = 'Можно прикрепить не больше ' . self::FEEDBACK_MAX_FILES . ' файлов.';
			return $result;
		}

		$allowed = $this->FeedbackAllowedTypes();
		$finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
		$total = 0;
		foreach ($list as $k => $f) {
			if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp'])) {
				$result['error'] = ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE)
					? 'Файл «' . $f['name'] . '» слишком большой. Максимум 10 МБ на файл.'
					: 'Не удалось загрузить файл «' . $f['name'] . '». Попробуйте ещё раз.';
				return $result;
			}
			if ($f['size'] > self::FEEDBACK_MAX_FILE_SIZE) {
				$result['error'] = 'Файл «' . $f['name'] . '» слишком большой. Максимум 10 МБ на файл.';
				return $result;
			}
			$total += $f['size'];
			$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
			if (!isset($allowed[$ext])) {
				$result['error'] = 'Файл «' . $f['name'] . '» этого формата принять не можем. Подойдут PDF, Word, Excel, PowerPoint, PNG, JPG, WebP, SVG и текстовые файлы.';
				return $result;
			}
			if ($finfo) {
				$mime = finfo_file($finfo, $f['tmp']);
				if (!in_array($mime, $allowed[$ext], true)) {
					$result['error'] = 'Содержимое файла «' . $f['name'] . '» не соответствует формату .' . $ext . '.';
					return $result;
				}
			}
			// SVG может содержать скрипты: такие не принимаем
			if ($ext == 'svg' && preg_match('/<script|javascript:|\bon[a-z]+\s*=|<foreignObject|<iframe|<!ENTITY/i', file_get_contents($f['tmp'], false, null, 0, self::FEEDBACK_MAX_FILE_SIZE))) {
				$result['error'] = 'SVG-файл «' . $f['name'] . '» содержит скрипты, такой принять не можем. Пришлите его в PNG или PDF.';
				return $result;
			}
			$list[$k]['ext'] = $ext;
		}
		if ($finfo) finfo_close($finfo);
		if ($total > self::FEEDBACK_MAX_TOTAL_SIZE) {
			$result['error'] = 'Общий размер файлов больше 25 МБ. Прикрепите меньше файлов или пришлите ссылку.';
			return $result;
		}

		// папка с запретом исполнения скриптов; у каждой заявки своя папка со случайным именем
		$base = ROOT_PATH . 'uploads/requests/';
		if (!is_dir($base)) @mkdir($base, 0755, true);
		if (is_dir($base) && !file_exists($base . '.htaccess')) {
			@file_put_contents($base . '.htaccess', "Options -Indexes -ExecCGI\nRemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phps\nRemoveType .php .phtml\n<IfModule mod_php7.c>\nphp_flag engine off\n</IfModule>\n<IfModule mod_headers.c>\nHeader set X-Content-Type-Options nosniff\nHeader set Content-Disposition attachment\n</IfModule>\n");
		}
		$dirName = date('Ymd') . '-' . bin2hex(random_bytes(12));
		$dir = $base . $dirName . '/';
		if (!@mkdir($dir, 0755, true)) {
			$result['error'] = 'Не удалось сохранить файлы. Попробуйте ещё раз или напишите нам на почту.';
			return $result;
		}

		$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		foreach ($list as $n => $f) {
			$baseName = pathinfo($f['name'], PATHINFO_FILENAME);
			$safe = strtr(mb_strtolower($baseName, 'UTF-8'), self::FeedbackTranslitMap());
			$safe = preg_replace('/[^a-z0-9_-]+/', '_', $safe);
			$safe = trim(substr($safe, 0, 60), '_-');
			if ($safe === '') $safe = 'file';
			$fileName = ($n + 1) . '-' . $safe . '.' . $f['ext'];
			if (!@move_uploaded_file($f['tmp'], $dir . $fileName)) {
				$result['error'] = 'Не удалось сохранить файл «' . $f['name'] . '».';
				return $result;
			}
			$result['files'][] = array(
				'name' => $f['name'],
				'path' => $dir . $fileName,
				'size' => $f['size'],
				'url' => $scheme . '://' . $_SERVER['HTTP_HOST'] . '/uploads/requests/' . $dirName . '/' . rawurlencode($fileName),
			);
		}
		return $result;
	}

	// отправляет уведомление в Telegram уже после ответа клиенту (api.telegram.org с хостинга отвечает нестабильно)
	private function FeedbackTelegramLater($title, $name, $tel, $text, $files, $page){
		$self = $this;
		register_shutdown_function(function() use ($self, $title, $name, $tel, $text, $files, $page) {
			if (function_exists('session_write_close')) @session_write_close();
			if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request();
			$self->FeedbackTelegram($title, $name, $tel, $text, $files, $page);
		});
	}

	// настройки в таблице settings (админка): TelegramNotifierToken, TelegramNotifierChatId (можно несколько через запятую),
	// TelegramNotifierProxy (необязательно, например socks5://host:port — если api.telegram.org недоступен с хостинга)
	public function FeedbackTelegram($title, $name, $tel, $text, $files, $page){
		$token = trim((string)stGetSetting('TelegramNotifierToken'));
		$chats = array_filter(array_map('trim', explode(',', (string)stGetSetting('TelegramNotifierChatId'))));
		$proxy = trim((string)stGetSetting('TelegramNotifierProxy'));
		if (!$token || !$chats) return false;

		$esc = function($v){ return htmlspecialchars((string)$v, ENT_NOQUOTES, 'UTF-8'); };
		$msg = "ð <b>" . $esc($title ? $title : 'Заявка') . "</b> â proudly.ru

";
		$msg .= "Имя: " . $esc($name) . "
";
		$msg .= "Телефон: " . $esc($tel) . "
";
		if (trim((string)$text) !== '') $msg .= "Текст: " . $esc(mb_substr($text, 0, 2500)) . "
";
		if ($files) $msg .= "
Файлы:
" . $esc(implode("
", $files)) . "
";
		if ($page) $msg .= "
Страница: " . $esc($page);

		$ok = true;
		foreach ($chats as $chat) {
			$sent = false;
			for ($try = 1; $try <= 3 && !$sent; $try++) {
				$ch = curl_init('https://api.telegram.org/bot' . $token . '/sendMessage');
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, array('chat_id' => $chat, 'text' => $msg, 'parse_mode' => 'HTML', 'disable_web_page_preview' => 'true'));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
				curl_setopt($ch, CURLOPT_TIMEOUT, 8);
				if ($proxy) curl_setopt($ch, CURLOPT_PROXY, $proxy);
				$out = curl_exec($ch);
				$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
				$err = curl_error($ch);
				curl_close($ch);
				if ($code === 200) { $sent = true; break; }
				// 4xx (неверный токен, чат не найден) повторять бессмысленно
				if ($code >= 400 && $code < 500) { $this->FeedbackTelegramLog('HTTP ' . $code . ' ' . $out); break; }
				$this->FeedbackTelegramLog('try ' . $try . ': HTTP ' . $code . ' ' . $err);
			}
			if (!$sent) $ok = false;
		}
		return $ok;
	}

	private function FeedbackTelegramLog($text){
		@file_put_contents(ROOT_PATH . 'telegram.log', '[' . date('d.m.Y H:i:s') . '] ' . $text . "
", FILE_APPEND);
	}

	function Feedback(){
		$email = '';
		$name = trim($_POST['name']);
		$tel = trim($_POST['tel']);
		$text = $_POST['text'];
		$code = $_POST['code'];

		$capcha = $_POST['capcha'];

		// превышен post_max_size: PHP отбрасывает всё тело запроса
		if (empty($_POST) && empty($_FILES) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
			return json_encode(array('status' => false, 'message' => 'Файлы слишком большие. Общий размер не должен превышать 25 МБ.'));
		}

		// ловушка для ботов: поле скрыто от людей
		if (!empty($_POST['website'])) {
			return json_encode(array('status' => true, 'message' => ''));
		}

		// не больше 5 заявок с вложениями за 10 минут с одной сессии
		if (!empty($_FILES['files'])) {
			$now = time();
			$hits = isset($_SESSION['feedback_hits']) ? array_filter($_SESSION['feedback_hits'], function($t) use ($now) { return $t > $now - 600; }) : array();
			if (count($hits) >= 5) {
				return json_encode(array('status' => false, 'message' => 'Слишком много заявок подряд. Попробуйте чуть позже или напишите нам на почту.'));
			}
			$hits[] = $now;
			$_SESSION['feedback_hits'] = $hits;
		}

		// проверка на спамбота
		// основывается на сверке user agent-ов
		if ($capcha == $_SERVER['HTTP_USER_AGENT']) {
			if ($name && $tel) {				
				$saved = $this->FeedbackSaveFiles();
				if ($saved['error']) {
					return json_encode(array('status' => false, 'message' => $saved['error']));
				}
				$form = $this->db->getRow('SELECT Id, Title, Success FROM forms WHERE Code=?s', $code);				
				$request = '';
				if ($name) $request .= "Имя: $name\r\n";
				if ($email) $request .= "E-mail: $email\r\n";
				if ($tel) $request .= "Телефон: $tel\r\n";
				if ($text) $request .= 'Текст:'."\r\n$text\r\n";
				$attachPaths = array();
				if ($saved['files']) {
					$attachTotal = 0;
					$request .= "Файлы:\r\n";
					foreach ($saved['files'] as $file) {
						$request .= $file['name'] . ' (' . ($file['size'] >= 1048576 ? round($file['size'] / 1048576, 1) . ' МБ' : max(1, round($file['size'] / 1024)) . ' КБ') . '): ' . $file['url'] . "\r\n";
						$attachTotal += $file['size'];
					}
					if ($attachTotal <= self::FEEDBACK_MAIL_ATTACH_LIMIT) {
						foreach ($saved['files'] as $file) $attachPaths[] = $file['path'];
					}
				}
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
				$mail->SendMailFromSite($adminEmail, $letter['subject'], $letter['html'], $attachPaths);

				// Интеграция с amoCRM отключена (неактуальна), вместо неё уведомление в Telegram:
				/*
				if ($name) $data['name'] = $name;
				if ($tel) $data['phone'] = $tel;
				if ($email) $data['email'] = $email;
				$data['form_id'] = $form['Id'];
				$data['form_name'] = $form['Title'];
				$data['page_name'] = 'Главая страница';
				AmoApi::SetLogLevel(AmoApi::LOGLEVELMAX);
				AmoApi::SendData($data);
				*/

				$tgFiles = array();
				foreach ($saved['files'] as $file) $tgFiles[] = $file['name'] . ': ' . $file['url'];
				$this->FeedbackTelegramLater($form['Title'], $name, $tel, $text, $tgFiles, isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');

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
		} else {
			$json = array(
				'status' => false,
				'message' => 'Сработал антиспам. При повторном возникновении, пожалуйста, обратитесь к администратору.'
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

		header('Content-Type: image/png');
		return LoadFile(IMAGE_DIR . 'mailservice.png');
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