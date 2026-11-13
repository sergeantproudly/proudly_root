<?php

krnLoadLib('settings');
krnLoadLib('ai');

// чат с ИИ-ассистентом: POST /assistant-act-Ask/ (q — вопрос), POST /assistant-act-Reset/ — начать разговор заново
class assistant extends krn_abstract{

	function __construct(){
		parent::__construct();
	}

	function GetResult(){
		header('HTTP/1.1 404 Not Found');
		exit;
	}

	private function Json($data){
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-store');
		return json_encode($data, JSON_UNESCAPED_UNICODE);
	}

	// запрос пришёл со страницы этого же сайта
	private function SameOrigin(){
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		foreach (array('HTTP_ORIGIN', 'HTTP_REFERER') as $h) {
			if (empty($_SERVER[$h])) continue;
			return parse_url($_SERVER[$h], PHP_URL_HOST) === preg_replace('/:\d+$/', '', $host);
		}
		return false;
	}

	private function &Conversation(){
		if (empty($_SESSION['ai_chat']) || !is_array($_SESSION['ai_chat'])) {
			$_SESSION['ai_chat'] = array('id' => bin2hex(random_bytes(8)), 'turns' => 0, 'history' => array());
		}
		return $_SESSION['ai_chat'];
	}

	function Reset(){
		unset($_SESSION['ai_chat']);
		return $this->Json(array('status' => true));
	}

	const MAX_LEADS = 3;   // заявок из текста чата за разговор

	protected function SendLead($name, $tel, $text, $page){
		$ajax = krnLoadModuleByName('ajax');
		$ajax->SaveRequest('ai-chat', $name, $tel, $text, array(), $page);
	}

	// заявка из сообщения с телефоном: уходит тем же путём, что формы сайта (requests, письмо, Telegram)
	private function ChatLead($ai, &$conv, $q, $masked, $tel, $page){
		if (empty($conv['leads'])) $conv['leads'] = array();
		$name = AiAssistant::FindName($q);
		if ($name) $conv['name'] = $name;
		elseif (!empty($conv['name'])) $name = $conv['name'];

		$res = array('answer' => '', 'ids' => array(), 'action' => 'none', 'link' => null, 'status' => AiAssistant::STATUS_LEAD, 'provider' => '', 'tokens' => 0);
		if (in_array($tel, $conv['leads'], true)) {
			$res['answer'] = 'Этот номер уже передали менеджеру, он перезвонит в течение рабочего дня.';
		} elseif (count($conv['leads']) >= self::MAX_LEADS) {
			$res['answer'] = 'Заявку из этого разговора менеджер уже получил и скоро свяжется с вами.';
		} else {
			$lines = array();
			foreach ($conv['history'] as $h) $lines[] = ($h['role'] == 'user' ? 'Посетитель: ' : 'Ассистент: ') . $h['text'];
			$text = "Номер оставлен в сообщении чата на сайте.\n\nСообщение: " . $q . ($lines ? "\n\nРазговор до этого:\n" . implode("\n", $lines) : '');
			$brief = AiAssistant::BriefText(isset($conv['brief']) ? $conv['brief'] : null);
			if ($brief) $text .= "\n\n" . $brief;
			$this->SendLead($name ? $name : 'Посетитель чата', $tel, $text, $page);
			$conv['leads'][] = $tel;
			$res['answer'] = 'Принято' . ($name ? ', ' . $name : '') . '! Менеджер перезвонит вам по номеру ' . $tel . ' в течение рабочего дня.';
		}

		$conv['turns']++;
		$conv['history'][] = array('role' => 'user', 'text' => $masked);
		$conv['history'][] = array('role' => 'assistant', 'text' => AiAssistant::Mask($res['answer']));
		$conv['history'] = array_slice($conv['history'], -AiAssistant::HISTORY_MESSAGES);
		$logged = $res;
		$logged['answer'] = AiAssistant::Mask($res['answer']);
		$ai->Log($conv['id'], $masked, $logged, 0, $page);

		return $this->Json(array('status' => true, 'answer' => $res['answer'], 'action' => 'none', 'lead_sent' => true));
	}

	// ---------- бриф: предложение, согласие, вопросы по порядку (ход брифа — в $conv['brief']) ----------

	const BRIEF_OFFER = 'Если удобно, могу задать несколько вопросов по брифу: так менеджер сразу подготовит предложение. Или можно сразу оставить телефон.';
	const BRIEF_YES = 'Да, давайте';
	const BRIEF_NO = 'Лучше оставлю телефон';

	private static function Typo($text){
		return AiAssistant::Typograf($text);
	}

	// прогресс брифа для подписи под ответом
	private static function BriefProgress($conv){
		if (empty($conv['brief'])) return null;
		$b = $conv['brief'];
		return array('done' => count($b['answers']) + count($b['skipped']), 'total' => count(AiAssistant::BriefQuestions($b['type'])), 'active' => (bool)AiAssistant::BriefActive($b));
	}

	// ответ без нейросети (начало брифа, отказ от него): в журнал и историю, как обычный
	private function LocalReply($ai, &$conv, $masked, $answer, $action, $page){
		$res = array('answer' => $answer, 'ids' => array(), 'action' => $action, 'link' => null, 'status' => AiAssistant::STATUS_OK, 'provider' => '', 'tokens' => 0);
		$ai->Log($conv['id'], $masked, $res, 0, $page);
		$conv['turns']++;
		$conv['history'][] = array('role' => 'user', 'text' => $masked);
		$conv['history'][] = array('role' => 'assistant', 'text' => $answer);
		$conv['history'] = array_slice($conv['history'], -AiAssistant::HISTORY_MESSAGES);
		$out = array('status' => true, 'answer' => $answer, 'action' => $action);
		if ($p = self::BriefProgress($conv)) $out['brief'] = $p;
		return $this->Json($out);
	}

	// ассистент предложил бриф: посетитель согласился, отказался или написал что-то своё (тогда предложение снимается)
	private function BriefReply($ai, &$conv, $masked, $page){
		$type = $conv['brief_offer'];
		$conv['brief_offer'] = '';
		$yes = '/^\s*(да|ага|давай|давайте|ок|окей|хорошо|можно|конечно|го|поехали|задавай|задавайте|согласен|согласна|начн[её]м|готов|готова)(?!\p{L})/iu';
		$no = '/(телефон|позвон|перезвон|не надо|не хочу|не буду|потом|некогда|^\s*нет(?!\p{L}))/iu';
		if (preg_match($yes, $masked) && !preg_match($no, $masked)) {
			$brief = array('type' => $type, 'answers' => array(), 'skipped' => array(), 'asked' => 1, 'stopped' => false);
			// тип сайта посетитель уже называл: первый вопрос («Какой сайт нужен») не задаём, берём его слова
			if ($type === 'site') {
				foreach ($conv['history'] as $h) {
					if ($h['role'] == 'user' && preg_match('/лендинг|корпоративн|интернет-магазин|магазин|портал|сервис|личн\S* кабинет|визитк/ui', $h['text'])) $brief['answers'][1] = $h['text'];
				}
			}
			$left = AiAssistant::BriefLeft($brief);
			$brief['asked'] = $left[0];
			$conv['brief'] = $brief;
			$qs = AiAssistant::BriefQuestions($type);
			return $this->LocalReply($ai, $conv, $masked, self::Typo("Отлично, тогда начнём, вопросов всего несколько. Если на какой-то нет ответа, просто напишите «не знаю».\n\n" . $qs[$brief['asked']]), 'none', $page);
		}
		if (preg_match($no, $masked)) {
			return $this->LocalReply($ai, $conv, $masked, self::Typo('Конечно! Оставьте телефон в форме ниже или просто напишите номер сюда — менеджер перезвонит.'), 'lead', $page);
		}
		return null;
	}

	// реакция нейросети на ответ брифа: без «Следующий вопрос: …» и без своих вопросов в конце — следующий вопрос брифа добавит сервер
	private static function CleanReaction($text){
		$text = trim(preg_replace('/\s*(следующий|далее|теперь|ещё один|еще один)\s+вопрос.*$/uis', '', trim($text)));
		$parts = preg_split('/(?<=[.!?])\s+/u', $text);
		while ($parts && preg_match('/\?$|расскаж|подскаж|уточнит|поделит|опишите/ui', end($parts))) array_pop($parts);
		// дежурные «Продолжаем.», «Продолжим!», «Двигаемся дальше.» убираем: без них реакция живее
		$parts = array_filter($parts, function($s){ return !preg_match('/^(итак,?\s*)?(продолжа|продолжи|двигаемся|идём дальше|идем дальше|едем дальше|поехали дальше)/ui', trim($s)); });
		$text = trim(implode(' ', $parts));
		return preg_replace('/,?\s*(продолжаем|продолжим)[.!]?$/ui', '.', $text);
	}

	// какая услуга нужна клиенту — по словам в его сообщениях (если нейросеть сама не отметила)
	private static function GuessNeed($conv, $masked){
		$text = $masked;
		foreach ($conv['history'] as $h) if ($h['role'] == 'user') $text .= ' ' . $h['text'];
		$map = array(
			'brand' => '/логотип|фирменн|брендинг|брендбук|айдентик/ui',
			'promo' => '/продвиж|реклам|\bseo\b|сео|директ|таргет|трафик/ui',
			'site' => '/сайт|лендинг|интернет-магазин|магазин|портал|личный кабинет/ui',
		);
		foreach ($map as $type => $re) if (preg_match($re, $text)) return $type;
		return '';
	}

	// после ответа нейросети: ведём бриф (записываем ответ, добавляем следующий вопрос) или предлагаем его. Возвращает кнопки-подсказки
	private function BriefAfter(&$conv, &$res, $masked){
		$b = isset($conv['brief']) ? $conv['brief'] : null;
		if ($b && AiAssistant::BriefActive($b)) {
			$qs = AiAssistant::BriefQuestions($b['type']);
			$res['status'] = AiAssistant::STATUS_OK;
			$res['action'] = 'none';
			$res['ids'] = array();
			$res['link'] = null;
			$reaction = self::CleanReaction($res['answer']);
			if ($res['brief_stop']) {
				$b['stopped'] = true;
				$res['answer'] = self::Typo('Хорошо, остановимся здесь: ваши ответы передам менеджеру. Оставьте телефон, и он перезвонит с предложением.');
				$res['action'] = 'lead';
			} else {
				$own = mb_strpos($masked, '?') !== false && !$res['brief_answered'] && !$res['brief_skip'];
				if ($res['brief_skip']) $b['skipped'][] = $b['asked'];
				elseif (!$own) $b['answers'][$b['asked']] = $masked;
				foreach ($res['brief_answered'] as $n) {
					if (isset($qs[$n]) && $n != $b['asked'] && !isset($b['answers'][$n])) $b['answers'][$n] = $masked;
				}
				$left = AiAssistant::BriefLeft($b);
				if ($left) {
					$b['asked'] = $left[0];
					$res['answer'] = ($reaction !== '' ? $reaction . "\n\n" : '') . self::Typo(($own ? 'Вернёмся к брифу. ' : '') . $qs[$b['asked']]);
				} else {
					$res['answer'] = ($reaction !== '' ? $reaction . "\n\n" : '') . self::Typo('Спасибо, это все вопросы! Оставьте телефон: менеджер изучит ответы и вернётся с предложением.');
					$res['action'] = 'lead';
				}
			}
			$conv['brief'] = $b;
			return array();
		}
		// клиенту нужна услуга, по которой есть бриф, и цель уже обсуждалась (со второго сообщения): один раз предлагаем бриф
		$need = $res['need'] ? $res['need'] : self::GuessNeed($conv, $masked);
		if (!$b && empty($conv['brief_offered']) && $need && $conv['turns'] >= 2 && $res['status'] === AiAssistant::STATUS_OK && $res['action'] !== 'call' && AiAssistant::BriefQuestions($need)) {
			$conv['brief_offered'] = true;
			$conv['brief_offer'] = $need;
			// встречные вопросы модели в конце («Подскажите…?», «Расскажите…») убираем: вместо них предложение брифа
			$parts = preg_split('/(?<=[.!?])\s+/u', trim($res['answer']));
			while ($parts && preg_match('/\?$|расскаж|подскаж|уточнит|поделит/ui', end($parts))) array_pop($parts);
			$cut = implode(' ', $parts);
			$res['answer'] = ($cut !== '' ? $cut . "\n\n" : '') . self::Typo(self::BRIEF_OFFER);
			$res['action'] = 'none';
			return array(self::BRIEF_YES, self::BRIEF_NO);
		}
		return array();
	}

	function Ask(){
		if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->SameOrigin()) {
			header('HTTP/1.1 403 Forbidden');
			return $this->Json(array('status' => false));
		}

		$q = trim(preg_replace('/\s+/u', ' ', (string)$_POST['q']));
		if ($q === '') return $this->Json(array('status' => false));
		if (mb_strlen($q) > AiAssistant::MAX_LENGTH) {
			return $this->Json(array('status' => true, 'answer' => 'Сообщение получилось длинным: в чат помещается до ' . AiAssistant::MAX_LENGTH . ' символов. Сократите вопрос или оставьте заявку, и менеджер разберётся в задаче.', 'action' => 'lead'));
		}

		$ai = new AiAssistant($this->db);
		$conv = &$this->Conversation();
		$masked = AiAssistant::Mask($q);
		$page = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

		// посетитель оставил номер прямо в сообщении («Перезвоните мне: Роман +7…»): сразу оформляем заявку
		$tel = AiAssistant::FindPhone($q);
		if ($tel) return $this->ChatLead($ai, $conv, $q, $masked, $tel, $page);

		if ($conv['turns'] >= AiAssistant::MAX_TURNS || $ai->HitsFromIp($ip) >= AiAssistant::MAX_PER_IP_HOUR) {
			$res = array('answer' => 'В этом разговоре я ответил на все вопросы, на которые мог. Оставьте контакт, и менеджер продолжит с вами лично.', 'ids' => array(), 'action' => 'lead', 'link' => null, 'status' => AiAssistant::STATUS_LIMIT, 'provider' => '', 'tokens' => 0);
			$ai->Log($conv['id'], $masked, $res, 0, $page);
			return $this->Json(array('status' => true, 'answer' => $res['answer'], 'action' => 'lead', 'limit' => true));
		}

		// ответ на предложение пройти бриф
		if (!empty($conv['brief_offer']) && empty($conv['brief'])) {
			$reply = $this->BriefReply($ai, $conv, $masked, $page);
			if ($reply) return $reply;
		}

		// сессия не нужна на время запроса к нейросети: освобождаем её, чтобы не блокировать другие запросы посетителя
		$history = $conv['history'];
		$brief = isset($conv['brief']) ? $conv['brief'] : null;
		$conv['turns']++;
		$turns = $conv['turns'];
		$id = $conv['id'];
		session_write_close();

		$t = microtime(true);
		$res = $ai->Answer($masked, $history, $brief);
		$ms = (int)round((microtime(true) - $t) * 1000);

		// модель повторила свой прошлый ответ слово в слово: заменяем коротким ответом по действию
		$prev = '';
		foreach ($history as $h) if ($h['role'] == 'assistant') $prev = $h['text'];
		$norm = function($s){ return mb_strtolower(preg_replace('/[\s\x{00A0}]+/u', ' ', trim($s))); };
		if ($prev !== '' && $norm($prev) === $norm($res['answer'])) {
			if ($res['action'] === 'call') $res['answer'] = 'Номер для звонка — ниже, звоните в рабочее время.';
			else {
				$res['answer'] = 'Форма заявки — ниже: оставьте имя и телефон, и менеджер перезвонит. Или напишите номер прямо сюда.';
				$res['action'] = 'lead';
			}
		}

		@session_start();
		$conv = &$this->Conversation();
		$conv['turns'] = max($conv['turns'], $turns);
		$chips = $this->BriefAfter($conv, $res, $masked);
		$ai->Log($id, $masked, $res, $ms, $page);
		$conv['history'][] = array('role' => 'user', 'text' => $masked);
		$conv['history'][] = array('role' => 'assistant', 'text' => $res['answer']);
		$conv['history'] = array_slice($conv['history'], -AiAssistant::HISTORY_MESSAGES);

		$out = array(
			'status' => true,
			'answer' => $res['answer'],
			'action' => $res['action'],
			'link' => $res['link'],
			'left' => max(0, AiAssistant::MAX_TURNS - $turns),
		);
		if ($p = self::BriefProgress($conv)) $out['brief'] = $p;
		if ($chips) $out['chips'] = $chips;
		if ($res['action'] === 'call') {
			$contacts = $ai->Contacts();
			if ($contacts['phone']) $out['phone'] = array('title' => $contacts['phone'], 'href' => 'tel:' . preg_replace('/[^\d\+]/', '', $contacts['phone']));
		}
		return $this->Json($out);
	}
}

?>
