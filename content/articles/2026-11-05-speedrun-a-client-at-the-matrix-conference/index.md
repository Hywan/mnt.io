+++
title = "Speedrun a client at the Matrix Conference"
date = "2026-11-05"
description = "I have speedrun a Matrix client at the Matrix Conference in Malmö. It was a fun exercise! Other talks were also amazing."
[taxonomies]
keywords=["matrix", "messaging", "client", "rust"]
+++

Malmö. <time datetime="2026-10-21 15:15">Thursday, October 21, 2026.
3:15pm</time>. I was
[back](@/articles/2024-10-30-sliding-sync-at-the-matrix-conference/index.md) on
stage at the [Matrix Conference], live and broadcasted on Internet, to do a
speedrun of a Matrix client with the [Matrix Rust SDK] in 20 minutes.

[Matrix] is an open network for secure, decentralised communication. It is an
important technology for Internet.

Matrix is a protocol. Everyone can implement it: either by providing its own
server and connect it to the federation, or by providing its own client and
connect it to the federation too. Nobody has a full control over the network,
and nobody controls the clients nor the servers. And yet, end-to-end encryption
is working, synchronisation is working, and everybody can talk to everybody,
communities organise themselves, the network grows and grows.

I am working at [Element] since 4 years now. I am sponsored to work on the
[Matrix Rust SDK], a project owned by the Matrix organisation. Everything we do
is available to the entire Matrix community, not only for Element. Well, this is
the open source world.

In this context, we thought it would have been nice to demonstrate how to build
a Matrix client in 20 minutes; just for fun. What a silly idea, <i>sight</i>!
It is obviously a difficult exercise. Yes, the SDK is getting mature, robust and
very easy to use, but, come on, 20 minutes is really _short_. Anyway, I've
enjoyed doing it, and the feedback were great! Thanks to anyone who has watched
it!

My talk can be viewed here:

{{ <ccc
  id="matrix-conference-2026-100919-speedrun-client-with-the-matrix-rust-sdk"
  title=" Speedrun Client with the Matrix Rust SDK "
  caption="
  [Download the slides as PDF (827Kib)](./slides.pdf).
  <br />
  [Watch this talk on YouTube](https://youtu.be/UhUYvhKFZLA?si=6K4NF9nu-CxvlkAr).
  "
/> }}

## Other Talks

[All the talks are available online][watch], including talks from the public
sector, such as Europe, Brazil, Dutch etc. Let's pick some technical talks from
my good friends.

### What's New in the Matrix Rust SDK

The creator and tech lead of the project, Damir Jelić, presents what's new and
what's changed this year in the Matrix Rust SDK. And yes, we have merged
1089 pull requests from 73 unique authors in a year, that's crazy for such a
small team.

{{ <ccc
  id="matrix-conference-2026-100912-what-s-new-in-the-matrix-rust-sdk"
  title="What's New in the Matrix Rust SDK"
  caption="
  [View and download the slides](https://2026.matrix.org/slides/slides_QEQAZY.pdf).
  <br />
  [Watch this talk on YouTube](https://youtu.be/RrSK9drMhk4?si=v4wU7eKHdo_fttDH)."
/> }}

### How Encryption Works in Matrix

Andy Balaam has this particular, rare talent to make a complex topic simple to
understand. If you want to learn about how encryption works in Matrix, this is a
must watch. I mean, really.

{{ <ccc
  id="matrix-conference-2026-98869-how-encryption-works-in-matrix"
  title="How Encryption works in Matrix"
  caption="
  [View and download the slides](https://2026.matrix.org/slides/slides_DDYYQW.pdf).
  <br />
  [Watch this talk on YouTube](https://youtu.be/yXhtMolX_3I?si=lZI8tMzslua0rUUu)."
/> }}

### New Phone Who Dis: User Verification Using X.509 Certificates

Richard van der Hoff has the same superpower than Andy: concisely explain
non-trivial concepts. If you don't know what X.509 certificates are, or if you
are curious to see how we can use them in Matrix to automatically ensure you're
chatting with the right person, not an intruder, without reading out numbers of
emojis, this talk is for you.

{{ <ccc
  id="matrix-conference-2026-100422-new-phone-who-dis-user-verification-using-x-509-certificates"
  title="New phone who dis: User verification using X.509 certificates"
  caption="
  [View and download the slides](https://2026.matrix.org/slides/slides_A8MVMH.pdf).
  <br />
  [Watch this talk on YouTube](https://youtu.be/4yAyIDuS4lA?si=jU4qH84WsVGN6ymT)."
/> }}

## About Transport

I currently live in Switzerland. The conference was in Sweden. Europe has a
fantastic rail network, and more importantly, a unique **night train** network!

The trip by train has generated
**9.17[kg CO<sub>2</sub>eq][co2eq], be 0.45% of the annual carbon budget** (for
a sustainable world, we should all be at 2000kg CO<sub>2</sub>eq maximum).
Going there by plane would have generated 848kg CO<sub>2</sub>eq, be 42% of
the annual carbon budget. It's 92 times more than the train.

Not only train is way less polluting, it's also cheaper! The full trip, 2900km
total, including night train, has costed 406EUR. With a flight, it would have
been around 520EUR, if I include trains to reach the airport, we are about
600EUR.

If you are curious [to try night train, you can check this map][night-train]
that lists all possible connections, stops, companies operating the trains
etc. Taking the night train is a nice way of travelling, and it saves a lot
of carbon emissions.

I've taken regular day trains to go to Malmö, and a night train to come back
home from Hamburg.

[Matrix Conference]: https://2026.matrix.org/
[Matrix Rust SDK]: https://github.com/matrix-org/matrix-rust-sdk/
[Matrix]: https://matrix.org/
[Element]: https://element.io/
[watch]: https://2026.matrix.org/watch/
[co2eq]: https://en.wikipedia.org/wiki/Global_warming_potential
[night-train]: https://back-on-track.eu/night-train-map/
