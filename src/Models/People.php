<?php

namespace App\Models ;

class People {

	private $id ;
	private $name ;
	private $description ;

	static $list = [] ;

	public function __construct($file)
	{
		$this->id = pathinfo($file)["filename"];

		$content = file_get_contents($file);
		$content = (new \Parsedown())->text($content);
		preg_match("/<h1>(.+)<\/h1>/m", $content, $res);
		$this->name = $res[1] ;
		$this->description = preg_replace("/<h1>(.+)<\/h1>/", "", $content);
	}

	static public function loadPeople(){

		$dir = __DIR__ . "/../../sources/acteurs/" ;
		foreach(scandir($dir) as $file){
			if(pathinfo($dir . $file)["extension"] == "md"){
				People::$list[] = new People($dir.$file) ;
			}
		}

	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return null|string|string[]
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @return array
	 */
	public static function getList(): array
	{
		return self::$list;
	}


}