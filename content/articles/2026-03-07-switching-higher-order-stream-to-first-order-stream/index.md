+++
title = "Switching higher-order stream to first-order stream"
date = "2026-03-07"
description = "foo"
[taxonomies]
keywords=["rust", "asynchronous", "stream"]
[extra]
pinned = true
+++

_Stream_ is to _Future_ what _Iterator_ is to _value_:

- An iterator produces values synchronously,
- A stream produces values asynchronously,

In terms of Rust, an `Iterator` is defined as:

```rust
trait Iterator {
    type Item;

    fn next(&mut self) -> Option<Self::Item>;
}

enum Option<T> {
    /// There is no value.
    None,
    /// There is a value!
    Some(T),
}
```

And a `Stream` is defined as:

```rust
trait Stream {
    type Item;

    fn poll_next(
        self: Pin<&mut Self>,
        cx: &mut Context<'_>,
    ) -> Poll<Option<Self::Item>>;
}

enum Poll<T> {
    /// The value is not ready yet.
    Pending,
    /// The value is immediately ready!
    Ready(T),
}
```

(Let's ignore [`Pin`] and [`Context`] for the moment[^pin]).

`Poll::Pending` means that no value is ready yet: the `Stream` must be polled
later. When is the best time to poll the `Stream` again is not the topic of this
article and won't be discussed. `Poll::Ready(Some(T))` means the value is ready
and returned. Finally, `Poll::Ready(None)` means the `Stream` has been _closed_
and must not be polled again!

{% comte() %}

Concise and straightforward. I like the similarities: `next` vs. `poll_next`,
`Option<T>` vs. `Poll<Option<T>>`. Did I tell you how much I appreciate
consistency?

Additionally, one of the strengths of `Iterator` is the _combinators_: a way
to transform an `Iterator` into another `Iterator`. These combinators can be
chained, like that:

```rust
iterator
    .skip_while(…)
    .filter(…)
    .enumerate(…)
    .map(…)
```

They are nothing more than methods returning a new `Iterator`, that's the secret
to chain them.

And similarly to `Iterator`, a `Stream` can have many _combinators_: a way to
transform a `Stream` into another `Stream`! Once again: consistency…

{% end %}

Exactly! That's really exciting because it brings really nice features.

Let's see an example with _map_:

<math display="block">
  <mrow>
    <mi>map</mi>
    <mo rspace="1rem">:</mo>
    <mtext>𝚂𝚝𝚛𝚎𝚊𝚖</mtext>
    <mo form="prefix">⟨</mo>
    <mi>Item</mi>
    <mo form="infix">=</mo>
    <mi>T</mi>
    <mo form="postfix">⟩</mo>
    <mo>→</mo>
    <mtext>𝚂𝚝𝚛𝚎𝚊𝚖</mtext>
    <mo form="prefix">⟨</mo>
    <mi>Item</mi>
    <mo form="infix">=</mo>
    <mi>U</mi>
    <mo form="postfix">⟩</mo>
  </mrow>
</math>

In Rust, it would look like this:

```rust
trait Stream {
    type Item;

    // …

    fn map<U, F>(self, f: F) -> impl Stream<U>
    where
        F: FnMut(Self::Item) -> U,
    {
        // …
    }
}
```

The method `map` takes a function `F` that maps the item of the `Stream` (of
type `Stream::Item`) to a new type `U`, resulting in another `Stream<U>`.

Let's see another example with _filter_, shall we?

<math display="block">
  <mrow>
    <mi>filter</mi>
    <mo rspace="1rem">:</mo>
    <mtext>𝚂𝚝𝚛𝚎𝚊𝚖</mtext>
    <mo form="prefix">⟨</mo>
    <mi>Item</mi>
    <mo form="infix">=</mo>
    <mi>T</mi>
    <mo form="postfix">⟩</mo>
    <mo>→</mo>
    <mtext>𝚂𝚝𝚛𝚎𝚊𝚖</mtext>
    <mo form="prefix">⟨</mo>
    <mi>Item</mi>
    <mo form="infix">=</mo>
    <mi>T</mi>
    <mo form="postfix">⟩</mo>
  </mrow>
</math>

In Rust, it would look like this:

```rust
trait Stream {
    type Item;

    // …

    fn filter<F>(self, f: F) -> impl Stream<Self::Item>
    where
        F: FnMut(&Self::Item) -> bool,
    {
        // …
    }
}
```

The method `filter` takes a function `F` that tells if the item of the `Stream`
should be kept or not, resulting in another `Stream` with the same type for
the items.

Okay. And just like `Iterator::flatten`, `Stream::flatten` can exist too. Things
start to be fun, let's dig a bit.

<math display="block">
  <mrow>
    <mi>flatten</mi>
    <mo rspace="1rem">:</mo>
    <mtext>𝚂𝚝𝚛𝚎𝚊𝚖</mtext>
    <mo form="prefix">⟨</mo>
    <mi>Item</mi>
    <mo form="infix">=</mo>
    <mtext>𝚂𝚝𝚛𝚎𝚊𝚖</mtext>
    <mo form="prefix">⟨</mo>
    <mi>Item</mi>
    <mo form="infix">=</mo>
    <mi>T</mi>
    <mo form="postfix">⟩</mo>
    <mo form="postfix">⟩</mo>
    <mo>→</mo>
    <mtext>𝚂𝚝𝚛𝚎𝚊𝚖</mtext>
    <mo form="prefix">⟨</mo>
    <mi>Item</mi>
    <mo form="infix">=</mo>
    <mi>T</mi>
    <mo form="postfix">⟩</mo>
  </mrow>
</math>

In Rust, it would look like this:

```rust
trait Stream {
    type Item;

    // …

    fn flatten(
        self,
    ) -> impl Stream<Item = <Self::Item as Stream>::Item>
    where
        Self::Item: Stream,
    {
        // …
    }
}
```

Do not be intimidated by the syntax. Try read it one piece at a time.

{% comte() %}

Hmm, let's dissect the `where` clause:

- `Self` represents the type implementing `Stream`.
- `Self::Item` represents the type of the item produced by `Stream`.
- So `Self::Item: Stream` means that the stream… produces… streams!

And the return type:

- `impl Stream` means any type that implements `Stream`.
- (We know that the stream produces streams with `Self::Item: Stream` so…)
- `<Self::Item as Stream>::Item` represents the type of stream item from
  `Self::Item`, i.e. considering `Self::Item` is a `Stream`, it's the
  `Stream::Item`.

The Mathematical notation was easier on this one.

{% end %}

It's probably easier but it doesn't explain what `flatten` does exactly. Let's
see a bit of pseudo-code to understand how it would work:

```rust
// Pseudo Rust-ish code.

let outer_stream;
let inner_stream;

loop {
    inner_stream = match outer_stream.poll_next() {
        ready(inner_stream) => inner_stream,
        pending => yield pending,
        closed => break,
    };

    loop {
        match inner_stream.poll_next() {
            ready(item) => yield item,
            pending => yield pending,
            closed => break,
        }
    }
}
```

{% procureur() %}

The terms _first-order_ and _higher-order_ can be intimating at first, but they
are rather simple to understand. A function `fn(x) -> y` is a first-order
function. However, `fn(fn(x) -> y) -> z` is a higher-order function: it's a
function that takes a function as an argument. The function `fn(x) -> fn(y) ->
z` is also a higher-order function because it returns a function.

Said differently: a function is a higher-order function if at least:

- it takes another function as arguments,
- it returns a function.

It's analog for a stream. A stream doesn't have _arguments_ nor _returns_
something: it _produces_ items. Knowing that, a stream is a first-order stream
if it produces non-stream items, and a stream is a higher-order stream if:

- the produced items are streams.

In the case of `flatten`, it transforms a stream of streams of `T` into a stream
of `T`. We go from a higher-order stream to a first-order stream.

{% end %}

<!--
Poll::Ready(loop {
    if let Some(s) = this.next.as_mut().as_pin_mut() {
        if let Some(item) = ready!(s.poll_next(cx)) {
            break Some(item);
        } else {
            this.next.set(None);
        }
    } else if let Some(s) = ready!(this.stream.as_mut().poll_next(cx)) {
        this.next.set(Some(s));
    } else {
        break None;
    }
})
-->

[`Pin`]: https://doc.rust-lang.org/std/pin/struct.Pin.html
[`Context`]: https://doc.rust-lang.org/std/task/struct.Context.html
[_Pin_]: https://without.boats/blog/pin/

[^pin]: I recommend to read [_Pin_], it's an excellent article that goes in
    depth about pinning.
