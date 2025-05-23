+++
title = "Observability"
date = "2024-09-19"
description = "The basis of reactive programming is observability. Let's play with it."
[taxonomies]
keywords=["rust", "async", "future", "stream"]
+++

Imagine a collection of values `T`. This collection can be updated by inserting
new values, removing existing ones, or the collection can truncated, cleared…
This collection acts as [the standard `Vec`][`Vec`]. However, there is a
subtlety: This collection is _observable_. It is possible for someone to
_subscribe_ to this collection and to receive its updates.

This observability pattern is the basis of reactive programming. It applies to
any kind of type. Actually, it can be generalised as a single `Observable<T>`
type. For collections though, we will see that an `ObservableVector<T>` type is
more efficient.

I’ve recently played a lot with this pattern as part of my work inside the
[Matrix Rust SDK], a set of Rust libraries that aim at developing robust
[Matrix] clients or bridges. It is notoriously used by the next generation
Matrix client developed by [Element], namely [Element X]. The Matrix Rust SDK is
cross-platform. Element X has two implementations: on iOS, iPadOS and macOS with
Swift, and on Android with Kotlin. Both languages are using our Rust bindings
to [Swift] and [Kotlin]. This is the story for another series (how we have
automated this, how we support asynchronous flows from Rust to foreign languages
etc.), but for the moment, let’s keep focus on reactive programming.

Taking the Element X use case, the room list –which is the central piece of the
app– is fully dynamic:

- Rooms are sorted by recency, so rooms move to the top when a new interesting
  message is received,
- The list can be filtered by room properties (one can filter by group or
  people, favourites, unreads, invites…),
- The list is also searchable by room names.

The rooms exposed by the room list are stored in a unique _observable_ type.
Why is it dynamic? Because the app continuously sync new data that update the
internal state: when a room gets an update from the network, the room list is
automatically updated. The beauty of it: we have nothing to do. Sorters and
filters are run automatically. Why? Spoiler: because everything is a `Stream`.

Thanks to the Rust async model, every part is lazy. The app never needs to ask
for Rust if a new update is present. It literally just waits for them.

I believe this reactive programming approach is pretty interesting to explore.
And this is precisely the goal of this series. We are going to play with
`Stream` a lot, with higher-order `Stream` a lot more, and w…

{% comte() %}
Hold on a second! I believe this first step is a bit steep for someone who's not
familiar with asynchronous code in Rust, don't you think?

Before digging in the implementation details you are obviously eager to share,
maybe we can start with examples.
{% end %}

Alrighty. Fair. Before digging into the really fun bits, we need some basis.

## Baby steps with reactive programming

Everything we are going to share with you has been implemented in [a library
called `eyeball`][`eyeball`]. To give you a good idea of what reactive
programming in Rust can look like, let's create a Rust program:

```sh
$ cargo new --bin playground
    Creating binary (application) `playground` package
$ cd playground
$ cargo add eyeball
    Updating crates.io index
      Adding eyeball v0.8.8 to dependencies
             Features:
             - __bench
             - async-lock
             - tracing
    Updating crates.io index
     Locking 3 packages to latest compatible versions
```

```rust
// in `src/main.rs`

use eyeball::Observable;

fn main() {
    let mut observable = Observable::new(7);

    let subscriber = Observable::subscribe(&observable);

    dbg!(Observable::get(&observable));
    dbg!(subscriber.get());

    Observable::set(&mut observable, 13);

    dbg!(Observable::get(&observable));
    dbg!(subscriber.get());
}
```

What do we see here? First off, `observable` is an observable value. Proof
is: It is possible to subscribe to it, see `subscriber`. Both `observable` and
`subscriber` are seeing the same initial value: 7. When `observable` receives a
new value, 13, both `observable` and `subscriber` are seeing the updated value. Let's take it for a spin:

```sh
$ cargo run --quiet
[src/main.rs:8:5] Observable::get(&observable) = 7
[src/main.rs:9:5] subscriber.get() = 7
[src/main.rs:13:5] Observable::get(&observable) = 13
[src/main.rs:14:5] subscriber.get() = 13
```

Tadaa. Fantastic, isn't it?

{% comte() %}
I… I am… speechless? Is it _really_ reactive programming? Where is the
reactivity here? It seems like you've only shared a value between an _owner_ and
a _watcher_. You're calling them _observable_ and _subscriber_, alright, but how
is this thing reactive? I only see synchronous code for the moment.
{% end %}

Hold on. You told me to start slow. You're right though: the `Observable` owns
the value. The `Subscriber` is able to read the value from the `Observable`.
However, `Subscriber::next` returns a [`Future`]! Let's add this:

```rust
// in `src/main.rs`

// …

fn main() {
    // …

    dbg!(subscriber.next().await);
}
```

```sh
$ cargo run --quiet
error[E0728]: `await` is only allowed inside `async` functions and blocks
  --> src/main.rs:16:28
   |
3  | fn main() {
   | --------- this is not `async`
...
16 |     dbg!(subscriber.next().await);
   |                            ^^^^^ only allowed inside `async` functions and blocks
```

Indeed. Almighty `rustc` is correct. The `main` function is not `async`. We need
an asynchronous runtime. Let's use [the `smol` project][`smol`], I enjoy it a
lot: it's a small, fast and well-written async runtime:

```sh
$ cargo add smol
    Updating crates.io index
      Adding smol v2.0.2 to dependencies
      [ … snip … ]
```

Now let's modify our `main` function a little bit:

```rust
// in `src/main.rs`

use eyeball::Observable;

fn main() {
    smol::block_on(async {
        let mut observable = Observable::new(7);

        let mut subscriber = Observable::subscribe(&observable);

        dbg!(Observable::get(&observable));
        dbg!(subscriber.get());

        Observable::set(&mut observable, 13);

        dbg!(Observable::get(&observable));
        dbg!(subscriber.get());

        dbg!(subscriber.next().await);
    })
}
```

Please `rustc`, be nice…

```sh
[src/main.rs:9:9] Observable::get(&observable) = 7
[src/main.rs:10:9] subscriber.get() = 7
[src/main.rs:14:9] Observable::get(&observable) = 13
[src/main.rs:15:9] subscriber.get() = 13
[src/main.rs:17:9] subscriber.next().await = Some(
    13,
)
```

Hurray!

We can even have a bit more ergonomics by using [the `smol-macros`
crate][`smol-macros`] which sets up a default [async runtime
`Executor`][`smol::Executor`] for us. It's useful in our case as we want to play
with something else (reactive programming), and don't want to focus on the async
runtime itself:

```sh
$ cargo add smol-macros macro_rules_attribute
    Updating crates.io index
      Adding smol-macros v0.1.1 to dependencies
      Adding macro_rules_attribute v0.2.0 to dependencies
             Features:
             - better-docs
             - verbose-expansions
    Updating crates.io index
     Locking 4 packages to latest compatible versions
      Adding macro_rules_attribute v0.2.0
      Adding macro_rules_attribute-proc_macro v0.2.0
      Adding paste v1.0.15
      Adding smol-macros v0.1.1
```

We will take the opportunity to improve our program a little bit. Let's spawn a
`Future` that will continuously read new updates from the `subscriber`.

```rust
use std::time::Duration;

use eyeball::Observable;
use macro_rules_attribute::apply;
use smol::{Executor, Timer};
use smol_macros::main;

#[apply(main!)]
async fn main(executor: &Executor) {
    let mut observable = Observable::new(7);
    let mut subscriber = Observable::subscribe(&observable);

    // Task that reads new updates from `observable`.
    let task = executor.spawn(async move {
        while let Some(new_value) = subscriber.next().await {
            dbg!(new_value);
        }
    });

    // Now, let's update `observable`.
    Observable::set(&mut observable, 13);
    Timer::after(Duration::from_secs(1)).await;

    Observable::set(&mut observable, 17);
    Timer::after(Duration::from_secs(1)).await;

    Observable::set(&mut observable, 23);

    // Wait on the task.
    task.await;
}
```

The little `Timer::after` calls are here to pretend the values are coming from
random events, for the moment. Let's run it again to see if we get the same
result:

```sh
$ cargo run --quiet
[src/main.rs:16:13] new_value = 13
[src/main.rs:16:13] new_value = 17
[src/main.rs:16:13] new_value = 23
^C
```

Here we go, perfect! See, ah ha! It's async and nice now.

{% comte() %}
I believe I start to appreciate it. However, I foresee you might hide something
behind these `Time::after`. Am I right?

And this `task.await` at the end makes the program to never finish. It explains
the need to send [a `SIGINT` signal][signal] to the program to interrupt it,
right?

[signal]: https://man.freebsd.org/cgi/man.cgi?query=signal
{% end %}

You're slick. Indeed, I wanted to focus on the `observable` and the
`subscriber`. Because there is a subtlety here. If the `Timer::after` are
removed, only the last update will be displayed on the output by `dbg!`.
And that's perfectly normal. The async runtime will execute all the
`Observable::set(&mut observable, new_value)` in a row, and then, once there
is an await point, another task will have room to run. In this case, that's
`subscriber.next().await`.

The subscriber only receives the **last** update, and that's pretty important
to understand. There is no buffer of all the previous updates here, no memory,
no trace, `subscriber` returns the last value when it is called. Note that this
is not always the case as we will see with `ObservableVector` later, but for the
moment, that's the case.

And yes, if we want the `task` to get a chance to consume more updates, we need
to tell the executor we will wait while the current other tasks are waken up. To
do that, we can use [the `smol::yield_now` function][`smol::yield_now`]:

```rust
    // Now, let's update `observable`.
    Observable::set(&mut observable, 13);
    // Eh `executor`: `task` can run now, we will wait!
    yield_now().await;

    // More updates.
    Observable::set(&mut observable, 17);
    Observable::set(&mut observable, 23);
    // Eh `executor`: _bis repetita placent_!
    yield_now().await;

    drop(observable)
    task.await;
}
```

Let's see what happens:

```sh
$ cargo run --quiet
[src/main.rs:14:13] new_value = 13
[src/main.rs:14:13] new_value = 23
```

Eh, see, `new_value = 17` is **not** displayed, because the `observable` is
updated but the `subscriber` is suspended by the executor. But the others are
read, good good.

Note that we are dropping the `observable`. Once it's dropped, the `subscriber`
won't be able to read any value from it, so it's going to close itself, and the
`task` will end. That's why waiting on the task with `task.await` will terminate
this time. And thus, the program will finish gracefully.

And that's it. That's the basis of reactive programming. Also note that
`Subscriber<T>` implements [`Send`] and [`Sync`] if `T` implements `Send` and
`Sync`, i.e. if the observed type implements these traits. That's pretty useful
actually: it is possible to send the subscriber in a different thread, and keep
waiting for new updates.

## Attack of the Clones

However, at the beginning of this episode, we were talking about a collection.
Let's focus on [`Vec`].

{% comte() %}
Why do we focus on `Vec` _only_? Why not `HashMap`, `HashSet`, `BTreeSet`,
`BTreeMap`, `BinaryHeap`, `LinkedList` or even `VecDeque`? It seems a bit
non-inclusive if you ask me. Are you aware there isn't only `Vec` in life?
{% end %}

Well, the reason is simple: `Vec` is supported by `eyeball`. It's a matter of
time and work to support other collections, it's definitely not impossible but
you will see that it's not trivial neither to support all these collections for
a simple reason: Did you notice that `Subscriber` produces an owned `T`? Not a
`&T`, but a `T`. That's because
[`Subscriber::next`][`eyeball::Subscriber::next`] requires `T: Clone`. It means
that the observed value will be cloned every time it is broadcasted to a
subscriber.

[Cloning a value][`Clone`] may be expensive. Here we are manipulating `usize`,
which is a primitive type, so it's all fine (it boils down to a [`memcpy`]).
But imagine an `Observable<Vec<BigType>>` where `BigType` is 512 bytes: the
memory impact is going to be quickly noticeable. So th…

{% comte() %}
… Excuse my interruption! You know how I love reading books. I like
defining myself as a bibliophile. Anyway. During my perusal of the `eyeball`
documentation, I have found
[`Subscriber::next_ref`][`eyeball::Subscriber::next_ref`]. The documentation
says:

> Wait for an update and get a read lock for the updated value.

and later:

> You can use this method to get updates of an `Observable` where the inner type
> does not implement `Clone`.

[`eyeball::Subscriber::next_ref`]: https://docs.rs/eyeball/0.8.8/eyeball/struct.Subscriber.html#method.next_ref-1
{% end %}

Can you stop cutting me off please? It's really unpleasant. And do not forget we
are not alone… <i>doing sideways head movement</i>

You're right though. There is `Subscriber::next_ref`. However, if you are such
an _assiduous reader_, you may have read the end of the documentation, aren't
you?

> However, the `Observable` will be locked (not updateable) while any read guards
> are alive.

Blocking the `Observable` might be tolerable in some cases, but it cannot be
generalised to all use cases. A user is more likely to prefer `next` instead of
`next_ref` by default.

Back to our `Observable<Vec<BigType>>` then. Imagine the collection contains a
lot of items: cloning the entire `Vec<_>` for every update to every subscriber
is a pretty inefficient way of programming. Remember that, as a programmer, we
have the responsibility to make our programs use as few resources as possible,
so that hardwares can be used longer. The hardware is the most polluting segment
of our digital world.

So. How a data structure like `Vec` can be cloned cheaply? We could put
it inside an [`Arc`] right? Cloning an _Atomically Reference Counted_ value
is really cheap: [it increases the counter by 1 atomically][`Arc::clone#src`],
the inner value is untouched. Nonetheless, we have a mutation problem now.
If we have `Observable<Arc<Vec<_>>>`, it means that the subscribers will be
`Subscriber<Arc<Vec<_>>>`. In this case, every time the observable wants to
mutate the data, it is going to… be… impossible because an `Arc` is nothing
less than a shared reference, and shared references in Rust disallow mutation by
default. Using `Observable::set` will create a new `Arc`, but we cannot update
the value _inside_ the `Arc`, except if we use a lock… Well, we are adding more
and more complexity.

<q lang="la">Spes salutis</q>[^spes_salutis]! Fortunately for us, _immutable
data structures_ exist in Rust.

> An immutable data structure is a data structure which can be copied and modified
> efficiently without altering the original.

It can be modified. However, as soon as it is copied (or cloned), it is still
possible to modify the copy but the original data is not modified. That's
extremely powerful.

Such structures bring many advantages, but one of them is _structural sharing_:

> If two data structures are mostly copies of each other, most of the memory
> they take up will be shared between them. This implies that making copies of
> an immutable data structure is cheap: it's really only a matter of copying
> a pointer and increasing a reference counter, where in the case of [`Vec`] you
> have to allocate the same amount of memory all over again and make a copy of
> every element it contains. For immutable data structures, extra memory isn't
> allocated until you modify either the copy or the original, and then only the
> memory needed to record the difference.

Well, <i>taking a deep breath</i>, it sounds exactly like what we
need to solve our issue, isn't it? The `Observable<Immutable<_>>` and the
`Subscriber<Immutable<_>>`s will share the same value, with the observable
being able to mutate its inner value. The subscribers can modify the received
value too, in an efficient way, without conflicting with the value from the
observable. Both values will continue to live on their side, but cloning the
value is cheap.

{% comte() %}
Dare I ask how immutable data structures are implemented? It sounds like complex
beasts.

I mean… a naive implementation sounds _relatively doable_ but I am guessing there
is a lot of subtleties, possible conflicts, and many memory guarantees that I am
not anticipating yet, right?
{% end %}

Oh… <q lang="la">beati pauperes in spiritu</q>[^beati_pauperes_in_spiritu]… it
is actually really complex. It may be a topic for another series or articles.
For the moment, if you interested, let me redirect you to one research paper
that proposes an immutable `Vec`: <cite>RRR Vector: A Practical General
Immutable Sequence</cite>[^SRUB2015]. Be cool though, understanding this part is
not necessary at all for what we are talking now. It's a great tool we are going
to use, no matter how it works internally.

Do you know the other good news? We don't have to implement it by ourselves,
because some nice people already did it! Enter [the `imbl` crate][`imbl`]. This
crate provides [a `Vector` type][`imbl::Vector`]. It can be used like a regular
`Vec`. (Side note: it's even smarter than a `Vec` because it implements smart
head and tail chunking[^UCR2014], and allocates in the stack or on the heap
depending on the size of the collection, similarly to [the `smallvec`
crate][`smallvec`]. End of digression)

## Observable (immutable) collection

The `imbl` crate then. It provides [a `Vector` type][`imbl::Vector`]. `eyeball`
provides a crate for working with immutable data structures (how surprising
huh?): [this crate is `eyeball-im`][`eyeball-im`].

Instead of providing an `Observable<T>` type, it provides [an
`ObservableVector<T>` type][`eyeball_im::ObservableVector`] which is a `Vector`,
but an observable one! Let's see… what do we have… <i>scroll the
documentation</i>, hmm, interesting, <i>scroll more…</i>, okay, that's
interesting:

* First off, there is methods like `append`, `pop_back`, `pop_front`,
  `push_back`, `push_front`, `remove`, `insert`, `set`, `truncate` and `clear`.
  It seems this collection is pretty flexible. The vocabulary is clear. They all
  take a `&mut self`, cool.
* Then, there is a `with_capacity` method, this is intriguing, <i>add to
  notes</i>,
* Finally, we find our not-so-ol' friend `subscribe`, but this time it returns a
  [`VectorSubscriber<T>`][`eyeball_im::VectorSubscriber`].

Let's explore `VectorSubscriber` a bit more, would you? <i>Scroll the
document</i>, contrary to
[`Subscriber::next`][`eyeball::Subscriber::next`], there is no `next` method. How
are we supposed to wait on an update?

{% comte() %}
Confer to the assiduous reader! If you read _carefully_ the documentation of the
`Subscriber::next` method, you will see:

> This method is a convenience so you don't have to import a `Stream` extension
> trait such as `futures::StreamExt` or `tokio_stream::StreamExt`.
{% end %}

… fair enough. So `Subscriber::next` mimics `StreamExt::next`. Okay. Let's look
at [`Stream`][`futures::stream::Stream`] first, it's from [the `futures`
crate][`futures`]. `Stream` defines itself as:

> A stream of values produced asynchronously.
>
> If `Future<Output = T>` is an asynchronous version of `T`, then `Stream<Item =
> T>` is an asynchronous version of `Iterator<Item = T>`. A stream represents
> a sequence of value-producing events that occur asynchronously to the caller.
>
> The trait is modeled after `Future`, but allows `poll_next` to be called even
> after a value has been produced, yielding None once the stream has been fully
> exhausted.

We aren't going to teach everything about `Stream`: why this design, its
pros and cons… However, <i>wave its hand to ask you to come
closer</i>, did you notice how [`Future::poll`] returns `Poll<Self::Output>`,
whilst [`Stream::poll_next`][`futures::stream::Stream::poll_next`] returns
`Poll<Option<Self::Item>>`? It's really similar to [`Iterator::next`] which
returns `Option<Self::Item>`.

Let's take a look at [`Poll<T>`][`Poll`] don't you mind? It's an enum with 2 variants:

* `Ready(value)` means a `value` is immediately ready,
* `Pending` means no value is ready yet.

Then, what `Poll<Option<T>>` represents for a `Stream`?

* `Poll::Ready(Some(value))` means this stream has successfully produced a
  `value`, and may produce more values on subsequent `poll_next` calls,
* `Poll::Ready(None)` means the stream has terminated (and `poll_next` should
  not be called anymore),
* `Poll::Pending` means no value is ready yet.

It makes perfect sense. A `Future` produces a single value, whilst a `Stream`
produces multiple values, and `Poll::Ready(None)` represents the termination of
the stream, similarly to `None` to represent the termination of an `Iterator`.
Ahh, I love consistency.

We have the basis. Now let's see [`StreamExt`][`futures::stream::StreamExt`]. It's
a trait extending `Stream` to add convenient combinator methods. Amongst other
things, we find [`StreamExt::next`][`futures::stream::StreamExt::next`]! Ah ha!
It returns a `Next` type which implements a `Future`, exactly what `eyeball`
does actually. Remember our:

```rust
// from `main.rs`

while let Some(new_value) = subscriber.next().await {
    dbg!(new_value);
}
```

It is exactly the same pattern with `StreamExt::next`:

```rust
// from the documentation of `StreamExt::Next`

use futures::stream::{self, StreamExt};

let mut stream = stream::iter(1..=3);

assert_eq!(stream.next().await, Some(1));
assert_eq!(stream.next().await, Some(2));
assert_eq!(stream.next().await, Some(3));
assert_eq!(stream.next().await, None);
```

Pieces start to come together, don't they?

End of the detour. Back to `eyeball_im::VectorSubscriber<T>` . It is possible to
transform this type into a `Stream` with its
[`into_stream`][`eyeball_im::VectorSubscriber::into_stream`] method. It returns
a [`VectorSubscriberStream`][`eyeball_im::VectorSubscriberStream`]. Naming is
hard, but if I would have to guess, I would say it implements… a… `Stream`?

```rust
// from `eyeball-im`

impl<T: Clone + Send + Sync + 'static> Stream for VectorSubscriberStream<T> {
    type Item = VectorDiff<T>;
```

[Yes, it does][`eyeball_im::VectorSubscriberStream_impl_Stream`]!

Dust blown away, the puzzle starts to appear clearly. Let's back on coding!

```sh
$ cargo add eyeball-im futures
    Updating crates.io index
      Adding eyeball-im v0.5.0 to dependencies
             Features:
             - serde
             - tracing
      Adding futures v0.3.30 to dependencies
             Features:
             + alloc
             + async-await
             + executor
             + std
             - bilock
             - cfg-target-has-atomic
             - compat
             - futures-executor
             - io-compat
             - thread-pool
             - unstable
             - write-all-vectored
      [ … snip … ]
```

```rust
// in `src/main.rs`

use eyeball_im::ObservableVector;
use futures::stream::StreamExt;
use macro_rules_attribute::apply;
use smol::{future::yield_now, Executor};
use smol_macros::main;

#[apply(main!)]
async fn main(executor: &Executor) {
    let mut observable = ObservableVector::new();
    // Subscribe to `observable` and get a `Stream`.
    let mut subscriber = observable.subscribe().into_stream();

    // Push one value.
    observable.push_back('a');

    // Task that reads new updates from `observable`.
    let task = executor.spawn(async move {
        while let Some(new_value) = subscriber.next().await {
            dbg!(new_value);
        }
    });

    // Now, let's update `observable`.
    observable.push_back('b');
    // Eh `executor`: `task` can run now!
    yield_now().await;

    // More updates.
    observable.push_back('c');
    observable.push_back('d');
    // Eh `executor`, same.
    yield_now().await;

    drop(observable);
    task.await;
}
```

Time to show off:

```sh
$ cargo run --quiet
[src/main.rs:18:13] new_value = PushBack {
    value: 'a',
}
[src/main.rs:18:13] new_value = PushBack {
    value: 'b',
}
[src/main.rs:18:13] new_value = PushBack {
    value: 'c',
}
[src/main.rs:18:13] new_value = PushBack {
    value: 'd',
}
```

Do you see something new?

{% comte() %}
Hmm, indeed. With `Observable`, some values may “miss” because `Observable`
and `Subscriber` have no buffer. The subscribers only return the current value
when asked for. However, with `ObservableVector`, things are different: no
missing values. There are all here. As if there… was a buffer!

And the values returned by the subscriber are not the raw `T`:
we see `PushBack`. It comes from, <i>check the documentation</i>,
[`VectorDiff::PushBack`][`eyeball_im::VectorDiff`]!

[`eyeball_im::VectorDiff`]: https://docs.rs/eyeball-im/0.5.0/eyeball_im/enum.VectorDiff.html
{% end %}

Good eyes, well done.

First off, that's correct that `PushBack` comes from
[`VectorDiff`][`eyeball_im::VectorDiff`]. Let's come back to this piece in
a second: it is the cornerstone of the entire series, it deserves a bit of
explanations.

Second, yes, `VectorSubscriber` returns **all values**! There is actually a
buffer. It's a bit annoying to continue with a `task` as we did so far, let's
use [`assert_eq!`] instead.

```rust
// in `src/main.rs`

use eyeball_im::{ObservableVector, VectorDiff};
//                                 ^^^^^^^^^^ new!
// …

#[apply(main!)]
async fn main(_executor: &Executor) {
    let mut observable = ObservableVector::new();
    let mut subscriber = observable.subscribe().into_stream();

    // Push one value.
    observable.push_back('a');

    assert_eq!(
        dbg!(subscriber.next().await),
        Some(VectorDiff::PushBack { value: 'a' }),
    );

    // Push another value.
    observable.push_back('b');
    observable.push_back('c');
    observable.push_back('d');

    assert_eq!(
        dbg!(subscriber.next().await),
        Some(VectorDiff::PushBack { value: 'b' }),
    );
    assert_eq!(
        dbg!(subscriber.next().await),
        Some(VectorDiff::PushBack { value: 'c' }),
    );
    assert_eq!(
        dbg!(subscriber.next().await),
        Some(VectorDiff::PushBack { value: 'd' }),
    );
}
```

```sh
$ cargo run --quiet
[src/main.rs:16:9] subscriber.next().await = Some(
    PushBack {
        value: 'a',
    },
)
[src/main.rs:26:9] subscriber.next().await = Some(
    PushBack {
        value: 'b',
    },
)
[src/main.rs:30:9] subscriber.next().await = Some(
    PushBack {
        value: 'c',
    },
)
[src/main.rs:34:9] subscriber.next().await = Some(
    PushBack {
        value: 'd',
    },
)
```

Beautiful! However… the code is a bit verbose, isn't it? <i>Desperately waiting
for an affirmative answer</i>, okay, okay, something you may not know about me:
I love macros. There. I said it. Let's quickly craft one:

```rust
// in `src/main.rs`
// before the `main` function

macro_rules! assert_next_eq {
    ( $stream:ident, $expr:expr $(,)? ) => {
        assert_eq!(dbg!( $stream .next().await), Some( $expr ));
    };
}
```

This macro does exactly what our `assert_eq!` was doing, except now it's shorter
to use, and thus more pleasant. Don't believe me? See by yourself:

```rust
// in `src/main.rs`
// at the end of the `main` function

// Push one value.
observable.push_back('a');

assert_next_eq!(subscriber, VectorDiff::PushBack { value: 'a' });

// Push another value.
observable.push_back('b');
observable.push_back('c');
observable.push_back('d');

assert_next_eq!(subscriber, VectorDiff::PushBack { value: 'b' });
assert_next_eq!(subscriber, VectorDiff::PushBack { value: 'c' });
assert_next_eq!(subscriber, VectorDiff::PushBack { value: 'd' });
```

There we go.

Having a scientific and rigorous approach is important in our domain. We said
`ObservableVector` seems to contain a buffer, and `VectorSubscriber` seems to
pop values from this buffer. Let's play with that. I see two things to test:

1. Modify the `ObservableVector`, and subscribe to it _after_: Does the
   subscriber receive the update before it was created?
2. How many values the buffer can hold?

```rust
let mut observable = ObservableVector::new();

// Push a value before the subscriber exists.
observable.push_back('a');

let mut subscriber = observable.subscribe().into_stream();

// Push another value.
observable.push_back('b');

assert_next_eq!(subscriber, VectorDiff::PushBack { value: 'b' });
```

If the `subscriber` receives `a`, it must fail, otherwise no error:

```sh
$ cargo run --quiet
[src/main.rs:25:5] subscriber.next().await = Some(
    PushBack {
        value: 'b',
    },
)
```

Look Ma', no error!

{% comte() %}
We have learned that a `VectorSubscriber` is aware of the new updates that are
made once it exists. A `VectorSubscriber` is not aware of updates that happened
before its creation.

In the example, `VectorDiff::PushBack { value: 'a' }` is not received before
`subscriber` was created. However, `VectorDiff::PushBack { value: 'b' }` is
received because it happened after `subscriber` was created. It makes perfect
sense.

It suggests that the buffer lives inside `VectorSubscriber`, and not inside
`ObservableVector`. Or maybe the buffer is shared between the observable and the
subscribers, with the buffer having some specific semantics, like a _channel_.
We would need to look at the implementation to be sure.
{% end %}

Agree. This is left as an exercise for the reader, <i>wink to you</i>.

We have an answer to question 1. What about question 2? The size of the buffer.

```rust
// in `src/main.rs`

let mut observable = ObservableVector::new();
let mut subscriber = observable.subscribe().into_stream();

// Push ALL THE VALUES!
observable.push_back('a');
observable.push_back('b');
observable.push_back('c');
observable.push_back('d');
observable.push_back('e');
observable.push_back('f');
observable.push_back('g');
observable.push_back('h');
observable.push_back('i');
observable.push_back('j');
observable.push_back('k');
observable.push_back('l');
observable.push_back('m');
observable.push_back('n');
observable.push_back('o');
observable.push_back('p');

assert_next_eq!(subscriber, VectorDiff::PushBack { value: 'a' });
// no need to assert the others
```

```sh
$ cargo run --quiet
[src/main.rs:36:5] subscriber.next().await = Some(
    PushBack {
        value: 'a',
    },
)
```

Hmm, the buffer doesn't seem to be full with 16 values. Let's add a couple more:

```rust
// in `src/main.rs`

// [ … snip … ]
observable.push_back('n');
observable.push_back('o');
observable.push_back('p');
observable.push_back('q');
//                    ^ new!
observable.push_back('r');
//                    ^ new!

assert_next_eq!(subscriber, VectorDiff::PushBack { value: 'a' });
```

```sh
$ cargo run --quiet
[src/main.rs:38:5] subscriber.next().await = Some(
    Reset {
        values: [
            'a',
            'b',
            'c',
            'd',
            'e',
            'f',
            'g',
            'h',
            'i',
            'j',
            'k',
            'l',
            'm',
            'n',
            'o',
            'p',
            'q',
            'r',
        ],
    },
)
thread 'main' panicked at src/main.rs:38:5:
assertion `left == right` failed
  left: Some(Reset { values: ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r'] })
 right: Some(PushBack { value: 'a' })
note: run with `RUST_BACKTRACE=1` environment variable to display a backtrace
```

Oh! An error, great! Our `assert_next_eq!` has failed. `subscriber`
does not receive a `VectorDiff::PopBack` but a `VectorDiff::Reset`.
Let's play with
[`ObservableVector::with_capacity`][`eyeball_im::ObservableVector::with_capacity`]
a moment, maybe it's related to the buffer capacity? Let's change a single line:

```rust
let mut observable = ObservableVector::with_capacity(32);
//                                     ^^^^^^^^^^^^^^^^^ new!
```

```sh
$ cargo run --quiet
[src/main.rs:38:5] subscriber.next().await = Some(
    PushBack {
        value: 'a',
    },
)
```

{% comte() %}
We have learned that `ObservableVector::with_capacity` controls the size of
the buffer.

The name could suggest that it controls the capacity of the observed `Vector`,
_à la_ [`Vec::with_capacity`], but it must not be confused.

For a reason we ignore so far, when the buffer is full, we receive a
`VectorDiff::Reset`. We need to learn more about this type.

[`Vec::with_capacity`]: https://doc.rust-lang.org/std/vec/struct.Vec.html#method.with_capacity
{% end %}

## Observable differences

The previous section was explaining how immutable data structures could save us
by cheaply and efficiently cloning the data between the observable and its
subscribers. However, we see that [`eyeball-im`], despite using [`imbl`], does
not share an [`imbl::Vector`] but an [`eyeball_im::VectorDiff`]. Why such design?
It looks like a drama. A betrayal. An act of treachery!

Well. Firstly, `eyeball-im` is relying on some immutable properties of `Vector`.
And secondly, the reason for which `VectorDiff` exists is simple. If a
subscriber receives `Vector`s, how is the user able to see what has changed? The
user (!) would be responsible to _calculate_ the differences between 2 `Vector`s
every time! Not only this is costly, but it is utterly error-prone.

{% comte() %}
Are you suggesting that `VectorSubscriber` (or `VectorSubscriberStream`)
calculates the differences between the `Vector`s itself so that the user doesn't
have to?

I still see many problems though. I believe the order of the `VectorDiff`s
matters a lot for some use cases. For example, let's consider two consecutive
`Vector`s:

1. `['a', 'b', 'c']` and
2. `['a', 'c', 'b']`.

Has `'b'` been removed and pushed back, or `'c'` been popped back and inserted?
How can you decide between the twos?
{% end %}

We can't —it would be implementation specifics anyway— and we don't want to.
The user is manipulating the `ObservableVector` in a special way, and we should
ideally not change that.

These `VectorDiff` actually comes from `ObservableVector` itself! Let's look at
the implementation of
[`ObservableVector::push_back`][`eyeball_im::ObservableVector::push_back#src`]:

```rust
pub fn push_back(&mut self, value: T) {
    // [ … snip … ]

    self.values.push_back(value.clone());
    //   ^^^^^^ this is a `Vector`!
    self.broadcast_diff(VectorDiff::PushBack { value });
    //                  ^^^^^^^^^^ here you are…
}
```

Each method adding or removing values on the `ObservableVector` emits its own
`VectorDiff` variant. No calculation, it's purely a mapping:

<figure>

  | `ObservableVector::…` | `VectorDiff::…` | Meaning |
  |-|-|-|
  | `append(values)` | `Append { values }` | Append many `values` |
  | `clear()` | `Clear` | Clear out all the values |
  | `insert(index, value)` | `Insert { index, value }` | Insert a `value` at `index` |
  | `pop_back()` | `PopBack` | Remove the value at the back |
  | `pop_front()` | `PopFront` | Remove the value at the front |
  | `push_back(value)` | `PushBack { value }` | Add `value` at the back |
  | `push_front(value)` | `PushFront { value }` | Add `value` at the front |
  | `remove(index)` | `Remove { index } ` | Remove value at `index` |
  | `set(index, value)` | `Set { index, value }` | Replace value at `index` by `value` |
  | `truncate(length)` | `Truncate { length }` | Truncate to `length` values |

  <figcaption>

  Mappings of `ObservableVector` methods to `VectorDiff` variants.

  </figcaption>

</figure>

See, for each `VectorDiff` variant, there is an `ObservableVector` method
triggering it.

{% comte() %}
And what about `VectorDiff::Reset`?

We were receiving it when the buffer was full apparently. You are not mentioning
it, and if I take a close look at `ObservableVector`'s documentation, I don't
see any `reset` method. Is it only an internal thing?
{% end %}

You are correct. When the buffer is full, the subscriber will provide a
`VectorDiff::Reset { values }` where `values` is the full list of values. The
documentation says:

> The subscriber lagged too far behind, and the next update that should have
> been received has already been discarded from the internal buffer.

If the subscriber didn't catch all the updates, the best thing it can do is to
say: <q>Okay, I am late at the party, I've missed several things, so here is the
current state!</q>. This is not ideal, but the subscriber is responsible to not
lag, and this design avoids having missing values. If a subscriber receives
too much `VectorDiff::Reset`s, the user may consider increasing the capacity of
the `ObservableVector`.

## Filtering and sorting with higher-order `Stream`s

We are reaching the end of this episode. And you know what? We have set all the
parts to talk about higher-order `Stream`, <i>chante victory and dance at the
same time</i>!

At the beginning of this episode, we were saying that the Matrix Rust SDK is
able to filter and to sort an `ObservableVector` representing all the rooms.
How? `VectorSubscriberStream` _is_ a `Stream`. More specifically, it is a
`Stream<Item = VectorDiff<T>>`. Now questions:

* What's the difference between an unfiltered `Vector` and a filtered `Vector`?
* What's the difference between an unsorted `Vector` and a sorted `Vector`?
* What's the difference between a filtered `Vector` and a sorted `Vector`?
* and so on.

All of them are strictly `Stream<Item = VectorDiff<T>>`! However, the
`VectorDiff`s aren't the same. A simple example. Let's say we build a vector by
inserting `1`, `2`, `3` and `4`. We subscribe to it, and we want to filter out
all the even numbers. Instead of receiving:

* `VectorDiff::Insert { index: 0, value: 1 }`,
* `VectorDiff::Insert { index: 1, value: 2 }`,
* `VectorDiff::Insert { index: 2, value: 3 }`,
* `VectorDiff::Insert { index: 3, value: 4 }`.

… we want to receive:

* `VectorDiff::Insert { index: 0, value: 1 }`,
* `VectorDiff::Insert { index: 1, value: 3 }`: note the `index`, it is not 2
  but 1!

We will see how all that works in the next episodes and how powerful this design
is, especially when it comes to cross-platform UI (user interface). We are going
to learn so much about `Stream` and `Future`, it's going to be fun!


[Matrix Rust SDK]: https://github.com/matrix-org/matrix-rust-sdk
[Matrix]: https://matrix.org/
[Element]: https://element.io/
[Element X]: https://element.io/labs/element-x
[Swift]: https://www.swift.org/
[Kotlin]: https://kotlinlang.org/
[HAMT]: https://en.wikipedia.org/wiki/Hash_array_mapped_trie
[B-tree]: https://en.wikipedia.org/wiki/B-tree

[`Vec`]: https://doc.rust-lang.org/std/vec/index.html
[`Future`]: https://doc.rust-lang.org/std/future/trait.Future.html
[`Send`]: https://doc.rust-lang.org/std/marker/trait.Send.html
[`Sync`]: https://doc.rust-lang.org/std/marker/trait.Sync.html
[`Clone`]: https://doc.rust-lang.org/std/clone/trait.Clone.html
[`Arc`]: https://doc.rust-lang.org/std/sync/struct.Arc.html
[`Arc::clone#src`]: https://github.com/rust-lang/rust/blob/f6bcd094abe174a218f7cf406e75521be4199f88/library/alloc/src/sync.rs#L2118-L2170
[`Future::poll`]: https://doc.rust-lang.org/std/future/trait.Future.html#tymethod.poll
[`Iterator::next`]: https://doc.rust-lang.org/std/iter/trait.Iterator.html#tymethod.next
[`Poll`]: https://doc.rust-lang.org/std/task/enum.Poll.html
[`assert_eq!`]: https://doc.rust-lang.org/std/macro.assert_eq.html

[`eyeball`]: https://docs.rs/eyeball
[`eyeball::Subscriber::next`]: https://docs.rs/eyeball/0.8.8/eyeball/struct.Subscriber.html#method.next-1

[`eyeball-im`]: https://docs.rs/eyeball-im
[`eyeball_im::ObservableVector`]: https://docs.rs/eyeball-im/0.5.0/eyeball_im/struct.ObservableVector.html
[`eyeball_im::ObservableVector::with_capacity`]: https://docs.rs/eyeball-im/0.5.0/eyeball_im/struct.ObservableVector.html#method.with_capacity
[`eyeball_im::ObservableVector::push_back#src`]: https://github.com/jplatte/eyeball/blob/4254403e385715380753bb0def20fb0398e91ebd/eyeball-im/src/vector.rs#L107-L114
[`eyeball_im::VectorSubscriber`]: https://docs.rs/eyeball-im/0.5.0/eyeball_im/struct.VectorSubscriber.html
[`eyeball_im::VectorSubscriber::into_stream`]: https://docs.rs/eyeball-im/0.5.0/eyeball_im/struct.VectorSubscriber.html#method.into_stream
[`eyeball_im::VectorSubscriberStream`]: https://docs.rs/eyeball-im/0.5.0/eyeball_im/struct.VectorSubscriberStream.html
[`eyeball_im::VectorSubscriberStream_impl_Stream`]: https://docs.rs/eyeball-im/0.5.0/eyeball_im/struct.VectorSubscriberStream.html#impl-Stream-for-VectorSubscriberStream%3CT%3E
[`eyeball_im::VectorDiff`]: https://docs.rs/eyeball-im/0.5.0/eyeball_im/enum.VectorDiff.html

[`smol`]: https://docs.rs/smol
[`smol::Executor`]: https://docs.rs/smol/2.0.2/smol/struct.Executor.html
[`smol-macros`]: https://docs.rs/smol-macros
[`smol::yield_now`]: https://docs.rs/smol/2.0.2/smol/future/fn.yield_now.html

[`imbl`]: https://docs.rs/imbl
[`imbl::Vector`]: https://docs.rs/imbl/3.0.0/imbl/struct.Vector.html

[`smallvec`]: https://docs.rs/smallvec

[`futures`]: https://docs.rs/futures
[`futures::stream::Stream`]: https://docs.rs/futures/0.3.30/futures/stream/trait.Stream.html
[`futures::stream::Stream::poll_next`]: https://docs.rs/futures/0.3.30/futures/stream/trait.Stream.html#tymethod.poll_next
[`futures::stream::StreamExt`]: https://docs.rs/futures/0.3.30/futures/stream/trait.StreamExt.html
[`futures::stream::StreamExt::next`]: https://docs.rs/futures/0.3.30/futures/prelude/stream/trait.StreamExt.html#method.next

[`memcpy`]: https://en.cppreference.com/w/c/string/byte/memcpy

[^spes_salutis]: Latine expression meaning _salavation hope_.
[^beati_pauperes_in_spiritu]: Latine expression meaning _bless are the poor in spirit_.
[^SRUB2015]: <cite><a href="https://infoscience.epfl.ch/server/api/core/bitstreams/7c8b929f-1f68-4948-8ea8-e364e4899b2a/content">Relaxed-Radix-Balanced
    (RRR) Vector: A Practical General Purpose Immutable
    Sequence</a></cite> by Sticki N., Rompf T., Ureche V. and Bagwell P. (2015,
    August), in <i>Proceedings of the 20th ACM SIGPLAN International Conference
    on Functional Programming (pp. 342-354).</i>
[^UCR2014]: <cite><a href="http://deepsea.inria.fr/pasl/chunkedseq.pdf">Theory
    and Practise of Chunked Sequences</a></cite> by Acar U. A., Charguéraud
    A., and Rainey M. (2014), in <i>Algorithms-ESA 2014: 22th Annual European
    Symposium, Wroclaw, Poland, September 8-10, 2014. Proceedings 21 (pp.
    25-36).</i>, Springer Berlin Heidelberg.
