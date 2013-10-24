# Pico Pages List

A nested pages list plugin for the stupidly simple & blazing fast, flat file CMS [Pico](http://pico.dev7studios.com).

## installation

Copy `pico_pages_list.php` to the `plugins` directory of your Pico Project.

## usage

Add a generated nested pages list in your *theme* by using the following Twig variable :

	{{ pages_list }}

You'll automatically get something like :

* [A cool page]()
* [untitled]()
* [Sub-page is coming]()
	* [The choosen one]()
* I am not a page, just a path to the following page
	* [A page]()

Under the hood :

```html
<ul>
	<li class="titled">
		<a href="http://mysite.com/titled">A cool page</a>
	</li>
	<li class="untitled">
		<a href="http://mysite.com/untitled">untitled</a>
	</li>
	<li class="foo-page parent">
		<a href="http://mysite.com/foo-page">Sub-page is coming</a>
		<ul>
			<li class="child current">
				<a href="http://mysite.com/foo-page/child">The choosen one</a>
			</li>
		</ul>
	</li>
	<li class="category">
		category
		<ul>
			<li class="bar">
				<a href="http://mysite.com/category/bar">A page</a>
			</li>
		</ul>
	</li>
</ul>
```

## features

Display the page title if there is one, and a link if the page exists, or use the name.

Generates a clean nested html list with handy css classes :

```css
#nav .foo-page a {
	/* access to a specific page link */
}
#nav .foo-page .child a {
	/* access to a specific foo-page/child link */
}
#nav .is-current {
	/* access to the current page item */
}
#nav .is-parent {
	/* access to every parent item of the current one */
}
```