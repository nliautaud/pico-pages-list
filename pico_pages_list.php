<?php

/**
 * A nested pages list plugin for the
 * stupidly simple & blazing fast, flat file CMS Pico.
 *
 * @author	Nicolas Liautaud
 * @link	http://nliautaud.fr
 * @link    http://pico.dev7studios.com
 * @license http://opensource.org/licenses/MIT
 */
class Pico_Pages_List
{
	private $pages;
	private $pages_urls;
	private $current_url;
	private $base_url;


	// HOOKS called by Pico ---------------

	/**
	 * Store the base url defined in Pico settings.
	 */
	public function config_loaded(&$settings)
	{
		$this->base_url = $settings['base_url'];
	}

	/**
	 * Store existing Pico pages urls, the current url
	 * and construct the nested pages array.
	 */
	public function get_pages(&$pages, &$current_page, &$prev_page, &$next_page)
	{
		$this->pages_urls = array();
		foreach ($pages as $p) {
			$this->pages_urls[] = $p['url'];
		}

		$this->pages = array();
		$this->current_url = $current_page['url'];
		$this->construct_pages($pages);
	}

	/**
	 * Register the html output in the Twig {{ pages_list }} variable.
	 */
	public function before_render(&$twig_vars, &$twig)
	{
		$twig_vars['pages_list'] = $this->output($this->pages);
	}


	// CORE ---------------


	/**
	 * Create a nested array of the pages, according to their paths.
	 * Merge all individual pages *nested_path*.
	 *
	 * @see    nested_path
	 * @param  array $pages Pico pages flat array
	 */
	private function construct_pages($pages)
	{
		array_multisort($this->pages_urls, SORT_ASC, $pages);
		foreach ($pages as $page)
		{
			if($page['hide']) continue;

			$page_path = substr($page['url'], strlen($this->base_url)+1);
			$nested_path = $this->nested_path($page_path, $page);
			$this->pages = array_merge_recursive($this->pages, $nested_path);
		}
	}

	/**
	 * Create a nested path of a given path, with page infos at the end.
	 * Each path fragment is a "_child" of its parent fragment.
	 *
	 * @param  string $path the/page/relative/path
	 * @param  array  $page the corresponding page data
	 * @return array        the nested path
	 */
	private function nested_path($path, $page)
	{
		$parts = explode('/', $path);
		$count = count($parts);

		$arr = array();
		$parent = &$arr;
		foreach($parts as $id => $part) {
			$value = array();
			if(!$part || $id == $count-1) {
				$value = array(
					'url'=>$page['url'],
					'title'=>$page['title'],
					'hide'=>$page['hide']
				);
			}
			if(!$part) {
				$parent = $value;
				break;
			}
			$parent['_childs'][$part] = $value;
			$parent = &$parent['_childs'][$part];
		}
		return $arr;
	}

	/**
	 * Create an html list based on the nested pages array.
	 *
	 * @param  array  $pages a nested pages array
	 * @return string        the html list
	 */
	private function output($pages)
	{
		if(!isset($pages['_childs'])) return '';

		$html = '<ul>';
		foreach ($pages['_childs'] as $page)
		{
			$url = $page['url'];
			$filename = basename($url);
			$childs = $this->output($page);

			// use title if the page have one, and make a link if the page exists.
			$item = !empty($page['title']) ? $page['title'] : $filename;
			if(in_array($url, $this->pages_urls))
				$item = '<a href="'.$url.'">'.$filename.'</a>';

			// add the filename in class, and indicates if is current or parent
			$class = $filename;
			if($this->current_url == $url) $class .= ' is-current';
			elseif(strpos($this->current_url, $url) === 0) $class .= ' is-parent';

			$html .= '<li class="'.$class.'">' . $item . $childs . '</li>';
		}
		$html .= '</ul>';
		return $html;
	}
}
?>