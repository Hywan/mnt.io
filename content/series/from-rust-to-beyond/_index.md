+++
title = "From Rust to beyond"
sort_by = "date"
template = "series-overview.html"
page_template = "series-episode.html"
weight = 0
[extra]
complete = true
+++

[At my work](https://automattic.com/), I had an opportunity to start an
experiment: Writing a single parser implementation in Rust for [the new
Gutenberg blogpost format](https://github.com/WordPress/gutenberg), and use
it on many platforms and environments, like JavaScript (via WebAssembly and
ASM.js), C, PHP… An existing stack using PEG.js and PEG.php was used, but it was
quickly showing its limitations: slow to parse, consuming too much memory… Let's
see how Rust compares the current solution, and let's learn how to use Rust in
all these environments!
