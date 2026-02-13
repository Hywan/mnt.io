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
bug in a higher-order stream: we even have a sweet treat! I believe we have
all the ingredients for a juicy story. Let's cook, and <em lang="fr">bon
appétit !</em>

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

[The `VectorDiff` type][`VectorDiff`] comes from the `eyeball-im` crate. It
looks like this:

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

It represents a change in [an `ObservableVector`][`ObservableVector`].
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

The Room List is simply blank. It doesn't load.

> What could freeze the Room List?

What are our options?

{% factotum() %}

It would be a real pleasure if you let me assist you in this task.

- The network sync is not running properly, hence giving the _impression_ of a
  frozen Room List? No, everything works as expected here.
- The “source streams” used by the Room List are not yielding the expected
  updates? No, everything works like a charm.
- The “merge of streams” is broken for some reasons? No, still not.
- The filtering of the streams? Not touched since a long time.
- The sorting? Ah, maybe, we have changed something here, but all the tests
  are green.

{% end %}


Several options were in front of us:


<!--
Let's look at how the stream is computed then:

```rust
let stream = stream! {
    loop {
        // Wait for the filter to be updated.
        let filter = filter_fn_cell.take().await;

        // Get the “raw” entries.
        let (raw_values, raw_stream) = self.entries();

        // Combine normal stream updates with other updates from rooms
        let stream = merge_stream_and_receiver(raw_values.clone(), raw_stream, other_updates);

        let (values, stream) = (values, stream)
            .filter(filter)
            .sort_by(new_sorter_lexicographic(vec![
                // Sort by latest event's kind, i.e. put the rooms with a
                // **local** latest event first.
                Box::new(new_sorter_latest_event()),
                // Sort rooms by their recency (either by looking
                // at their latest event's timestamp, or their
                // `recency_stamp`).
                Box::new(new_sorter_recency()),
                // Finally, sort by name.
                Box::new(new_sorter_name()),
            ]))
            .dynamic_head_with_initial_value(page_size, limit_stream.clone());

        // Clearing the stream before chaining with the real stream.
        yield stream::once(ready(vec![VectorDiff::Reset { values }]))
            .chain(stream);
    }
}
.switch();
```

There is a lot to understand here. 
-->

### Sorter

Taking a step back, I was asking myself: is it really frozen? I was unable to reproduce the problem. Even the reporters of the problem were unable to reproduce it consistently. Hmm, a random problem? Fortunately, two of the reporters are obstinate. Ultimately, we got analysis.

### Memory Pressure

### Lock Contention

### The Desert

[defy]: https://dygma.com/pages/defy
[Element]: https://element.io/
[Matrix Rust SDK]: https://github.com/matrix-org/matrix-rust-sdk
[`VectorDiff`]: https://docs.rs/eyeball-im/0.8.0/eyeball_im/enum.VectorDiff.html
[`ObservableVector`]: https://docs.rs/eyeball-im/0.8.0/eyeball_im/struct.ObservableVector.html
[`ObservableVector::subscribe`]: https://docs.rs/eyeball-im/0.8.0/eyeball_im/struct.ObservableVector.html#method.subscribe
[SwiftUI]: https://developer.apple.com/swiftui/
[Jetpack Compose]: https://developer.android.com/compose
[`CollectionDifference.Change`]: https://developer.apple.com/documentation/swift/collectiondifference/change
[`MutableList`]: https://kotlinlang.org/api/core/kotlin-stdlib/kotlin.collections/-mutable-list/

[^vectordiff_on_other_uis]: On [SwiftUI], there is the
    [`CollectionDifference.Change`] enum. For example: `VectorDiff::PushFront`
    is equivalent to `Change.insert(offset: 0)`. On [Jetpack Compose], there is
    [`MutableList`] object. For example: `VectorDiff::Clear` is equivalent to
    `MutableList.clear()`!

<!--

size of `VectorDiff<Room>` at the end is 120 bytes

-->
