+++
title = "`flaque`, a fast, concurrent, lock-free, and sometimes wait-free, pool implementation"
date = "2026-11-13"
description = "…"
[taxonomies]
keywords=["data structure", "rust", "non-blocking"]
[extra]
pinned = true
+++

On my hobby time, it's been a couple of months I got
<del>obsessed</del><ins>passionate</ins> by concurrent, non-blocking data
structures. To learn and experiment, I picked a project I had in mind for
a moment: a pool implementation. Today, I'm releasing [`flaque`], a fast,
concurrent, lock-free, and sometimes wait-free, pool implementation.

A _pool_ is a limited-sized collection of _objects_. When an object is fetched
from the pool, the number of available objects decreases by one. Once the
fetched object is dropped, it goes back into the _pool_, and the number of
available objects increases by one. Being as fast as possible, avoiding
blocking, and, whenever possible, avoiding waiting, is the name of the game.

This project sounded “simple” enough to be a nice playground. How credulous was
I. To start off, with the hope of tempting your attention, let's talk about
results.

## Results

When talking about pool implementation in Rust, the elephant in the room
is [`deadpool`], perhaps the most popular library[^deadpool-downloads].
Coincidentally, the [Matrix Rust SDK uses it][matrix-rust-sdk-uses-deadpool].
Coincidentally again, I'm being sponsored by [Element] to work on the [Matrix
Rust SDK][`matrix-rust-sdk`]. Not only I can write benchmarks for `flaque`
itself, but I can have real life results on real applications used by millions
of users (from Element X, Fractal and others) on various devices!

In the past, we tried to improve some of the `deadpool`'s performance
issues[^matrix-rust-sdk#pr/5841] or bugs[^deadpool#pr/461][^deadpool#pr/453],
but we knew other problems were still present. Anyway, it's time to benchmark
`flaque` against `deadpool`.

The most common case is to get an object and wait on one to be available, i.e.
running this `pool.get().await`. I tried to vary the pool size, the number of
readers, and the number of threads. For each point in this matrix, the
benchmarks compute the time in milliseconds, and the throughput in millions
of objects per second. The results fall in 3 scenarios:

1. **No contention** — a pool of size 16, with 8 readers: there are ½ readers
   per object. There is absolutely no contention in this scenario.
2. **High contention on a small pool** — a pool of size 16, with 2048 readers:
   there are 128 readers per object. We have a contention on this pool, and the
   pool is rather small.
3. **High contention on a large pool** — a pool of size 512, with 1 million
   readers: there are 2048 readers per object. We still have contention on this
   pool, and the pool is large. It's interesting to measure how the pool
   supports pressure depending of its size.

<figure>

<div class="overflow-inline">
<table>
<thead>
  <tr>
    <th colspan="3"></th>
    <th scope="col" colspan="12">Number of threads</th>
  </tr>
  <tr>
    <th scope="col" rowspan="2">Pool size</th>
    <th scope="col" rowspan="2">Number of readers</th>
    <th scope="row" rowspan="2">Library</th>
    <th scope="row" colspan="6">Time (ms)</th>
    <th scope="row" colspan="6">Throughput (M<sub>object</sub>/sec)</th>
  </tr>
  <tr>
    <th scope="col">1</th>
    <th scope="col">2</th>
    <th scope="col">4</th>
    <th scope="col">8</th>
    <th scope="col">16</th>
    <th scope="col">32</th>
    <th scope="col">1</th>
    <th scope="col">2</th>
    <th scope="col">4</th>
    <th scope="col">8</th>
    <th scope="col">16</th>
    <th scope="col">32</th>
  </tr>
</thead>
<tbody>
  <tr>
    <th scope="row" rowspan="3">16 (small)</th>
    <th scope="row" rowspan="3">
      8
      (2<sup>3</sup>)
    </th>
    <th scope="row"><code>flaque</code></th>
    <td>0.00203</td>
    <td>0.00205</td>
    <td>0.00215</td>
    <td>0.00227</td>
    <td></td>
    <td></td>
    <td>3.94</td>
    <td>3.90</td>
    <td>3.72</td>
    <td>3.52</td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <th scope="row"><code>deadpool</code></th>
    <td>0.00286</td>
    <td>0.00292</td>
    <td>0.00322</td>
    <td>0.00333</td>
    <td></td>
    <td></td>
    <td>2.79</td>
    <td>2.74</td>
    <td>2.48</td>
    <td>2.41</td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <th scope="row"><em>speed up</em></th>
    <td><em>1.4</em></td>
    <td><em>1.4</em></td>
    <td><em>1.5</em></td>
    <td><em>1.5</em></td>
    <td></td>
    <td></td>
    <td><em>1.4</em></td>
    <td><em>1.4</em></td>
    <td><em>1.5</em></td>
    <td><em>1.5</em></td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <th scope="row" rowspan="3">16 (small)</th>
    <th scope="row" rowspan="3">
      2048
      (2<sup>11</sup>)
    </th>
    <th scope="row"><code>flaque</code></th>
    <td>0.81</td>
    <td>0.93</td>
    <td>1.27</td>
    <td>2.45</td>
    <td>2.73</td>
    <td>2.99</td>
    <td>5.08</td>
    <td>4.41</td>
    <td>3.21</td>
    <td>1.67</td>
    <td>1.50</td>
    <td>1.37</td>
  </tr>
  <tr>
    <th scope="row"><code>deadpool</code></th>
    <td>1.51</td>
    <td>1.82</td>
    <td>2.13</td>
    <td>3.31</td>
    <td>5.41</td>
    <td>6.40</td>
    <td>2.71</td>
    <td>2.25</td>
    <td>1.92</td>
    <td>1.23</td>
    <td>0.76</td>
    <td>0.640</td>
  </tr>
  <tr>
    <th scope="row"><em>speed up</em></th>
    <td><em>1.9</em></td>
    <td><em>2.0</em></td>
    <td><em>1.7</em></td>
    <td><em>1.4</em></td>
    <td><em>2.0</em></td>
    <td><em>2.1</em></td>
    <td><em>1.9</em></td>
    <td><em>2.0</em></td>
    <td><em>1.7</em></td>
    <td><em>1.4</em></td>
    <td><em>2.0</em></td>
    <td><em>2.1</em></td>
  </tr>
  <tr>
    <th scope="row" rowspan="3">512 (large)</th>
    <th scope="row" rowspan="3">
      1'048'576
      (2<sup>20</sup>)
    </th>
    <th scope="row"><code>flaque</code></th>
    <td>363.35</td>
    <td>420.59</td>
    <td>588.70</td>
    <td>971.94</td>
    <td>976.48</td>
    <td>1035.40</td>
    <td>5.77</td>
    <td>4.99</td>
    <td>3.56</td>
    <td>2.16</td>
    <td>2.15</td>
    <td>2.03</td>
  </tr>
  <tr>
    <th scope="row"><code>deadpool</code></th>
    <td>705.07</td>
    <td>850.66</td>
    <td>1030.10</td>
    <td>1571.30</td>
    <td>1661.30</td>
    <td>1684.20</td>
    <td>2.97</td>
    <td>2.47</td>
    <td>2.04</td>
    <td>1.33</td>
    <td>1.26</td>
    <td>1.25</td>
  </tr>
  <tr>
    <th scope="row"><em>speed up</em></th>
    <td><em>1.9</em></td>
    <td><em>2.0</em></td>
    <td><em>1.7</em></td>
    <td><em>1.6</em></td>
    <td><em>1.7</em></td>
    <td><em>1.6</em></td>
    <td><em>1.9</em></td>
    <td><em>2.0</em></td>
    <td><em>1.7</em></td>
    <td><em>1.6</em></td>
    <td><em>1.7</em></td>
    <td><em>1.6</em></td>
  </tr>
</tbody>
</table>
</div>

<figcaption>

_Pool size_ and _Number of readers_ define the 3 scenarios: _No contention_,
_High contention on a small pool_, and _High contention on a large pool_. For
each scenario, a _speed up_ rows is added to compare `flaque` and `deadpool`.

On average, `flaque` is 1.7 times faster than `deadpool`. It is between 1.9 to
2.1 times faster in 30% of cases.

</figcaption>

</figure>

I was extremely happy by these results! When improving performance, winning 1%
is already huge, but 200%… is… unhoped-for!

## Presentation

## How Does It Really Work?

## How to Test It?

[`flaque`]: https://codeberg.org/hywan/flaque
[`deadpool`]: https://github.com/deadpool-rs/deadpool/
[`matrix-rust-sdk`]: https://github.com/matrix-org/matrix-rust-sdk
[matrix-rust-sdk-uses-deadpool]: https://github.com/matrix-org/matrix-rust-sdk/blob/b93751e93da6949a6a8ef8ff0b2c1c8faacaf677/crates/matrix-sdk-sqlite/Cargo.toml#L35-L36
[Element]: https://element.io

[^deadpool-downloads]: [The project counts 173 million of
    downloads](https://crates.io/crates/deadpool) at the time of writing.
[^matrix-rust-sdk#pr/5841]: <cite>Replace `deadpool-sqlite` by our own
    implementation
    ([`matrix-rust-sdk` PR #5841](https://github.com/matrix-org/matrix-rust-sdk/pull/5841))</cite>.
[^deadpool#pr/461]: <cite>Fix a panic when calling `JoinError::into_panic`
    ([`deadpool` PR #461](https://github.com/deadpool-rs/deadpool/pull/461))</cite>.
[^deadpool#pr/453]: <cite>Remove a double call to `PoolBuilder::runtime`
    ([`deadpool` PR #453](https://github.com/deadpool-rs/deadpool/pull/453))</cite>.
