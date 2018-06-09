<?php

namespace App\Models ;

use Parsedown;

class Article
{

	private $id ;
	private $name ;
	private $contentFile ;

	private $x ;
	private $y ;
	private $z ;

	private $rotateX;
	private $rotateY;
	private $rotateZ;

	private $isHead ;

	public function __construct($id, $config)
	{
		$this->id = $id ;
		$this->name = $config["name"];
		$this->contentFile = __DIR__ . "/../../sources/" . $config["file"] ;

		$this->x = isset($config["position"]) && isset($config["position"]["x"]) ? $config["position"]["x"] : null;
		$this->y = isset($config["position"]) && isset($config["position"]["y"]) ? $config["position"]["y"] : null;
		$this->z = isset($config["position"]) && isset($config["position"]["z"]) ? $config["position"]["z"] : null;

		$this->rotateX = isset($config["rotation"]) && isset($config["rotation"]["x"]) ? $config["rotation"]["x"] : null;
		$this->rotateY = isset($config["rotation"]) && isset($config["rotation"]["y"]) ? $config["rotation"]["y"] : null;
		$this->rotateZ = isset($config["rotation"]) && isset($config["rotation"]["z"]) ? $config["rotation"]["z"] : null;

		$this->isHead = isset($config["isHead"]) && $config["isHead"] === true ;

		foreach (["x", "y", "z", "rotateX", "rotateY", "rotateZ"] as $field){
			$this->$field = eval("return " . $this->$field . ";");
		}

	}

	public function renderContent(){
		$content = file_get_contents($this->contentFile);

		if(pathinfo($this->contentFile)["extension"] == "md"){

			$parsedown = new Parsedown();
			$content = $parsedown->text($content);

			preg_match_all('/(?<!\\\\){(.+)(?<!\\\\)}/m', $content, $matches);

			dump($matches);

			for($i = 0 ; $i < count($matches[0]) ; $i++){
				$content = preg_replace('/ ?' . $matches[0][$i] . '/', "<sup>$i</sup>", $content, 1);
				$content .= "\n <small>$i : " . $matches[1][$i] ."</small><br>";
			}

			$content = str_replace("\{", "{", $content);
			$content = str_replace("\}", "}", $content);

		}

		return $content ;
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
	 * @return string
	 */
	public function getContentFile(): string
	{
		return $this->contentFile;
	}

	/**
	 * @return null
	 */
	public function getX()
	{
		return $this->x;
	}

	/**
	 * @return null
	 */
	public function getY()
	{
		return $this->y;
	}

	/**
	 * @return null
	 */
	public function getZ()
	{
		return $this->z;
	}

	/**
	 * @return null
	 */
	public function getRotateX()
	{
		return $this->rotateX;
	}

	/**
	 * @return null
	 */
	public function getRotateY()
	{
		return $this->rotateY;
	}

	/**
	 * @return null
	 */
	public function getRotateZ()
	{
		return $this->rotateZ;
	}

	/**
	 * @return bool
	 */
	public function isHead(): bool
	{
		return $this->isHead;
	}



}