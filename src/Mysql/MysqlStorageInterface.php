<?php

namespace Rmphp\Storage\Mysql;

interface MysqlStorageInterface {

	/**
	 * @return \Mysqli
	 */
	public function mysql() : \Mysqli;

	/**
	 * Метод прямого запроса к текущей БД
	 * @param string $sql
	 * @return bool|\mysqli_result
	 */
	public function query(string $sql) : bool|\mysqli_result;

	/**
	 * Метод добавления записи в текущую БД
	 * @param string $tbl
	 * @param array $data
	 * @param array $modifier
	 * @param bool $update
	 * @return bool
	 */
	public function insert(string $tbl, array $data, bool $update = false) : bool;

	/**
	 * @param string $tbl
	 * @param array $data
	 * @return bool
	 */
	public function batchInsert(string $tbl, array $data) : bool;

	/**
	 * Метод редактирования записи в текущей БД по ID
	 * @param string $tbl
	 * @param array $data
	 * @param int $id
	 * @param array $modifier
	 * @return bool
	 */
	public function updateById(string $tbl, array $data, int $id, array $modifier = []) : bool;

	/**
	 * @param string $tbl
	 * @param array $data
	 * @param string $case
	 * @param array $modifier
	 * @return bool
	 */
	public function updateByParam(string $tbl, array $data, string $case, array $modifier = []) : bool;

	/**
	 * @param string $tbl
	 * @param array $data
	 * @return bool
	 */
	public function replace(string $tbl, array $data) : bool;

	/**
	 * @param string $tbl
	 * @param int $id
	 * @return bool
	 */
	public function deleteById(string $tbl, int $id) : bool;

	/**
	 * @param string $tbl
	 * @param string $case
	 * @return bool
	 */
	public function deleteByParam(string $tbl, string $case) : bool;

	/**
	 * @param string $sql
	 * @param int $ln
	 * @param int $numPage
	 * @param int $count
	 * @return bool|MysqlStorageData
	 */
	public function find(string $sql, int $ln = 0, int $numPage = 1, int $count=0) : bool|MysqlStorageData;

	/**
	 * @param string $sql
	 * @return array
	 */
	public function findOne(string $sql) : array;

	/**
	 * Метод экранирования данных с учетом текущего подключения в т.ч для LIKE
	 * @param string $var
	 * @return string|null
	 */
	public function escapeReg(string $var) : ?string;

	/**
	 * Метод экранирования данных с учетом текущего подключения
	 * @param string|null $var
	 * @return string|null
	 */
	public function escapeStr(?string $var) : ?string;

	/**
	 * Метод наполнения статичного массива с логами
	 * @param string $log
	 */
	public function addLog(string $log) : void;

	/**
	 * @return array
	 */
	public function getLogs() : array;

	/**
	 * @return string
	 */
	public function getLastLog() : string;
}