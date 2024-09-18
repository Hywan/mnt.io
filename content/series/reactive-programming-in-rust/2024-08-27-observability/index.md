+++
title = "Observability"
date = "2024-08-27"
description = "The basis of reactive programming is observability."
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

## Baby steps with reactive programming

Everything I'm going to share with you has been implemented in [a library called
`eyeball`][`eyeball`]. To give you a good idea of what reactive programming in
Rust can look like, let's create a Rust program:

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

The `subscriber` only receives the **last** update, and that's pretty important
to understand. There is no buffer of all the previous updates here, no memory,
no trace, `subscriber` returns the last value when it is called. Note that this
is not always the case as we will see with `ObservableVector` later, but for the
moment, that's the case.

And yes, if we want the `task` to get a chance to consume more updates, we need
to tell the executor we will wait while the current other tasks are waken up. To
do that, we can use [the `yield_now` function][`smol::yield_now`]:

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

## Attack of the Clones

And that's it. That's the basis of reactive programming. As we have seen, the
`subscriber` implements [`Send`] and [`Sync`] if the `T` in `Observable<T>`
implements `Send` and `Sync`, i.e. if the observed type implements these traits.
That's pretty useful actually: it is possible to send the `subscriber` in a
different thread, and keep waiting for new updates.

However, at the beginning of this episode, we were talking about a collection.
Let's focus on [`Vec`].

{% comte() %}
Why do we focus on `Vec` only? Why not `HashMap`, `HashSet`, `BTreeSet`,
`BTreeMap`, `BinaryHeap`, `LinkedList` or even `VecDeque`? It seems a bit
non-inclusive if you ask me. Are you aware there isn't only `Vec` in life?
{% end %}

Well, the reason is simple: `Vec` is supported by `eyeball`, and it's a
matter of time and work to support other collections, it's definitely not
impossible. You will see that's it's not so trivial to support all these
collections for a simple reason: Did you notice that `Subscriber` returns
an owned `T`? Not a `&T`, but a `T`. That's because
[`Subscriber::next`][`eyeball::Subscriber::next`] requires `T: Clone`. It means
that the observed value will be cloned every time.

[Cloning a value][`Clone`] may be expensive. Here we are manipulating `usize`, which is
a primitive type, so it's all fine. But imagine an `Observable<Vec<BigType>>`
where `BigType` is several kibibytes: the memory impact is going to be quickly
noticeable. So th…

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

You're right. There is `Subscriber::next_ref`. However, if you are such an
assiduous reader, you may have read the end of the documentation, aren't you?

> However, the `Observable` will be locked (not updateable) while any read guards
> are alive.

Blocking the `Observable` might be tolerable in some cases, but it cannot be
generalized to all use cases. A user is more likely to prefer `next` instead of
`next_ref` by default.

Back to our `Observable<Vec<BigType>>` then. Imagine the collection contains 800
items, cloning then entire `Vec<_>` for every update to every subscriber is a
pretty inefficient way of programming. Remember that, as a programmer, we have
the responsability to make our programs use as few resources as possible, so
that hardwares can be used longer. The hardware is the most polluting segment of
our digital world.

So. How a data structure like `Vec` can be cloned cheaply? We could put
it inside an [`Arc`] right? Cloning an _Atomically Reference Counted_ value
is really cheap: [it increases the counter by 1 atomically][`Arc::clone`], the
inner value is untouched. Nonetheless, we have a mutation problem now. If
we have `Observable<Arc<Vec<_>>>`, it means that the subscribers will be
`Subscriber<Arc<Vec<_>>>`. In this case, every time the observable wants to
mutate the data, it is going to… be… impossible because an `Arc` is basically
a shared reference, and shared references in Rust disallow mutation by default,
with `Arc` not being an exception. Using `Observable::set` will create a new
`Arc`, but we cannot update the value inside the `Arc`, except if we use a lock…
Well, we are adding more and more complexity.

<q>Spes salutis</q>[^spes_salutis]! Fortunately for us, _immutable data
structures_ exist in Rust.

> An immutable data structure is data structure which can be copied and modified
> efficiently without altering the original.

Such structures bring many advantages, but one of them is _structural sharing_:

> If two data structures are mostly copies of each other, most of the memory
> they take up will be shared between them. This implies that making copies of
> an immutable data structure is cheap: it's really only a matter of copying
> a pointer and increasing a reference counter, where in the case of [`Vec`] you
> have to allocate the same amount of memory all over again and make a copy of
> every element it contains. For immutable data structures, extra memory isn't
> allocated until you modify either the copy or the original, and then only the
> memory needed to record the difference.

Here, _immutable_ actually means the data cannot be modified, but a copy is
created and the mutation happens on this copy until the value is shared or
copied.

Well. <i>Taking a deep breath</i>. It sounds exactly like what we
need to solve our issue, isn't it? The `Observable<Immutable<_>>` and the
`Subscriber<Immutable<_>>`s will share the same value, with the observable being
able to mutate its inner value. The subscribers can modify the received value
too, in an efficient way.

How immutable data structures are implemented? Oh… <q>beati pauperes in
spiritu</q>[^beati_pauperes_in_spiritu]… it is actually really complex. It may
be a topic for another series or articles. For the moment, let me redirect you
to one research paper that proposes an immutable `Vec`: <cite>RRR Vector: A
Practical General Immutable Sequence</cite>[^SRUB2015].

Do you know the good news though? We don't have to implement it by ourself,
because some people already did it! Enter [the `imbl` crate][`imbl`]. This
crates provides [a `Vector` type][`imbl::Vector`]. It can be use like a regular
`Vec`. (Side note: it's even smarter than a `Vec` because it implements smart
head and tail chunking[^UCR2014], and allocates in the stack or on the heap
depending on the size of the collection, similarly to [the `smallvec`
crate][`smallvec`]. End of digression)

## Observable (immutable) collection

The `imbl` crate then. It provides a `Vector` type, which is similar to `Vec`
but it is immutable. The other goods news is that `eyeball` provides a crate for
working with immutable data structure (how surprising huh?): this is [the
`eyeball-im` crate][`eyeball-im`].

[Matrix Rust SDK]: https://github.com/matrix-org/matrix-rust-sdk
[Element]: https://element.io
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
[`Arc::clone`]: https://github.com/rust-lang/rust/blob/f6bcd094abe174a218f7cf406e75521be4199f88/library/alloc/src/sync.rs#L2118-L2170

[`eyeball`]: https://docs.rs/eyeball
[`eyeball::Subscriber::next`]: https://docs.rs/eyeball/0.8.8/eyeball/struct.Subscriber.html#method.next-1

[`eyeball-im`]: https://docs.rs/eyeball-im

[`smol`]: https://docs.rs/smol
[`smol::Executor`]: https://docs.rs/smol/2.0.2/smol/struct.Executor.html
[`smol-macros`]: https://docs.rs/smol-macros
[`smol::yield_now`]: https://docs.rs/smol/2.0.2/smol/future/fn.yield_now.html

[`imbl`]: https://docs.rs/imbl
[`imbl::Vector`]: https://docs.rs/imbl/3.0.0/imbl/struct.Vector.html

[`smallvec`]: https://docs.rs/smallvec

[^spes_salutis]: Latine expression meaning _salavation hope_.
[^beati_pauperes_in_spiritu]: Latine expression meaning _bless are the poor in spirit_.
[^SRUB2015]: <cite><a href="https://infoscience.epfl.ch/server/api/core/bitstreams/7c8b929f-1f68-4948-8ea8-e364e4899b2a/content">Relaxed-Radix-Balanced
    (RRR) Vector: A Practical General Purpose Immutable Sequence</a></
    cite> by Sticki N., Rompf T., Ureche V. and Bagwell P. (2015, August),
    in <i>Proceedings of the 20th ACM SIGPLAN International Conference on
    Functional Programming (pp. 342-354).</i>
[^UCR2014]: <cite><a href="http://deepsea.inria.fr/pasl/chunkedseq.pdf">Theory
    and Practise of Chunked Sequences</a></cite> by Acar U. A., Charguéraud
    A., and Rainey M. (2014), in <i>Algorithms-ESA 2014: 22th Annual European
    Symposium, Wroclaw, Poland, September 8-10, 2014. Proceedings 21 (pp.
    25-36).</i>, Springer Berlin Heidelberg.
