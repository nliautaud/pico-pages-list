# Pico Pages List

A nested pages list plugin for the stupidly simple & blazing fast, flat file CMS [Pico](http://pico.dev7studios.com).

## Installation

Copy `pico_pages_list.php` to the `plugins` directory of your Pico Project.

## Usage

Add a generated nested pages list in your *theme* by using the following Twig variable :

	{{ pages_list }}

You'll automatically get something like :

* [A cool page]()
* [Sub-page is coming]()
	* [The choosen one]()
	* category
		* [A page]()
* [untitled]()

Under the hood :

```html
<ul>
	<li class="titled">
		<a href="http://mysite.com/titled">A cool page</a>
	</li>
	<li class="foo-page is-parent">
		<a href="http://mysite.com/foo-page">Sub-page is coming</a>
		<ul>
			<li class="child is-current">
				<a href="http://mysite.com/foo-page/child">The choosen one</a>
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
	</li>
	<li class="untitled">
		<a href="http://mysite.com/untitled">untitled</a>
	</li>
</ul>
```

## Features

Generates a clean nested html list, with links only if the page exists, using page titles for those who have one. Uses handy css classes :

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

Search for short and clean source code.

*The plugin will add server-side per-page options only if it became possible to define custom page headers and access their values in `get_pages()` hook, and will not implements this functionality using Pico config file, as a design choice. Basic filtering may use CSS hiding tough. *