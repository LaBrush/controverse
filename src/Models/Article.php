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
	private $color ;
	private $menuSep ;

	private $isHead ;

	static private $entries = null ;



	public function __construct($id, $config)
	{
		$this->id = $id ;
		$this->name = $config["name"];
		$this->relativePath = $config["file"] ;
		$this->contentFile = __DIR__ . "/../../sources/" . $config["file"] ;
		$this->color = isset($config["color"]) ? $config["color"] : null ;
		$this->menuSep = isset($config["menu_sep"]) ? $config["menu_sep"] : null ;

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

		if(pathinfo($this->contentFile)["extension"] == "md"){

			$parsedown = new Parsedown();
			$content = $parsedown->text($content);

			$content = preg_replace('/quote\{(.+)\}\{(.+)}/mU', '<blockquote class="blockquote"><p class="mb-0">$1</p><footer class="blockquote-footer">$2</footer></blockquote>', $content);

			preg_match_all('/(?<!\\\\){(.+)(?<!\\\\)}/mU', $content, $matches);

			for($i = 0 ; $i < count($matches[0]) ; $i++){
				try {

					$citation_key = $matches[1][$i];
					$citation = null ;

					foreach(Article::$entries as $entry){
						if($entry["citation-key"] == $citation_key){
							$citation = $entry ;
							break ;
						}
					}

					if($citation){
						$author = "" ;
						$year = "" ;

						if(isset($citation["author"])){ $author = $citation["author"] ; }
						if(isset($citation["journal"])){ $author = $citation["journal"] ; }

						if(isset($citation["year"])){ $year = ", " . $citation["year"]; }

						$content = preg_replace('/ ?' . preg_quote($matches[0][$i], "/") . '/', "<span class='text-muted'>(" . $author . $year . ")</span>", $content, 1);
					}

				} catch (\Exception $e){
					//throw new \Exception($this->name);
					throw $e ;
				}
			}

			$content = str_replace("\{", "{", $content);
			$content = str_replace("\}", "}", $content);

			$content = "<div class='container'>$content</div>";
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
	public function getColor()
	{
		return $this->color;
	}

	/**
	 * @return null
	 */
	public function getMenuSep()
	{
		return $this->menuSep;
	}


}