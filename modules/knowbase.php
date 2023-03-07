<?php

krnLoadLib('settings');

class knowbase extends krn_abstract{

	protected $keyword;

	public function __construct(){
		parent::__construct();
		$this->folder = $this->db->getRow('SELECT Title, Content, SeoTitle, SeoKeywords, SeoDescription FROM static_pages WHERE Code = ?s', $_GET['p_code']);

		$this->keyword = $_GET['keyword'];
		
		global $Config;
		$this->pageTitle = $this->folder['Title'];
	}	

	public function GetResult(){
		global $Config;
		$Blocks = krnLoadModuleByName('blocks');
		if ($_GET['r_code']) {
			$result = krnLoadPage();
			$result = SetContent($result, $this->GetThing());

		} else {
			$result = krnLoadPageByTemplate('base_knowbase');
			$result=strtr($result,array(
				'<%META_KEYWORDS%>'		=> $this->folder['Keywords']?$this->folder['Keywords']:$Config['Site']['Keywords'],
				'<%META_DESCRIPTION%>'	=> $this->folder['Description']?$this->folder['Description']:$Config['Site']['Description'],
				'<%PAGE_TITLE%>'		=> $this->pageTitle,
				'<%KEYWORD%>' => $this->keyword,
				'<%CONTENT%>' => $this->GetThings(),
			));
		}
		return $result;
	}

	public function GetThings() {
		$element = LoadTemplate('knowbase_things_el');
		$element2 = LoadTemplate('knowbase_things_el2');
		$content = '';

		if ($this->keyword) {
			$recs = $this->db->getAll('SELECT t.Id, t.Title, t.Image600_600 AS Image, t.Video, t.Code 
				FROM kb_things t 
				LEFT JOIN kb_tags_to_things tg2t ON tg2t.ThingId = t.Id 
				LEFT JOIN kb_tags tg ON tg.Id = tg2t.TagId 
				LEFT JOIN kb_tags_alias tga ON tga.TagId = tg.Id 
				WHERE t.Title LIKE ?s OR tg.Title LIKE ?s OR tga.Title LIKE ?s 
				GROUP BY t.Id 
				ORDER BY t.Date DESC', '%' . $this->keyword . '%', '%' . $this->keyword . '%', '%' . $this->keyword . '%');
		} else {
			$recs = $this->db->getAll('SELECT Id, Title, Image600_600 AS Image, Video, Code FROM kb_things ORDER BY Date DESC');
		}
		foreach ($recs as $rec) {
			$rec['Alt'] = htmlspecialchars($rec['Title'], ENT_QUOTES);
			$rec['Link'] = '/knowbase/' . $rec['Code'] . '/';
			if ($rec['Video']) {
				$content .= SetAtribs($element2, $rec);
			} else {
				$content .= SetAtribs($element, $rec);
			}
		}

		return $content;
	}

	public function GetThing() {
		$code = $_GET['r_code'];

		$rec = $this->db->getRow('SELECT Id, Title, Image AS ImageFull, Image600_600 AS Image, Video, Text FROM kb_things WHERE Code = ?s', $code);
		$tags = $this->db->getAll('SELECT Title, Code FROM kb_tags_to_things t2t LEFT JOIN kb_tags t ON t2t.TagId = t.Id WHERE ThingId = ?i', $rec['Id']);
		$rec['Tags'] = '';
		foreach ($tags as $tag) {
			$rec['Tags'] .= (!empty($rec['Tags']) ? ', ' : '') . '<a href="/knowbase-keyword-' . $tag['Title'] . '/">#' . $tag['Title'] . '</a>';
		}
		$rec['Alt'] = htmlspecialchars($rec['Title'], ENT_QUOTES);
		if ($rec['Video']) {
			$rec['Poster'] = SetAtribs(LoadTemplate('knowbase_thing_video'), $rec);
		} else {
			$rec['Poster'] = SetAtribs(LoadTemplate('knowbase_thing_photo'), $rec);
		}

		$result = SetAtribs(LoadTemplate('knowbase_thing'), $rec);
		$result = strtr($result, array(
			'<%KEYWORD%>' => $this->keyword,
		));
		return $result;
	}

}
?>