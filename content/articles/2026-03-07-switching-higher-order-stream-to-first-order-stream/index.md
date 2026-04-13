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
        context: &mut Context<'_>,
    ) -> Poll<Option<Self::Item>>;
}

enum Poll<T> {
    /// The value is not ready yet.
    Pending,
    /// The value is immediately ready!
    Ready(T),
}
```

(Let's ignore [`Pin`] and [`Context`] in this article[^pin]).

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

The method `map` takes a function `F` that maps the items of the `Stream` (of
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

The method `filter` takes a function `F` that tells if the items of the `Stream`
should be kept or not, resulting in another `Stream` with the same type for
the items.

## Flatten

Okay. Another one? Just like `Iterator::flatten`, `Stream::flatten` can exist
too. The idea is to flatten a stream of streams, i.e. an outer stream yielding
inner streams. Things start to be fun, let's dig a bit.

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
- `Self::Item` represents the type of the items produced by the `Stream`.
- So `Self::Item: Stream` means that the items are… streams! Phrased a bit
  differently: the stream produces streams.

And the return type:

- `impl Stream` means _any type that implements `Stream`_.
- (We know that the stream produces streams with `Self::Item: Stream` so…)
- `<Self::Item as Stream>::Item` represents the type of items produced by the
  produced streams.

The Mathematical notation was easier on this one.

{% end %}

It's probably easier but it doesn't explain what `flatten` does exactly. Let's
see a bit of Rust code to understand how it would work:

```rust
/// Type to flatten a stream.
// Ignore the `#[pin]`. We said we keep `Pin` and projection aside.
pub struct Flatten<Outer, Inner> {
    /// The outer stream that produces the inner streams.
    #[pin]
    outer_stream: Outer,

    /// The most recently produced inner stream.
    #[pin]
    inner_stream: Option<Inner>,
}
```

Good, good. Baby steps. Now, let's implement `Stream` on `Flatten` shouldn't we?

```rust
impl<Outer> Stream for Flatten<Outer, Outer::Item>
//                                    ^^^^^^^^^^^
//                                    |
//                                    our inner stream type!
where
    // My name is stream, `Outer` stream 🕵️.
    Outer: Stream,
    // `Outer` is a stream that produces streams!
    Outer::Item: Stream,
{
    type Item = <Outer::Item as Stream>::Item;

    fn poll_next(
        self: Pin<&mut Self>,
        context: &mut Context<'_>
    ) -> Poll<Option<Self::Item>>
    {
        // Ignore this. We said we keep `Pin` and projection aside.
        let mut this = self.pin_projection();

        loop {
            if let Some(inner_stream) = this.inner_stream.as_mut().as_pin_mut() {
                // There is an inner stream: let's poll it!
                match inner_stream.poll_next(context) {
                    // The inner stream produced an `item`!
                    Poll::Ready(Some(item)) => return Poll::Ready(Some(item)),

                    // The inner stream is closed: let's forget it.
                    Poll::Ready(None) => {
                        this.inner_stream.set(None);
                        // Let the loop run again.
                    }

                    // The inner stream is pending: nothing to do.
                    Poll::Pending => return Poll::Pending,
                }
            } else {
                // No inner stream? No problem: let's poll the outer stream!
                match this.outer_stream.as_mut().poll_next(context) {
                    // New inner stream!
                    // “Welcooomme iinneer streeaaam” says the crowd.
                    Poll::Ready(Some(inner_stream)) => {
                        this.inner_stream.set(Some(inner_stream));
                        // Let the loop run again.
                    }

                    // The outer stream is closed: let's close everything.
                    Poll::Ready(None) => return Poll::Ready(None),

                    // The outer stream is pending: nothing to do.
                    Poll::Pending => return Poll::Pending,
                }
            }
        }
    }
}

```

A cake walk. Thanks to `Poll` being an enum, and `match` being [`match`], this
code is easy to read and to understand. No surprise. Almost boring.

We notice that each newly polled inner stream —i.e. produced by the outer
stream— is consumed **entirely** until it's closed. Then, a new inner stream is
polled, and it keeps going until the outer stream is closed.

<figure>

<svg viewBox="0 0 886 214" role="img" class="schema">
  <g transform="translate(0 1)">
    <path class="arrow" stroke-miterlimit="10" d="M7.5 180.5h810"/>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M567.5 30.5h303.63"/>
      <path class="arrow-end" d="m876.38 30.5-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M7.5 30.5h113.63"/>
      <path class="arrow-end" d="m126.38 30.5-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <circle cx="157" cy="30" r="30" fill="#fff" stroke="#000" transform="translate(.5 .5)"/>
    <text x="157" y="35" font-size="12" text-anchor="middle">A</text>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M187.5 30.5h93.63"/>
      <path class="arrow-end" d="m286.38 30.5-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <circle cx="317" cy="30" r="30" fill="#fff" stroke="#000" transform="translate(.5 .5)"/>
    <text x="317" y="35" font-size="12" text-anchor="middle">B</text>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M347.5 30.5h153.63"/>
      <path class="arrow-end" d="m506.38 30.5-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M537.5 60.5v20.07q0 10 10 10h130q10 0 10 10v70q0 10 10 9.98l23.63-.04"/>
      <path class="arrow-end" d="m726.38 180.5-6.99 3.51 1.74-3.5-1.75-3.5Z"/>
    </g>
    <g font-size="12" text-anchor="middle">
      <path fill="#fff" d="M557 85h91v15h-91z"/>
      <text x="602.27" y="94">Inner stream</text>
    </g>
    <circle cx="537" cy="30" r="30" fill="#fff" stroke="#000" transform="translate(.5 .5)"/>
    <text x="537" y="35" font-size="12" text-anchor="middle">C</text>
    <circle cx="227" cy="180" r="30" fill="#fff" stroke="#000" transform="translate(.5 .5)"/>
    <text x="227" y="185" font-size="12" text-anchor="middle">1</text>
    <circle cx="407" cy="180" r="30" fill="#fff" stroke="#000" stroke-width="2"/>
    <text x="407" y="185" font-size="12" text-anchor="middle">3</text>
    <circle cx="317" cy="180" r="30" fill="#fff" stroke="#000" transform="translate(.5 .5)"/>
    <text x="317" y="185" font-size="12" text-anchor="middle">2</text>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M157.5 60.5v110q0 10 10 10h23.63"/>
      <path class="arrow-end" d="m196.38 180.5-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <g font-size="12" text-anchor="middle">
      <path fill="#fff" d="M123 104h69v15h-69z"/>
      <text x="156.67" y="115">Inner stream</text>
    </g>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M257.5 180.5h23.63"/>
      <path class="arrow-end" d="m286.38 180.5-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M347.5 180.5h23.63"/>
      <path class="arrow-end" d="m376.38 180.5-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M567.5 180.57h20-10 13.63"/>
      <path class="arrow-end" d="m596.38 180.57-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <circle cx="537" cy="180" r="30" fill="#fff" stroke="#000" transform="translate(.5 .5)"/>
    <text x="537" y="185" font-size="12" text-anchor="middle">4</text>
    <circle cx="627" cy="180" r="30" fill="#fff" stroke="#000" stroke-width="2"/>
    <text x="627" y="185" font-size="12" text-anchor="middle">5</text>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M317.5 60.5v50q0 10 10 10h130q10 0 10 10v40q0 10 10 10h23.63"/>
      <path class="arrow-end" d="m506.38 180.5-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <g font-size="12" text-anchor="middle">
      <path fill="#fff" d="M342 113h91v15h-91z"/>
      <text x="387.67" y="124.67">Inner stream</text>
    </g>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M787.5 180.57h20-10l13.63-.03"/>
      <path class="arrow-end" d="m816.38 180.53-6.99 3.52 1.74-3.51-1.75-3.49Z"/>
    </g>
    <circle cx="757" cy="180" r="30" fill="#fff" stroke="#000" transform="translate(.5 .5)"/>
    <text x="757" y="185" font-size="12" text-anchor="middle">6</text>
    <circle cx="847" cy="180" r="30" fill="#fff" stroke="#000" stroke-width="2"/>
    <text x="847" y="185" font-size="12" text-anchor="middle">7</text>
    <path fill="none" d="M17.5.5h90v30h-90z"/>
    <text x="19" y="20" font-size="12">Outer stream</text>
    <path fill="none" d="M17.5 150.5h110v30h-110z"/>
    <text x="19" y="170" font-size="12">Resulting stream</text>
  </g>
</svg>

<figcaption>

Behaviour of `Flatten`: An outer stream produces inner streams. Each inner
stream is consumed entirely before jumping on the next inner stream produced
by the outer stream. Even if the outer stream would be ready with a new inner
stream, it's not polled until the current inner stream is closed.

The outer stream produces the inner stream _A_ that produces 1, 2 and 3. Then,
since _A_ is closed, the outer stream is polled again and produces the new inner
stream _B_, which itself produces 4 and 5. It keeps going with the outer stream
producing the inner stream _C_, which itself produces 6 and 7.

</figcaption>

</figure>

Action time via a basic example, this time with the help of [the `futures`
crate][`futures`].

```rust
// `StreamExt` is the trait that “extends” the `Stream` trait.
// It contains all the combinators.
use futures::{executor, stream::{self, StreamExt}};

fn main() {
    executor::block_on(async {
        let stream = stream::iter(vec![
            // First inner streams.
            stream::iter(vec![1, 2, 3]),
            // Second inner stream.
            stream::iter(vec![4, 5]),
            // Third inner stream.
            stream::iter(vec![6, 7, 8, 9]),
        ])
        .flatten();
//       ^^^^^^^
//       |
//       the important piece

        dbg!(&stream.collect::<Vec<_>>().await);
    });
}
```

There is a lot going on here.

- `futures::executor` is an asynchronous runtime, also called an executor. The
  `block_on` function is a special executor that runs a future to completion on
  the current thread. It's perfect in our case as we just want to run a single
  asynchronous block, i.e. a future.
- [`stream::iter`][`futures::stream::iter`] builds a special stream called
  [`Iter`]. It converts an [`Iterator`] into a `Stream`. One can argue this is
  a bit useless as all items are already known. To whom I would gently remind…
  it's an example! It's here to illustrate the behaviour of `flatten`.
- So. `stream::iter`. We are building the outer stream that produces three inner
  streams. These inner streams are all `stream::iter` streams too. How lovingly
  pleasant.
- Finally, [`flatten`][`futures::StreamExt::flatten`] is called right before
  returning the flattened `stream`.

To test what the stream will return, we use the combinator
[`collect`][`futures::StreamExt::collect`], which collects all items, here in a
`Vec`. Since `collect` does _not_ return a `Stream` but a `Future`: it produces
a single value, not many. That's why we can simply `await` for the result. And
speaking of result: it displays…

```text
[src/main.rs:17:9] &stream.collect::<Vec<_>>().await = [
    1,
    2,
    3,
    4,
    5,
    6,
    7,
    8,
    9,
]
```

<i>throw confetti</i>.

`Flatten` is a classical example of a higher-order stream!

{% procureur() %}

The terms _first-order_ and _higher-order_ can be intimating at first, but they
are rather simple to understand.

A function `fn(x) -> y` is a first-order function. However, `fn(fn(x) -> y)
-> z` is a higher-order function: it's a function that takes a function as an
argument. The function `fn(x) -> fn(y) -> z` is also a higher-order function
because it returns a function.

Said differently: A function is a higher-order function if at least:

- it takes another function as argument,
- it returns a function.

This is analog for a stream. A stream doesn't have _arguments_ nor _returns_
something: it _produces_ items. Knowing that, a stream is a first-order stream
if it produces non-stream items, and a stream is a higher-order stream if:

- the produced items are streams.

In the case of `flatten`, it transforms a stream of streams of `T` into a stream
of `T`. We go from a higher-order stream to a first-order stream.

{% end %}

## <q lang="la">Ad astra</q>

Good news: this is the end of the introduction. Another good news: now we can
talk about the `switch` combinator!

{% comte() %}

All this was just an introduction? Really? I can't imagine how traumatized your
kids are when they ask a simple <q>why</q>…

That said, while we are on the subject of showing off with Latin quotes… one
comes to my mind timely: <q lang="la">fabricando fit faber</q>!

{% end %}

Exactly. We needed to explain all that to understand what the `switch`
combinator does. It _switches_ a higher-order stream to a first-order stream,
but not like `flatten`.

<math display="block">
  <mrow>
    <mi>switch</mi>
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

Okay, the signature of the combinator is exactly the same than `flatten`, but
the behaviour is different! Do you remember that `flatten` uses the outer stream
to produce an inner stream, which is consumed _entirely_ before polling the outer
stream again? `switch` is different, <i>open the documentation</i>:

> This combinator flattens a stream of streams, i.e. an outer stream yielding
> inner streams. This combinator always keeps the most recently yielded inner
> stream, and yields items from it, until the outer stream produces a new inner
> stream, at which point the inner stream to yield items from is switched to the
> new one.

The produced inner stream is kept until… the outer stream produces a new inner
stream.

- `flatten` polls the outer stream when there is no inner stream, or when the
  inner stream is closed.
- `switch` polls the outer stream every time and will poll the inner stream if
  the outer stream is pending or closed.

<figure>

<svg viewBox="0 0 656 214" role="img" class="schema">
  <g transform="translate(0 1)">
    <path class="arrow" stroke-miterlimit="10" d="M7.5 180.5h640"/>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M467.5 30.5h173.63"/>
      <path class="arrow-end" d="m646.38 30.5-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <circle cx="157" cy="30" r="30" transform="translate(.5 .5)"/>
    <text x="157" y="35" font-size="12" text-anchor="middle">A</text>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M187.5 30.5h33.63"/>
      <path class="arrow-end" d="m226.38 30.5-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <circle cx="257" cy="30" r="30" transform="translate(.5 .5)"/>
    <text x="257" y="35" font-size="12" text-anchor="middle">B</text>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M287.5 30.5h113.63"/>
      <path class="arrow-end" d="m406.38 30.5-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M437.5 60.5v20q0 10 10 10h5q5 0 5 10v70q0 10 10 10h23.63"/>
      <path class="arrow-end" d="m496.38 180.5-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <g font-size="12" text-anchor="middle">
      <path fill="#fff" d="M425 124h69v15h-69z"/>
      <text x="458.5" y="135.5">Inner stream</text>
    </g>
    <circle cx="437" cy="30" r="30" transform="translate(.5 .5)"/>
    <text x="437" y="35" font-size="12" text-anchor="middle">C</text>
    <circle cx="227" cy="180" r="30" transform="translate(.5 .5)"/>
    <text x="227" y="185" font-size="12" text-anchor="middle">1</text>
    <g stroke="#000" stroke-miterlimit="10">
      <path class="arrow" d="M157.5 60.5v110q0 10 10 10h23.63"/>
      <path class="arrow-end" d="m196.38 180.5-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <g font-size="12" text-anchor="middle">
      <path fill="#fff" d="M124 113h69v15h-69z"/>
      <text x="157.5" y="124.5">Inner stream</text>
    </g>
    <path class="arrow" stroke-miterlimit="10" d="M407.5 180.5h20-10 20"/>
    <circle cx="377" cy="180" r="30" transform="translate(.5 .5)"/>
    <text x="377" y="185" font-size="12" text-anchor="middle">4</text>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M257.5 60.5v50q0 10 10 10h30q10 0 10 10v40q0 10 10 10h23.63"/>
      <path class="arrow-end" d="m346.38 180.5-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <g font-size="12" text-anchor="middle">
      <path fill="#fff" d="M223 81h69v15h-69z"/>
      <text x="256.5" y="92.5">Inner stream</text>
    </g>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M557.5 180.5h20-10 13.63"/>
      <path class="arrow-end" d="m586.38 180.5-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <circle cx="527" cy="180" r="30" transform="translate(.5 .5)"/>
    <text x="527" y="185" font-size="12" text-anchor="middle">6</text>
    <circle cx="617" cy="180" r="30" stroke-width="2"/>
    <text x="617" y="185" font-size="12" text-anchor="middle">7</text>
    <path class="arrow" stroke-miterlimit="10" d="M257.5 180.5h30"/>
    <g stroke-miterlimit="10">
      <path class="arrow" d="M7.5 30.5h113.63"/>
      <path class="arrow-end" d="m126.38 30.5-7 3.5 1.75-3.5-1.75-3.5Z"/>
    </g>
    <path fill="none" d="M17.5.5h90v30h-90z"/>
    <text x="19" y="20" font-size="12">Outer stream</text>
    <path fill="none" d="M17.5 150.5h100v30h-100z"/>
    <text x="19" y="170" font-size="12">Resulting stream</text>
  </g>
</svg>

<figcaption>

Behaviour of `Switch`: An outer stream produces inner streams. Each inner stream
is consumed as long as the outer stream is pending. As soon as the outer stream
is ready with a new inner stream, the current inner stream is replaced by the
new one.

The outer stream produces the inner stream _A_. It starts producing 1, when
suddenly, the outer stream is ready with a new inner stream _B_. The inner
stream _A_ is replaced by _B_, which produces 4. Then, the outer stream is ready
with a new inner stream _C_, which replaces _B_. It produces 6 and 7.

</figcaption>

</figure>

Let's see how we can implement that in Rust:

```rust
/// Type to switch a stream.
// Ignore the `#[pin]`. We said we keep `Pin` and projection aside.
pub struct Switch<Outer>
where
    Outer: Stream
{
    /// The outer stream that produces the inner streams.
    #[pin]
    outer_stream: Outer,

    /// The state of the inner stream.
    #[pin]
    inner_stream_state: InnerStreamState<Outer::Item>,
}

/// `Option` with more super-powers.
// Ignore the `#[pin]`. Blah blah blah, you know the score.
enum InnerStreamState<Inner> {
    /// No inner stream has been yielded yet.
    None,

    /// The latest yielded inner stream.
    Some {
        #[pin]
        inner_stream: Inner,
    }
}
```

And now, the moment we are all waiting for, the implementation of `Stream`:

```rust
impl<Outer> Stream for Switch<Outer>
where
    // My name is stream, `Outer` stream 🕵️.
    Outer: Stream,
    // `Outer` is a stream that produces streams!
    Outer::Item: Stream,
{
    type Item = <Outer::Item as Stream>::Item;

    fn poll_next(
        self: Pin<&mut Self>,
        context: &mut Context<'_>
    ) -> Poll<Option<Self::Item>>
    {
        // Ignore this. We said we keep `Pin` and projection aside.
        let mut this = self.pin_projection();

        let mut outer_stream_is_closed = false;

        // Poll the latest inner stream eagerly.
        while let Poll::Ready(ready) = this.outer_stream.as_mut().poll_next(context) {
            match ready {
                // There is a new `inner_stream`!
                Some(inner_stream) => {
                    this.inner_stream_state.set(InnerStreamState::Some { inner_stream });
                    // Let the loop run.
                }

                None => {
                    outer_stream_is_closed = true;
                    break;
                }
            }
        }

        match this.inner_stream_state.pin_projection() {
            // No inner stream has been produced yet.
            InnerStreamState::None => {
                // The stream' state is the outer stream' state.
                if outer_stream_is_closed {
                    Poll::Ready(None)
                } else {
                    Poll::Pending
                }
            }

            // An inner stream exists: poll it!
            InnerStreamState::Some { inner_stream } => match inner_stream.poll_next(context) {
                // Inner stream produced an item.
                Poll::Ready(Some(item)) => Poll::Ready(Some(item)),

                // Both inner and outer streams are closed.
                Poll::Ready(None) if outer_stream_is_closed => Poll::Ready(None),

                // Only inner stream is closed or is pending.
                Poll::Ready(None) | Poll::Pending => Poll::Pending,
            },
        }
    }
}
```

Not rocket science, but as much fun! Now… action time!

```rust
let stream =
    stream::iter([
        // First inner stream.
        stream::iter(vec![1, 2, 3]),
        // Second inner stream.
        stream::iter(vec![4, 5]),
        /// Third inner stream.
        stream::iter(vec![6, 7, 8, 9]),
    ])
    .switch();
//   ^^^^^^
//   |
//   oh dear!

assert_eq!(
    stream.collect::<Vec<_>>().await,
    vec![6, 7, 8, 9],
);
```

{% comte() %}

Huh. The first and second inner streams are ignored. Like if the outer stream
was polled repeatedly until being pending.

Note, it matches the code _and_ the documentation when it says:

> This combinator always keeps the most recently yielded inner stream, and
> yields items from it

{% end %}

Correct! To understand the rest of the documentation and _why_ the `switch` combinator is pretty powerful, we need to imagine a funny example. <i>close eyes, and try to imagine a relevant example…</i>

{% factotum() %}

May I? We could imagine this flow:

- outer stream produces 7:
  - inner stream produces 7, 8, 9, 10, 11…
- outer stream produces 42:
  - inner stream produces: 42, 43, 44, 45, 46…

This example illustrates pretty well the power of `switch`: inner streams are
computed dynamically depending of the outer stream. It instantiates to a lot of concrete use cases, e.g.:

- given a UI component for a list
- the list can be updated via _diff_ operations, e.g.:
  - `Insert { index, value }` to insert new item `value` at `index`,
  - `Remove { index }` to remove an item at `index`,
  - `Set { index, value }` to update item at `index` to `value`,
  - `Reset { values }` to clear all items and insert new ones,
  - and so on.
- the list can be dynamically filtered by a term: when the filter is updated,
  the list must be reset (with `Reset`), and then new updates can be produced…

This is similar to the first example with 7 and 42.

{% end %}

Excellent idea! Let's write it.

First off, we need a way to have a stream that produces values coming from
another channel. <i>head is getting warm</i> Eh, _channel_! The `futures` crate
provides a [`channel::mpsc`][`futures::channel::mpsc`] module! `mpsc` stands
for _multi-producer, single-consumer_, that's one kind of queue for sending
values across asynchronous tasks. Basically, we have one or many _senders_ (the
_producers_) and a _receiver_ (the _consumer_). <i>scroll the documentation</i>.
[The `Receiver` type implements `Stream`][`impl Stream for Receiver`]! Okay,
it's official, the _receiver_ part is our outer stream here:

```rust
use futures::channel::mpsc;

// The queue is “infinite”, we said it is “unbounded”.
let (sender, receiver)  = mpsc::unbounded::<usize>();

let stream = receiver;

// `stream` is pending until the outer stream `receiver`
// produces something.
sender.unbounded_send(7).unwrap();
sender.unbounded_send(42).unwrap();

assert_eq!(
    stream.collect::<Vec<_>>().await,
    vec![7, 42],
);
```

That's a good start, isn't it?

So far, `stream` is a first-order stream. Let's compute inner streams! Hum,
wait a second, how do we create a `Stream` quickly without having to implement
`Stream` on a new structure?

{% factotum() %}

<q lang="la">Aut disce aut discede</q> (see, I know Latin too!). <i>clear its
throat</i>. Hum hum. Once again, the `futures` crate got you covered! There
is this excellent [`stream::poll_fn`][`futures::stream::poll_fn`] function: it
creates a `Stream` that runs the given function when polled.

[`futures::stream::poll_fn`]: https://docs.rs/futures/0.3.32/futures/stream/fn.poll_fn.html

{% end %}

I swear, I have nothing to do with these people. Nonetheless, it's a good
recommendation. Let's start easy:

```rust
use std::task::Poll;

let mut next_value: usize = 7;

let stream = stream::poll_fn(move |_| {
    let current_value = next_value;
    next_value = next_value.saturating_add(1);

    Poll::Ready(Some(current_value))
});

assert_eq!(
    stream.take(6).collect::<Vec<_>>().await,
    vec![7, 8, 9, 10, 11, 12],
);
```

The created stream never closes itself, that's why we need to use the `take`
combinator to… well… _take_… only 6 values here. But it works! Damn, it works!
Every time the stream is polled, it produces the next integer. Let's combine
these two streams and —come on!— let's use the `switch` combinator or what?!

```rust
let (sender, receiver) = mpsc::unbounded::<usize>();

let mut stream = pin!(receiver
    // For every new received value from `sender`…
    .map(|init_value| {
        // … let's create a new inner stream:
        let mut next_value = init_value;

        stream::poll_fn(move |_| {
            let current_value = next_value;
            next_value = new_value.saturating_add(1);

            Poll::Ready(Some(current_value))
        })
    })
    .switch());
//   ^^^^^^^^
//   |
//   finally!

sender.unbounded_send(7).unwrap();

// `stream` has switched to the inner stream.
// Let's take 5 items.
assert_eq!(
    stream.by_ref().take(5).collect::<Vec<_>>().await,
    vec![7, 8, 9, 10, 11],
);
// Let's take 5 more items.
assert_eq!(
    stream.by_ref().take(5).collect::<Vec<_>>().await,
    vec![12, 13, 14, 15, 16],
);

// All good.
// Let's trigger a new inner stream.
sender.unbounded_send(42).unwrap();

// `stream` has been “reset” and produces a new inner stream.
assert_eq!(
    stream.take(5).collect::<Vec<_>>().await,
    vec![42, 43, 44, 45, 46],
);
```

Tadaa! It's the same `stream`. We keep polling it. But the inner stream is reset
_dynamically_. How cool is that?

<q lang="la">Acta fabula est</q>

In this journey, we played with streams. We can even brag we understand
what first-order and higher-order streams mean. We introduced a new kind
of stream: `Switch`. We also saw how the `futures` crate contains _many_
useful tools. That's why I've opened the [pull request #2997, _feat: Add
`StreamExt::switch`_ on `rust-lang/futures-rs`][futures-rs#2997]. This
is a port of the [`async_rx::Switch`] implementation, written by Jonas
Platte and maintained by Jonas and I, initially for the purpose of the
[Matrix Rust SDK][matrix-rust-sdk]. We thought it could be useful for other
persons, so we are. This was the motivation behind this article: explaining how
`switch` differs from `flatten`, and how useful it can be.

Hope you had fun!

[`Pin`]: https://doc.rust-lang.org/std/pin/struct.Pin.html
[`Context`]: https://doc.rust-lang.org/std/task/struct.Context.html
[_Pin_]: https://without.boats/blog/pin/
[`match`]: https://doc.rust-lang.org/reference/expressions/match-expr.html
[`futures`]: https://docs.rs/futures/0.3.32/futures/
[`futures::stream::iter`]: https://docs.rs/futures/0.3.32/futures/stream/fn.iter.html
[`futures::StreamExt::flatten`]: https://docs.rs/futures/0.3.32/futures/stream/trait.StreamExt.html#method.flatten
[`futures::StreamExt::collect`]: https://docs.rs/futures/0.3.32/futures/stream/trait.StreamExt.html#method.collect
[`Iter`]: https://docs.rs/futures/0.3.32/futures/stream/fn.iter.html
[`Iterator`]: https://doc.rust-lang.org/std/iter/trait.Iterator.html
[`futures::channel::mpsc`]: https://docs.rs/futures/0.3.32/futures/channel/mpsc/index.html
[`impl Stream for Receiver`]: https://docs.rs/futures/0.3.32/futures/channel/mpsc/struct.Receiver.html#impl-Stream-for-Receiver%3CT%3E
[futures-rs#2997]: https://github.com/rust-lang/futures-rs/pull/2997
[`async_rx::Switch`]: https://github.com/jplatte/async-rx
[matrix-rust-sdk]: https://github.com/matrix-org/matrix-rust-sdk

[^pin]: I recommend to read [_Pin_], it's an excellent article that goes in
    depth about pinning.
