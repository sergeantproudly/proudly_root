<?php

	/**
	 * Типограф: расставляет неразрывные пробелы в тексте готовой HTML-страницы.
	 * Правит только текстовые узлы: теги, атрибуты, script/style/textarea/pre/code/svg/title не трогаются.
	 *
	 *  - предлоги, союзы и частицы не остаются в конце строки («в&nbsp;слове»);
	 *  - частицы «ли», «же», «бы» не отрываются от предыдущего слова;
	 *  - перед тире ставится неразрывный пробел, « - » между пробелами становится «—»;
	 *  - число не отрывается от единицы измерения («5&nbsp;лет», «100&nbsp;₽»), «№», «г.», «ул.» — от значения;
	 *  - инициалы («А.&nbsp;С.&nbsp;Пушкин») не разрываются.
	 */

	// слова, которые «прилипают» к следующему
	define('TYPOGRAF_GLUE_WORDS', 'в|во|к|ко|с|со|у|о|об|обо|от|ото|до|по|на|за|из|изо|без|над|под|про|при|для|или|и|а|но|да|не|ни|то|что');
	// частицы, которые «прилипают» к предыдущему
	define('TYPOGRAF_PARTICLES', 'ли|же|бы');
	// единицы измерения после числа
	define('TYPOGRAF_UNITS', '₽|руб|тыс|млн|млрд|шт|кг|км|мм|см|мес|дн|дней|дня|день|ч|мин|сек|лет|года|год|г|раз|раза|человек|%');

	function typografText($text){
		$nb = "\xC2\xA0";
		// уже расставленные &nbsp; считаем обычными неразрывными пробелами
		$text = str_replace('&nbsp;', $nb, $text);

		// тире: « - » -> « — », перед тире неразрывный пробел
		$text = preg_replace('/(?<=\S) +[-–] +(?=\S)/u', $nb.'— ', $text);
		$text = preg_replace('/ +(—|&mdash;|&#151;|&#8212;|&ndash;|–)/u', $nb.'$1', $text);

		// инициалы: «А. С. Пушкин»
		$text = preg_replace('/(?<![\p{L}])([А-ЯЁ]\.) +(?=[А-ЯЁ]\.)/u', '$1'.$nb, $text);
		$text = preg_replace('/(?<![\p{L}])([А-ЯЁ]\.) +(?=[А-ЯЁ][а-яё])/u', '$1'.$nb, $text);

		// сокращения и знак номера
		$text = preg_replace('/(?<![\p{L}])(№|г\.|гг\.|ул\.|д\.|стр\.|оф\.|пр\.|пер\.|тел\.|ИНН|ОГРН) +(?=[\p{L}\p{N}])/u', '$1'.$nb, $text);

		// число + единица
		$text = preg_replace('/(\d) +(?=('.TYPOGRAF_UNITS.')(?![\p{L}]))/u', '$1'.$nb, $text);

		// предлоги и союзы (дважды — для цепочек вроде «и в офисе»)
		$glue = '/(?<![\p{L}\p{N}_\-])('.TYPOGRAF_GLUE_WORDS.') +(?=\S)/iu';
		$text = preg_replace($glue, '$1'.$nb, $text);
		$text = preg_replace($glue, '$1'.$nb, $text);

		// частицы
		$text = preg_replace('/ +(?=('.TYPOGRAF_PARTICLES.')(?![\p{L}\p{N}_\-]))/iu', $nb, $text);

		$text = str_replace($nb, '&nbsp;', $text);

		// слова с коротким началом до дефиса («e-mail», «по-моему», «1С-Битрикс») не переносятся по частям;
		// предлог перед ними входит в ту же обёртку (иначе браузер может оторвать его на границе тега)
		$text = preg_replace('/(?<![\p{L}\p{N}_\-])((?:\p{L}{1,3}&nbsp;)?[\p{L}\p{N}]{1,3}-[\p{L}\p{N}]+)(?![\p{L}\p{N}_\-])/u', '<span style="white-space:nowrap">$1</span>', $text);

		return $text;
	}

	function typografHtml($html){
		if(!is_string($html) || $html === '') return $html;

		$skip = 'script|style|textarea|pre|code|svg|title|select|option|noscript';
		$parts = preg_split('~(<!--.*?-->|<(?:'.$skip.')\b.*?</(?:'.$skip.')>|<[^>]+>)~isu', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
		if($parts === false) return $html;

		foreach($parts as $i => $part){
			// чётные элементы — текст между тегами
			if($i % 2 === 0 && trim($part) !== '') $parts[$i] = typografText($part);
		}
		return implode('', $parts);
	}

?>
