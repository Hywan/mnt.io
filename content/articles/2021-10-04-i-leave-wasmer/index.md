+++
title = "I've loved Wasmer, I still love Wasmer"
date = "2021-10-04"
description = "I'm proud of what I've done at Wasmer, but the toxic working environment forces me to leave. Here is the story of a really successful and beautiful project with a chaotic management."
[taxonomies]
keywords=["webassembly", "runtime", "job"]
[extra]
pinned = true
+++

This article could also have been titled *How I failed to change
Wasmer*.

Today is my last day at [Wasmer](https://wasmer.io/). For those who
don't know this name, it has a twofold meaning: it's a [very popular
WebAssembly runtime](http://github.com/wasmerio/wasmer), as well as a
startup. I want to write about what I've been able to accomplish during
my time at Wasmer (a high overview, not a technical view), and what
_forces_ me to leave the company despite being one of its co-founder. I
reckon my testimony can help other people to avoid digging into the hell
I (and my colleagues) had to endure. I'm available for work, you can
contact me at [ivan@mnt.io](mailto:ivan@mnt.io),
[@mnt_io](https://twitter.com/mnt_io),
[ivan-enderlin](https://www.linkedin.com/in/ivan-enderlin/) (LinkedIn).

## From nothing to pure awesomeness

I've joined the Wasmer company at its early beginning, in March 2019.
The company was 3 months old. My initial role was to write and to
improve the runtime itself, and to create many embeddings, i.e. ways to
integrate the Wasmer runtime inside various technologies, so that
WebAssembly can run everywhere.

I can say with confidence that my work is a success. I've learned a lot,
and I've worked on so many different projects, technologies, hacked so
many things, collaborated with so many people, every action was led by
the **passion**.

At the time of writing, Wasmer has an incredible growth. In 2.5 years
only, the runtime has more than 10'500 stars on Github, and is **one of
the most popular WebAssembly runtime in the world**! It's used by many
various companies, such as [Confio](https://confio.tech/), [Fluence
Labs](https://fluence.network/), [HOT-G](https://hotg.dev/),
[Brave](https://brave.com/), [Google](https://google.com/),
[Apple](https://www.apple.com/), [SpaceMesh](https://spacemesh.io/),
[Linkerd](https://linkerd.io/),
[SingleStore](https://www.singlestore.com/),
[CleverCloud](https://www.clever-cloud.com/) or
[Kong](https://konghq.com/) to name a few (for the ones I can name
though, however other companies are also using Wasmer in very critical
environments).

Most of my engineering job happened on the Wasmer runtime itself. At the
time of writing, I'm the \#2 contributor on the project. I was working
on [every parts of the
runtime](https://github.com/wasmerio/wasmer/tree/f9ff574e10d4ee97f836565bdae99035e04ac879/lib):
the API, the C API, the compilers, the ABI (mostly WASI), the engines,
the middlewares, and the VM itself which is the most low-level
foundamental layer of the runtime.

The runtime provides so many features. It is an impressively powerful
runtime for WebAssembly, and I'm saying that with a neutral and
respectful mindset. Not everything is perfect obviously but I did my
best to set up a truly user-friendly learning environment, with an
important documentation and [a collection of
examples](https://github.com/wasmerio/wasmer/tree/f9ff574e10d4ee97f836565bdae99035e04ac879/examples)
that illustrate many features. I strongly believe it contributed to
Wasmer's popularity to great extent.

I would like to highlight the most notable embedding projects I've
created:

- [`wasmer-c-api`](https://github.com/wasmerio/wasmer/tree/master/lib/c-api) is
  the C embedding for Wasmer. It's part of the Wasmer runtime itself, and is
  fully written in Rust.
  [The documentation, the C
  examples](https://docs.rs/wasmer-c-api/*/wasmer_c_api/wasm_c_api/index.html),
  everything is super polished to offer the best experience possible. [Mark
  McCaskey](https://github.com/MarkMcCaskey) and I are the authors of this
  project.
- [`wasmer-python`](https://github.com/wasmerio/wasmer-python) is the Python
  embedding for Wasmer. At the time of writing, it's been installed more than 5
  millions times (I'm counting the compiler packages too, like
  `wasmer-compiler-cranelift` and so on), and 1300 stars on Github. There is
  about 300'000 downloads per months, and it continues to grow! The code is
  written in Rust, and it relies on
  [the awesome `pyo3` project](https://pyo3.rs/) .
- [`wasmer-go`](https://github.com/wasmerio/wasmer-go/) is the Go embedding for
  Wasmer. It's hard to know how much total downloads we have because of how the
  Go ecosystem is designed, but we have about 60'000 downloads per months from
  Github (I'm excluding the forks of the project), and 1600 stars on Github. The
  code is written in Go and uses [`cgo`](https://golang.org/cmd/cgo/) to bind
  against the C API. Almost all blockchain projects that use WebAssembly are
  using `wasmer-go`, which is a popularity I wasn't expecting.
- [`wasmer-ruby`](https://github.com/wasmerio/wasmer-ruby/) is the Ruby
  embedding for Wasmer. It's not as popular as the others, but it's also very
  polished and it's finding its place in the Ruby ecosystem. The code is written
  in Rust, and it relies on
  [the awesome `rutie` project](https://github.com/danielpclark/rutie) .
- I won't detail all the projects, but there is also
  [`wasmer-php`](https://github.com/wasmerio/wasmer-php),
  [`wasmer-java`](https://github.com/wasmerio/wasmer-java),
  [`wasmer-postgres`](https://github.com/wasmerio/wasmer-postgres)… Because of
  the Wasmer runtime API and C API we have designed, many developers around the
  globe have been able to create a lot more embeddings, such as in
  [C#](https://github.com/migueldeicaza/WasmerSharp),
  [D](https://github.com/chances/wasmer-d),
  [Elixir](https://github.com/tessi/wasmex),
  [R](https://github.com/dirkschumacher/wasmr),
  [Swift](https://github.com/AlwaysRightInstitute/SwiftyWasmer),
  [Zig](https://github.com/zigwasm/wasmer-zig),
  [Dart](https://github.com/dart-lang/wasm),
  [Lisp](https://github.com/helmutkian/cl-wasm-runtime) and so on.

Other fun notable projects are:

- [`sonde-rs`](https://github.com/wasmerio/sonde-rs), a library to
  compile USDT probes into a Rust library,
- [`llvm-custom-builds`](https://github.com/wasmerio/llvm-custom-builds), a
  sandbox to produce custom LLVM builds for various platforms,
- [`loupe`](https://github.com/wasmerio/loupe), a set of tools to
  analyse and to profile Rust code,
- [`wasmer-interface-types`](https://github.com/wasmerio/interface-types), a
  “living” (understand an unstable playground) library that implements
  [the WebAssembly Interface Types proposal](https://github.com/WebAssembly/interface-types),
- [`inline-c-rs`](https://github.com/Hywan/inline-c-rs/), to write and
  to execute C code inside Rust,
- in-memory filesystem, that acts exactly like `std::fs`.

As you might think, I've learned so much. The impostor syndrom was very
present because I was constantly trying to do something I didn't know.
It's part of the routine at Wasmer: Trying something for the first time.
But it's also what kept me motivated, and it was the energy for my
passion.

This list above shows released projects, but I've also experimented (and
sometimes at two hairs of a release) with:

- [Unikernels](https://en.wikipedia.org/wiki/Unikernel); this one was really
  fun, given a WebAssembly module and a filesystem, we were able to generate a
  unikernel that was executing the given program,
- Parser; to write the fastest WebAssembly parser possible, it was working, but
  never released,
- HAL (Hardware Abstraction Layer) ABI for WebAssembly, so that we can run
  WebAssembly on small chips super easily (think of IoT),
- Networking; an extension to WASI to support networking (TCP and UDP sockets),
  with an implementation in [Rust](https://www.rust-lang.org/),
  [libc](https://en.wikipedia.org/wiki/C_standard_library), and even
  [Zig](https://github.com/ziglang/zig/)! We were able to compile C programs to
  WebAssembly like cURL, or TCP servers written with kqueue or epoll etc, and to
  execute them on any platforms.

All those things were working.

It's absolutely crazy what WebAssembly can do today, and I still truly
and deeply believe in this technology. I'm not the only one:
[YCombinator](https://www.ycombinator.com/companies/wasmer) and
[SpeedInvest](https://medium.com/speedinvest/the-next-generation-of-cloud-computing-investing-in-wasmer-768c9aac5922)
are also founders that believe in Wasmer.

So. What a dream, huh?

## The toxic working environment

WebAssembly is _nothing_ without its community. I won't name people to
avoid missing important persons, but all the contributors are doing
amazing work, to create something new, something special, something
_right_.

Wasmer is a success. The Wasmer runtime is _nothing_ without the
incredible, marvelous, exceptional team of engineers behind it. In no
particular order: [Lachlan Sneff](https://github.com/lachlansneff),
[Mark McCaskey](https://github.com/MarkMcCaskey), [Julien
Bianchi](https://github.com/jubianchi), [Nick
Lewycky](https://github.com/nlewycky), [Heyang
Zhou](https://github.com/losfair), [Mackenzie
Clark](https://github.com/xmclark), [Brandon
Fish](https://github.com/bjfish). All of them, with no exception, have
put a lot of passion in this project. It is what it is today because of
them and also because of the contributors we have been honored to
welcome. The open source side of Wasmer was intense but also an
important source of joy. It is a respectful place to work.

However, the inside reality was very different. All the employees
hereinabove have left the company. Almost all of them due to a burn-out
or conflicts or strong disagreements with the company leadership. I am
leaving due to a severe burn-out. I would like to briefly share my
journey to my first burn-out in few points:

- I've started as an engineer. I love coding. I love hacking. In Wasmer, I've
  found a place to learn a lot and to express my passion. We had a lot of
  pressure mostly because our friendly “competitors” had more people dedicated
  to work on their runtimes, more money, more power, better marketing and so on.
  That's the inevitable burden of any startup. When you're competing against
  giants, that's what happens. And that's OK. It's part of the game.
- During that time, we were delivering more and more projects, more and more
  features, at an incredible pace. New hats: Release manager, project manager,
  more product ownership, more customers to be connected with, more contributors
  to help, more issues to triage, blog writer etc. The pace was accelerating too
  fast, something we did notice on multiple occasions.
- The CEO, [Syrus Akbary](https://github.com/syrusakbary), had evidently a lot
  of pressure on its shoulders. It sadly resulted in the worst possible way:
  micro-management, stress, pressure, bad leadership, lack of vision, lack of
  trust in the employees, changing the roadmap constantly, lies, secrets etc.
- As one of the older in the company, with a family of two kids, I probably got
  more “wisdom”. I've decided to create a safe place for employees to express
  their frustrations, their needs, to find solutions together. _De facto_, I
  became the “person of trust” in the company. I got new hats, new pressures.
- SARS-CoV-2 hit. School at home. Lock-down. More micro-management, more stress.
  Wasmer was running out of money. I brought a new investor that saved the
  company. New hat.
- After too many departures (85% of the engineering team!), I tried to take more
  space and to take more responsabilities in the company. That was at the
  beginning of 2021. It was my last attempt to save the company from a disaster
  before leaving. **I couldn't imagine leaving such brilliant and successful
  projects without having tried everything I could**.
- Then **I became a _late co-founder_ of Wasmer**. Too many new hats: Doing
  hiring interviews, accountabilities, helping to define the roadmap (with
  another awesome person, friend, and employee), handling legal aspects to hire
  people in multiple countries with non-precarious contracts etc.
- Obviously, I was also doing the job of all the engineers that have left. They
  were not replaced for unknown reasons. It was absolutely madness. The pace was
  unsustainable.
- Finally, the crack. The CEO continued to change the roadmap, to take bad
  decisions, to not recognize all the efforts we were doing to save/grow the
  company. It was my turn to be declared in a _severe_ burn-out by my physician.
  The last engineer to fall.

Another point: Syrus Akbary also has made many public errors that have
created hostility against the company. Hopefully people were smart and
kind enough to make the difference between the employees and the CEO (I
won't name the people but they will recognize themselves: Thank you). I
tried to fix that situation. Discussing with dozens of person to restore
empathy and forgiveness, to create better collaboration, to cure and
move forward. It was exhausting. I know people have appreciated what I
did, but my mental health was ruined.

Considering all the time I've devoted to the company, the very few
consideration I got in return, the countless work hours (4 days per
week, but frequently closing the computer at 1am due to very late
meetings, I was working like hell), the precarious contract I had (did
you ever see a co-founder with a freelancer contract?), the toxic
working environment, the constant pressure etc., my passion was intact
but my motivation was seriously damaged. Doing overtime was never
recorded and was happening more than frequently, but taking half a day
to take care of a sick child was immediately counted as holidays; the
balance was broken. Criticisms. Micro-management. Disapprovals.
Rewriting the facts and the reality to criticize what you're doing,
flipping things against you, avoiding discussion when things get stormy.
We even had a meeting titled “Why you're not productive enough” whilst
everyone was working as hell, right after the rewrite of the entire
runtime to release Wasmer 1.0, a period we all affectionately called
“The Rewrite of Hell”. The team deserved vacations, congratulations,
attentions, gratitude, … not such a shitty meeting. Well, you get the
idea.

When I've been declared in a severe burn-out, I had to take a break. The
reaction from the CEO was… unexpected: Zero empathy, asking to never
ever being sick again (otherwise I will be fired), dividing my equities,
asking me to work more, saying I've never been involved in the company
etc. That was the final straw to me. That's _the_ wrong way to treat an
employee, a collaborator, a contributor, the co-founder.

## What's next?

I need to recover. As you can imagine, working 2.5 years at this pace
leaves sequelae. Hopefully a couple of months should suffice.

I'm still in love with Wasmer, the **runtime**, the open source projects
we have created. It has a bright future. More companies are contributing
to it, more individual contributors are bringing their stones to the
monument. The project is owned by the public, by the users, by the
contributors, they are doing most of the work today. It's well tested,
well documented, it's easy to contribute to it. It's a fabulous open
source piece of technology.

I strongly _hope_ Wasmer, the **company**, will change. The products
that are going to be released are absolutely fantastic. It's a
technology shift. It will enable the Decentralized Web, how we compile
and distribute programs, how we will even consume programs. Wasmer has a
solid bright future. I really _hope_ things will change, and I wish the
best to and am passing on the torch to the adventurers that will
continue to move the company forward. I'm just too _skeptical_ that
things can improve or even slightly change. We have built something
great. Please take a great care of it.

As I said, I'm available for a new adventure, you can contact me at
[ivan@mnt.io](mailto:ivan@mnt.io),
[@mnt_io](https://twitter.com/mnt_io),
[ivan-enderlin](https://www.linkedin.com/in/ivan-enderlin/) (LinkedIn).

Discussions [on
Twitter](https://twitter.com/mnt_io/status/1445310721185783811) and [on
HackerNews](https://news.ycombinator.com/item?id=28772863).
