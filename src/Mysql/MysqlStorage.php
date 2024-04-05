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
			if($this->mysqli->errno) throw new Exception();
			$this->addLog("OK - ".$sql);
			return $result;
		}
		catch (Exception $exception){
			$this->addLog("Err - SQL: ".$sql." | error: ".$this->mysqli->error);
			return false;
		}
	}


	/** @inheritDoc */
	public function insert(string $tbl, array $data, bool $update = false) : bool {
		$chunks = $this->getInsertValue($data);
		$upd = $this->getUpdateValue($data);
		if (!$update) {
			$sql = "insert low_priority into ".$this->escapeStr($tbl)." (".implode(",", $chunks['columns']).") values (".implode(",", $chunks['values']).")";
		} else{
			$sql = "insert low_priority into ".$this->escapeStr($tbl)." (".implode(",", $chunks['columns']).") values (".implode(",", $chunks['values']).") on duplicate key update ".implode(",", $upd);
		}
		return $this->query($sql);
	}

	/**
	 * @param string $tbl
	 * @param array $data
	 * @return bool
	 */
	public function batchInsert(string $tbl, array $data) : bool {
		foreach($data as $insertRow){
			$chunks = $this->getInsertValue($insertRow);
			$val[] = "(".implode(",", $chunks['values']).")";
		}
		$sql = "insert low_priority into ".$this->escapeStr($tbl)." (".implode(",", $chunks['columns'] ?? []).") values ".implode(",", $val ?? []);
		return $this->query($sql);
	}

	/** @inheritDoc */
	public function updateById(string $tbl, array $data, int $id, array $modifier = []) : bool {
		$chunks = $this->getUpdateValue($data);
		$sql = "update low_priority ".implode(" ", $modifier)." ".$this->escapeStr($tbl)." set ".implode(",", $chunks)." where id = '".$id."'";
		return $this->query($sql);
	}

	/** @inheritDoc */
	public function updateByParam(string $tbl, array $data, string $case,  array $modifier = []) : bool {
		$chunks = $this->getUpdateValue($data);
		$sql = "update low_priority ".implode(" ", $modifier)." ".$this->escapeStr($tbl)." set ". implode(",", $chunks)." where ".$case;
		return $this->query($sql);
	}

	/** @inheritDoc */
	public function replace(string $tbl, array $data) : bool {
		$chunks = $this->getInsertValue($data);
		$sql  = "replace low_priority into ".$this->escapeStr($tbl)." (".implode(",", $chunks['columns']).") values (".implode(",", $chunks['values']).")";
		return $this->query($sql);
	}


	/** @inheritDoc */
	public function deleteById(string $tbl, int $id) : bool {
		$sql = "delete low_priority from ".$this->escapeStr($tbl)." where id='.$id.'";
		// возвращаем число затронутых строк/false
		return $this->query($sql);
	}

	/** @inheritDoc */
	public function deleteByParam(string $tbl, string $case) : bool {
		$sql = "delete low_priority from ".$this->escapeStr($tbl)." where ".$case;
		// возвращаем число затронутых строк/false
		return $this->query($sql);
	}


	/** @inheritDoc */
	public function find(string $sql, int $ln=0, int $numPage=1, int $count=0): bool|MysqlStorageData {

		if ($ln > 1) {
			$cnts = (!empty($count)) ? $count : $this->query($sql)->num_rows;
		}

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
	public function findOne(string $sql) : bool|array {
		$result = $this->query($sql);
		if (!$result || $result->num_rows == 0) return false;
		$data = new MysqlStorageData($result);
		return $data->fetchOne();
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


	/**
	 * @param array $array
	 * @return array[]
	 */
	private function getInsertValue(array $array) : array {
		foreach ($array as $key => $value) {
			$col[] = "`$key`";
			$val[] = ($value !== NULL) ? "'$value'" : "NULL";
		}
		return [
			"columns" => $col ?? [],
			"values" => $val ?? []
		];
	}

	/**
	 * @param array $array
	 * @return array
	 */
	private function getUpdateValue(array $array) : array {
		foreach ($array as $key => $value) {
			$out[] = ($value !== NULL) ? "`$key`='$value'" : "`$key`=NULL";
		}
		return $out ?? [];
	}

}