build-css: build-site
	cp public/giallo.css static/style/syntax-theme.css
	lightningcss static/style/*.css --minify --output-dir static/style/min/

build-site:
	zola build --drafts

build-search:
	export PATH=".:$$PATH"; pagefind --site public/ --output-subdir search/ --glob '{articles/*/index.html,series/*/*/index.html}'

build: build-site build-css build-search

watch:
	zola serve --drafts && fg
