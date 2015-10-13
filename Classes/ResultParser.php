<?php

class ResultParser {
	private $colNumbers = array();
	private $currentRow = 0;
	private $currentId = 0;
	private $data = array();
	private $specialSubClasses = array();
	private $uniqueArr = array();
	public $result = array();
	public $tableNumber = 0;
	public $isReturn = false;


	public function setColsName($colsNameArray, $tableHeadCells) {	
		foreach ($tableHeadCells as $key => $tableHeadCell) {
			$dataColName = str_replace("\n", ' ', $tableHeadCell->plaintext);
			$this->selectColType($colsNameArray, $dataColName, $key);					
		}
	}

	private function selectColType($colsNameArray, $dataColName, $key) {
		foreach ($colsNameArray as $colName => $colValue) {

			if(strpos($dataColName, $colValue) !== false) {
				$this->colNumbers[$key] = $colName;

				if($colName === 'class') {
					$this->specialSubClasses[$key] = trim($this->getSpecialSubClass($dataColName));
				}
			}
		}		
	}

	private function getSpecialSubClass($class) {
		$matches = array();

		preg_match('/\(([^)]+)\)/', $class, $matches);

		if(isset($matches[1])) {
			return $matches[1];
		}

		return 'none';
	}

	public function parseTable($rowsArr) {
		foreach ($rowsArr as $row) {
			$this->parseRow($row);
		}

		return $this->result;
	}

	private function parseRow($row) {
		$this->currentRow++;
		$this->data[$this->currentRow] = array();
		$cells = $row->find('td');

		foreach ($cells as $key => $cell) {
			$this->parseCell($cell, $key);
		}

		$this->createDepartureDate();
		$this->createArrivalDate();

		unset($this->data[$this->currentRow][$this->getSpecificKey('date')]);

		$this->result[$this->loadKey()][] = $this->data[$this->currentRow];

		unset($this->data[$this->currentRow]);
	}

	public function withUnique($uniqueArray) {
		$key = 'forward';

		if($this->isReturn) {
			$key = 'backward';
		}

		$this->uniqueArr = $uniqueArray[$key];

		return $this;
	}

	private function loadKey() {
		$str = '';

		foreach ($this->uniqueArr as $key) {
			$str .= $this->data[$this->currentRow][$key];

			unset($this->data[$this->currentRow][$key]);
		}

		return $str;
	}

	private function parseCell($cell, $key) {
		$this->currentId = $key;

		switch ($this->colNumbers[$key]) {
			case 'date':
				$this->parseDate($cell);
				break;
			case 'code_and_time':
				$this->parseCode_and_time($cell);
				break;
			case 'go_and_back_aero':
				$this->parseGo_and_back_aero($cell);
				break;
			case 'class':
				$this->parseClass($cell);
				break;
			
			default:
				break;
		}
	}

	private function parseDate($cell) {
		$matches = array();

		preg_match('/([0-9]{2}\.[0-9]{2})/', $cell->innertext, $matches);

		if(isset($matches[1])) {			
			$this->setDataKeyValue('date', $matches[1]);
		}
	}

	private function parseCode_and_time($cell) {
		$matches = array();

		preg_match('/([A-Za-z]+)&nbsp;([0-9]+)[^0-9]+([0-9]{2}:[0-9]{2})-([0-9]{2}:[0-9]{2})/', $cell->innertext, $matches);

		if(isset($matches[1]) && isset($matches[2]) && isset($matches[3]) && isset($matches[4])) {
			$this->setDataKeyValue('AirCompanyCode', $matches[1]);
			$this->setDataKeyValue('FlightNum', $matches[2]);
			$this->setDataKeyValue('timeDeparture', $matches[3]);
			$this->setDataKeyValue('timeArrival', $matches[4]);
		}
	}

	private function setDataKeyValue($key, $value) {
		$this->data[$this->currentRow][$this->getSpecificKey($key)] = $value;
	}

	private function getDataKeyValue($key) {
		return $this->data[$this->currentRow][$this->getSpecificKey($key)];
	}

	private function getSpecificKey($key) {
		if($this->isReturn) $key .= ' Return';

		return $key;
	}

	private function parseGo_and_back_aero($cell) {
		$matches = array();
		preg_match('/([A-Z0-9]+)-([A-Z0-9]+)/', $cell->plaintext, $matches);

		if(isset($matches[1]) && isset($matches[2])) {
			$this->createIATA($matches[1], $matches[2]);
		}
	}

	public function setTableNumber($tableNumber) {
		$this->tableNumber = intval($tableNumber);

		if($this->tableNumber == 0) {
			$this->isReturn = false;			
		} elseif ($this->tableNumber == 1) {
			$this->isReturn = true;
		} else {
			throw new Exception("Лишняя таблица №" . $this->tableNumber);
		}

	}

	private function createIATA($firstIATA, $secondIATA) {
		$this->setDataKeyValue('DepartureIATA', $this->getIATAAndSetTerminalNumber($firstIATA, 'DepartureTerminal'));
		$this->setDataKeyValue('ArrivalIATA', $this->getIATAAndSetTerminalNumber($secondIATA, 'ArrivalTerminal'));		
	}

	private function parseClass($cell) {
		$specialSubClass = $this->specialSubClasses[$this->currentId];
		$this->setDataKeyValue('Seats', array($specialSubClass => $cell->plaintext));
	}

	private function getIATAAndSetTerminalNumber($str, $terminalName) {
		$str = trim($str);
		$this->setDataKeyValue($terminalName,  '');

		if(strlen($str) > 3) {
			$this->setDataKeyValue($terminalName, substr($str, 3)); // first 3 sybmols = aeroportCode. Else - terminal number
			$str = substr($str, 0, 3); // first 3 sybmols = aeroportCode. Else - terminal number
		}

		return $str;
	}

	private function createDepartureDate() {
		$this->setDataKeyValue(
			'DepartureDate', 
			$this->createDateTime(
				$this->getDataKeyValue('date')
				, $this->getDataKeyValue('timeDeparture')
				, 'createDepartureDate'
			)
		);
	}

	private function createArrivalDate() {
		$isNextDay = $this->isNextDay($this->getDataKeyValue('timeDeparture'), $this->getDataKeyValue('timeArrival'));
		$dateStr = $this->getDataKeyValue('date');

		if($isNextDay) {
			$dateStr = $this->createNextDay($dateStr);
		}

		$this->setDataKeyValue(
			'ArrivalDate', 
			$this->createDateTime(
				$dateStr
				, $this->getDataKeyValue('timeArrival')
				, 'createArrivalDate'
			)
		);

		unset($this->data[$this->currentRow][$this->getSpecificKey('timeArrival')]);
		unset($this->data[$this->currentRow][$this->getSpecificKey('timeDeparture')]);
	}

	private function createDateTime($date, $time, $me) {
		$datetime = DateTime::createFromFormat('d.m H:s', $date . ' ' . $time);
		$error = DateTime::getLastErrors();

		if($error['error_count'] > 0) {
			print_r($error['errors']);
			echo '<br>',$date,' ', $time, ' ',$me, '<br>';
			print_r($this->data[$this->currentRow]);

			throw new Exception("Error Processing Request");			
		}

		return $datetime->format('Y-m-d H:m');
	}

	private function isNextDay($time1, $time2) {
		$matches = array();
		$time1Minutes = $this->createMinutes($time1);
		$time2Minutes = $this->createMinutes($time2);

		return ($time1Minutes > $time2Minutes);
	}

	private function createMinutes($timeStr) {
		$matches = array();

		preg_match('/([^:]+):([0-9]+)/', $timeStr, $matches);

		return $matches[1] * 60 + $matches[2];
	}

	private function createNextDay($date) {
		$datetime = DateTime::createFromFormat('d.m', $date);
		$datetime->add(date_interval_create_from_date_string('1 day'));
		
		return $datetime->format('d.m');
	}
}

?>