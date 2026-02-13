+++
title = "About memory pressure and lock contention"
date = "2026-02-12"
description = ""
[taxonomies]
keywords=["rust", "performance", "lock", "memory"]
[extra]
pinned = true
+++

I'm here to narrate you a story about performance. Recently, I was in the same
room than a Memory Pressure and a Lock Contention. It took me a while to recognize
them. The legend says it only happens in obscure, low-level systems, but I'm
here to refute the legend. While exploring, I had the pleasure to fix a funny
bug in a higher-order stream: lucky us, to top it all off, we even have a sweet
treat! I believe we have all the ingredients for a juicy story. Let's cook, and
<em lang="fr">bon appétit !</em>

## On a Beautiful Morning…

Switching on my [Dygma Defy][defy], unlocking my computer, reading the news from
my colleagues, when suddenly I come across this message:

> Does anyone also experience a frozen room list?

Ah yeah, since some years now, I'm employed by [Element] to work on the [Matrix
Rust SDK]. If one needs to write a complete, modern, cross-platform, fast Matrix
client or bot, this SDK is an excellent choice. The SDK is composed of many
crates. Some are very low in the stack and are not aimed to be used directly by
the developers, like `matrix_sdk_crypto`. Some others are higher in the stack,
where the highest is for User Interfaces (UI) with `matrix_sdk_ui`. Despite
being a bit opinionated, they are designed to provide high-quality features
everybody expects in a modern Matrix client.

One of them is the Room List. The Room List is the place where most of users
spent their time in a messaging application (along with the Timeline, i.e. the
room's messages). Some expectations from this component:

- Be superfast,
- List all the rooms,
- Interact with rooms (open them, mark them as unread etc.),
- Filter the rooms,
- Sort the rooms.

Let's focus on the part that interests us today: _Sort the rooms_. The Room List
holds… no room. It actually provides a _stream of updates about rooms_; more
precisely a `Stream<Item = Vec<VectorDiff<Room>>>`. What does it mean? This
stream yields a vector of “diffs” of rooms. I'm writing [a series about reactive
programming](@/series/reactive-programming-in-rust/_index.md), you might be
interested to read more about it. Otherwise, here is what you need to know.

[The `VectorDiff` type][`VectorDiff`] comes from [the `eyeball-im`
crate][`eyeball_im`], initially created for the Matrix Rust SDK as a solid
foundation for reactive programming. It looks like this:

```rust
pub enum VectorDiff<T> {
    Append {
        values: Vector<T>,
    },
    Clear,
    PushFront {
        value: T,
    },
    PushBack {
        value: T,
    },
    PopFront,
    PopBack,
    Insert {
        index: usize,
        value: T,
    },
    Set {
        index: usize,
        value: T,
    },
    Remove {
        index: usize,
    },
    Truncate {
        length: usize,
    },
    Reset {
        values: Vector<T>,
    },
}
```

It represents a _change_ in [an `ObservableVector`][`ObservableVector`].
This is like a `Vec`, but [one can subscribe to the
changes][`ObservableVector::subscribe`], and will receive… well… `VectorDiff`s!

The Room List type merges several streams into a single stream representing
the list of rooms. For example, let's imagine the room at index 3 receives a
new message. Its “preview” (the _latest event_ displayed beneath the room's
name, you know, <q>Alice: Hello!</q>) has changed. Moreover, the Room List is
also sorting rooms by their “recency” (the _time_ of the room). And since the
“preview” has changed, its “recency” changes too, which means the room is sorted
and re-positioned. Then, we expect the Room List's stream to yield:

1. `VectorDiff::Set { index: 3, value: new_room }` because of the new “preview”,
2. `VectorDiff::Remove { index: 3 }` to remove the room… immediately followed by
3. `VectorDiff::PushFront { value: new_room }` to insert the room at the top of the Room List.

This reactive programming mechanism has proven to be extremely efficient.

{% comte() %}

I did my calculation: the size of `VectorDiff<Room>` is 72 bytes (mostly
because `Room` contains [an `Arc`][`Arc`] over the real struct type). This is
pretty small for an update. Not only it brings a small memory footprint, but it
crosses the FFI boundary pretty easily, making it easy to map to other languages
like Swift or Kotlin. Languages that provide UI components, like [SwiftUI] or
[Jetpack Compose].

[`Arc`]: https://doc.rust-lang.org/std/sync/struct.Arc.html
[SwiftUI]: https://developer.apple.com/swiftui/
[Jetpack Compose]: https://developer.android.com/compose

{% end %}

Absolutely! Two popular UI components where a `VectorDiff` maps
straightforwardly to their List component update operations. They are actually
remarkably pretty close[^vectordiff_on_other_uis].

You're always a good digression companion, thank you. Let's go back on our
problem:

> What frozen means here?

The Room List is simply… _blank_, _empty_, <em lang="fr">vide</em>, <em
lang="es">vacía</em>, <em lang="it">vuoto</em>, <em lang="ar">خلو</em>… well,
you get the idea.

> What could freeze the Room List?

What are our options?

{% factotum() %}

It would be a real pleasure if you let me assist you in this task.

- The network sync is not running properly, hence giving the _impression_ of a
  frozen Room List? Hmm, no, everything works as expected here. Moreover, local
  data should be displayed.
- The “source streams” used by the Room List are not yielding the expected
  updates? No, everything works like a charm.
- The “merge of streams” is broken for some reasons? No, still not.
- The filtering of the streams? Not touched since a long time.
- The sorting? Ah, maybe, I reckon we have changed something here…

{% end %}

Indeed, we have changed one sorter recently. Let's take a look at how this Room List stream is computed, shall we?

```rust
let stream = stream! {
    loop {
        // Wait for the filter to be updated.
        let filter = filter_cell.take().await;

        // Get the “raw” entries.
        let (raw_values, raw_stream) = self.entries();

        // Combine normal stream updates with other updates from rooms.
        let stream = merge_stream_and_receiver(raw_values.clone(), raw_stream, other_updates);

        let (values, stream) = (values, stream)
            .filter(filter)
            .sort_by(new_sorter_lexicographic(vec![
                // Sort by latest event's kind.
                Box::new(new_sorter_latest_event()),
                // Sort rooms by their recency.
                Box::new(new_sorter_recency()),
                // Finally, sort by name.
                Box::new(new_sorter_name()),
            ]))
            .dynamic_head_with_initial_value(page_size, limit_stream);

        // Clearing the stream before chaining with the real stream.
        yield once(ready(vec![VectorDiff::Reset { values }]))
            .chain(stream);
    }
}
.switch();
```

There is a lot going on here. Sadly, we are not going to explain everything in
this beautiful piece of art[^switch].

The `.filter()`, `.sort_by()`, `.dynamic_head_with_initial_value()` methods
are part of [the `eyeball-im-util` crate][`eyeball_im_util`]. They are used
to filter, sort etc. a stream: They are essentially mapping a `Stream<Item
= Vec<VectorDiff<T>>>` to another `Stream<Item = Vec<VectorDiff<T>>>`. In
other terms, they “change” the `VectorDiff`s on-the-fly to simulate filtering,
sorting, or something else. Let's see a very concrete example with [the `Sort`
higher-order stream][`eyeball_im_util::vector::Sort`] (the following example
is mostly a copy of  the documentation of `Sort`, but [since I wrote this
algorithm, I guess you, dear reader, will find it acceptable][eyeball#43]).

How about a vector of `char`? We want a `Stream` of _changes_ about this vector
(the famous `VectorDiff`). We also want to _simulate_ a sorted vector, by only
modifying the _changes_. It looks like so:

```rust
use std::cmp::Ordering;

use eyeball_im::{ObservableVector, VectorDiff};
use eyeball_im_util::vector::VectorObserverExt;
use stream_assert::{assert_next_eq, assert_pending};

// Our comparison function.
fn cmp<T>(left: &T, right: &T) -> Ordering
where
    T: Ord,
{
    left.cmp(right)
}

// Our vector.
let mut vector = ObservableVector::<char>::new();
let (initial_values, mut stream) = vector.subscribe().sort_by(cmp);
//                                                            ^^^
//                                                            |
//                                                            there

assert!(initial_values.is_empty());
assert_pending!(stream);
```

Alrighty. That's a good start. `vector` is empty, so the initial values from the
subscribe are empty, and the `stream` is also pending[^stream_assert]. Time to
play with this new toy, isn't it?

```rust
// Append unsorted values.
vector.append(vector!['d', 'b', 'e']);

// We get a `VectorDiff::Append` with sorted values!
assert_next_eq!(
    stream,
    VectorDiff::Append { values: vector!['b', 'd', 'e'] }
);
assert_pending!(stream);

// Let's recap what we have. `vector` is our `ObservableVector`,
// `stream` is the “sorted view”/“sorted stream” of `vector`:
//
// | index    | 0 1 2 |
// | `vector` | d b e |
// | `stream` | b d e |
```

So far, so good. It looks naive and simple: one operation in, one operation out.
It's funnier when things get more complicated though:

```rust
// Append multiple other values.
vector.append(vector!['f', 'g', 'a', 'c']);

// We get three `VectorDiff`s this time!
assert_next_eq!(
    stream,
    VectorDiff::PushFront { value: 'a' }
);
assert_next_eq!(
    stream,
    VectorDiff::Insert { index: 2, value: 'c' }
);
assert_next_eq!(
    stream,
    VectorDiff::Append { values: vector!['f', 'g'] }
);
assert_pending!(stream);

// Let's recap what we have:
//
// | index    | 0 1 2 3 4 5 6 |
// | `vector` | d b e f g a c |
// | `stream` | a b c d e f g |
//              ^   ^     ^^^
//              |   |     |
//              |   |     with `VectorDiff::Append { .. }`
//              |   with `VectorDiff::Insert { index: 2, .. }`
//              with `VectorDiff::PushFront { .. }`
```

Notice how `vector` is _never_ sorted. That's the power of these higher-order
streams of `VectorDiff`s: light and —more importantly— **combinable**! I repeat
myself: we are always mapping a `Stream<Item = Vec<VectorDiff<T>>>` to another
`Stream<Item = Vec<VectorDiff<T>>>`. That's the same type! The whole collection
is never computed entirely (except for the initial values): only the changes are
handled and trigger a computation. Knowing that, in the manner of [`Future`],
`Stream` is lazy —i.e. it does something only when polled—, it makes things
pretty efficient. And…

{% comte() %}

… as your favourite digression companion, I really, deeply, appreciate these
details. Nonetheless, I hope you dont't mind if… I suggest to you that… you
might want to, maybe, go back to… <small>the main… subject, don't you think?</small>

{% end %}

Which topic? Ah! The frozen Room List! Sorters are _not_ the culprit. There.
Happy? Short enough?

These details were important. Kind of. I hope you've learned something along
the lines. Next, let's see how a sorter works, and how it is responsible for our
memory pressure and lock contention.

### Randomness

Taking a step back, I was asking myself: <q>Is it really frozen?</q>. Cherry
on the cake: I was unable to reproduce the problem! Even the reporters of
the problem were unable to reproduce it consistently. Hmm, a random problem?
Fortunately, two of the reporters are obstinate. Ultimately, we got analysis.

{% figure_image(file="./memory-pressure") %}

Memory analysis of Element X in Android Studio (Element X is based on the Matrix
Rust SDK). It presents a callback tree, with the number of allocations and
deallocations for each node in this tree.

And, holy cow, we see **a lot** of memory allocations, exactly 322'042 to be
precise, counting for 743Mib, for the `eyeball_im_util::vector::sort::SortBy`
type!

{% end %}

The Room List wasn't frozen. It was taking **a lot** of time to yield values.
Sometimes, up to 4 minutes on a phone. Alright, we have two problems to solve
here:

1. Why is it random?
2. Why so much memory allocations and deallocations?

The second problem will be discussed in the next section. Let's start with the
first problem in this section, shall we?

Let's dig by the beginning. `eyeball_im_util::vector::sort::SortBy` is used
like so:

```rust
stream
    .sort_by(new_sorter_lexicographic(vec![
        Box::new(new_sorter_latest_event()),
        Box::new(new_sorter_recency()),
        Box::new(new_sorter_name()),
    ]))
```

`sort_by` receives a sorter: [`new_sorter_lexicographic`], it's from
[`matrix_sdk_ui::room_list::sorters`], and it's a constructor for a…
lexicographic sorter. All sorters must implement [the `Sorter` trait][`Sorter`].
Once again, it's a trait from `matrix_sdk_ui`, nothing fancy, it's simply this:

```rust
// Trait “alias”.
pub trait Sorter: Fn(&RoomListItem, &RoomListItem) -> Ordering {}

// All functions `F` are auto-implementing `Sorter`.
impl<F> Sorter for F
where
  F: Fn(&RoomListItem, &RoomListItem) -> Ordering {}
```

Put it differently, all functions with two parameters of type `&RoomListItem`,
and with a return type `Ordering` is considered a sorter. There. It's crystal
clear now, except… what's a lexicographic sorter?

{% procureur() %}

Should I really quote the documentation of `new_sorter_lexicographic`? My work here is turning into a tragedy.

It creates a new sorter that will run multiple sorters. When the nth sorter
returns `Ordering::Equal`, the next sorter is called. It stops as soon as a
sorter returns `Ordering::Greater` or `Ordering::Less`.

This is an implementation of a lexicographic order as defined for [cartesian
products].

[cartesian products]: https://en.wikipedia.org/wiki/Lexicographic_order#Cartesian_products

{% end %}

To make it short, we are executing 3 sorters: by _latest event_, by _recency_
and by _name_.

None of these sorters are using any form of randomness. It's a
<em lang="fr">cul-de-sac</em>. Let's take a step back by looking at `SortBy`
in `eyeball_im_util` itself maybe? <i>scroll the documentation</i>, not here,
<i>read the initial patch</i>, hmm, I see a mention of a binary search, <i>jump
on the code</i>, ah, [here, look at the comment][sort-by-binary-search]:

> When looking for the _position_ of a value (e.g. where to insert a new
> value?), `Vector::binary_search_by` is used — it is possible because the
> `Vector` is sorted. When looking for the _unsorted index_ of a value,
> `Iterator::position` is used.

[`Vector::binary_search_by`] doesn't mention any form of randomness in its documentation. Another <em lang="fr">cul-de-sac</em>.

{% comte() %}

Remember that the Room List appears frozen but it is actually blank. The problem
is not when the stream receives an update, but when the stream is “created”,
i.e. when the initial items are sorted for the first time before receiving
updates.

Moreover, the comment says <q>it is possible because the `Vector` is sorted</q>,
which indicates that “the vector” (I guess it's a buffer somewhere) _has been
sorted_ one way or another. What do you think?

{% end %}

Ah! Brilliant. That's correct! Looking at [the constructor of
`SortBy`][`SortImpl::new`] (or its implementation), we notice it's using
[`Vector::sort_by`]. And guess what? It's relying on… <i>drum roll</i>…
[quicksort][`Vector::sort_by#quicksort`]! Following the path, we see
[it actually creates a pseudo random number generator (PRNG) to do the
quicksort][`quicksort`].

Phew. Finally. Time for a cup of tea and a biscuit[^biscuit].

My guess here is the following. Depending of the (pseudo randomly) generated
pivot index, the number of comparisons aren't identical. We can enter
in a pathological case where more comparisons means more memory pressure,
which means slower sorting, which means… A Frozen Room List<sup><abbr
title="Trademark">TM</abbr></sup>, <i>play a horror movie music</i>!

### Memory Pressure

### Lock Contention

### The Desert

[defy]: https://dygma.com/pages/defy
[Element]: https://element.io/
[Matrix Rust SDK]: https://github.com/matrix-org/matrix-rust-sdk
[`VectorDiff`]: https://docs.rs/eyeball-im/0.8.0/eyeball_im/enum.VectorDiff.html
[`eyeball_im`]: https://docs.rs/eyeball-im/0.8.0/eyeball_im/
[`ObservableVector`]: https://docs.rs/eyeball-im/0.8.0/eyeball_im/struct.ObservableVector.html
[`ObservableVector::subscribe`]: https://docs.rs/eyeball-im/0.8.0/eyeball_im/struct.ObservableVector.html#method.subscribe
[SwiftUI]: https://developer.apple.com/swiftui/
[Jetpack Compose]: https://developer.android.com/compose
[`CollectionDifference.Change`]: https://developer.apple.com/documentation/swift/collectiondifference/change
[`MutableList`]: https://kotlinlang.org/api/core/kotlin-stdlib/kotlin.collections/-mutable-list/
[`async_rx::Switch`]: https://docs.rs/async-rx/0.1.3/async_rx/struct.Switch.html
[`eyeball_im_util`]: https://docs.rs/eyeball-im-util/0.10.0/eyeball_im_util/
[`eyeball_im_util::vector::Sort`]: https://docs.rs/eyeball-im-util/0.10.0/eyeball_im_util/vector/struct.Sort.html
[eyeball#43]: https://github.com/jplatte/eyeball/pull/43
[`stream_assert`]: https://docs.rs/stream_assert/0.1.1/stream_assert/
[`Future`]: https://doc.rust-lang.org/std/future/trait.Future.html
[`new_sorter_lexicographic`]: https://docs.rs/matrix-sdk-ui/0.16.0/matrix_sdk_ui/room_list_service/sorters/fn.new_sorter_lexicographic.html
[`matrix_sdk_ui::room_list::sorters`]: https://docs.rs/matrix-sdk-ui/0.16.0/matrix_sdk_ui/room_list_service/sorters/
[`Sorter`]: https://docs.rs/matrix-sdk-ui/0.16.0/matrix_sdk_ui/room_list_service/sorters/trait.Sorter.html
[sort-by-binary-search]: https://github.com/jplatte/eyeball/blob/b7dc6fde71e507459ecbd7519a8a22f12bf2a8de/eyeball-im-util/src/vector/sort.rs#L315-L318
[`Vector::binary_search_by`]: https://docs.rs/imbl/7.0.0/imbl/type.Vector.html#method.binary_search_by
[`SortImpl::new`]: https://github.com/jplatte/eyeball/blob/b7dc6fde71e507459ecbd7519a8a22f12bf2a8de/eyeball-im-util/src/vector/sort.rs#L261
[`Vector::sort_by`]: https://docs.rs/imbl/7.0.0/imbl/type.Vector.html#method.sort_by
[`Vector::sort_by#quicksort`]: https://github.com/jneem/imbl/blob/6feb48d04ed9bd2a004968541d1a90d61c423d31/src/vector/mod.rs#L1575-L1583
[`quicksort`]: https://github.com/jneem/imbl/blob/6feb48d04ed9bd2a004968541d1a90d61c423d31/src/sort.rs#L177-L185
[biscuit]: https://www.biscuitsec.org/

[^vectordiff_on_other_uis]: On [SwiftUI], there is the
    [`CollectionDifference.Change`] enum. For example: `VectorDiff::PushFront`
    is equivalent to `Change.insert(offset: 0)`. On [Jetpack Compose], there is
    [`MutableList`] object. For example: `VectorDiff::Clear` is equivalent to
    `MutableList.clear()`!
[^switch]: I would _love_ to talk about how this `Stream` produces
    a `Stream`, how the outer stream and the inner stream are switched (with
    `.switch()`!), how we've implemented that from scratch, but it's probably
    for another article. Meanwhile, you can take a look at [`async_rx::Switch`].
[^stream_assert]: Do you know [`stream_assert`]? It's another crate we've
    written to easily apply assertions on `Stream`s. Pretty convenient.
[^biscuit]: Yes, [biscuit].

<!--

size of `VectorDiff<Room>` at the end is 120 bytes

-->
