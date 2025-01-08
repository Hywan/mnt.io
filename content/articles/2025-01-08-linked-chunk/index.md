+++
title = "Linked Chunk"
date = "2025-01-08"
description = ""
[taxonomies]
keywords=["matrix", "rust", "data-structure"]
[extra]
pinned = true
+++

[The Matrix protocol][Matrix] is a solid piece of technology for providing
decentralized, end-to-end encrypted, real-time communication. Some people
are using it to develop personal messaging applications, like [Element] or
[Fractal]. Some people are using it to develop bots, like [trinity]. Usages
are radically different: a regular user of a messaging application can have
150 or 200 rooms, a power user can have 4000 rooms, and a bot can be present
in 10'000 or 500'000 rooms.

As a contributor of the [Matrix Rust SDK][matrix-rust-sdk], I feel pretty
concerned by the performance of this set of libraries that aim at developing
Matrix clients. This project is used for this magnitude of different projects,
and must provide great performances in all situations.

Recently, my good friend and excellent developer [Benjamin Bouvier][bouvier] and
I have been thinking about a way to efficiently store and manipulate all events
received from the federation of homeservers onto a client. An event in the
Matrix universe is kind of a message, but not all messages are text: a media,
a call, a state, a reaction, a kick, a membership update, a room creation, an
invite… they are typed events. Storing all these events is what this article is
about: a new data structure we have designed to store hundreds of thousands of
events, in different scenarii, while still providing [a reactive programming
approach](@/series/reactive-programming-in-rust/_index.md) to easily connect
this data structure to other API inside the Matrix Rust SDK.

## The constraints

First and foremost, we need to define our constraints, because without constr…

{% comte() %}
What is Computer Science if not the expression of one solution amongst many to a
particular well-defined problem?

After all, a solution that responds to all problems solves none of them
efficiently. Nonetheless, it must not be confused with a _generic_ solution
that adapts to multiple forms of the same problem, a problem that has been
meticulously abstracted, cut off, simplified. This is the essence of Computer
Science: to take a complex problem, and to reduce it to a finite set of smaller
problems, until they are small enough to be efficiently resolved.

Don't you agree with me?
{% end %}

… oh, you're still here. Alrighty. Yes, you're correct. That's also how I
understand Computer Science, and that's why I consider it is an _Art_. To me,
coding is not the difficult part: being able to see how to split a large problem
into smaller ones, to see patterns, to transform the problems until they reach
simple forms… that the beauty of it, and this is _the_ difficult part[^1]. Anyway,
let's jump back on Matrix if you don't mind.

Without constraints, so, we cannot express a correct solution. We need to list
them. When dealing with the Matrix protocol, we have 2 ways of fetching events
from a federation of homeservers onto a client:

1. By syncing: syncing refers to the mechanism that provides the most recent
   events, it starts from _now_, no absolute point in time, it is just, well,
   _now_.
2. By paginating: paginating can happen in two directions, forwards or backwards,
   starting from an absolute point in time, known as a _batch token_, until
   another _batch token_ and until a certain number of events are fetched.

Syncing happens via different HTTP API. The most recent one is defined by the
[MSC4186], known as [Simplified Sliding Sync (see my article about it)](@/articles/2024-10-30-sliding-sync-at-the-matrix-conference/index.md).
Paginating is part of the specification, [see
`/rooms/{room_id}/messages`](/messages).

Okay. What are the constraints then?

When syncing, we don't want to sync _all new events_. It could dramatically be
slow from the network point of view, or heavy from the homeserver point of view.
It can pretty quickly reach mebibytes[^2] of data. Indeed, considering a modest
encrypted event is about 800 bytes, syncing 100 events per room, for 200 rooms,
would result in a payload of approximately 15.3 MiB. That's a lot.

That why MSC4186 offers (very roughly explained) a mechanism to limit the number
of events per room the client will _sync_, over a subset of rooms: this is
on-demand iterative syncing. For each room, the client will receive new events
in addition of a _batch token_. When a room is opened, the client might want to
_paginate_ to fetch more events.

The assiduous reader you are is starting to see the problem. A room contains
events, but also… holes waiting to be filled! Imagine the events are numbered:

| room events before |  sync response | room events after |
|-|-|-|
| `[]` | `[$e0, $e1]` | `[$e0, $e1]` |
| `[$e0, $e1]` | `[$e5, $e6]` | `[$e0, $e1, …, $e5, $e6]` |
| `[$e0, $e1, …, $e5, $e6]` | `[$e7]` | `[$e0, $e1, …, $e5, $e6, $e7]` |

In this table, the `…` represent a hole. The first sync returns 2 events. The
second sync returns 2 events but they are _not_ connected to the previous one,
we know there is a hole. The third sync returns 1 event, which is connected to
the previous one, so there is no hole.

Now, back to our question. What structure to use to store this kind of data?

{% comte() %}
I guess we didn't mention all the constraints, do we?

I also foresee we will need an in-memory data structure, and a persistent data
structure, like on disk.
{% end %}

Absolutely. This article won't list all the constraints, because they are many,
and it can rapidly become boring. Although, we can add two important
constraints:

* Room events are not read-only: we must be able to remove one event at a
  particular position,
* Room events are not append-only: we must be able to insert events at any
  position, not only after already fetched events.

About in-memory versus on-disk, yes, this is really important constraint. It
should be light in memory, it should load fast… HERE

That's enough details to understand the problem.

> We need to represent a set of events with holes. A hole can be filled by
> events, plus other holes.




[Matrix]: https://matrix.org/
[Element]: https://element.io/
[Fractal]: https://gitlab.gnome.org/GNOME/fractal
[trinity]: https://github.com/bnjbvr/trinity
[matrix-rust-sdk]: https://github.com/matrix-org/matrix-rust-sdk
[bouvier]: https://bouvier.cc/
[MSC4186]: https://github.com/matrix-org/matrix-spec-proposals/pull/4186
[/messages]: https://spec.matrix.org/v1.13/client-server-api/#get_matrixclientv3roomsroomidmessages


[^1]: If, between the lines, you read a reaction to the generative artifical
intelligence trend that desperately wants to replace developers by machines to
generate code, you read it well.

[^2]: Yes, [mebibytes](https://en.wikipedia.org/wiki/Byte#Multiple-byte_units),
isn't it a cute name?
