<?php

namespace Rmphp\Storage;


class MysqlStorageData {

	private ?\mysqli_result $result;
	private array $arrayData = [];
	public int $count;

	/**
	 * MysqlDataObject constructor.
	 * @param \mysqli_result|null $result
	 */
	public function __construct(\mysqli_result $result = null) {
		$this->result = $result;
	}

	/**
	 * @return bool
	 */
	public function isResult() : bool {
		return isset($this->result);
	}

	/**
	 * @return \mysqli_result|null
	 */
	public function getMysqlResult() : ?\mysqli_result {
		return $this->result;
	}

	/**
	 * @return iterable
	 */
	public function fatch(): iterable {
		if(!empty($this->arrayData)) return $this->arrayData;
		if(!$this->result) return [];
		return $this->generator();
	}


	public function fatchOne(int $index = 0) : array {
		if(!$this->result) return [];
		$this->result->data_seek($index);
		return $this->result->fetch_assoc();
	}

	/**
	 * @return array
	 */
	public function getData() : iterable {
		if(!empty($this->arrayData)) return $this->arrayData;
		if(!$this->result) return [];
		$this->result->data_seek(0);
		while ($row = $this->result->fetch_assoc()) {
			$this->arrayData[] = $row;
		}
		return $this->arrayData;
	}

	/**
	 * @return iterable
	 */
	private function generator() : iterable {
		$this->result->data_seek(0);
		while ($row = $this->result->fetch_assoc()) {
			yield $row;
		}
	}

	public function __destruct() {
		if($this->result) $this->result->close();
	}

}