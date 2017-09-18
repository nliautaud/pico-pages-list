<?php
/**
 * A nested pages list plugin for Pico CMS.
 *
 * @author  Nicolas Liautaud
 * @link    https://github.com/nliautaud/pico-pages-list
 * @link    http://picocms.org
 * @license http://opensource.org/licenses/MIT The MIT License
 */
class PicoPagesList extends AbstractPicoPlugin
{
    public $items;
    private $base_url;
    private $current_url;

    /**
     * Store the current url and construct the nested pages array.
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
        $this->current_url = $currentPage['url'];
        $this->construct_pages($pages);
    }

    /**
     * Register `$this` in the Twig `{{ PagesList }}` variable.
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
        $twigVariables['PagesList'] = $this;
        $twigVariables['pages_list'] = $this->html(); // backward compatibility
    }

    /**
     * Create the nested pages array according to the pages paths.
     *
     * @see    nested_path
     * @param  array $pages Pico pages flat array
     */
    private function construct_pages($pages)
    {
        $this->base_url = $this->getConfig('base_url');
        $this->items = array();
        foreach ($pages as $page) {
            $nested_path = $this->nested_path($page);
            $this->items = array_merge_recursive($this->items, $nested_path);
        }
    }

    /**
     * Create a nested array of a given path, with the page at the end.
     * Each path fragment is in "_childs" of the parent.
     *
     * @param  array  $page the page array
     * @param  array  $base_url the base url, substracted from the page url
     * @return array  the nested path relative to $base_url
     */
    private function nested_path($page)
    {
        $path = rtrim(str_replace($this->base_url, '', $page['url']), '/');
        $parts = explode('/', $path);
        $count = count($parts);

        $arr = array();
        $parent = &$arr;
        foreach($parts as $id => $part) {
            $value = array();
            if(!$part || $id == $count-1) {
                $value = $page;
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
     * Render a nested html list of pages.
     *
     * @param  array|string  $paths array or comma-separated list of pages paths to filter
     * @param  boolean  $exclude if true render all but the given paths
     * @return string the html list
     */
    public function html($paths = array(), $exclude = false)
    {
        if (!is_array($paths)) $paths = explode(',', $paths);
        return $this->output($this->items, $paths, $exclude);
    }

    /**
     * Return an html nested list based on a nested pages array.
     *
     * @param  array  $pages a nested pages array
     * @param  array  $paths_filters array of paths to keep or skip
     * @param  string  $currentPath the current walked path
     * @return string the html list
     */
    private function output($pages, $paths_filters, $isFilterExcluding, $currentPath = '')
    {
        if(!$pages['_childs']) return;
        $html = '<ul>';
        foreach ($pages['_childs'] as $pageID => $page)
        {
            $childPath = $currentPath ? $currentPath.'/'.$pageID : $pageID;

            $is_filtered = $this->isConcerned($childPath, $paths_filters);
            if($is_filtered && $isFilterExcluding) continue;
            if($paths_filters && !$is_filtered && !$isFilterExcluding) continue;

            $childs = $this->output($page, $paths_filters, $childPath);

            $url = isset($page['url']) ? $page['url'] : false;

            // use title if the page have one, and make a link if the page exists.
            if(!$url) $item = "<span>$pageID</span>";
            else {
                $name = !empty($page['title']) ? $page['title'] : $pageID;
                $item = "<a href=\"$url\">$name</a>";
            }

            // add the pageID in class, and indicates if is current or parent of current
            $class = $pageID;
            $class .= $url ? ' is-page' : ' is-directory';
            if ($childs) $class .= ' has-childs';
            if ($this->current_url == $url) $class .= ' is-current';
            if (strpos($this->current_url, $this->base_url . $childPath) === 0) $class .= ' is-active';

            $html .= "<li class=\"$class\">$item$childs</li>";
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
    private static function isConcerned($path, $excluded_paths)
    {
        foreach($excluded_paths as $p)
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
