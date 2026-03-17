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

Okay. Another one. And just like `Iterator::flatten`, `Stream::flatten` can
exist too. Things start to be fun, let's dig a bit.

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
see a bit of pseudo-code to understand how it would work:

```rust
// Pseudo Rust-ish code.

'outer_stream: loop {
    let mut inner_stream = match outer_stream.poll_next() {
        Poll::Ready(Some(inner_stream)) => inner_stream,
        Poll::Ready(None) => break, // closed!
        Poll::Pending => yield pending,
    };

    'inner_stream: loop {
        match inner_stream.poll_next() {
            Poll::Ready(Some(item)) => yield item,
            Poll::Ready(None) => break, // closed!
            Poll::Pending => yield pending,
        }
    }
}
```

Each new polled inner stream —i.e. produced by the outer stream— is consumed
entirely until it's closed. Then, a new inner stream is polled, and it keeps
going until the outer stream is closed.

We can see that via a basic example, this time with real Rust code, and with the
help of [the `futures` crate][`futures`].

```rust
// `StreamExt` is the trait that “extends” the `Stream` trait.
// It contains all the combinators.
use futures::stream::{self, StreamExt};

fn main() {
    futures::executor::block_on(async {
        let outer_stream = stream::iter(vec![
            // First inner streams.
            stream::iter(vec![1, 2, 3]),
            // Second inner stream.
            stream::iter(vec![4, 5]),
            // Third inner stream.
            stream::iter(vec![6, 7, 8, 9]),
        ])
        .flatten();

        dbg!(&outer_stream.collect::<Vec<_>>().await);
    });
}
```

There is a lot going on here. `futures::executor` is an asynchronous runtime,
also called an executor. The `block_on` function is a special executor that runs
a future to completion on the current thread. It's perfect in our case as we
just want to run a single asynchronous block.

[`stream::iter`][`futures::stream::iter`] builds a special stream called
[`Iter`]. It converts an [`Iterator`] into a `Stream`. One can argue this is a
bit useless as all items are already known. To whom I would gently remind… it's
an example! It's here to illustrate the behaviour of `flatten`.

So. `stream::iter`. We are building the outer stream that produces three inner
streams. These inner streams are all `stream::iter` streams too. How lovingly
pleasant.

Finally, `flatten()` is called right before returning the `outer_stream`.

To test what the stream will return, we use the special combinator `collect`,
which collects all items, here in a `Vec`. Since `collect` does _not_ return
a `Stream` but a `Future`: it produces a single value, not many. So we need to
`await` for the result. And the result is…

```text
[src/main.rs:12:9] &outer_stream.collect::<Vec<_>>().await = [
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

Each inner stream is consumed entirely, i.e. until it's closed, one after the
other, until the outer stream is closed.

This is a classical example of a higher-order stream!

{% procureur() %}

The terms _first-order_ and _higher-order_ can be intimating at first, but they
are rather simple to understand.

A function `fn(x) -> y` is a first-order function. However, `fn(fn(x) -> y)
-> z` is a higher-order function: it's a function that takes a function as an
argument. The function `fn(x) -> fn(y) -> z` is also a higher-order function
because it returns a function.

Said differently: A function is a higher-order function if at least:

- it takes another function as arguments,
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

Okay, the signature of the combinator is exactly the same, but the behaviour is
different! Do you remember that `flatten` uses the outer stream to produce an
inner stream, which is consumed entirely before polling the outer stream again?
`switch` is different, <i>open the documentation</i>:

> This combinator flattens a stream of streams, i.e. an outer stream yielding
> inner streams. This combinator always keeps the most recently yielded inner
> stream, and yields items from it, until the outer stream produces a new inner
> stream, at which point the inner stream to yield items from is switched to the
> new one.

The produced inner stream is kept until… the outer stream produces a new inner
stream. `flatten` consumes the inner stream entirely, regardless of the outer
stream being ready with a new inner stream.

Let's take an example:

```rust
let outer_stream = stream::iter([
    // First inner stream.
    stream::iter(vec![1, 2, 3]),
    // Second inner stream.
    stream::iter(vec![4, 5]),
    /// Third inner stream.
    stream::iter(vec![6, 7, 8, 9]),
])
.switch();

assert_eq!(
    stream.collect::<Vec<_>>().await,
    vec![6, 7, 8, 9],
);
```

{% comte() %}

Huh. The first and second inner streams are ignored. Like if the outer stream
was polled until being pending.

Note, it matches the documentation when it says:

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

<q lang="la">Aut disce, aut discede</q> (see, I know Latin too!). <i>clear its
throat</i>. Hum hum. Once again, the `futures` crate got you covered! There
is this excellent [`stream::poll_fn`][`futures::stream::poll_fn`] function: it
creates a `Stream` that runs the given function when polled.

[`futures::stream::poll_fn`]: https://docs.rs/futures/0.3.32/futures/future/fn.poll_fn.html

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

Tada! It's the same `stream`. We keep polling it. But the inner stream is reset
_dynamically_. How cool is that?

<q lang="la">Acta fabula est</q>

[`Pin`]: https://doc.rust-lang.org/std/pin/struct.Pin.html
[`Context`]: https://doc.rust-lang.org/std/task/struct.Context.html
[_Pin_]: https://without.boats/blog/pin/
[`futures`]: https://docs.rs/futures/0.3.32/futures/
[`futures::stream::iter`]: https://docs.rs/futures/0.3.32/futures/stream/fn.iter.html
[`Iter`]: https://docs.rs/futures/0.3.32/futures/stream/fn.iter.html
[`Iterator`]: https://doc.rust-lang.org/std/iter/trait.Iterator.html
[`futures::channel::mpsc`]: https://docs.rs/futures/0.3.32/futures/channel/mpsc/index.html
[`impl Stream for Receiver`]: https://docs.rs/futures/0.3.32/futures/channel/mpsc/struct.Receiver.html#impl-Stream-for-Receiver%3CT%3E

[^pin]: I recommend to read [_Pin_], it's an excellent article that goes in
    depth about pinning.
