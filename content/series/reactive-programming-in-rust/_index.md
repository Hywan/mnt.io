+++
title = "Reactive programming in Rust"
sort_by = "date"
template = "series-overview.html"
page_template = "series-episode.html"
weight = 1
[extra]
complete = false
+++

This series explores how to implement and to use efficient reactive programming
patterns in Rust. I've used this technique succesfully [at my
work](https://element.io) inside the [Matrix Rust
SDK](https://github.com/matrix-org/matrix-rust-sdk), where changes happen
on the Rust side and are propagated to subscribers, even if they sit in
foreign languages, like Swift or Kotlin. This is how Matrix Rust SDK powers
cross-platform applications, like Element X (the next-generation Element
client).
