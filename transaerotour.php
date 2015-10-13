<?php

header('Content-type: text/html;charset=utf-8');
$startime = microtime(1);
$mem_start = memory_get_usage();
set_time_limit(0);

require dirname(__FILE__).DIRECTORY_SEPARATOR.'Classes'.DIRECTORY_SEPARATOR.'includer.php';


// =================================================================
$nameXLS = 'transaerotour.xls';//date("d_m_Y_H_i_").
$writeToXLS = 'new.xls';

$uniqueRows = array(
	'forward'
		=>	array('AirCompanyCode', 'FlightNum', 'DepartureIATA', 'ArrivalIATA', 'DepartureDate', 'ArrivalDate', 'DepartureTerminal','ArrivalTerminal')
	, 'backward' 
		=>	array('AirCompanyCode Return', 'FlightNum Return', 'DepartureIATA Return', 'ArrivalIATA Return', 'DepartureDate Return', 'ArrivalDate Return', 'DepartureTerminal Return', 'ArrivalTerminal Return'));

$neededWays = array(
	'Москва-Даламан' 
		=>	array('Uch', 'U')
	, 'Москва-Бангкок' 
		=>	array('N', 'Y')
	, 'Москва-Пхукет' 
		=>	array('N', 'Y')
);

$xls = new ExcelCreator($nameXLS);
$xls->setRowsName(array(
	'forward' =>
		array('AirCompanyCode' => 'B1'
			, 'FlightNum' => 'H1'
			, 'DepartureIATA' => 'C1'
			, 'ArrivalIATA' => 'D1'
			, 'DepartureDate' => 'I1'
			, 'ArrivalDate' => 'J1'
			, 'DepartureTerminal' => 'E1'
			, 'ArrivalTerminal' => 'F1'
			, 'Seats' => 'N1'
		),
	'backward' =>
		array('AirCompanyCode Return' => 'T1'
			, 'FlightNum Return' => 'Z1'
			, 'DepartureIATA Return' => 'U1'
			, 'ArrivalIATA Return' => 'V1'
			, 'DepartureDate Return' => 'AA1'
			, 'ArrivalDate Return' => 'AB1'
			, 'DepartureTerminal Return' => 'W1'
			, 'ArrivalTerminal Return' => 'X1'
			, 'Seats Return' => 'AD1'
		)
	)
); // перечисление ячеек по именам важно ставить в том порядке, в котором они находятся в массиве $uniqueRows

$xls->onlyWorksheetsWithNumber(array(1));
$xls->doUnique($uniqueRows);
$xls->getSheetData();

// =================================================================
$parser = new Parser('http://online.transaerotour.com/mw9215/Extra/AviaQuotes.aspx');
$parser->fromDateFieldName = 'ctl00$generalContent$MultiTwinDatepicker$TxtMultiDatepickerFrom';
$parser->toDateFieldName = 'ctl00$generalContent$MultiTwinDatepicker$TxtMultiDatepickerTo';
//addition post
$parser->addPostData(array('ctl00$ScriptManager' => 'ctl00$ScriptManager|ctl00$generalContent$BtnShowQuotes',
							'__ASYNCPOST' => 'true',
							'ctl00$generalContent$BtnShowQuotes' => 'Показать'));
$parser->createDom()->createRequestURL()->createRequestPostArray()->waysList($except = '-1')->setDate('300 days');
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
// =================================================================



$tmpCounter = 0;

foreach($parser->ways as $wayName => $way) {
	if(!array_key_exists($wayName, $neededWays)) continue;

	echo '<br>' . $wayName . '<br>';

	$resultDom = str_get_html($parser->getCurrentWayResult($way));
	$tables = $resultDom->find('table>tr>td[valign=top]');

	foreach ($tables as $tableN => $table) {

		$resultParser = new ResultParser();
		$resultParser->setColsName(
			array('date' => 'Дата'
				, 'code_and_time' => 'Рейс '
				, 'go_and_back_aero' => 'Вылет'
				, 'class' => ' класс')
			, $table->find('tr[class=h1] td'));
		$resultParser->setTableNumber($tableN);

		try {
			$resultParser->withUnique($uniqueRows)->parseTable($table->find('tr[!class]'));
		} catch (Exception $e) {
			echo "\n<br>" . 'Поймано исключение: ',  $e->getMessage(), "\n<br>Для: " . $way . '<br>';
		}

		$wayDirection = '';

		if($resultParser->isReturn) {
			$wayDirection = 'backward';
		} else {
			$wayDirection = 'forward';
		}

		$xls->currentDirection = $wayDirection;
		$xls->currentOrder = $neededWays[$wayName];

		foreach ($resultParser->result as $ticketKey => $row) {

			if(isset($xls->data[$wayDirection][$ticketKey])) {
				echo '<br>'. (++$tmpCounter) . ' matches found <br>';

				try {
					$xls->setRowsValues($ticketKey, $row);
					
					unset($resultParser->result[$ticketKey]);
				} catch (Exception $e) {
					echo "\n<br>" . 'Поймано исключение библиотеки: ',  $e->getMessage(), "\n<br>Для: " . $way . '<br>';
				} 
			}
		}
	}
}

$saveResult = $xls->save($writeToXLS);
// =================================================================


if($saveResult == 0) {
	echo 'Bad work';
} else {
	echo '<br>' . $saveResult . ' cells has been changed<br>';
}

// http://viavia.ru/transaerotour.php
// http://online.transaerotour.com/mw9215/Extra/AviaQuotes.aspx
// https://phpexcel.codeplex.com/wikipage?title=Examples&referringTitle=Home

?>
