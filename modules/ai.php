<?php

krnLoadLib('settings');

// Страница /ai/ — ИИ-решения
class ai extends krn_abstract{

	public function __construct(){
		parent::__construct();
		$this->pageTitle='ИИ-решения для бизнеса: ИИ-агенты, чат-боты, автоматизация — Proudly';
		$this->description='Встраиваем ИИ-агентов в сайт, CRM и 1С. Пилот за 2–3 недели, поддержка после запуска. Proudly, Казань.';
	}

	public function GetResult(){
		$Blocks=krnLoadModuleByName('blocks');

		// пункты меню ведут на якоря главной, а здесь мы уже не на ней
		$menu=str_replace('href="#','href="/?#',$Blocks->MenuMain());

		$result=krnLoadPageByTemplate('base_ai');
		$result=strtr($result,array(
			'<%META_KEYWORDS%>'		=> 'ИИ-агенты, чат-боты, автоматизация бизнеса, внедрение ИИ',
			'<%META_DESCRIPTION%>'	=> $this->description,
			'<%PAGE_TITLE%>'		=> $this->pageTitle,
			'<%MN_MAIN%>'			=> $menu,
			'</head>'				=> '<link type="text/css" rel="stylesheet" href="assets/css/ai.css?v='.filemtime(ROOT_PATH.'assets/css/ai.css').'"/></head>',
		));
		return $result;
	}

}
?>
