<?php

/**
 * A nested pages list plugin for Pico CMS.
 *
 * @author  Nicolas Liautaud
 * @link    https://github.com/nliautaud/pico-pages-list
 * @link    http://picocms.org
 * @license http://opensource.org/licenses/MIT The MIT License
 */
final class PicoPagesList extends AbstractPicoPlugin
{
	private $pages;
	private $pages_urls;
	private $current_url;
	private $base_url;
	private $hide_list;
    
    /**
	 * Register Pico base url and hide_list config.
	 *
	 * Triggered after Pico has read its configuration
     *
     * @see    Pico::getConfig()
     * @param  array &$config array of config variables
     * @return void
     */
	public function onConfigLoaded(array &$config)
	{
        $this->base_url = rtrim($config['base_url'], '/') . '/';
		$this->hide_list = array_map('trim', explode(',', $config['hide_pages']));
	}

	/**
	 * Store existing Pico pages urls, the current url
	 * and construct the nested pages array.
	 *
     * Triggered after Pico has read all known pages
     *
     * See {@link DummyPlugin::onSinglePageLoaded()} for details about the
     * structure of the page data.
     *
     * @see    Pico::getPages()
     * @see    Pico::getCurrentPage()
     * @see    Pico::getPreviousPage()
     * @see    Pico::getNextPage()
     * @param  array[]    &$pages        data of all known pages
     * @param  array|null &$currentPage  data of the page being served
     * @param  array|null &$previousPage data of the previous page
     * @param  array|null &$nextPage     data of the next page
     * @return void
     */
    public function onPagesLoaded(
        array &$pages,
        array &$currentPage = null,
        array &$previousPage = null,
        array &$nextPage = null
    ) {
		$this->pages_urls = array();
		foreach ($pages as $p) {
			$this->pages_urls[] = $p['url'];
		}
		$this->pages = array();
		$this->current_url = $currentPage['url'];
		$this->construct_pages($pages);
	}

	/**
	 * Register the html output in the Twig {{ pages_list }} variable.
	 *
     * Triggered before Pico renders the page
     *
     * @see    Pico::getTwig()
     * @see    DummyPlugin::onPageRendered()
     * @param  Twig_Environment &$twig          twig template engine
     * @param  array            &$twigVariables template variables
     * @param  string           &$templateName  file name of the template
     * @return void
     */
    public function onPageRendering(Twig_Environment &$twig, array &$twigVariables, &$templateName)
    {
		$twigVariables['pages_list'] = $this->output($this->pages);
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
		foreach ($pages as $page)
		{
			$page['path'] = rtrim(str_replace($this->base_url,'',$page['url']), '/');
			$nested_path = $this->nested_path($page);
			$this->pages = array_merge_recursive($this->pages, $nested_path);
		}
	}

	/**
	 * Create a nested path of a given path, with page infos at the end.
	 * Each path fragment is a "_child" of its parent fragment.
	 *
	 * @param  array  $page the corresponding page data, with 'path' key.
	 * @return array        the nested path
	 */
	private function nested_path($page)
	{
		$parts = explode('/', $page['path']);
		$count = count($parts);

		$arr = array();
		$parent = &$arr;
		foreach($parts as $id => $part) {
			$value = array();
			if(!$part || $id == $count-1) {
				$value = array(
					'url'=>$page['url'],
					'path'=>$page['path'],
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
		foreach ($pages['_childs'] as $key => $page)
		{
			if($this->is_hidden($page['path'])) continue;

			$url = $page['url'];
			$filename = basename($url);
			$childs = $this->output($page);

			// use title if the page have one, and make a link if the page exists.
			$item = !empty($page['title']) ? $page['title'] : ($filename ? $filename : $key);
			if($url && in_array($url, $this->pages_urls))
				$item = '<a href="'.$url.'">'.$item.'</a>';

			// add the filename in class, and indicates if is current or parent
			$class = $filename;
			if($this->current_url == $url) $class .= ' is-current';
			elseif(strpos($this->current_url, $url) === 0) $class .= ' is-parent';

			$html .= '<li class="'.$class.'">' . $item . $childs . '</li>';
		}
		$html .= '</ul>';
		return $html;
	}

	/**
	 * Return if the given path had to be hidden or not.
	 *
	 * @param  string  $path the page short path
	 * @return boolean
	 */
	private function is_hidden($path)
	{
		foreach($this->hide_list as $p)
		{
			if( !$p ) continue;
			if( $path == $p ) return true;
			if( strpos($path, $p) === 0 ) {
				if( substr($p,-1) == '/' ) return true;
				elseif( $path[strlen($p)] == '/' ) return true;
			}
		}
		return false;
	}
}
?>