<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\Routes;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LanguageDataControllerProvider implements ControllerProviderInterface {

	private $langListRegExp = '^([a-zA-Z]{2}([-][a-zA-Z]{2})?(~|$))*';

	public function convertLangList($list, Request $request){

		$langs = array_filter(array_unique(explode('~', $list)));

		if (empty($langs)){
			return array('en');
		}

		return $langs;
	}

    public function connect(Application $app){

    	$controller = $app['controllers_factory'];

		/**
		 * meta data for languages (name, code, ...)
		 */
		$controller->get('/meta', function(Application $app, Request $request ) {

			$data = $app['ytplaylist']->getAvailableLanguagesLike( $request->get('q') );
			return $app->json($data);

		})->bind('langmeta');

		/**
		 * All videos
		 */
		$controller->get('/languages', function(Application $app ) {
			
			return $app['twig']->render('videolist.twig', array(

				'videos' => $app['ytplaylist']->getVideos()
			));

		})->bind('langall');

		/**
		 * find videos filtered by languages provided
		 */
		$controller->get('/languages/{withWithout}/{anyEvery}/{lang_list}', function( $withWithout, $anyEvery, array $lang_list, Application $app ) {
			
			return $app['twig']->render('videolist.twig', array(

				'videos' => $app['ytplaylist']->getVideosFilterLang( 
					$lang_list, 
					array(
						'type' => $anyEvery, 
						'negate' => $withWithout === 'with'
					)
				)
			));
		})
		->bind('langfilter')
		->assert('lang_list', $this->langListRegExp)
		->assert('withWithout', '(with|without)')
		->assert('anyEvery', '(any|every)')
		->convert('lang_list', array(&$this, 'convertLangList'));

		return $controller;
	}
}
