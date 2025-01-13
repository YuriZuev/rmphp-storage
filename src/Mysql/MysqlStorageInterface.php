<?php

namespace Rmphp\Storage\Mysql;

use Mysqli;
use mysqli_result;

interface MysqlStorageInterface {

	/**
	 * @return Mysqli
	 */
	public function mysql() : Mysqli;

	/**
	 * Метод прямого запроса к текущей БД
	 * @param string $sql
	 * @return bool|mysqli_result
	 */
	public function query(string $sql) : bool|mysqli_result;

	/**
	 * Метод добавления записи в текущую БД
	 * @param string $table
	 * @param array $data
	 * @param bool $update
	 * @return bool
	 */
	public function insert(string $table, array $data, bool $update = false) : bool;

	/**
	 * @param string $table
	 * @param array $data
	 * @return bool
	 */
	public function batchInsert(string $table, array $data) : bool;

	/**
	 * Метод редактирования записи в текущей БД по ID
	 * @param string $table
	 * @param array $data
	 * @param mixed $id
	 * @param array $modifier
	 * @return bool
	 */
	public function updateById(string $table, array $data, mixed $id, array $modifier = []) : bool;

	/**
	 * @param string $table
	 * @param array $data
	 * @param string $case
	 * @param array $modifier
	 * @return bool
	 */
	public function updateByParam(string $table, array $data, string $case, array $modifier = []) : bool;

	/**
	 * @param string $table
	 * @param array $data
	 * @return bool
	 */
	public function replace(string $table, array $data) : bool;

	/**
	 * @param string $table
	 * @param mixed $id
	 * @return bool
	 */
	public function deleteById(string $table, mixed $id) : bool;

	/**
	 * @param string $table
	 * @param string $case
	 * @return bool
	 */
	public function deleteByParam(string $table, string $case) : bool;

	/**
	 * @param string $sql
	 * @param int $ln
	 * @param int $numPage
	 * @param int $count
	 * @return bool|MysqlResultData
	 */
	public function find(string $sql, int $ln = 0, int $numPage = 1, int $count=0) : bool|MysqlResultData;

	/**
	 * @param string $sql
	 * @return bool|array
	 */
	public function findOne(string $sql) : bool|MysqlResultData;

	/**
	 * @param string $table
	 * @param mixed $id
	 * @param string $name
	 * @return bool|array
	 */
	public function findById(string $table, mixed $id, string $name = 'id') : bool|array;

	/**
	 * Метод экранирования данных с учетом текущего подключения в т.ч для LIKE
	 * @param string $string
	 * @return string|null
	 */
	public function escapeReg(string $string) : ?string;

	/**
	 * Метод экранирования данных с учетом текущего подключения
	 * @param string|null $string
	 * @return string|null
	 */
	public function escapeStr(?string $string) : ?string;

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
