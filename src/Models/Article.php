<?php

namespace App\Models ;

use Parsedown;

class Article
{
	private $id ;
	private $name ;
	private $contentFile ;

	private $isHead ;

	public function __construct($id, $config)
	{
		$this->id = $id ;
		$this->name = $config["name"];
		$this->relativePath = $config["file"] ;
		$this->contentFile = __DIR__ . "/../../sources/" . $config["file"] ;

		$this->isHead = isset($config["isHead"]) && $config["isHead"] === true ;

	}

	public function renderContent(){
		$content = file_get_contents($this->contentFile);

		//dump($this);

		if(pathinfo($this->contentFile)["extension"] == "md"){

			$parsedown = new Parsedown();
			$content = $parsedown->text($content);

			preg_match_all('/(?<!\\\\){(.+)(?<!\\\\)}/m', $content, $matches);

			for($i = 0 ; $i < count($matches[0]) ; $i++){
				try {
					$c = $i + 1 ;
					$content = preg_replace('/ ?' . preg_quote($matches[0][$i], "/") . '/', "<sup>$c</sup>", $content, 1);
					$content .= "\n <small>$c : " . $matches[1][$i] . "</small><br>";
				} catch (\Exception $e){
					throw new \Exception($this->name);
				}
			}

			$content = str_replace("\{", "{", $content);
			$content = str_replace("\}", "}", $content);

		}

		/* Réécriture des urls */
		$rp = $this->relativePath ;
		$content = preg_replace_callback('/<img src=\"(\S+)\"/m', function($arg) use (&$rp){
			$res = str_replace($arg[1], "images/" . pathinfo($rp)["dirname"] . "/" . $arg[1], $arg[1]);
			$res = str_replace($arg[1], $res, $arg[0]);

			return $res;
		}, $content);

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
	 * @return bool
	 */
	public function isHead(): bool
	{
		return $this->isHead;
	}

}