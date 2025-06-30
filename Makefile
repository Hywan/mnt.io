build-css:
	lightningcss static/style/*.css --minify --output-dir static/style/min/

build-site:
	zola build

build: build-css build-site

watch:
	zola serve && fg
