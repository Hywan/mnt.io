install-css-dev:
	npm install -g postcss postcss-cli postcss-preset-env cssnano

build-css:
	postcss 'static/style/*.css' --dir static/style/min --no-map --use postcss-preset-env --use cssnano

build-site:
	zola build

build: build-css build-site

watch:
	postcss 'static/style/*.css' --dir static/style/min --no-map --use postcss-preset-env --use cssnano --watch &
	zola serve -f && fg
