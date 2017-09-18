# Pico Pages List

A nested pages list plugin for [Pico CMS](http://picocms.org).

## Installation

Copy `PicoPagesList.php` to the `plugins` directory of your Pico Project.

## Usage

Add a nested navigation in your theme by using the following Twig command :

```twig
{{ PagesList.html }}
```

You'll automatically get something like :

* [A cool page]()
* [Sub-page is coming]()
	* [The choosen one]()
	* category
		* [A page]()
* [untitled]()

### Styling

The default html output is a clean nested list with extra classes that provides the possibility to build hierarchical navigations and to target specific pages and directories.

```html
<ul>
  <li class="titled is-page">
    <a href="http://mysite.com/titled">A cool page</a>
  </li>
  <li class="foo is-page has-childs is-current">
    <a href="http://mysite.com/foo">Sub-page is coming</a>
    <ul>
      <li class="child is-page has-childs is-current is-active">
        <a href="http://mysite.com/foo/child">The choosen one</a>
      </li>
      <li class="category is-directory has-childs">
        <span>category</span>
        <ul>
          <li class="bar is-page">
            <a href="http://mysite.com/foo/category/bar">A page</a>
          </li>
        </ul>
      </li>
    </ul>
  </li>
  <li class="untitled is-page">
    <a href="http://mysite.com/untitled">untitled</a>
  </li>
</ul>
```

```css
.foo-item { /* an item named "foo-item" */ }
.foo-item > a { /* the link of a page named "foo-item" */ }
.foo-item > span { /* the name of a directory named "foo-item"  */ }
.foo-item > ul { /* the childs of "foo-item" */ }
.foo-item > ul ul { /* the deep childs of "foo-item" */ }

.is-page { /* the pages, with links */ }
.is-directory { /* the directories, with simple names */ }
.is-current { /* the current page */ }
.is-active { /* the items in the path of the current page */ }
.has-childs { /* the items with childs */ }
```

As a simple example, you may show sub-pages only if their parent is active :

```css
.mymenu li.is-page:not(.is-active) ul {
    display: none;
}
```

### Filtering output

You can target or exclude specific paths from the output with `PagesList.html()` parameters.

```twig
{{ PagesList.html(paths, exclude) }}

{{ PagesList.html }}  // all
{{ PagesList.html('foo/bar') }}  // only foo/bar childs
{{ PagesList.html('foo/bar', true) }}  // all except foo/bar childs
```

You can specify multiple paths by using an array or a comma-separated string.

```twig
// filter multiple paths
{{ PagesList.html('foo/bar,other') }}
{{ PagesList.html(['foo/bar', 'other']) }}
```

## Custom loop

You can access the items within `PagesList.items`.

Every item may contain child entries in `_childs`, so you may want a recursive Twig template or macro to walk trough it.

```twig
{% macro menu(item) %}
    {% import _self as macros %}
    {% for name,child in item._childs %}
        <li>
            {% if child.url %}
              <a href="{{ child.url }}">{{ child.title }}</a>
            {% else %}
              <span>{{ name }}</span>
            {% endif %}
            {% if child._childs %}
            <ul>
                {{ macros.menu(child) }}
            </ul>
            {% endif %}
        </li>
    {% endfor %}
{% endmacro %}

{% import _self as macros %}

<ul class="main-menu">
    {{ macros.menu(PagesList.items) }}
</ul>
```

## Settings

The lists are sorted according to the default settings in Pico `config.php`.

```php
$config['pages_order_by'] = 'date';
$config['pages_order'] = 'desc';
```
