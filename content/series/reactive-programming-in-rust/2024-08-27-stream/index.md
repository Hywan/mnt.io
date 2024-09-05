+++
title = "Observability"
date = "2024-08-27"
description = "The basis of reactive programming is observability. We need to talk about `Future` and `Stream`"
[taxonomies]
keywords=["rust", "async", "future", "stream"]
+++

Imagine a collection of values `T`. This collection can be updated by inserting
new values, removing existing ones, or the collection can truncated, cleared…
This collection acts as the standard `Vec`. However, there is a subtlety: This
collection is _observable_. It is possible for someone to _subscribe_ to this
collection and to receive its updates.

This observability pattern is the basis of reactive programming. It applies to
any kind of type. Actually, it can be generalized as a single `Observable<T>`
type. For collections though, we will see that an `ObservableVector<T>` type is
more efficient.

I’ve recently played a lot with this pattern as part of my work inside the
[Matrix Rust SDK], a set of Rust libraries that aim at developing robust Matrix
clients or bridges. It is used by the next generation Matrix client developed
by [Element], namely [Element X]. The Matrix Rust SDK is cross-platform:
Element X has two implementations: on iOS, iPadOS and macOS with Swift, and on
Android with Kotlin. Both languages are using our Rust bindings to [Swift] and
[Kotlin]. This is the story for another series (how we have automated this, how
we support asynchronous flows from Rust to foreign languages etc.), but for the
moment, let’s keep focus on reactive programming.

Taking the Element X use case, the room list –which is the central piece of the
app– is fully dynamic:

- rooms are sorted by recency, so rooms move to the top when a new message is
  received,
- the list can be filtered by room properties (one can filter by group or
  people, favourite, unreads, invites…),
- the list is also searchable by room names.

Why is it dynamic? Because the app continuously sync new data: when a room gets
an update from the network, the room list is automatically updated. The beauty
of it: we have nothing to do. Sorters and filters are run automatically. Why?
Spoiler: because everything is a `Stream`.

Because of the Rust async model, every part is lazy. The app never needs to ask
for Rust if a new update is present. It literally just waits.

I believe this reactive programming approach is pretty interesting to explore.
And this is precisely the goal of this series. We are going to play with
`Stream` a lot, with higher-order `Stream` a lot more. Let's st…

{% comte() %}

Hold on a second! I'm sure this first step is a bit steep for someone who's not
familiar with asynchronous code i Rust, don't you think? Obviously, it doesn't
involve

Before digging in the implementation details you are eager to share, maybe we
can start with examples.

{% end %}

Before digging into the really fun bits, we need some basis.

[Matrix Rust SDK]: https://github.com/matrix-org/matrix-rust-sdk
[Element]: https://element.io
[Element X]: https://element.io/labs/element-x
[Swift]: https://www.swift.org/
[Kotlin]: https://kotlinlang.org/
