+++
title = "Faster find algorithms in nom"
date = "2017-05-23"
description = "This article explains quickly how I've improved `nom`'s performance by 78% when parsing in some cases."
[taxonomies]
keywords=["string", "algorithm", "rust"]
[extra]
pinned = true
+++

[Tagua VM](https://github.com/tagua-vm/) is an experimental PHP virtual
machine written in Rust and LLVM. It is composed as a set of libraries.
One of them that keeps me busy these days is
[`tagua-parser`](https://github.com/tagua-vm/parser). It contains the
lexical and syntactic analysers for the PHP language, in addition to the
AST (Abstract Syntax Tree). If you would like to know more about this
project, you can see this conference I gave at the PHPTour last week:
[Tagua VM, a safe PHP virtual
machine](https://speakerdeck.com/hywan/tagua-vm-a-safe-php-virtual-machine).

The library `tagua-parser` is built with parser combinators. Instead of
having a classical grammar, compiled to a parser, we write pure
functions acting as small parsers. We then combine them together. This
post does not explain why this is a sane approach in our context, but
keep in mind this is much easier to test, to maintain, and to optimise.

Because this project is complex enought, we are delegating the parser
combinator implementation to [nom](https://github.com/Geal/nom/).

> nom is a parser combinators library written in Rust. Its goal is to
> provide tools to build safe parsers without compromising the speed or
> memory consumption. To that end, it uses extensively Rust's *strong
> typing*, _zero copy_ parsing, _push streaming_, _pull streaming_, and
> provides macros and traits to abstract most of the error prone
> plumbing.

Recently, I have been working on optimisations in the `FindToken` and
`FindSubstring` traits from nom itself. These traits provide methods to
find a token (i.e. a lexeme), and to find a substring, crazy naming.
However, this is not totally valid: `FindToken` expects to find a single
item (if implemented for `u8`, it will look for a `u8` in a `&[u8]`),
and `FindSubstring` really is about finding a substring, so a token of
any length.

It appeared that these methods can be optimised in some cases. Both
default implementations are using Rust iterators: Regular iterator for
`FindToken`, and [window
iterator](https://doc.rust-lang.org/std/slice/struct.Windows.html) for
`FindSubstring`, i.e. an iterator over overlapping subslices of a given
length. We have benchmarked big PHP comments, which are analysed by
parsers actively using these two trait implementations.

Here are the result, before and after our optimisations:

```text
test …::bench_span ... bench:      73,433 ns/iter (+/- 3,869)
test …::bench_span ... bench:      15,986 ns/iter (+/- 3,068)
```

A boost of 78%! Nice!

The [pull request has been merged](https://github.com/Geal/nom/pull/507)
today, thank you Geoffroy Couprie! The new algorithms heavily rely on
[the `memchr` crate](https://github.com/BurntSushi/rust-memchr). So all
the credits should really go to Andrew Gallant! This crate provides a
safe interface `libc`'s `memchr` and `memrchr`. It also provides
fallback implementations when either function is unavailable.

The new algorithms are only implemented for `&[u8]` though. Fortunately,
the implementation for `&str` fallbacks to the former.

This is small contribution, but it brings a very nice boost. Hope it
will benefit to other projects!

I am also blowing the dust off of [Algorithms on
Strings](https://www.amazon.com/Algorithms-Strings-Maxime-Crochemore/dp/0521848997),
by M. Crochemore, C. Hancart, and T. Lecroq. I am pretty sure it should
be useful for nom and `tagua-parser`. If you haven't read this book yet,
I can only encourage you to do so!
