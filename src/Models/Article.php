<?php

namespace App\Models ;

use Parsedown;
use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;

class Article
{
	private $id ;
	private $name ;
	private $contentFile ;
	private $references ;
	private $background ;

	private $isHead ;

	static private $entries = null ;



	public function __construct($id, $config)
	{
		$this->id = $id ;
		$this->name = $config["name"];
		$this->relativePath = $config["file"] ;
		$this->contentFile = __DIR__ . "/../../sources/" . $config["file"] ;
		$this->background = isset($config["background"]) ? $config["background"] : null ;

		$this->isHead = isset($config["isHead"]) && $config["isHead"] === true ;

		if(Article::$entries == null){
			$parser = new Parser();          // Create a Parser
			$listener = new Listener();      // Create and configure a Listener
			$parser->addListener($listener); // Attach the Listener to the Parser
			$parser->parseString(file_get_contents(__DIR__ . "/../../sources/bibliographie.bib"));   // or parseFile('/path/to/file.bib')
			Article::$entries = $listener->export();  // Get processed data from the Listener
		}
	}

	public function renderContent(){
		$content = file_get_contents($this->contentFile);

		//dump($this);

		if(pathinfo($this->contentFile)["extension"] == "md"){

			$parsedown = new Parsedown();
			$content = $parsedown->text($content);

			preg_match_all('/(?<!\\\\){(.+)(?<!\\\\)}/m', $content, $matches);

			$classes = [] ;

			for($i = 0 ; $i < count($matches[0]) ; $i++){
				try {
					$c = $i + 1 ;
					$content = preg_replace('/ ?' . preg_quote($matches[0][$i], "/") . '/', "<sup>$c</sup>", $content, 1);
					$classes[] = $matches[1][$i];

				} catch (\Exception $e){
					throw new \Exception($this->name);
				}
			}

			foreach (Article::$entries as $entry){
				if(in_array($entry["citation-key"], $classes)){
					$this->references[] = $entry ;
				}
			}

			$content = str_replace("\{", "{", $content);
			$content = str_replace("\}", "}", $content);

			$content = preg_replace("/<h([1-6])>/", "<h$1 class='title is-$1'>", $content);
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

	/**
	 * @return mixed
	 */
	public function getReferences()
	{
		return $this->references;
	}

	/**
	 * @return null
	 */
	public function getBackground()
	{
		return $this->background;
	}


}