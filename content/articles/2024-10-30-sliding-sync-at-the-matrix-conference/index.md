+++
title = "Sliding Sync at the Matrix Conference"
date = "2024-10-30"
description = "I have presented Sliding Sync, a novel, fast and efficient synchronisation mechanism for the Matrix protocol, at the first Matrix Conference in Berlin. It's been many many months that I'm working on this project, and I'm joyful it's now available to everyone for a better Matrix user experience!"
[taxonomies]
keywords=["matrix", "messaging", "sync", "rust"]
+++

Berlin. <time datetime="2024-09-21 10:00">Saturday, September 21, 2024.
10am</time>. I was live on stage and broadcasted on Internet, to talk about
(Simplified) Sliding Sync, the next sync mechanism for Matrix, at the first
[Matrix Conference].

[Matrix] is an open network for secure, decentralised communication. It is an
important technology for Internet.

Matrix is a protocol. Everyone can implement it: either by providing its own
server and connect it to the federation, or by providing its own client and
connect it to the federation too. Nobody has a full control over the network,
and nobody controls the clients nor the servers. And yet, end-to-end encryption
is working, synchronisation is working, and everybody can talk to everybody,
communities organize themselves, the network grows and grows.

I am working at [Element] since 2 years now. I am paid to work on the [Matrix
Rust SDK], a project owned by the Matrix organisation. Everything we do is
available to the entire Matrix community, not only for Element. Well, this is
the open source world.

Matrix previous synchronisation mechanism is slow and inefficient. To put
Matrix on the hands of everyone for a daily pleasant usage, we have started
to experiment with a new sync mechanism, called Sliding Sync. The MSC —which
stands for Matrix Spec Changes— like RFC for example—, so the [MSC3575] was
our experimental foundation to play with a new sync mechanism. After many sweat
and tears, we ultimately found a working pattern and design that fulfill a
large majority of our usecases. Along the way, the implementation inside the
Sliding Sync Proxy —a proxy that sits on the top of a homeserver[^1] to provide
this new sync mechanism— was starting to feel really buggy and was really slow.
It was time to clean up everything, including the MSC.

Enter [MSC4186], which is basically Simplified Sliding Sync. We have mostly
removed features from [MSC3575], so that the implementation on the server-side
is much simpler and lighter. Simplified Sliding Sync is now implemented and
enabled by default on [Synapse], one of the major homeserver implementations.
Other homeservers have implemented MSC3575 and are working on supporting
MSC4186.

Sliding Sync has a huge impact on the overall user experience. Syncing is now
fast and almost transparent. It also works linearly whether the user has 10
or 10'000 rooms.

My talk can be viewed here:

{{ youtube(
  id = "kI2lSCVEunw",
  title = "Simplified Sliding Sync, by Ivan Enderlin, at the Matrix Conference 2024, Berlin",
  caption = "[Download the slides as PDF (21MiB)](./slides.pdf)"
) }}

<h2>Other talks</h2>

[All the talks are available online][watch], including talks from the public
sector, like NATO, Sweden, French or German administrations… I encourage you to
check the list! Nonetheless, I take the opportunity of this article to highlight
some announcement talks, or technical (Matrix internals) talks, I've enjoyed.

<h3>Matrix 2.0 and the launch of Element X!</h3>

Two presentations for the price of one: <cite>Matrix 2.0 Is Here!</cite> by
Matthew Hogdson. 10 years after the original launch of Matrix, and 5 years after
Matrix 1.0, what a best anniversary to announce Matrix 2.0.

{{ youtube(
  id = "ZiRYdqkzjDU"
  title = "Matrix 2.0 Is Here!, by Matthew Hogdson, at the Matrix Conference 2024, Berlin"
  caption = "[View and download the slides](https://2024.matrix.org/documents/talk_slides/LAB3%202024-09-20%2010_15%20Matthew%20-%20Matrix%202.0%20is%20Here_%20The%20Matrix%20Conference%20Keynote.pdf)"
)}}


The second video is <cite>Element X Launch!</cite> by Amandine Le Pape, Ștefan
Ceriu, and Amsha Kalra. They present Element X, how it's been designed,
developed, how it uses the Matrix Rust SDK, and where you can see awesome demos
of Element X with Element Call and so on! It was a great moment for everyone
working at Element and users!

{{ youtube(
  id = "gHyHO3xPfQU"
  title = "Element X Launch!, by Amandine Le Pape, Ștefan Ceriu, and Amsha Kalra, at the Matrix Conference 2024, Berlin"
  caption = "[View and download the slides](https://2024.matrix.org/documents/talk_slides/LAB3%202024-09-20%2017_45%20Amandine%20Le%20Pape,%20Amsha%20Kalra,%20Stefan%20Ceriu%20-%20Element%20X%20Launch%20Complete%20Presentation.pdf)"
)}}

<h3>Unable to decrypt this mesage</h3>

<cite>Unable to decrypt this message</cite> by Kegan Dougal. This talk explains
why one can see an _Unable To Decrypt_ error while trying to view a message in
Matrix. Most problems have been solved today, but the great message about this
presentation is to show how hard it is (was!) to provide reliable end-to-end
encryption over a federated network. One homeserver can be overused and then
slowed down, or a connection between two servers can be broken, or one device
lost its connectivity because it's used in the subway, or whatever. All these
classes of problems are illustrated and explained. I liked it a lot because it
gives a good sense of why end-to-end encryption is hard over a giant
decentralised, federated network, with encryption keys being renewed frequently,
and how problems have been solved.

{{ youtube(
  id = "FHzh2Y7BABQ"
  title = "Unable to decrypt this message, by Kegan Dougal, at the Matrix Conference 2024, Berlin"
  caption = "[View and download the slides](https://2024.matrix.org/documents/talk_slides/LAB4%202024-09-21%2014_30%20Kegan%20Dougal%20-%20Unable%20to%20decrypt%20this%20message.pdf)"
)}}

<h3>News from the Matrix Rust SDK</h3>

<cite>Strengthening the Base: Laying the Groundwork for a more robust Rust
SDK</cite> by Benjamin Bouvier, a good friend! This talk explains the recent
updates of the Matrix Rust SDK: how we have designed new API to make the
developer experience easier and more robust.

{{ youtube(
  id = "KOaoZKc1tgo"
  title = "Strengthening the Base: Laying the Groundwork for a more robust Rust SDK, by Benjamin Bouvier, at the Matrix Conference 2024, Berlin"
  caption = "[View and download the slides](https://2024.matrix.org/documents/talk_slides/LAB3%202024-09-20%2011_15%20Benjamin%20Bouvier%20-%20Rust%20SDK%20Foundation.pdf)"
)}}

<h2>About transport</h2>

I currently live in Switzerland. The conference was in Germany. Europe has a
fantastic rail network, and more importantly, a unique **night train** network!

Going there by plane would have generated 1'344 [kg CO<sub>2</sub>eq][co2eq],
be 68% of my annual carbon budget (for a sustainable world, we should all be at
2'000 kg maximum). Taking the train however has generated
**6.5 kg CO<sub>2</sub>eq, be 0.33% of my annual carbon budget**. It's 206 times
less than the plane!

If you are curious [to try night train, you can check this map][night-train]
that lists all possible connections, stops, companies operating the trains
etc. Taking the night train is a nice way of travelling, and it saves a lot
of emissions.

I've taken a regular day train to go to Berlin, and a night train to come back
home.


[^1]: A _homeserver_ in the Matrix terminology is simply a Matrix server.

[Matrix Conference]: https://2024.matrix.org/
[Matrix]: https://matrix.org/
[Matrix Rust SDK]: https://github.com/matrix-org/matrix-rust-sdk/
[Element]: https://element.io/
[MSC3575]: https://github.com/matrix-org/matrix-spec-proposals/blob/kegan/sync-v3/proposals/3575-sync.md
[MSC4186]: https://github.com/matrix-org/matrix-spec-proposals/blob/erikj/sss/proposals/4186-simplified-sliding-sync.md
[Synapse]: https://github.com/element-hq/synapse/
[watch]: https://2024.matrix.org/watch/
[co2eq]: https://en.wikipedia.org/wiki/Global_warming_potential
[night-train]: https://back-on-track.eu/night-train-map/
