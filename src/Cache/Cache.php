<?php
/**
 * Created by PhpStorm.
 * User: Zuev Yuri
 */


namespace Rmphp\Storage\Cache;

use Exception;

class Cache implements CacheInterface {

	/**
	 * @param string $path
	 * @param int $defaultTime
	 * @param int $subLength
	 * @throws Exception
	 */
	public function __construct(
		private string $path,
		private readonly int $defaultTime = 120,
		private readonly int $subLength = 2
	){
		$this->path = rtrim($path, "/");
		if(!is_dir($this->path) && !mkdir($this->path, 0777, true)) throw new Exception("Error create path");
	}

	/** @inheritDoc */
	public function save(string $name, $value, int $expires = 0) : bool {
		if(empty($expires)) $expires = time() + $this->defaultTime;
		if(!$this->fileSave($name, $expires."::".$name."::".serialize($value))) return false;
		return true;
	}

	/** @inheritDoc */
	public function getItem(string $key) {
		if($this->hasItem($key)){
			$data = explode("::", $this->fileRead($key), 3);
			return unserialize($data[2]);
		}
		return null;
	}

	/** @inheritDoc */
	public function hasItem(string $key) : bool {
		if (!empty($fileValue = $this->fileRead($key))){
			if(count($data = explode("::", $fileValue, 3)) != 3) return false;
			if(time() < (int)$data[0]) return true;
		}
		$this->fileDelete($key);
		return false;
	}

	/** @inheritDoc */
	public function get(string $name, callable $function, int $expires = 0) : mixed {
		if($this->hasItem($name)){
			$data = explode("::", $this->fileRead($name), 3);
			return unserialize($data[2]);
		}
		$value = $function();
		if(empty($expires)) $expires = time() + $this->defaultTime;
		$this->fileSave($name, $expires."::".$name."::".serialize($value));
		return $value;
	}

	/**
	 * @param string $name
	 * @return object
	 */
	private function getPath(string $name) : object {
		$hexName = md5($name);
		$subPath = substr($hexName, 0, $this->subLength);
		return (object)[
			"path" => $this->path."/".$subPath,
			"file" => $this->path."/".$subPath."/".$hexName,
		];
	}

	/**
	 * @param string $name
	 * @param string $data
	 * @return bool
	 */
	private function fileSave(string $name, string $data) : bool {
		$pathObject = $this->getPath($name);
		if(!is_dir($pathObject->path)) mkdir($pathObject->path, 0777, true);
		if(!file_put_contents($pathObject->file, $data)) return false;
		return true;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	private function fileRead(string $name) : string {
		$pathObject = $this->getPath($name);
		if (file_exists($pathObject->file) && $fileValue = file_get_contents($pathObject->file)) return $fileValue;
		return "";
	}

	/**
	 * @param $key
	 * @return bool
	 */
	public function fileDelete($key) : bool {
		$pathObject = $this->getPath($key);
		if(file_exists($pathObject->file)) unlink($pathObject->file);
		return true;
	}
}