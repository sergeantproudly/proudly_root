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
	
	/** Knowbase */
	function OnAddKnowbase($newRecord) {
		if (!$newRecord['Code']) {
			krnLoadLib('chars');
			$code = mb_strtolower(chrTranslit($newRecord['Title']));
			$code = strtr($code,array(','=>'','.'=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>'','"'=>'',"'"=>''));
			while (dbGetValueFromDb('SELECT COUNT(Id) FROM kb_things WHERE `Code`="' . $code . '"')) {
				$code .= '_';
			}
			dbDoQuery('UPDATE kb_things SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		} else {
			$code = $newRecord['Code'];
		}
	}
	
	function OnEditKnowbase($newRecord,$oldRecord){
		if (!$newRecord['Code']) {
			krnLoadLib('chars');
			$code = mb_strtolower(chrTranslit($newRecord['Title']));
			$code = strtr($code,array(','=>'','.'=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>'','"'=>'',"'"=>''));
			while (dbGetValueFromDb('SELECT COUNT(Id) FROM kb_things WHERE `Code`="' . $code . '"')) {
				$code .= '_';
			}
			dbDoQuery('UPDATE kb_things SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		} else {
			$code = $newRecord['Code'];
		}
	}

	/** Knowbase tag */
	function OnAddKnowbaseTag($newRecord) {
		if (!$newRecord['Code']) {
			krnLoadLib('chars');
			$code = mb_strtolower(chrTranslit($newRecord['Title']));
			$code = strtr($code,array(','=>'','.'=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>'','"'=>'',"'"=>''));
			while (dbGetValueFromDb('SELECT COUNT(Id) FROM kb_tags WHERE `Code`="' . $code . '"')) {
				$code .= '_';
			}
			dbDoQuery('UPDATE kb_tags SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		} else {
			$code = $newRecord['Code'];
		}
	}
	
	function OnEditKnowbaseTag($newRecord,$oldRecord){
		if (!$newRecord['Code']) {
			krnLoadLib('chars');
			$code = mb_strtolower(chrTranslit($newRecord['Title']));
			$code = strtr($code,array(','=>'','.'=>'',' '=>'_','*'=>'','!'=>'','?'=>'','@'=>'','#'=>'','$'=>'','%'=>'','^'=>'','('=>'',')'=>'','+'=>'','-'=>'_','«'=>'','»'=>'','—'=>'',':'=>'',';'=>'','ь'=>'','"'=>'',"'"=>''));
			while (dbGetValueFromDb('SELECT COUNT(Id) FROM kb_tags WHERE `Code`="' . $code . '"')) {
				$code .= '_';
			}
			dbDoQuery('UPDATE kb_tags SET `Code`="'.$code.'", LastModTime='.time().' WHERE Id='.$newRecord['Id'],__FILE__,__LINE__);
		} else {
			$code = $newRecord['Code'];
		}
	}
}

?>