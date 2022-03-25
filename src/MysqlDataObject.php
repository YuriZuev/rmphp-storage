<?php
/**
 * Created by PhpStorm.
 * User: Zuev Yuri
 * Date: 23.08.2021
 * Time: 21:02
 */

namespace Rmphp\Storage;


class MysqlDataObject {

	private ?\mysqli_result $result;
	public int $count;
	public string $nav;
	private array $arrayData = [];

	/**
	 * MysqlDataObject constructor.
	 * @param \mysqli_result|null $result
	 */
	public function __construct(\mysqli_result $result = null) {
		$this->result = $result;
	}

	/**
	 * @return iterable
	 */
	public function fatch(): iterable {
		if(!empty($this->arrayData)) return $this->arrayData;
		if(!$this->result) return [];
		return $this->generator();
	}

	public function fatchOne(int $index = 0) : iterable {
		if(!$this->result) return [];
		$this->result->data_seek($index);
		return $this->result->fetch_assoc();
	}

	/**
	 * @return iterable
	 */
	public function getData() : iterable {
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