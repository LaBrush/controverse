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

	private $isTitle ;
	private $titleLevel ;

	private $menuSep ;
	private $isHead ;
	private $newPage ;


	private $parsedown;

	static private $entries = null ;
	static private $articles = [] ;

	public function __construct($id, $config)
	{
		$this->id = $id ;
		$this->name = $config["name"];
		$this->relativePath = $config["file"] ;
		$this->contentFile = __DIR__ . "/../../sources/" . $config["file"] ;
		$this->color = isset($config["color"]) ? $config["color"] : null ;
		$this->illustration = isset($config["illustration"]) ? "images/" . $config["illustration"] : null ;

		$this->isTitle = isset($config["type"]) && strpos($config['type'], "title") == 0;
		$this->titleLevel = $this->isTitle ? explode("-", $config['type'])[1] : 0 ;

		$this->menuSep = isset($config["menu_sep"]) ? $config["menu_sep"] : null ;
		$this->isHead = isset($config["isHead"]) && $config["isHead"] === true ;
		$this->newPage = isset($config["new_page"]) && $config["new_page"] === true ;

		$this->parsedown = new Parsedown()  ;

		if(Article::$entries == null){
			$parser = new Parser();          // Create a Parser
			$listener = new Listener();      // Create and configure a Listener
			$parser->addListener($listener); // Attach the Listener to the Parser
			$parser->parseString(file_get_contents(__DIR__ . "/../../sources/bibliographie.bib"));   // or parseFile('/path/to/file.bib')
			Article::$entries = $listener->export();  // Get processed data from the Listener
		}

		Article::$articles[] = $this ;
	}

	public function renderContent(){
		if($this->isTitle() && $this->relativePath == null){
			return "<div class='container'><h" . $this->titleLevel .">" . $this->getName() . "</h" . $this->titleLevel ."></div>" ;
		}

		$content = file_get_contents($this->contentFile);

		try {
			pathinfo($this->contentFile)["extension"];
		} catch (\Exception $e){
			trigger_error($this->contentFile);
		}

		if(pathinfo($this->contentFile)["extension"] == "md"){

			$content = $this->parsedown->text($content);

			# creation de la breadcrumb

			for($i = 0, $c = count(Article::$articles) ; $i < $c ; $i++){
				if(Article::$articles[$i] == $this){
					break ;
				}
			}

			$stack = [$this] ;

			for (; $i >= 0 ; $i--){
				if (Article::$articles[$i]->isTitle() && (Article::$articles[$i]->titleLevel < $stack[0]->titleLevel || $stack[0]->titleLevel == null)){
					array_unshift($stack, Article::$articles[$i]);
				}

				if(Article::$articles[$i]->isHead()){
					array_unshift($stack, Article::$articles[$i]);
					break ;
				}
			}

			$breadcrumb = '<nav aria-label="breadcrumb">' ;
			$breadcrumb .= '<ol class="breadcrumb">' ;

			for($i = 0, $c = count($stack) ; $i < $c ; $i++){
				$breadcrumb .= '<li class="breadcrumb-item ' . (($i == $c-1) ? "active" : "") .  '"><a href="#' . $stack[$i]->getId() . '">' . $stack[$i]->getName() .'</a></li>' ;
			}

			$breadcrumb .= '</ol>';
			$breadcrumb .= '</nav>';

			$content = $breadcrumb . $content ;

			# creation de la biliographie

			$bib = "<ol class='text-muted'>" ;

			foreach (Article::$entries as $entry){
				$bib .= "<li>" ;

				if($entry["type"] == "article"){
					$bib .= (isset($entry["author"]) ? $entry["author"] : "") . " (" . (isset($entry["year"]) ? $entry["year"] : "") . ")" . (isset($entry["title"]) ? $entry["title"] : "") . " <em>" . (isset($entry["journal"]) ? $entry["journal"] : "") . "</em>";
				} else {
					$bib .= (isset($entry["year"]) ? $entry["year"] : ""). " <em>" . (isset($entry["title"]) ? $entry["title"] : ""). "</em> " . (isset($entry["author"]) ? $entry["author"] : "") . ", " . (isset($entry["month"]) ? $entry["month"] : "");
				}

				$bib .= "</li>" ;
			}

			$bib .= "</ol>";

			$content = str_replace("{{bibliographie}}", $bib, $content);

			# création des liens

			preg_match_all('/ (?<!quote)(?<!\\\\){((.(?!\{))+)(?<!\\\\)}/mU', $content, $matches);

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

					if($citation == null){
						$citation = ["author" => "<span style='color: red;'><strong>Référence incorrecte</strong>" . $matches[0][$i] . "</span>"];
					}

					if($citation){
						$author = "" ;
						$year = "" ;

						if(isset($citation["author"])){ $author = $citation["author"] ; }
						if(isset($citation["journal"])){ $author = "<em>" . $citation["journal"] . "</em>" ; }

						if(isset($citation["year"])){ $year = ", " . $citation["year"]; }

						$content = preg_replace('/ ?' . preg_quote($matches[0][$i], "/") . '/', " <span class='text-muted' title='" . (isset($citation["title"]) ? $citation["title"] : "") ."'>(" . $author . $year . ")</span>", $content, 1);
					}

				} catch (\Exception $e){
					//throw new \Exception($this->name);
					throw $e ;
				}
			}

			# échappement des accolades
			$content = str_replace("\{", "{", $content);
			$content = str_replace("\}", "}", $content);

			# encadrés
			$content = str_replace("\start_encadre", "<div class='border p-3 rounded'>", $content);
			$content = str_replace("\stop_encadre", "</div>", $content);

			# ajout des citations
			$content = preg_replace('/quote\{((.|\n)+)\}\{(.+)}/mU', '<blockquote class="blockquote"><p class="mb-0">$1</p><footer class="blockquote-footer">$3</footer></blockquote>', $content);

			# ajout des noms de personnes
			$content = preg_replace_callback("/people{(.+)}{(.+)}/U", function($arg){

				foreach (People::$list as $people){
					if($people->getId() == $arg[2]){
						return $this->makeHover($people, $arg[1]);
					}
				}

				return "<span class='text-danger'>Bad reference made to " . $arg[1] .  " with " . $arg[2] . "</span>";

			},$content);

			$content = "<div class='container text-justify'>$content</div>";
		}

		/* Réécriture des urls */
		$rp = $this->relativePath ;
		$content = preg_replace_callback('/<img src=\"(\S+)\"/m', function($arg) use (&$rp){
			$res = str_replace($arg[1], "images/" . pathinfo($rp)["dirname"] . "/" . $arg[1], $arg[1]);
			$res = str_replace($arg[1], $res, $arg[0]);

			return $res;
		}, $content);

		$content = preg_replace_callback('/<img .+ alt="(.+)" .+>/U', function($arg) {
			$alt = $arg[1] ;
			$alt = str_replace("float-left", '', $alt);
			$alt = str_replace("float-right", '', $alt);

			$alt = preg_replace("/col\-[0-9]+/", "", $alt);

			$float = "" ;
			if(strpos($arg[1], 'float-left')){ $float = "float-left" ; }
			elseif(strpos($arg[1], 'float-right')){ $float = "float-right" ; }
			else { $float = "m-auto" ; }

			$col = "" ;
			preg_match("/col-([0-9]+)/", $arg[1], $match);

			if($match){
				$col = "col-md-" . $match[1] ;
			}

			return "<div class='text-center px-4 $float $col'>" . $arg[0] ."<p class='text-muted pt-2 mb-0'><em>" . $alt . "</em></p></div>";
		}, $content);
		$content = $content . "<div class='clearfix'></div>";

		if (strpos($content, "no_popover") === false) {
			$content = preg_replace_callback("/href=['|\"]#(.+)['|\"]/Um", function ($arg) {
				$popover = "";

				$file = __DIR__ . "/../../sources/resumes/" . $arg[1] . ".md";
				if (file_exists($file)) {
					$preview = file_get_contents($file);
					$preview = $this->parsedown->text($preview);

					preg_match("/<h[1-6]>(.+)<\/h[1-6]>/", $preview, $title);
					if (count($title) >= 2) {
						$title = $title[1];
					} else {
						$title = "";
					}

					$preview = preg_replace("/<h[1-6]>(.+)<\/h[1-6]>/", "", $preview);

					$popover = 'data-container="body" data-toggle="popover" data-trigger="focus hover" data-placement="top" data-title="' . $title . '" data-content="' . $preview . '""';
				}

				return $arg[0] . " " . $popover;
			}, $content);
		}
		$content = str_replace("no_popover", "", $content);

		$content = preg_replace_callback("/{readfile\((.+)\)}/", function ($arg){

			$path = __DIR__ . "/../../sources/resumes/" . $arg[1] . ".md" ;
			if(file_exists($path)){
				return htmlspecialchars($this->parsedown->text(file_get_contents($path)));
			} else {
				return "" ;
			}

		}, $content);

		if($this->isNewPage()){
			# ajout des bookmarks

			for($i = 0, $c = count(Article::$articles) ; $i < $c ; $i++){
				if(Article::$articles[$i] == $this){
					break ;
				}
			}

			$nav = "" ;

			if($i > 0){
				$prev = Article::$articles[$i - 1];
				$nav .= "<div class='col-md-6'><a class='no-back' href='#" . $prev->getId() . "'>Article précédent : " . $prev->getName() . "</a></div>";
			}

			if($i < count(Article::$articles) - 2){
				$next = Article::$articles[$i + 1];
				$nav .= "<div class='col-md-6 text-right'><a class='no-back' href='#" . $next->getId() . "'>Article suivant : " . $next->getName() . "</a></div>";
			}

			$content = $content . "<div class='container py-4'><div class='row'>$nav</div></div>";
		}

		return $content ;
	}

	function makeHover(People $people, $name = null){
		return "<span class='text-primary' data-container='body' data-toggle='popover' data-trigger='hover' data-placement='top' data-title='" . htmlspecialchars($people->getName()) . "' data-content='" . htmlspecialchars($people->getDescription()) . "'>" . ($name !== null ? $name : $people->getName()) ."</span>" ;
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

	/**
	 * @return bool
	 */
	public function isTitle(): bool
	{
		return $this->isTitle;
	}

	/**
	 * @return int
	 */
	public function getTitleLevel(): int
	{
		return $this->titleLevel;
	}

	/**
	 * @return bool
	 */
	public function isNewPage(): bool
	{
		return $this->newPage;
	}
}