<?php

namespace Rmphp\Storage\Mysql;

use Exception;
use Mysqli;
use mysqli_result;

class MysqlStorage implements MysqlStorageInterface {

	public array $log = array();
	public bool $logsEnabled = false;
	private Mysqli $mysqli;

	/**
	 * Внутренний конструктор подключения к БД
	 * Mysql constructor.
	 * @param array $params
	 * @throws Exception
	 */
	public function __construct(array $params) {
		$this->mysqli = new mysqli($params['host'], $params['user'], $params['pass'], $params['base']);
		// выводим ошибку при неудачном подключении
		if ($this->mysqli->connect_errno) {
			throw new Exception($this->mysqli->connect_errno);
		}
		$this->mysqli->set_charset("utf8");
		if(!empty($params['logsEnable'])) $this->logsEnabled = true;
	}

	/** @inheritDoc */
	public function mysql() : Mysqli {
		return $this->mysqli;
	}

	/** @inheritDoc */
	public function query(string $sql) : bool|mysqli_result
	{
		try{
			$result = $this->mysqli->query($sql);
			// запись в log
			($this->mysqli->errno)
				? $this->addLog("Err - SQL: ".$sql." | error: ".$this->mysqli->error)
				: $this->addLog("OK - ".$sql);
			return $result;
		}
		/* 8.1.0 Теперь по умолчанию установлено значение MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT и выбрасывается исключение. Ранее оно было MYSQLI_REPORT_OFF. */
		catch (Exception $exception){
			$this->addLog("Err - SQL: ".$sql." | error: ".$this->mysqli->error);
			return false;
		}
	}

	/** @inheritDoc */
	public function add(string $tbl, array $arr, bool $update = false) : bool {
		foreach ($arr as $key => $value) {
			$col[] = "`$key`";
			$val[] = ($value !== NULL) ? "'$value'" : "NULL";
			$upd[] = ($value !== NULL) ? "`$key`='$value'" : "`$key`=NULL";
		}
		// Собираем в строки для использования в запросе
		$col = implode(", ", $col);
		$val = implode(", ", $val);

		if (!$update) {
			$sql = "insert low_priority into ".$this->escapeStr($tbl)." (".$col.") values (" . $val . ")";
		} else{
			$sql = "insert low_priority into ".$this->escapeStr($tbl)." (".$col.") values (".$val.") on duplicate key update ".implode(", ", $upd);
		}
		return $this->query($sql);
	}

	/** @inheritDoc */
	public function edit(string $tbl, array $arr, string $case, bool $ignore=false) : bool {
		foreach ($arr as $key => $value) {
			$isql[] = ($value !== NULL) ? "`$key`='$value'" : "`$key`=NULL";
		}
		$where = (preg_match("'^[0-9]+$'",$case)) ? "id = '".(int) $case."'" : $case;
		if(empty($ignore)) {
			$sql = "update low_priority " . $this->escapeStr($tbl) . " set " . implode(", ", $isql) . " where " . $where;
		} else {
			$sql = "update low_priority ignore " . $this->escapeStr($tbl) . " set " . implode(", ", $isql) . " where " . $where;
		}
		return $this->query($sql);
	}

	/** @inheritDoc */
	public function replace(string $tbl, array $arr) : bool {
		foreach ($arr as $key => $value) {
			$col[] = "`$key`";
			$val[] = ($value !== NULL) ? "'$value'" : "NULL";
		}
		// Собираем в строки для использования в запросе
		$col = implode(", ", $col);
		$val = implode(", ", $val);

		$sql  = "replace low_priority into ".$this->escapeStr($tbl)." (".$col.") values (".$val.")";
		return $this->query($sql);
	}

	/** @inheritDoc */
	public function del(string $tbl, string $case) : bool {
		$where = (preg_match("'^[0-9]+$'",$case)) ? "id = '".(int) $case."'" : $case;
		$sql = "delete low_priority from ".$this->escapeStr($tbl)." where ".$where;
		// возвращаем число затронутых строк/false
		return $this->query($sql);
	}

	/** @inheritDoc */
	public function read(string $sql, int $ln=0, int $numPage=1, int $count=0): bool|MysqlStorageData {

		if ($ln > 1) {
			$cnts = (!empty($count)) ? $count : $this->query($sql)->num_rows;
		}

		// часть строки запроса с лимит
		switch (true){
			case ($ln > 1 || $numPage > 1) : $limit = " limit ".(($numPage * $ln) - $ln).", ".$ln; break;
			case ($ln == 1): $limit = " limit 0, 1"; break;
			default: $limit = "";
		}

		$result = $this->query($sql.$limit);
		if (!$result || $result->num_rows == 0) return false;

		$data = new MysqlStorageData($result);
		$data->count = $cnts ?? 0;
		$data->hex = md5($sql);
		return $data;
	}



	/** @inheritDoc */
	public function chktbl(string $tbl) : bool {
		$result = $this->mysqli->query("SHOW TABLES LIKE '".$this->escapeStr($tbl)."'");
		if ($result->num_rows == 0) {
			$this->addLog(__METHOD__.":"." Err - Table ".$tbl." doesn't exist"); return false;
		}
		$this->addLog(__METHOD__.":"." OK - Table ".$tbl." exist");
		return true;
	}

	/** @inheritDoc */
	public function escapeReg(string $var) : ?string {
		if(!isset($var)) return null;
		return trim(addcslashes($this->mysqli->real_escape_string($var), "%_"));
	}

	/** @inheritDoc */
	public function escapeStr(?string $var) : ?string {
		if(!isset($var)) return null;
		return trim($this->mysqli->real_escape_string($var));
	}

	/** @inheritDoc */
	public function addLog(string $log) : void {
		if($this->logsEnabled) $this->log[] = $log;
	}

	/** @inheritDoc */
	public function getLogs() : array {
		return $this->log;
	}

	/** @inheritDoc */
	public function getLastLog() : string {
		return $this->log[count($this->log)-1];
	}

}