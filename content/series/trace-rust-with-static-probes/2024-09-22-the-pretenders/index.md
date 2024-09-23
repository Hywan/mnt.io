+++
title = "The pretenders"
date = "2024-09-22"
description = "…"
[taxonomies]
keywords=["rust", "tracing", "probe"]
+++

We are all excellent developers. We know exactly what our programs do, when,
and in which order. No need to check what happens. No error can happen. There
are always performant. We control all our dependencies and we know exactly what
they do on all platforms our programs run. Testing is for the weaks. Tracing
is wavering.

Is it true? Absolutely not. These are blatant lies. Let's face it: programming
is difficult. Inspecting and analysing what a program does is part of our job,
be either to track a bug (because… <i>taking a reassuring voice</i>, yes, we do
create bugs — sometimes), or to track a performance issue, or to keep track of
what our program does on the long-term.

Now, close your eyes and imagine an HTTP server. Such a server will listen to
requests, and will reply with responses. You have developed this server, and
it now runs in production. Time to chill, to take a delicate cup of tea, and to
listen to birds, singing for the sun. When suddenly, someone, somewhere, reports
frantically the server is _slow_. Oh yeah, this someone has dared to say it,
The Word has been pronounced. And now, it's your responsibility to demonstrate
that they are wrong!

How do you do that? Let's think:

* Run a benchmark to send a bunch of HTTP requests. Hmm, maybe it's not a good
  idea, since the server runs in production. Which kind of requests can we
  generate to not impact the database with dirty data? Is it possible to clean
  the data afterwards? We need a test account. Does it reflect the reality of
  the other users though?

  Would it be even useful? We may notice that some requests may be slow but,
  if that's the case, we would not know where the problem is. We will just
  have a general metrics for the entire roundtrip, without any kind of internal
  details.

* Update the code to insert instructions that will print timings at various
  points in our program. Okay, that's an idea, that's a Mesolithic approach, but
  it can work.

  However, where do we print these metrics? In `/dev/stdout`, ohh, no, that
  would be a bad idea. In a database then? That may slow things greatly.

  Here is an idea: let's not record these metrics when we are not interested by
  them. We want to enable the recording on-demand in this case.

* That's for the metrics, but we probably need more. Can we “inspect” our
  program, to see where it flows, with which data etc. This is not only about
  metrics, but about understanding how it runs.

Enter tracing.

## Tracing framework

Honestly, [Wikipedia][Wikipedia/tracing] brings a good definition of tracing:

> Tracing in software engineering refers to the process of capturing and
> recording information about the execution of a software program. This
> information is typically used by programmers for debugging purposes, and
> additionally, depending on the type and detail of information contained in a
> trace log, by experienced system administrators or technical-support personnel
> and by software monitoring tools to diagnose common problems with software.

This is a really vast topic with many technologies. It's nice to explore
them. For this series, I want to focus on a technology that provides low-level,
low CPU overhead, static tracing, namely [DTrace].

DTrace has originally been developed by [Solaris] and its descendant [illumos],
and since then, been ported to Unix-like systems and more. It runs on Linux,
macOS, FreeBSD, NetBSD and even [Windows][dtrace_on_windows]! It looks like a
good tool to have on your belt: we can reuse our knowledge on various platforms.
Nice.

DTrace comes with its own language, called D, to write tracing programs. The D
language is similar to C or [`awk`] and is event based. It consists of a list of
_probes_, with an action associated to each of them. Such D program is compiled
into a static object file (a `.o` on Unix-like systems), which is linked to the
program we want to probe. We will play with that in a moment.

DTrace provides lightweight probes by design. Once compiled, probes are defined
in a section of [the executable file format] (ELF on Unix-like, Mach-O on
Darwin, PE on Windows…). Taking the case of ELF, a probe is translated into
[a `nop` instruction][`nop`], and its metadata are stored in the ELF's `.note.stapstd`
section. When registering a probe, DTrace tool (like `dtrace`, `bcc`, `bpftrace`
etc.) will read the ELF section, and instrument the instruction from `nop`
to [`breakpoint`], and after that, the attached tracing event is run. After
deregistering the probe, the DTrace tool will restore the `nop` instruction
from `breakpoint`.

Thus, the overhead of using such probes is almost zero when no tool is listening
the probes, otherwise a tiny overhead can be noticed.

## Play with `dtrace`

## Probe a Rust program


[Wikipedia/tracing]: https://en.wikipedia.org/wiki/Tracing_(software)
[DTrace]: https://dtrace.org/
[Solaris]: https://www.oracle.com/solaris/solaris11/
[illumos]: https://illumos.org/
[dtrace_on_windows]: https://github.com/microsoft/DTrace-on-Windows
[`awk`]: https://en.wikipedia.org/wiki/AWK
[the executable file format]: https://en.wikipedia.org/wiki/Comparison_of_executable_file_formats
[`nop`]: https://en.wikipedia.org/wiki/NOP_(code)
[`breakpoint`]: https://en.wikipedia.org/wiki/Breakpoint
