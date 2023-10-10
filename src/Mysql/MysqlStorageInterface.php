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
	 * @param array $arr
	 * @param bool $update
	 * @return bool
	 */
	public function add(string $tbl, array $arr, bool $update = false) : bool;

	/**
	 * Метод редактирования записи в текущей БД по ID
	 * @param string $tbl
	 * @param array $arr
	 * @param string $case
	 * @param bool $ignore
	 * @return bool
	 */
	public function edit(string $tbl, array $arr, string $case, bool $ignore=false) : bool;

	/**
	 * Метод добавления записи в текущую БД
	 * @param string $tbl
	 * @param array $arr
	 * @return bool
	 */
	public function replace(string $tbl, array $arr) : bool;

	/**
	 * @param string $tbl
	 * @param string $case
	 * @return bool
	 */
	public function del(string $tbl, string $case) : bool;

	/**
	 * @param string $sql
	 * @param int $ln
	 * @param int $numPage
	 * @param int $count
	 * @return bool|MysqlStorageData
	 */
	public function read(string $sql, int $ln = 0, int $numPage = 1, int $count=0) : bool|MysqlStorageData;

	/**
	 * @param string $tbl
	 * @return bool
	 */
	public function chktbl(string $tbl) : bool;

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