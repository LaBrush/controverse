<?php

namespace App\Controller;

use App\Models\Article;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Yaml\Yaml;

class IndexController extends Controller
{
    /**
     * @Route("/", name="index")
     */
    public function index()
    {
		$config = Yaml::parseFile(__DIR__ . "/../../sources/sources.yaml");

		$articles = [];
		foreach($config as $id => $c){
			$articles[] = new Article($id, $c);
		}

        return $this->render("index.html.twig", [
        	"articles" => $articles
        ]);
    }
}
