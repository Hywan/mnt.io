build-css:
	lightningcss static/style/*.css --minify --output-dir static/style/min/

build-site:
	zola build

build-search:
	export PATH=".:$$PATH"; pagefind --site public/ --output-subdir search/ --glob '{articles/*/index.html,series/*/*/index.html}'

build: build-css build-site build-search

watch:
	zola serve && fg
