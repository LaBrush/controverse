<?php

namespace App\Twig;

use App\Models\Article;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class StepExtension extends AbstractExtension
{
	private $twig ;

	/**
	 * SlideExtension constructor.
	 * @param $twig
	 */
	public function __construct(\Twig_Environment $twig)
	{
		$this->twig = $twig;
	}


	public function getFilters(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render', [$this, 'renderStep'], ["is_safe" => ["html"]]),
        ];
    }

	/**
	 * @param Article $article
	 * @return string
	 * @throws \Twig_Error_Loader
	 * @throws \Twig_Error_Runtime
	 * @throws \Twig_Error_Syntax
	 */
    public function renderStep(Article $article)
    {
    	return $this->twig->render("slide.html.part.twig", [
			"article" => $article
	    ]);
    }
}
