<?php

ini_set('include_path', ini_get('include_path').';'.dirname(__FILE__).DIRECTORY_SEPARATOR.'Classes'.DIRECTORY_SEPARATOR);

/** PHPExcel */
include 'PHPExcel.php';

class ExcelCreator {
	public $rowStart = 0;
	public $data = array();
	public $currentDirection = '';
	public $currentOrder = array();
	private $objPHPExcel = '';
	private $rowsHeads = array();
	private $allowedWorksheets = array();
	private $typesInSheetRows = array();
	private $changedCounter = 0;
	private $inputFileType = '';

	public function __construct($name) {
		$fileName = dirname(__FILE__) . '/../' . $name;
		$this->inputFileType = 'Excel5';
		$objPHPExcelReader = PHPExcel_IOFactory::createReader($this->inputFileType);
		$this->objPHPExcel = $objPHPExcelReader->load($fileName);
	}

	public function setRowsName($cellArray) {
		foreach ($cellArray as $direction => $directionCellsArray) {
			$this->rowsHeads[$direction]= array();

			foreach ($directionCellsArray as $rowName=> $rowHeadCoordinate) {
				$this->setRowHead($rowName, $rowHeadCoordinate, $direction);
			}
		}
	}

	private function setRowHead($rowName, $rowHeadCoordinate, $direction) {
		$row = preg_split('#(?<=[a-z])(?=\d)#i', $rowHeadCoordinate);
		$this->rowsHeads[$direction][$rowName] = $row[0]; //get character of coordinates

		if($this->rowStart === 0) $this->rowStart = intval($row[1]) + 1;
	}

	public function onlyWorksheetsWithNumber($numberArray) {
		foreach ($numberArray as $value) {
			$this->allowedWorksheets[] = intval($value) - 1;
		}
		return $this;
	}

	public function doUnique($uniqueRows) {
		foreach ($uniqueRows as $direction => $directionUniqueRows) {
			$this->typesInSheetRows[$direction] = array();

			foreach ($directionUniqueRows as $uniqueRowName) {
				$this->typesInSheetRows[$direction][$uniqueRowName] = array();
			}
		}
	}

	public function getSheetData() {
		foreach ($this->objPHPExcel->getWorksheetIterator() as $worksheetKey => $worksheet) {
			if(!in_array($worksheetKey, $this->allowedWorksheets)) continue;

			foreach ($worksheet->getRowIterator() as $row) {
				if($this->rowStart > $row->getRowIndex()) continue;
				
				$this->getRowCells($row);
			}
		}

		$this->objPHPExcel->setActiveSheetIndex(0); //may be change

		return $this->data;
	}

	public function getRowCells($row) {
		foreach($this->rowsHeads as $rowWay => $rowHeads) {
			$rowCells = array();
			$rowKey = '';
			$isRowEmpty = true;

			foreach ($rowHeads as $rowName => $rowCharacter) {
				$cellValue = $this->objPHPExcel->getActiveSheet()->getCell($rowCharacter.$row->getRowIndex())->getValue();

				if($isRowEmpty) $isRowEmpty = $this->isValEmpty($cellValue);
				if( strpos(strtolower($rowName), 'date') !== false) {
					$datetime = new DateTime($cellValue);
					$cellValue = $datetime->format('Y-m-d H:m');
				}

				$rowCells[$rowName] = $cellValue;

				if(!$isRowEmpty && array_key_exists($rowName, $this->typesInSheetRows[$rowWay])) {
					$rowKey .= (string) $cellValue;
					unset($rowCells[$rowName]);						
				}
			}

			$rowCells['rowIndex'] = $row->getRowIndex();

			if(!$isRowEmpty) {
				if(!isset($this->data[$rowWay][$rowKey])) $this->data[$rowWay][$rowKey] = array();
				$this->data[$rowWay][$rowKey][] = $rowCells;
			}
		}		
	}

	public function isValEmpty($value) {
		return $value === '' || $value === null || $value === false;
	}

	public function setRowsValues($rowKey, $rowsValues) {
		$xlsRows = $this->data[$this->currentDirection][$rowKey];

		foreach ($xlsRows as $xlsRow) {
			$this->setRowValue($xlsRow, $rowsValues);
		}

		unset($this->data[$this->currentDirection][$rowKey]);
	}

	private function setRowValue($xlsRow, $rowsValues) {
		$index = $this->getIndex($xlsRow);

		foreach ($rowsValues as $rowValues) {
			foreach ($rowValues as $cellName => $cellValueArray) {
				if(!is_array($cellValueArray)) continue;

				$cellValue = $this->selectValueByOrder($cellValueArray);
				$cellCoord = $this->rowsHeads[$this->currentDirection][$cellName] . $index;

				$this->objPHPExcel->getActiveSheet()->setCellValue($cellCoord, $cellValue);
				$this->changedCounter++;				
			}
		}
	}

	public function getIndex($row) {
		return $row['rowIndex'];
	}	

	private function selectValueByOrder($value) {
		foreach ($this->currentOrder as $orderKey) {
			if(isset($value[$orderKey])) return $value[$orderKey];
		}

		throw new Exception('Have no key in |' . json_encode($this->currentOrder) . '" for: ' . json_encode($value) . "\n<br>");
		return '';
	}

	public function save($writeToXLS) {
		if($this->changedCounter > 0) {
			$this->objPHPExcel->setActiveSheetIndex(0);
			$objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, $this->inputFileType);
			$objWriter->save($writeToXLS);
		}
		return $this->changedCounter;
	}	
}

?>