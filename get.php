<?php

header('Content-type: text/html;charset=utf-8');
$startime = microtime(1);
$mem_start = memory_get_usage();
set_time_limit(0);

require dirname(__FILE__).DIRECTORY_SEPARATOR.'Classes'.DIRECTORY_SEPARATOR.'includer.php';

$nameXLS = date("d_m_Y_H_i_").'tui.xls';
$parser = new Parser('http://booking.tui.ru/extra/aviaquotes.aspx');
$parser->fromDateFieldName = 'ctl00$generalContent$MultiTwinDatepicker$TxtMultiDatepickerFrom';
$parser->toDateFieldName = 'ctl00$generalContent$MultiTwinDatepicker$TxtMultiDatepickerTo';

//addition post
$parser->addPostData(array('ctl00$ScriptManager' => 'ctl00$ScriptManager|ctl00$generalContent$BtnShowQuotes',
							'__ASYNCPOST' => 'true',
							'ctl00$generalContent$BtnShowQuotes' => 'Показать'));
$parser->createDom()->createRequestURL()->createRequestPostArray()->waysList($except = '-1')->setDate('15 days');

// костыль
$scripts = $parser->dom->find('script');
foreach($scripts as $script) {
	if(strpos($script->src, 'ctl00_ScriptManager_HiddenField') !== false) {
		$scriptSrc = htmlspecialchars_decode(urldecode($script->src));
		$scriptSrcArr = explode('&', $scriptSrc);
		foreach($scriptSrcArr as $scriptParam) {
			if(($pos = strpos($scriptParam, '_TSM_CombinedScripts_')) !== false) {
				$parser->requestPost['ctl00_ScriptManager_HiddenField'] = substr($scriptParam, $pos+ strlen('_TSM_CombinedScripts_')+1);
			}			
		}
	}	
}

//foreach ways
foreach($parser->ways as $way) {
	$resultDom = str_get_html($parser->getCurrentWayResult($way));
	$tables = $resultDom->find('table table');

	foreach ($tables as $value) {
		$trip = $value->find('tr[!class]', 0)->innertext() .'<br>';
		echo htmlspecialchars($trip) . '<br>';
	}
}

?>