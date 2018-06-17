<?php

namespace App\Controller;

use App\Models\Article;
use App\Models\People;
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
    	# on prend les images

	    $public_images = __DIR__ . "/../../public/images" ;
		if(!file_exists($public_images)){
			mkdir($public_images);
		}

		$this->copyFolder(__DIR__ . "/../../sources", $public_images);

		# on charge les personnes

	    People::loadPeople();

		# puis on compile la page
		$config = Yaml::parseFile(__DIR__ . "/../../sources/sources.yaml");

		$articles = [];
		foreach($config as $id => $c){
			$articles[] = new Article($id, $c);
		}

        return $this->render("index.html.twig", [
        	"articles" => $articles,
	        "bibtex" => file_get_contents(__DIR__ . "/../../sources/bibliographie.bib")
        ]);
    }

    private function copyFolder($from_folder, $to_folder){

    	foreach(scandir($from_folder) as $file){
    		if(strpos($file, ".") === 0){continue ;}

    		if(
    			strpos(finfo_file(finfo_open(FILEINFO_MIME_TYPE), $from_folder . "/" . $file), "image") !== 0 &&
		        !is_dir($from_folder . "/" . $file)
		    ){
    			continue;
		    }

		    if(is_dir($from_folder . "/" . $file)){
    			if(!file_exists($to_folder . "/" . $file)){ mkdir($to_folder . "/" . $file); }
			    $this->copyFolder($from_folder . "/" . $file, $to_folder . "/" . $file);
		    } else {
    		    copy($from_folder . "/" . $file, $to_folder . "/" . $file);
		    }
	    }

    }
}
