+++
title = "Linked Chunk"
date = "2025-01-08"
description = ""
[taxonomies]
keywords=["matrix", "rust", "data-structure"]
[extra]
pinned = true
+++

[The Matrix protocol][Matrix] is a solid piece of technology providing
decentralized, end-to-end encrypted, real-time communication. Some people
are using it to develop personal messaging applications, like [Element] or
[Fractal]. Some people are using it to develop bots, like [trinity]. Usages
are radically different: a regular user of a messaging application can have
150 or 200 rooms, a power user can have 4000 rooms, and a bot can be present
in 10'000 or 500'000 rooms.

As a contributor of the [Matrix Rust SDK][matrix-rust-sdk], I feel pretty
concerned by the performance of this set of libraries aiming at developing
Matrix clients. These libraries are used for this magnitude of different
projects, and must provide great performances in all situations.

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

… oh, you again. Alrighty. Yes, you're correct. That's also how I understand
Computer Science, and that's why I consider it is an _Art_. To me, coding is not
the difficult part: being able to see how to split a large problem into smaller
ones, to see patterns, to transform the problems until they reach simple forms…
that the beauty of it, and this is _the_ difficult part[^1]. Anyway, let's jump
back on Matrix if you don't mind.

Without constraints, so, we cannot express a correct solution. We need to list
them. When dealing with the Matrix protocol, we have 2 ways of fetching events
from a federation of homeservers onto a client:

1. By syncing: syncing refers to the mechanism that provides the new events, it
   starts from _now_, no absolute point in time, it is just, well, _now_,
2. By paginating: paginating can happen in two directions, forwards or backwards,
   starting from an absolute point in time, known as a _batch token_, up to
   another _batch token_, or until a certain number of events are fetched.

Syncing happens via different HTTP API, usually ending by
`/sync`. The most recent one is defined by the [MSC4186], known as [Simplified
Sliding Sync (see my talk at the Matrix Conference 2024 about
it)](@/articles/2024-10-30-sliding-sync-at-the-matrix-conference/index.md).
Paginating is part of the specification, [see
`/rooms/{room_id}/messages`][/messages]. There are additional ways to fetch
events, like [`/rooms/{room_id}/context/{event_id}`][/context] for example,
however this article doesn't aim at explaining how the Matrix protocol works.

Okay. What are the constraints then?

When syncing, we don't want to sync _all new events_ since the last sync. It
could dramatically be slow from the network point of view, or heavy from the
homeserver point of view. It can pretty quickly reach mebibytes[^2] of data.
Indeed, considering a modest encrypted event is about 800 bytes, syncing 100
events per room, for 200 rooms, would result in a payload of approximately
15.3 MiB. That's a lot.

That why MSC4186 offers (very roughly explained) a mechanism to limit the number
of events per room the client will _sync_, over a subset of rooms: this is
on-demand iterative syncing. For each room, the client will receive new events
in addition of a _batch token_. When a room is opened, the client might want to
_paginate_ to fetch more events.

The assiduous reader you are is starting to see the problem. A room contains
events, but also… holes waiting to be filled! Indeed, imagine a client has
synced events, then was offline for a moment, and online again: a new sync
starts, but not all events are fetched, then there is a hole between old and new
events. It can be summarized with the following sequence of syncs:

<figure>
  
  | sync response | limited | room events becomes… |
  |:-|:-|:-|
  | `[$e0, $e1]` | no | `[$e0, $e1]` |
  | `[$e5, $e6]` | yes | `[$e0, $e1, …, $e5, $e6]` |
  | `[$e7]` | no | `[$e0, $e1, …, $e5, $e6, $e7]` |

  <figcaption>
  
  A sequence of syncs, which return up to 2 events each time, starting from an
  empty room. The _limited_ column represents whether the sync has returned a
  partial response, i.e. if events are missing between this sync response and
  the previous one. \
  In this table, the `…` represents a hole.

  </figcaption>

</figure>

The first sync returns 2 events. The second sync returns 2 events but they are
_not_ connected to the previous one, we know there is a hole. The third sync
returns 1 event, which is connected to the previous one, so there is no hole.
A well-written client should back-fill these holes by using the back-pagination
mechanism.

Now, back to our question. What structure to use to represent this kind of data?

{% comte() %}
I guess we didn't mention all the constraints, do we?

I also foresee we will need an in-memory data structure, and a persistent data
structure, like on disk. After all, the goal is to _store_ the events so that
one doesn't need to re-fetch them from the network. Once they are stored, it's
useful to do operations on events, like looking for all medias, searching some
texts etc. This dual approach (in-memory vs on-disk) is an important constraint,
is it?
{% end %}

Absolutely. This article won't list all the constraints, because they are many,
and it can rapidly become boring. Constraints are necessary to know which
operations will be applied on the data structure, and make the most frequent
ones efficient. Although, we can add two important constraints:

* Room events are not read-only: we must be able to remove one event at a
  particular position (think of [redaction]),
* Room events are not append-only: we must be able to insert events at any
  position, not only after already fetched events (think of pagination).

About in-memory versus on-disk, yes, this is really an important constraint.

Why do we need an in-memory representation? For instance, [many events have a
relationship to other events][relates_to], it implies many look up operations
(i.e. iterate over events). Another example, we want to provide a cache: a user
is allowed to switch rooms to interact with multiple groups of people, in this
case we don't want to reload all events everytime a room is opened, it would be
a waste of resources.

Why do we need an on-disk representation? As {{ comte_name() }} said, we want
to not refetch all events from the network everytime. It brings offline support,
but also a large palette of nice API, like searching events, listing media,
listing links, stuff like that. However, we must be careful to keep in-memory
and on-disk synchronized: defining a strict flow for the data updates is
important considering in-memory data is likely to be a subset of on-disk data,
more on that later. The in-memory data structure must be loadable from the
on-disk representation efficiently.

One last thing to know, in the Matrix protocol, there is 2 different orders
for events (without giving too much details):

1. Sync ordering: you can assign a rank to each event received via the sync,
2. Topological order: it's a more global order from the federation point of
   view.

The problem is: topological order is used by pagination, whilst sync order
is used by sync. There is no canonical way to reconcile both orderings. Put
in other terms: given an event, it's **impossible** to always know whether it
should be _before_ or _after_ another event. The answer depends on how the event
has been fetched. If you want to learn more, I beg you to read [<cite>Message
order in Matrix: right now, we are deliberately inconsistent</cite>][orders],
from another excellent developer and colleague Andy Balaam.

That's enough details to understand the problem.

> We need to represent a set of partially ordered events with holes. A hole can
> be filled by events, plus other holes.

## Gazing existing solutions

Before unveiling our super-inventor costume, let's look at what exists in the
wild.

Because events cannot be ordered by themselves, but has a “contextual order”
(i.e. it depends if the events have been received by sync or pagination), it
excludes all data structures like B-trees, B-tree maps. It actually excludes
sets, maps, hash maps and so on.

Let's turn toward sequences, like vectors or arrays. First off, we can exclude
arrays as they are fixe-sized. Vectors might be a candidate, but inserting at
random positions means all events after this position must be shifted. An
insertion is
<math>
  <mi>O</mi><mo>(</mo>
    <mi>n</mi><mo>-</mo><mi>i</mi>
  <mo>)</mo>
</math>[^3]
where <math><mi>i</mi></math> is the insertion index and <math><mi>n</mi></math>
is the length of the sequence. This could be okay-ish if insertions were not
happening so often: everytime a back-pagination is done, insertions happen.
Consequently, we can say good bye to vectors.

Another kind of structure for sequences is linked list. An event would fill
the role of the value held by the linked list. Each event would be linked to a
previous and to a next event. In this case, insertion is
<math>
  <mi>O</mi><mo>(</mo>
    <mi>min</mi><mo>(</mo>
      <mi>i</mi>
      <mo>,</mo>
      <mi>n</mi><mo>-</mo><mi>i</mi>
    <mo>)</mo>
  <mo>)</mo>
</math>; unlike a vector, we have to
traverse the linked list until we find the correct place where to insert, but
insertion doesn't imply shifting all events after.



[Matrix]: https://matrix.org/
[Element]: https://element.io/
[Fractal]: https://gitlab.gnome.org/GNOME/fractal
[trinity]: https://github.com/bnjbvr/trinity
[matrix-rust-sdk]: https://github.com/matrix-org/matrix-rust-sdk
[bouvier]: https://bouvier.cc/
[MSC4186]: https://github.com/matrix-org/matrix-spec-proposals/pull/4186
[/messages]: https://spec.matrix.org/v1.13/client-server-api/#get_matrixclientv3roomsroomidmessages
[/context]: https://spec.matrix.org/v1.13/client-server-api/#get_matrixclientv3roomsroomidcontexteventid
[redaction]: https://spec.matrix.org/v1.13/client-server-api/#redactions
[relates_to]: https://spec.matrix.org/v1.13/client-server-api/#forming-relationships-between-events
[orders]: https://artificialworlds.net/blog/2024/12/04/message-order-in-matrix/


[^1]: If, between the lines, you read a reaction to the generative artifical
intelligence trend that desperately wants to replace developers by machines to
generate code, you read it well.

[^2]: Yes, [mebibytes](https://en.wikipedia.org/wiki/Byte#Multiple-byte_units),
isn't it a cute name?

[^3]: [Big O notation](https://en.wikipedia.org/wiki/Big_O_notation) is a way to
classify algorithms according to how their run time or space requirements grow
as the input size grows.
