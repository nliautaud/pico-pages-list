<?php
/**
 * Flat and nested pages list navigation for Pico CMS.
 *
 * - Adds twig global `{{ nested_pages }}` in addition to `{{ pages }}`
 * - Render flat or nested HTML navigation tree with `navigation` twig filter
 * - Filter pages and nested pages by paths with `exclude()` and `only()` twig filters
 *
 * Examples :
 *
 *     {{ pages | navigation }} // output a flat pages list
 *     {{ nested_pages | navigation }} // output a nested pages list
 *     {{ nested_pages | exclude('sub/page') | navigation }} // filtered nested pages list
 *     {% assign filtered = pages | only('sub/path/') %} // get filtered flat pages array
 *
 * @author  Nicolas Liautaud
 * @link    https://github.com/nliautaud/pico-pages-list
 * @link    http://picocms.org
 * @license http://opensource.org/licenses/MIT The MIT License
 */
class PicoPagesList extends AbstractPicoPlugin
{
    const API_VERSION = 3;

    protected $items;

    /**
     * Construct the nested pages array.
     *
     * Triggered after Pico has read all known pages
     *
     * See {@link DummyPlugin::onSinglePageLoaded()} for details about the
     * structure of the page data.
     *
     * @see    Pico::getPages()
     * @param  array[] &$pages data of all known pages
     * @return void
     */
    public function onPagesLoaded(array &$pages)
    {
        $this->items = $this->nestedPages($pages);
    }

    /**
     * Register `$this` in the Twig `{{ PagesList }}` variable.
     *
     *
     * @see    Pico::getTwig()
     * @param  Twig_Environment &$twig Twig instance
     * @return void
     */
    public function onTwigRegistered(Twig_Environment &$twig)
    {
        $twig->addFilter(new Twig_SimpleFilter('navigation', function($pages) {
            return $this->output($pages);
        }, array('is_safe' => array('html'))));

        $twig->addFilter(new Twig_SimpleFilter('exclude', function($pages, array $paths = array()) {
            return $this->filterPages($pages, $paths);
        }, array('is_variadic' => true)));

        $twig->addFilter(new Twig_SimpleFilter('only', function($pages, array $paths = array()) {
            return $this->filterPages($pages, $paths, true);
        }, array('is_variadic' => true)));
    }

    /**
     * Register `$this` in the Twig `{{ PagesList }}` variable.
     *
     * Triggered before Pico renders the page
     *
     * @see    Pico::getTwig()
     * @see    DummyPlugin::onPageRendered()
     * @param  string           &$templateName  file name of the template
     * @param  array            &$twigVariables template variables
     * @return void
     */
    public function onPageRendering(string &$templateName, array &$twigVariables)
    {
        $twigVariables['nested_pages'] = $this->items;
    }

    /**
     * Create the nested pages array according to the pages paths.
     *
     * @see    nested_path
     * @param  array $pages Pico pages flat array
     */
    private function nestedPages($pages)
    {
        $this->items = array();
        foreach ($pages as $page) {
            $nested_path = $this->nested_path($page);
            $this->items = array_replace_recursive($this->items, $nested_path);
        }
        return $this->items['_childs'];
    }

    /**
     * Create a nested array of a given path, with the page at the end.
     * Each path fragment is in "_childs" of the parent.
     *
     * @param  array  $page the page array
     * @return array  the nested path
     */
    private function nested_path($page)
    {
        $path = self::rtrim($page['id'], '/index');
        $parts = explode('/', $path);
        $count = count($parts);

        $arr = array();
        $parent = &$arr;
        foreach($parts as $id => $part) {
            $value = [];
            if(!$part || $id == $count-1) {
                $value = $page;
            } else {
                $currpath = implode('/', array_slice($parts, 0, $id+1));
                $value['id'] = $currpath;
            }
            if($path && !$part) {
                $parent = $value;
                break;
            }

            $parent['_childs'][$part] = $value;
            $parent = &$parent['_childs'][$part];
        }
        return $arr;
    }

    /**
     * Strip a substring from the end of a string
     *
     * @param  array  $str The input string.
     * @param  array  $substr The substring to remove.
     * @return string  The modified string.
     */
    private static function rtrim($str, $substr)
    {
        $length = strlen($substr);
        return (substr($str, -$length) === $substr) ? substr($str, 0, -$length) : $str;
    }

    /**
     * Filter the pages array according to given paths, as exclusive or inclusive.
     *
     * @param array $pages The flat or nested pages array.
     * @param array $filteredPaths The paths to filter.
     * @param boolean $isInclusive If `true` only corresponding paths are kept.
     * @return array The filtered array of pages.
     */
    public static function filterPages(
        $pages,
        $filteredPaths,
        $isInclusive = false,
        $inclusiveOutput = []
    ) {
        foreach($pages as $i => $page) {
            if (!isset($page['id'])) return;

            $path = self::rtrim($page['id'], '/index');
            $isSubPath = self::isSubPath($path, $filteredPaths);
            if ($isSubPath) {
                if ($isInclusive) $inclusiveOutput[$i] = $page;
                else unset($pages[$i]);
                continue;
            }
            if (isset($page['_childs'])) {
                $childs = self::filterPages($page['_childs'], $filteredPaths, $isInclusive, $inclusiveOutput);
                if ($isInclusive) $inclusiveOutput = $childs;
                else $pages[$i]['_childs'] = $childs;
            }
        }
        return $isInclusive ? $inclusiveOutput : $pages;
    }

    /**
     * Return if the given path is a subpath of the given parent path(s)
     *
     * @param  string  $path
     * @param  array  $parentPaths array of paths
     * @return boolean
     */
    private static function isSubPath($path, $parentPaths)
    {
        foreach($parentPaths as $p) {
            if (!is_string($p)) continue;
            if ($path == $p) return true;
            if (strncmp($path, $p, strlen($p)) === 0)
                return true;
        }
        return false;
    }

    /**
     * Return an html nested list based on a nested pages array.
     *
     * @param  array  $pages a nested pages array
     * @return string the html list
     */
    private function output($pages)
    {
        if (!is_array($pages)) return;
        $html = '<ul>';
        foreach ($pages as $pageID => $page)
        {
            if (!empty($page['hidden'])) continue;

            $childsOutput = '';
            if(isset($page['_childs'])) {
                $childsOutput = $this->output($page['_childs']);
            }

            $url = isset($page['url']) ? $page['url'] : false;

            // use title if the page has one and make a link if the page exists.
            if(!$url) $item = "<span>$pageID</span>";
            else {
                $name = !empty($page['title']) ? $page['title'] : $pageID;
                $item = "<a href=\"$url\">$name</a>";
            }

            // add the pageID in class and indicate if it is the current or parent of the current page.
            $class = $pageID;
            $class .= $url ? ' is-page' : ' is-directory';
            if ($childsOutput) $class .= ' has-childs';

            $currentPage = $this->getPico()->getCurrentPage();
            if ($currentPage && $currentPage['id']) {
                if ($currentPage['id'] === $page['id']) {
                    $class .= ' is-current is-active';
                } elseif ($page['id'] !== 'index') {
                    $pagePath = (basename($page['id']) === 'index') ? dirname($page['id']) . '/' : $page['id'] . '/';
                    if (substr_compare($pagePath, $currentPage['id'], 0, strlen($pagePath)) === 0) $class .= ' is-active';
                }
            }

            $html .= "<li class=\"$class\">$item$childsOutput</li>";
        }
        $html .= '</ul>';
        return $html;
    }
}
