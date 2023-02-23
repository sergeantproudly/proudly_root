<?php

krnLoadLib('settings');
krnLoadLib('amo');

class amo extends krn_abstract {
	public function __construct() {
		parent::__construct();
	}

	public function GetResult() {
		AmoApi::PrintInfo();
	}

	

	public function Authorize() {

	}

}
?>