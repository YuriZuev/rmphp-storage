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
	 * @param object $params
	 * @throws Exception
	 */
	public function __construct(
		private readonly object $params
	) {
		$this->mysqli = new mysqli(
			$this->params->host,
			$this->params->user,
			$this->params->pass,
			$this->params->base
		);
		// выводим ошибку при неудачном подключении
		if ($this->mysqli->connect_errno) {
			throw new Exception($this->mysqli->connect_errno);
		}
		$this->mysqli->set_charset("utf8");
		if(!empty($this->params->logsEnable)) $this->logsEnabled = true;
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
			$this->addLog("OK - $sql");
			return $result;
		}
		catch (Exception $exception){
			$this->addLog("Err - SQL: $sql | error: ".$this->mysqli->error);
			return false;
		}
	}


	/** @inheritDoc */
	public function insert(string $table, array $data, bool $update = false) : bool {
		$chunks = $this->getInsertValue($data);
		$upd = $this->getUpdateValue($data);
		if (!$update) {
			$sql = "insert low_priority into ".$this->escapeStr($table)." (".implode(",", $chunks['columns']).") values (".implode(",", $chunks['values']).")";
		} else{
			$sql = "insert low_priority into ".$this->escapeStr($table)." (".implode(",", $chunks['columns']).") values (".implode(",", $chunks['values']).") on duplicate key update ".implode(",", $upd);
		}
		return $this->query($sql);
	}

	/** @inheritDoc */
	public function batchInsert(string $table, array $data) : bool {
		foreach($data as $insertRow){
			$chunks = $this->getInsertValue($insertRow);
			$values[] = "(".implode(",", $chunks['values']).")";
		}
		$sql = "insert low_priority into ".$this->escapeStr($table)." (".implode(",", $chunks['columns'] ?? []).") values ".implode(",", $values ?? []);
		return $this->query($sql);
	}

	/** @inheritDoc */
	public function updateById(string $table, array $data, mixed $id, array $modifier = []) : bool {
		$chunks = $this->getUpdateValue($data);
		$id = (is_numeric($id)) ? (int) $id : $this->escapeStr($id);
		$sql = "update low_priority ".implode(" ", $modifier)." ".$this->escapeStr($table)." set ".implode(",", $chunks)." where id = '$id'";
		return $this->query($sql);
	}

	/** @inheritDoc */
	public function updateByParam(string $table, array $data, string $case, array $modifier = []) : bool {
		$chunks = $this->getUpdateValue($data);
		$sql = "update low_priority ".implode(" ", $modifier)." ".$this->escapeStr($table)." set ". implode(",", $chunks)." where ".$case;
		return $this->query($sql);
	}

	/** @inheritDoc */
	public function replace(string $table, array $data) : bool {
		$chunks = $this->getInsertValue($data);
		$sql  = "replace low_priority into ".$this->escapeStr($table)." (".implode(",", $chunks['columns']).") values (".implode(",", $chunks['values']).")";
		return $this->query($sql);
	}


	/** @inheritDoc */
	public function deleteById(string $table, mixed $id) : bool {
		$id = (is_numeric($id)) ? (int) $id : $this->escapeStr($id);
		$sql = "delete low_priority from ".$this->escapeStr($table)." where id='$id'";
		// возвращаем число затронутых строк/false
		return $this->query($sql);
	}

	/** @inheritDoc */
	public function deleteByParam(string $table, string $case) : bool {
		$sql = "delete low_priority from ".$this->escapeStr($table)." where ".$case;
		// возвращаем число затронутых строк/false
		return $this->query($sql);
	}


	/** @inheritDoc */
	public function find(string $sql, int $ln=0, int $numPage=1, int $count=0): bool|MysqlResultData {

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

		$data = new MysqlResultData($result);
		$data->count = $cnts ?? 0;
		$data->hex = md5($sql);
		return $data;
	}

	/** @inheritDoc */
	public function findOne(string $sql) : bool|MysqlResultData {
		$result = $this->query($sql." limit 0, 1");
		if (!$result || $result->num_rows == 0) return false;
		return new MysqlResultData($result);
	}

	/** @inheritDoc */
	public function findById(string $table, mixed $id, string $name = 'id') : bool|array {
		$id = (is_numeric($id)) ? (int) $id : $this->escapeStr($id);
		$result = $this->query("select * from ".$this->escapeStr($table)." where `$name`='$id' limit 0, 1");
		if (!$result || $result->num_rows == 0) return false;
		$data = new MysqlResultData($result);
		return $data->fetchOne();
	}


	/** @inheritDoc */
	public function escapeReg(string $string) : ?string {
		if(!isset($string)) return null;
		return trim(addcslashes($this->mysqli->real_escape_string($string), "%_"));
	}

	/** @inheritDoc */
	public function escapeStr(?string $string) : ?string {
		if(!isset($string)) return null;
		return trim($this->mysqli->real_escape_string($string));
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
			$colunms[] = "`$key`";
			$values[] = ($value !== NULL) ? "'$value'" : "NULL";
		}
		return [
			"columns" => $colunms ?? [],
			"values" => $values ?? []
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
