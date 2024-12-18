+++
title = "Building a new site!"
date = "2024-10-08"
description = "It's time to rewrite my site from scratch. I'm nostalgic of the good old Web. This site is hand-written, and promotes smallness, speed, simplicity and fun. Let's discover the new lore (!), and let's talk about series a little bit."
[taxonomies]
keywords=["rust", "site", "rdfa"]
+++

The time has come. I needed to rewrite my site from scratch. It was first
implemented with [Jekxyl], a static site generator written with [the XYL
language][XYL], a language I've developed inside [Hoa]. I've migrated my blog to
[WordPress.com] when [I was working there](@/articles/2017-04-18-bye-bye-liip-hello-automattic/index.md). The [Gutenberg editor][Gutenberg] is really great,
but there is no great support for `<code>`. Plus, the theme I was using was
pretty heavy. The homepage was 1.15MiB! A simple article was 1.9MiB. Clearly
not really efficient. I wanted something more customisable, something light,
something I can hack, and more importantly, I wanted to start series.

# Enter Zola

[Zola] is a static site generator written in [Rust]. It uses [CommonMark] for
the markup, which is nice and straightforward to use. The template system is
powerful and simple. Zola can build 34 pages in 392ms at the time of writing, I
consider this is fast.

Nothing particular to say. It's a boring tool, which is great compliment. It
just works! In a couple of hours, I was able to get everything up and running.

# Site's features

The site contains articles and series. A series is composed of several episodes.
That's it. The URL patterns are the followings:

* `/articles/<article-id>/` to view an article,
* `/series/<series-id>/` to view all episodes of a series,
* `/series/<series-id>/<episode-id>/` to view a particular episode of a series.

## Homepage

The homepage provides:

* the latest series, and
* pinned articles.

To _pin_ an article, I add the following TOML declarations in the frontmatter of
an article:

```toml
[extra]
pinned = true
```

This `pinned` declaration is not recognised by Zola: the `[extra]` section
contains user-defined values. Then, it's a matter of filtering by this value in
the template:

```html
{% for page in section.pages | filter(attribute = "extra.pinned", value = true) -%}
```

That's a nice feature to promote some articles.

In comparison to WordPress.com, the new homepage is 36.8KiB, that's 31 times less!

## Articles

An article has some metadata like:

* the publishing time,
* the reading time,
* keywords,
* edition.

If you read this article in October 2024, you might see all that in this very
article. The beauty of this hides in the source code though:

```html
<main vocab="https://schema.org">
  <article class="article" typeof="Article">
    <header>
      <h1 property="name">Building a new site!</h1>

      <div class="metadata">
        <time title="Published date" datetime="2024-10-08" property="datePublished">October 08, 2024</time>
        <span title="Reading time" property="timeRequired" content="PT2M">2 minutes read</span>
        <span title="Keywords" property="keywords" content="rust, site">
          Keywords:&nbsp;<a href="/keywords/rust">rust</a>, <a href="/keywords/site">site</a>, <a href="/keywords/rdfa">rdfa</a>
        </span>
        <span><a href="https://github.com/Hywan/mnt.io/edit/main/content/articles&#x2F;2024-10-08-building-a-new-site&#x2F;index.md" title="Submit a patch for this page">Edit</a> this page</span>
        <meta property="description" content="…" />
      </div>
    </header>

    <!-- … -->
```

First off, the site uses HTML semantics as much as possible with `article`,
`header`, `time`, `meta` etc. Second, you may also notice the `vocab`, `typeof`,
`property` and `content` attributes. This is an extension to HTML for [Resource
Description Framework in Attributes][RDFa] (RDFa for short). This is common to
add more semantics data to your content. It helps automated tools to analyse the
content of a Web document, and makes sense of it. [schema.org] is a
collaborative effort to create schemas for structured data, and that's what I
use in this site for the moment.

Last neat thing: did you notice you can edit the page? The code lives on Github,
and everyone is free to submit a patch!

## Series

A series is pretty similar to an article, except that it adds another level of
indirection with episodes.

Similarly to articles with `pinned`, a series has its own metadata:

```toml
[extra]
complete = true
```

`complete` indicates whether the series is complete or in progress.

A series also has buttons to navigate to the previous or the next episodes.
Nothing fancy, but it's fun to be able to do all that with Zola.

The hierarchy is intuitive to understand, and it uses RDFa heavily too, for
example a series overview with all its episodes looks like this:

```html
<main vocab="https://schema.org">
  <section typeof="CreativeWorkSeries">
    <h1 property="name">From Rust to beyond</h1>

    <!-- … -->

    <h2>Episodes</h2>

    <div role="list">
      <div role="listitem" class="article-poster" property="hasPart" typeof="Article">
        <a href="/series/from-rust-to-beyond/prelude/" property="url">Episode 1 – <span property="name">Prelude</span></a>
        
        <div class="metadata">
          <!-- … --> 
        </div>
      </div>

      <div role="listitem" class="article-poster" property="hasPart" typeof="Article">
        <a href="/series/from-rust-to-beyond/the-webassembly-galaxy/" property="url">Episode 2 – <span property="name">The WebAssembly galaxy</span></a>

        <div class="metadata">
          <!-- … --> 
        </div>

      <!-- … -->
```

First off, we use the `role` HTML attribute to change the semantics
of some elements: here `div` become a `ul` or a `li`. Second, we use
`typeof="CreativeWorkSeries"` to describe a series, which is composed
of different parts: `property="hasPart"`. Each part is an article:
`typeof="Article"`, which has its own semantics: `property="name"` etc. The
markup is extremely simple but it contains all required information. HTML is
really powerful, I'm not going to lie!

## Discuss

One novelty is the _Discuss_ menu item at the top of the site. It contains a
link to a [Matrix] public room: [https://matrix.to/#/#mnt_io:matrix.org][discuss], where
anybody can come to talk about an article, a series, ask questions, or simply
chill. You're very welcome there!

## Good ol' Web

The site has a short CSS stylesheet written by hand with no framework (oh yeah).
It weights 11KiB (uncompressed), heavy, I know.

The site also has [RSS] and [Atom] feeds for syndications. It even has a
blogroll under the _Recommandations_ Section in the footer. Well, you get it,
I'm nostalgic of the old Web. It's absolutely incredible what it is possible
to achieve today with HTML and CSS without any frameworks or polyfills. So much
resources are wasted nowadays…

## <q>Personnages</q>

The biggest novelty is [the lore](@/lore.md) I've developed for this new version
of the site. Please, welcome 3 characters: _Le Compte_, _Le Factotum_, and _Le
Procureur_.

These characters will help to explain not trivial concepts by interacting with
me. Let me copy the lore here.

### Le Comte

{% comte() %}
My name is _Le Comte_. I enjoy being the main character of this story. I am
mostly here to learn, and to interrogate our dear author.

My resources are unlimited. I am fortunate to have a fortune with a secret
origin. If I want to understand something, I will work as hard as possible to
try to make light on it. My new caprice is these new modern things people are
calling _computers_. They seem really powerful and I want to learn everything
about them!

I often ask help to my Factotum for the dirty, and sometimes illegal tasks. I
rarely ask help to Le Procureur, we don't really appreciate his presence.
{% end %}

### Le Factotum

{% factotum() %}
My name is _Le Factotum_. It's a latin word, literally saying “do everything”.
I'm here to assist Le Comte in its fancies.

Even if I have an uneventful life now, Le Comte is partly aware of my smuggling
past. He says no word about it, but he knows I kept contact with old friends
across various countries and cultures. These relations are useful to Le Comte to
achieve its quests to learn everything about computers.

Fundamentally, when Le Comte wants to do something manky, he asks me the best
way to do that. And I always have a solution.
{% end %}

### Le Procureur

{% procureur() %}
My name is _Le Procureur_. I am the son of the Law and the Order. I know what
is legal, and what is illegal. If an information is missing, a detail, an
exactness, I know where to find the answer.

Some people believe I am irritating, but I consider myself the defenser of
discipline.
{% end %}

## Optimised for smallness, speed, semantics and fun!

At this step, it should be clear the site has been optimised for smallness,
speed and semantics. Even the fonts aren't custom: I use [Modern Font Stacks]
to find a font stacks that work on most computers. Two devices may not have the
same look and feel for this site and that's perfectly fine. That's the nature of
the Web.

I encourage you [to read the source code of this site][mnt.io], to fork it, to
play with it, to get inspired by it. It's important to own your content, and to
not give your work to other platforms.

I really hope you'll enjoy the content I'm preparing. You can start with the
first episode of the new series: [Reactive programming in Rust,
Observability](@/series/reactive-programming-in-rust/2024-09-19-observability/index.md).
See you there!

[Jekxyl]: https://github.com/jekxyl/jekxyl
[XYL]: https://github.com/hoaproject/Xyl
[Hoa]: https://github.com/hoaproject/Central
[WordPress.com]: https://wordpress.com
[Gutenberg]: https://github.com/WordPress/gutenberg
[Zola]: https://www.getzola.org/
[Rust]: https://www.rust-lang.org/
[CommonMark]: https://commonmark.org/
[RDFa]: https://www.w3.org/TR/rdfa-primer/
[schema.org]: https://schema.org/
[Matrix]: https://matrix.org/
[discuss]: https://matrix.to/#/#mnt_io:matrix.org
[Modern Font Stacks]: https://modernfontstacks.com/
[RSS]: https://en.wikipedia.org/wiki/RSS
[Atom]: https://en.wikipedia.org/wiki/Atom_(web_standard)
[mnt.io]: https://github.com/Hywan/mnt.io
