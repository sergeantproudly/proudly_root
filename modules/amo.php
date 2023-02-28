<?php

krnLoadLib('settings');
krnLoadLib('amo');

class amo extends krn_abstract {
	public function __construct() {
		parent::__construct();
	}

	public function GetResult() {
		AmoApi::SetLogLevel(AmoApi::LOGLEVELMAX);
		//AmoApi::PrintInfo();
		AmoApi::SendData([
			'name' => 'Новая заявка: Роман',
			'phone' => '+79872608688',
			'email' => 'sgtpepper2000@yandex.ru',
			'text' => 'Текстовое сообщение',
			'form_id' => 1,
			'form_name' => 'Форма заяки',
			'page_name' => 'Главная',
		]);
	}

}
?>