+++
title = "Hello fruux!"
date = "2014-11-24"
description = "Eh, new job!"
[taxonomies]
keywords=["job", "caldav", "carddav", "webdav"]
+++

## Leaving the research world

I have really enjoyed my time at INRIA and Femto-ST, 2 research
institutes in France. But after 8 years at the university and a hard PhD
thesis (but with great results by the way!), I would like to see other
things.

My time as an intern at Mozilla and my work in the open-source world
have been very _seductive_. Open-source contrasts a lot with the
research world, where privacy and secrecy are first-citizens of every
project. All the work I have made and all the algorithms I have
developed during my PhD thesis have been implemented under an
open-source license, and I ran into some issues because of such decision
(patents are sometimes better, sometimes not… long story).

So, I like research but I also like to hack and share everything. And
right now, I have to get a change of air! So I asked on Twitter:

> I (Ivan Enderlin, a fresh PhD, creator of Hoa) am looking for a job.
> Here is my CV:
> [http://t.co/dAdLm35RUu](http://t.co/dAdLm35RUu).
> Please, contact me!
> [\#hoajob](https://twitter.com/hashtag/hoajob?src=hash)
>
> — Hoa project (@hoaproject) [July 24th,
> 2014](https://twitter.com/hoaproject/status/492382581271572480)

And what a surprise! A **lot** of companies answered to my tweet (most
of them in private of course), but the most interesting one at my eyes
was… fruux 😉.

## fruux

fruux defines itself as: “A unified contacts/calendaring system that
works across [platforms and
devices](https://fruux.com/supported-devices/). We are behind
[`sabre/dav`](https://fruux.com/opensource), which is the most popular
open-source implementation of the
[CardDAV](http://en.wikipedia.org/wiki/CardDAV) and
[CalDAV](http://en.wikipedia.org/wiki/CardDAV) standards. Besides us,
developers and companies around the globe use our `sabre/dav` technology
to deliver sync functionality to millions of users”.

<figure>

  ![Fruux's logo](./fruux-logo.png)

  <figcaption>
  fruux's logo.
  </figcaption>
</figure>

Several things attract me at fruux:

1. low-layers are open-source,
2. viable ecosystem based on open-source,
3. accepts remote working,
4. close timezone to mine,
5. touching millions of people,
6. standards in minds.

The first point is the most important for me. I don't want to make a
company richer without any benefits for the rest of the world. I want my
work to be beneficial to the rest of the world, to share my work, I want
my work to be reused, hacked, criticized, updated and shared again. This
is the spirit of the open-source and the hackability paradigms. And
fortunately for me, fruux's low-layers are 100% open-source, namely
`sabre/dav` & co.

However, being able to eat at the end of the month with open-source is
not that simple. Fortunately for me, fruux has a stable economic model,
based on open-source. Obviously, I have to work on closed projects,
obviously, I have to work for some specific customers, but I can go back
to open-source goodnesses all the time 😉.

In addition, I am currently living in Switzerland and fruux is located
in Germany. Fortunately for me, fruux's team is kind of dispatched all
around Europe and the world. Naturally, they accept me to work remotely.
Whilst it can be inconvenient for some people, I enjoy to have my own
hours, to organize myself as I would like etc. Periodical meetings and
phone-calls help to stay focused. And I like to think that people are
more productive this way. After 4 years at home because of my Master
thesis and PhD thesis, I know how to organize myself and exchange with a
decentralized team. This is a big advantage. Moreover, Germany is in the
same timezone as Switzerland! Compared to companies located at, for
instance, California, this is simpler for my family.

Finally, working on an open-source project that is used by millions of
users is very motivating. You know that your contributions will touch a
lot of people and it gives meaning to my work on a daily basis. Also,
the last thing I love at fruux is this desire to respect standards, RFC,
recommandations etc. They are involved in these processes, consortiums
and groups (for instance
[CalConnect](http://calconnect.org/mbrlist.shtml)). I love standards and
specifications, and this methodology reminds me the scientific approach
I had with my PhD thesis. I consider that a standard without an
implementation has no meaning, and a well-designed standard is a piece
of a delicious cake, especially when everyone respects this standard 😄.

(… but the cake is a lie!)

## `sabre/*`

fruux has mostly hired me because of my experience on
[Hoa](http://hoa-project.net/). One of my main public job is to work on
all the `sabre/*` libraries, which include:

- [`sabre/dav`](https://github.com/fruux/sabre-dav),
- [`sabre/davclient`](https://github.com/fruux/sabre-davclient),
- [`sabre/event`](https://github.com/fruux/sabre-event),
- [`sabre/http`](https://github.com/fruux/sabre-http),
- [`sabre/proxy`](https://github.com/fruux/sabre-proxy),
- [`sabre/tzserver`](https://github.com/fruux/sabre-tzserver),
- [`sabre/vobject`](https://github.com/fruux/sabre-vobject),
- [`sabre/xml`](https://github.com/fruux/sabre-xml).

You will find the documentations and the news on
[sabre.io](http://sabre.io/).

All these libraries serve the first one: `sabre/dav`, which is an
implementation of the WebDAV technology, including extensions for
CalDAV, and CardDAV, respectively for calendars, tasks and address
books. For the one who does not know what is WebDAV, in few words: The
Web is mostly a read-only media, but WebDAV extends HTTP in order to be
able to write and collaborate on documents. The way WebDAV is defined is
fascinating, and even more, the way it can be extended.

Most of the work is already done by [Evert](http://evertpot.com/) and
many contributors, but we can go deeper! More extensions, more
standards, better code, better algorithms etc.!

If you are interested in the work I am doing on `sabre/*`, you can
check this [search result on
Github](https://github.com/search?q=user%3Afruux+author%3Ahywan&type=Issues).

## Future of Hoa

Certain people have asked me about the future of Hoa: Whether I am going
to stop it or not since I have a job now.

Firstly, a PhD thesis is exhausting, and believe me, it requires more
energy than a regular job, even if you are passionate about your job and
you did not count working hours. With a PhD thesis, you have no weekend,
no holidays, you are always out of time, you always have a ton (sic) of
articles and documents to read… there is no break, no end. In these
conditions, I was able to maintain Hoa and to grow the project though,
thanks to a very helpful and present community!

Secondly, fruux is planning to use Hoa. I don't know how or when, but if
at a certain moment, using Hoa makes sense, they will. What does it
imply for Hoa and me? It means that I will be paid to work on Hoa at a
little percentage. I don't know how much, it will depend of the moments,
but this is a big step forward for the project. Moreover, a project like
fruux using Hoa is a big chance! I hope to see the fruux's logo very
soon on the homepage of the Hoa's website.

Thus, to conclude, I will have more time (on evenings, weekends,
holidays and sometimes during the day) to work on Hoa. Do not be afraid,
the future is bright 😄.

## Conclusion

_Bref_, I am working at fruux!
