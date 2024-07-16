+++
title = "DuckDuckGo in a Shell"
date = "2015-08-05"
+++

## The tip

When I go outside my terminal, I am kind of lost. I control everything
from my terminal and I hate clicking. That's why I found a small tip
today to open a search on DuckDuckGo directly from the terminal. It
redirects me to my default browser in the background, which is the
expected behavior.

First, I create a function called `duckduckgo`:

```bash
function duckduckgo {
    query=`php -r 'echo urlencode($argv[1]);' "$1"`
    open 'https://duckduckgo.com/?q='$query
}
```

Note how I (avoid to) deal with quotes in `$1`.

Then, I just have to create an alias called `?`:

```bash
alias '?'='duckduckgo'
```

And here we (duckduck) go!

```sh
$ ? "foo bar's baz"
```

You can [see the
commit](https://github.com/Hywan/Dotfiles/commit/fab6d98448240a787eb0e34ab836c5c43d50379c)
that adds this to my “shell home framework”.

Oh, and to open the default browser, I use
[`open (1)`](https://developer.apple.com/library/mac/documentation/Darwin/Reference/ManPages/man1/open.1.html),
like this:

```bash
alias open='open -g'
```

Hope it helps!
