+++
title = "Trace Rust with static probes"
sort_by = "date"
template = "series-overview.html"
page_template = "series-episode.html"
weight = 2
[extra]
complete = false
+++

[Userland Statically Defined Tracing][USDT] probes (USDT for short) is a
technique inherited from [DTrace]. It allows user to define statically tracing
probes in their own applicaiton; while they are traditionally declared in the
kernel.

USDT probes can be naturally consumed with DTrace, but also with [eBPF] (`bcc,
`bpftrace`…).

In this series, we will explore how to compile and to use such user-defined
probes in a Rust application with a nice idiomatic API around them.


[USDT]: https://illumos.org/books/dtrace/chp-usdt.html
[DTrace]: https://en.wikipedia.org/wiki/DTrace
[eBPF]: http://www.brendangregg.com/blog/2019-01-01/learn-ebpf-tracing.html
