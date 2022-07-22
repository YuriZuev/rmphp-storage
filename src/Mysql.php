<?php
/**
 * Created by PhpStorm.
 * User: Zuev Yuri
 * Date: 16.08.2021
 * Time: 0:04
 */

namespace Rmphp\Storage;

class Mysql implements StorageMysqlInterface {

	public array $log = array();
	public bool $logsEnabled = false;
	private \Mysqli $mysqli;

	/**
	 * Внутренний конструктор подключения к БД
	 * Mysql constructor.
	 * @param array $params
	 * @throws \Exception
	 */
	public function __construct(array $params) {
		$this->mysqli = new \mysqli($params['host'], $params['user'], $params['pass'], $params['base']);
		// выводим ошибку при неудачном подключении
		if ($this->mysqli->connect_errno) {
			throw new \Exception($this->mysqli->connect_errno);
		}
		$this->mysqli->set_charset("utf8");
		if(!empty($params['logsEnable'])) $this->logsEnabled = true;
	}

	/**
	 * @return \Mysqli
	 */
	public function mysql() : \Mysqli {
		return $this->mysqli;
	}

	/**
	 * Метод прямого запроса к текущей БД
	 * @param string $sql
	 * @return bool|\mysqli_result
	 */
	public function query(string $sql)
	{
		$result = $this->mysqli->query($sql);
		// запись в log
		if ($this->mysqli->errno) {
			$this->addLog("Err - SQL: ".$sql." | error:".$this->mysqli->error);
		} else {
			$this->addLog("OK - ".$sql);
		}
		return $result;
	}

	/**
	 * Метод добавления записи в текущую БД
	 * @param string $tbl
	 * @param array $arr
	 * @param bool $update
	 * @return bool
	 */
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
			$sql = "insert low_priority into ".$this->ekrval($tbl)." (".$col.") values (" . $val . ")";
		} else{
			$sql = "insert low_priority into ".$this->ekrval($tbl)." (".$col.") values (".$val.") on duplicate key update ".implode(", ", $upd);
		}
		return $this->query($sql);
	}

	/**
	 * Метод редактирования записи в текущей БД по ID
	 * @param string $tbl
	 * @param array $arr
	 * @param string $case
	 * @param bool $ignore
	 * @return bool
	 */
	public function edit(string $tbl, array $arr, string $case, bool $ignore=false) : bool {
		foreach ($arr as $key => $value) {
			$isql[] = ($value !== NULL) ? "`$key`='$value'" : "`$key`=NULL";
		}
		$where = (preg_match("'^[0-9]+$'",$case)) ? "id = '".(int) $case."'" : $case;
		if(empty($ignore)) {
			$sql = "update low_priority " . $this->ekrval($tbl) . " set " . implode(", ", $isql) . " where " . $where;
		} else {
			$sql = "update low_priority ignore " . $this->ekrval($tbl) . " set " . implode(", ", $isql) . " where " . $where;
		}
		return $this->query($sql);
	}

	/**
	 * Метод добавления записи в текущую БД
	 * @param string $tbl
	 * @param array $arr
	 * @return bool
	 */
	public function replace(string $tbl, array $arr) : bool {
		foreach ($arr as $key => $value) {
			$col[] = "`$key`";
			$val[] = ($value !== NULL) ? "'$value'" : "NULL";
		}
		// Собираем в строки для использования в запросе
		$col = implode(", ", $col);
		$val = implode(", ", $val);

		$sql  = "replace low_priority into ".$this->ekrval($tbl)." (".$col.") values (".$val.")";
		return $this->query($sql);
	}

	/**
	 * @param string $tbl
	 * @param string $case
	 * @return bool
	 */
	public function del(string $tbl, string $case) : bool {
		$where = (preg_match("'^[0-9]+$'",$case)) ? "id = '".(int) $case."'" : $case;
		$sql = "delete low_priority from ".$this->ekrval($tbl)." where ".$where;
		// возвращаем число затронутых строк/false
		return $this->query($sql);
	}

	/**
	 * @param string $sql
	 * @param int $ln
	 * @param int $numPage
	 * @param int $count
	 * @return bool|MysqlDataObject
	 */
	public function read(string $sql, int $ln = 0, int $numPage = 1, int $count = 0) : bool|MysqlDataObject {

		// для исключения лишней нагрузки при единичной выборки не смотрим кол-во результатов
		if ($ln > 1) {
			$cnts = (!empty($count)) ? $count : $this->query($sql)->num_rows;
			if($cnts > $ln) $nav = $this->nav($count, $ln, $numPage);
		}

		// часть строки запроса с лимит
		switch (true){
			case ($ln > 1 || $numPage > 1) : $limit = " limit ".(($numPage * $ln) - $ln).", ".$ln; break;
			case ($ln == 1): $limit = " limit 0, 1"; break;
			default: $limit = "";
		}

		$result = $this->query($sql.$limit);
		if (!$result || $result->num_rows == 0) return false;

		$data = new MysqlDataObject($result);
		$data->count = $cnts ?? 0;
		$data->nav = $nav ?? "";
		return $data;
	}

	/**
	 * @param $_cnt
	 * @param $_ln
	 * @param $_numPage
	 * @return string
	 */
	public function nav($_cnt, $_ln, $_numPage) : string {
		//TODO: Обьект навигация или массив (без html)
		$req_uri = "#";
		// вычисляем десятки от номера страницы (от кокого генирируем)
 		// это необходимо для того, чтобы выводить 10 ссылок не с текущей
		// страницы, а с начала актуального десятка (с 1, 11, 21, и.т.д)
		$dozen = (floor(($_numPage - 1) / 10) * 10);
		// создаем ссылку на начало и на предыдущий десяток если она нужна
		$str_nav = "<span>";
		if ($dozen > 0) {
			$str_nav.= "<a href=\"" . $req_uri . "\">1</a>";
			$str_nav.= "<a href=\"" . $req_uri. ($dozen) . "\">...</a>";
		}
		// генерируем список цфровых ссылок
		for ($i = 1; ($dozen + $i) * $_ln < $_cnt + $_ln && $i <= 10; $i++) {
			// если сгенерированный номер страниц равен номеру переданной
			if (($dozen + $i) == $_numPage) {
				$str_nav.= "<font>".$_numPage."</font>";
			}
			// если сгенерированный номер страниц не равен номеру переданной
			else {
				$str_nav.= "<a href=\"" . $req_uri . ($dozen + $i) . "\">" . ($dozen + $i) . "</a>";
			}
		}
		// создаем ссылку на следующий десяток
		if (($dozen + ($i - 1)) * $_ln < $_cnt) {
			$str_nav.= "<a href=\"" . $req_uri . ($dozen + $i) . "\">...</a>";
		}
		// создаем ссылку на конец если она нужна
		if (($dozen + ($i - 1)) < (floor(($_cnt - 1) / $_ln) + 1)) {
			$str_nav.= "<a href=\"" . $req_uri . (floor(($_cnt - 1) / $_ln) + 1) . "\">" . ceil($_cnt / $_ln) . "</a>";
		}
		$str_nav.= "</span>";
		// Возвращем
		return $str_nav;
	}

	/**
	 * @param $_cnt
	 * @param $_ln
	 * @param $_numPage
	 * @return string
	 */
	public function navb($_cnt, $_ln, $_numPage) : string
	{
		$req_uri = "#";
		$str_nav = "<span>";
		if($_numPage == 1){
			$str_nav.= "<font>Первая страница</font>";
		} else {
			$str_nav.= "<a href=\"".$req_uri."1\">Начало</a>";
		}
		if($_numPage > 2){
			$str_nav.= "<a href=\"".$req_uri.($_numPage-1)."\">Назад</a>";
		}
		if($_numPage != 1){
			$str_nav.= "<font>Станица ".$_numPage."</font>";
		}
		if($_cnt > $_ln){
			$str_nav.= "<a href=\"".$req_uri.($_numPage+1)."\">Далее</a>";
		}

		$str_nav.= "</span>";
		// Возвращем
		return $str_nav;
	}

	/**
	 * @param string $tbl
	 * @return bool
	 */
	public function chktbl(string $tbl) : bool {
		$result = $this->mysqli->query("SHOW TABLES LIKE '".$this->ekrval($tbl)."'");
		if ($result->num_rows == 0) {
			$this->addLog(__METHOD__.":"." Err - Table ".$tbl." doesn't exist"); return false;
		}
		$this->addLog(__METHOD__.":"." OK - Table ".$tbl." exist");
		return true;
	}

	/**
	 * Метод экранирования данных с учетом текущего подключения в т.ч для LIKE
	 * @param string $var
	 * @return string
	 */
	public function ekrreg(string $var) : string {
		$var = $this->mysqli->real_escape_string($var);
		$var = addcslashes($var, "%_");
		$var = trim($var);
		return $var;
	}

	/**
	 * Метод экранирования данных с учетом текущего подключения
	 * @param string $var
	 * @return string
	 */
	public function ekrval(string $var) : string {
		$var = $this->mysqli->real_escape_string($var);
		$var = trim($var);
		return $var;
	}

	/**
	 * Метод наполнения статичного массива с логами
	 * @param string $log
	 */
	public function addLog(string $log) : void {
		if($this->logsEnabled) $this->log[] = $log;
	}

	/**
	 * @return array
	 */
	public function getLogs() : array {
		return $this->log;
	}

	/**
	 * @return array
	 */
	public function getLastLog() : string {
		return $this->log[count($this->log)-1];
	}

}