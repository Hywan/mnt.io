+++
title = "`flaque`, a fast, concurrent, lock-free, and sometimes wait-free, pool implementation"
date = "2026-11-13"
description = "…"
[taxonomies]
keywords = ["data structure", "rust", "non-blocking"]
[extra]
table_of_contents = true
pinned = true
+++

It's been a couple of months that, on my hobby time, I got
<del>obsessed</del><ins>passionate</ins> by concurrent, non-blocking data
structures. To learn and experiment, I picked a project I had in mind for a
moment: a pool implementation. Today, I'm thrilled to announce I'm releasing
[`flaque`], a fast, concurrent, lock-free, and sometimes wait-free, pool
implementation.

---

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

In the past, we tried to address some `deadpool`'s performance
issues[^matrix-rust-sdk#pr/5841] or bugs[^deadpool#pr/461][^deadpool#pr/453],
but we knew other problems were still present, which was one of the motivations
for me to work on `flaque`. Anyway, it's time to benchmark `flaque` vs.
`deadpool`.

The most common case is to get an object and wait on one to be available, i.e.
running the following code:

```rust
let object = pool.get().await;
```

I tried to vary the pool size, the number of readers, and the number of threads.
For each point in this matrix, the benchmarks compute the time in milliseconds,
and the throughput in millions of objects per second. The results fall in
3 scenarios:

1. **No contention** — a pool of size 16, with 8 readers: there are ½ readers
   per object. There is absolutely no contention in this scenario.
2. **High contention on a small pool** — a pool of size 16, with 2048 readers:
   there are 128 readers per object. We have a contention on this pool, and the
   pool is rather small.
3. **High contention on a large pool** — a pool of size 512, with 1 million
   readers: there are 2048 readers per object. We still have contention on this
   pool, and the pool is large. It's interesting to measure how the pool
   supports pressure depending of its size.

Now, the big table.

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
    <th scope="col" rowspan="2">Library</th>
    <th scope="col" colspan="6">Time (ms)</th>
    <th scope="col" colspan="6">Throughput (M<sub>object</sub>/sec)</th>
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
each scenario, a _speed up_ row is added to compare `flaque` and `deadpool`.

On average, `flaque` is 1.7 times faster than `deadpool`. It is between 1.9 to
2.1 times faster in 30% of cases. **`flaque` is always faster than `deadpool`**.
At worse, it is only 1.4 times faster.

</figcaption>

</figure>

I was extremely surprised and happy by these results! When improving
performance, winning 1% is already huge, but 200%… is… unhoped-for!

## Presentation

`flaque` is a pool implementation. The pool has a size —derived from
a _desired_ size— that is, internally, a power of 2 with a minimum of 8
(<math><msup><mn>2</mn><mn>3</mn></msup></math>). The pool also creates objects
lazily, on-demand, with an _object builder_. It can either be synchronous or
asynchronous, and either fallible (i.e. it can return a `Result`) or infallible.
It is reflected with the constructor:

- `new(desired_size: usize, builder: B)` where `B: SyncBuilder<T>` builds a pool
  with a synchronous object builder,
- `new_with_async_builder(desired_size: usize, builder B)` where
  `B: AsyncBuilder<T>` builds a pool with an asynchronous object builder.

To know the final size computed by the pool, one can use the `size` method.

Here is how it looks:

```rust
use flaque::Pool;

// Let's define what the pool will hold.
struct T {
    x: u8,
}

impl Default for T {
    fn default() -> Self {
        Self { x: 0 }
    }
}

// Let's create the pool: capacity of 8, and the synchronous infallible
// builder for the `T`s.
let pool = Pool::new(8, T::default);
```

Then come the classical operations: `try_get` and `get`!

```rust
// Let's try to get an object from the pool.
let Some(object_0) = pool.try_get() else { /* … */ };

// Or, let's wait to get an object from the pool.
let object_1 = pool.get().await;
```

`try_get` immediately returns `Ok(object)` if one is available in the pool,
`Err(_)` otherwise. It also errors if the pool is closed, or if the builder
fails to build an object. If the object builder is asynchronous, `try_get` is
asynchronous, but fetching the object is always immediate and synchronous: only
the “object building” phase is asynchronous, propagating to the whole method
signature..

`get` is different: it returns a future named `Get`, which resolves to
`Ok(object)` as soon as an object is available, `Err(_)` otherwise. Similarly to
`try_get`, it also errors if the pool is closed (before or after the method has
been called), or if the builder fails to build an object.

An object is wrapped in an `Object` type. It can either be freshly built from
the builder, or “recycled”, i.e. an object that has previously been acquired. An
object conserves its state.

It is important to note that an object **owns** the real value `T`. `Object`
`deref`s and `deref_mut`s to `T`. `Object` doesn't hold a reference to the real
value: it owns it.

When an object is dropped, it returns immediately inside the pool. I would like
to insist on this details: _immediately_. It is synchronous, and it is
immediately available to be fetched with `try_get`, pending `Get` or a new
`Get`.

### Closing and Reopening

Another important feature that kept my brain busy for a moment was
_closing the pool_. Once the pool is closed, it can be _reopened_. Thus, we have
two methods: `close` and `reopen`. Implementing them accurately in a
non-blocking way was a headache. The behaviour is the following:

- `close` returns a future named `Close`,
- `Close` resolves once all objects have returned in the pool.

The solution I came with is the following:

- `close` takes ownership of `self`
- the pool will be returned back once the future resolves.

This guarantees that it is impossible to reopen the pool, and to fetch new
objects, while waiting for all objects to return back in the pool.

Relying on the Rust borrow checker did help a lot to solve this delicate
problem. Let's see an example:

```rust
use std::thread::spawn;

// Create a pool.
let pool = Pool::new(8, T::default);

// Fetch 2 objects, and send them in threads for example.
let object_0 = pool.try_get().unwrap();
let object_1 = pool.try_get().unwrap();

spawn(move || {
    // Do something with `object_0`.
    let _ = object_0;
});

spawn(move || {
    // Do something with `object_1`.
    let _ = object_1;
});

// Close the pool and wait for all objects to return.
// Once done, we get `pool` back!
let pool = pool.close().await;
assert!(pool.is_closed());
```

Wh…

{% <comte> %}

Huh, this pattern is funny to read:

```rust
let pool = pool.close().await;
```

The signature of `Pool::close` is:

```rust
fn close(self) -> Close<T, B>
```

`Close` is a future that outputs… `Pool<T, B>`, exactly our pool!

```rust
impl<T, B> Future for Close<T, B> {
    type Output = Pool<T, B>;

    // …
}
```

Because `close` takes ownership of the pool, it's impossible for someone else to
use it. It's a great guarantee!

{% </comte> %}

<i>Taking a deep breath</i>, you interrupted me, _again_. I was about to
explain `close` in details, but I guess I should thank you.

Once `close` has resolved, one is free to call `reopen` to, well, reopen the
pool. That's pretty straightforward to use.

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
