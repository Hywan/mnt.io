+++
title = "Observability"
date = "2024-08-27"
description = "The basis of reactive programming is observability. We need to talk about `Future` and `Stream`"
[taxonomies]
keywords=["rust", "async", "future", "stream"]
+++

Imagine a collection of values `T`. This collection can be updated by inserting
new values, removing existing ones, or the collection can truncated, cleared…
This collection acts as [the standard `Vec`][`Vec`]. However, there is a
subtlety: This collection is _observable_. It is possible for someone to
_subscribe_ to this collection and to receive its updates.

This observability pattern is the basis of reactive programming. It applies to
any kind of type. Actually, it can be generalized as a single `Observable<T>`
type. For collections though, we will see that an `ObservableVector<T>` type is
more efficient.

I’ve recently played a lot with this pattern as part of my work inside the
[Matrix Rust SDK], a set of Rust libraries that aim at developing robust
Matrix clients or bridges. It is notoriously used by the next generation Matrix
client developed by [Element], namely [Element X]. The Matrix Rust SDK is
cross-platform. Element X has two implementations: on iOS, iPadOS and macOS with
Swift, and on Android with Kotlin. Both languages are using our Rust bindings
to [Swift] and [Kotlin]. This is the story for another series (how we have
automated this, how we support asynchronous flows from Rust to foreign languages
etc.), but for the moment, let’s keep focus on reactive programming.

Taking the Element X use case, the room list –which is the central piece of the
app– is fully dynamic:

- rooms are sorted by recency, so rooms move to the top when a new message is
  received,
- the list can be filtered by room properties (one can filter by group or
  people, favourites, unreads, invites…),
- the list is also searchable by room names.

The rooms exposed by the room list are stored in a unique _observable_ type.
Why is it dynamic? Because the app continuously sync new data: when a room gets
an update from the network, the room list is automatically updated. The beauty
of it: we have nothing to do. Sorters and filters are run automatically. Why?
Spoiler: because everything is a `Stream`.

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
Everything I'm going to share with you has been in a library called [`eyeball`].
To give you a good idea of what reactive programming in Rust can look like,
let's create a Rust program:

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
However, `Subscriber` can be used as a [`Future`] with its `next` method! Let's add this:

```rust
// in `src/main.rs`

dbg!(subscriber.next().await);
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

Indeed. All mighty `rustc` is correct. The `main` function is not `async`. We
need an asynchronous runtime.

```sh
$ cargo add smol
    Updating crates.io index
      Adding smol v2.0.2 to dependencies
    Updating crates.io index
     Locking 46 packages to latest compatible versions
      Adding async-channel v2.3.1
      Adding async-executor v1.13.1
      Adding async-fs v2.1.2
      Adding async-io v2.3.
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

Here we go! See, ah ha! It's async now.

[Matrix Rust SDK]: https://github.com/matrix-org/matrix-rust-sdk
[Element]: https://element.io
[Element X]: https://element.io/labs/element-x
[Swift]: https://www.swift.org/
[Kotlin]: https://kotlinlang.org/
[`Vec`]: https://doc.rust-lang.org/std/vec/index.html
[`eyeball`]: https://crates.io/crates/eyeball
[`Future`]: https://doc.rust-lang.org/std/future/trait.Future.html
