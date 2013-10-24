# Pico Pages List

A nested pages list plugin for the stupidly simple & blazing fast, flat file CMS [Pico](http://pico.dev7studios.com).

## installation

Copy `pico_pages_list.php` to the `plugins` directory of your Pico Project.

## usage

Add a generated nested pages list in your *theme* by using the following Twig variable :

	{{ pages_list }}

You'll automatically get something like :

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
				<a href="http://mysite.com/foo-page/child">A cool page</a>
			</li>
		</ul>
	</li>
</ul>
```

## features

Generates a clean nested html list with handy css classes. Display the page title or the path name.

```css
#nav .foo-page a {
	# access to a specific page link
}
#nav .foo-page .child a {
	# access to a specific foo-page/child link
}
#nav .is-current {
	# access to the current page item
}
#nav .is-parent {
	# access to every parent item of the current one
}
```