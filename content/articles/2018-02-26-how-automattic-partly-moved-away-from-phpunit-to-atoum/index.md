+++
title = "How Automattic (WordPress.com & co.) partly moved away from PHPUnit to atoum?"
date = "2018-02-26"
[taxonomies]
keywords=["test", "php", "atoum"]
+++

Hello fellow developers and testers,

Few months ago at [Automattic](https://automattic.com/), my team and I
started a new project: **Having better tests for the payment system**.
The payment system is used by all the services at Automattic, i.e.
[WordPress](https://wordpress.com/),
[VaultPress](https://vaultpress.com/), [Jetpack](https://jetpack.com/),
[Akismet](http://akismet.com/), [PollDaddy](http://polldaddy.com/) etc.
It's a big challenge! Cherry on the cake: Our experiment could define
the future of the testing practices for the entire company. No pressure.

This post is a summary about what have been accomplished so far, the
achievements, the failures, and the future, focused around manual tests.
As the title of this post suggests, we are going to talk about
[PHPUnit](https://phpunit.de/) and [atoum](http://atoum.org/), which are
two PHP test frameworks. This is not a PHPUnit vs. atoum fight. These
are observations made for our software, in our context, with our
requirements, and our expectations. I think the discussion can be useful
for many projects outside Automattic. I would like to apologize in
advance if some parts sound too abstract, I hope you understand I can't
reveal any details about the payment system for obvious reasons.

## Where we were, and where to go

For historical reasons, WordPress, VaultPress, Jetpack & siblings use
[PHPUnit](https://phpunit.de/) for server-side manual tests. There are
unit, integration, and system manual tests. There are also end-to-end
tests or benchmarks, but we are not interested in them now. When those
products were built, PHPUnit was the main test framework in town. Since
then, the test landscape has considerably changed in PHP. New
competitors, like [atoum](http://atoum.org/) or
[Behat](http://behat.org/), have a good position in the game.

Those tests exist for many years. Some of them grew organically. PHPUnit
does not require any form of structure, which is —despite being
questionable according to me— a reason for its success. It is a
requirement that the code does not need to be well-designed to be
tested, *but* too much freedom on the test side comes with a cost in the
long term if there is not enough attention.

**Our situation is the following**. The code is complex for justified
reasons, and the *testability* is sometimes lessened. Testing across
many services is indubitably difficult. Some parts of the code are
really old, mixed with others that are new, shiny, and well-done. In
this context, it is really difficult to change something, especially
moving to another test framework. The amount of work it represents is
colossal. Any new test framework does not worth the price for this huge
refactoring. But maybe the new test frameworks can help us to better
test our code?

I'm a [long term contributor of
atoum](https://github.com/atoum/atoum/graphs/contributors) (top 3
contributors). And at the time of writing, I'm a core member. You have
to believe me when I say that, at each step of the discussions or the
processes, I have been neutral, arguing in favor or against atoum. The
idea to switch to atoum partly came from me actually, but my knowledge
about atoum is definitively a plus. I am in a good position to know the
pros and the cons of the tool, and I'm perfectly aware of how it could
solve issues we have.

So after many debates and discussions, we decided to *try* to move to
atoum. A survey and a meeting were scheduled 2 months later to decide
whether we should continue or not. Spoiler: We will partly continue with
it.

## Our needs and requirements

Our code is difficult to test. In other words, the testability is low
for some parts of the code. atoum has features to help increase the
testability. I will try to summarize those features in the following
short sections.

### `atoum/phpunit-extension`

As I said, it's not possible to rewrite/migrate all the existing tests.
This is a colossal effort with a non-neglieable cost. Then, enter
[`atoum/phpunit-extension`](https://github.com/atoum/phpunit-extension).

As far as I know, atoum is the only PHP framework that is able to run
tests that have been written for another framework. The
`atoum/phpunit-extension` does exactly that. It runs tests written with
the PHPUnit API with the atoum engines. This is *fabulous*! PHPUnit is
not required at all. With this extension, we have been able to run our
“legacy” (aka PHPUnit) tests with atoum. The following scenarios can be
fulfilled:

- Existing test suites written with the PHPUnit API can be run
  seamlessly by atoum, no need to rewrite them,
- Of course, new test suites are written with the atoum API,
- In case of a test suite migration from PHPUnit to atoum, there are two
  solutions:
  1.  Rewrite the test suite entirely from scratch by logically using
      the atoum API, or
  2.  Only change the parent class from `PHPUnit\Framework\TestCase` to
      `atoum\phpunit\test`, and suddenly it is possible to use both API
      at the same time (and thus migrate one test case after the other
      for instance).

This is a very valuable tool for an adventure like ours.

`atoum/phpunit-extension` is not perfect though. Some PHPUnit APIs are
missing. And while the test verdict is strictly the same, error messages
can be different, some PHPUnit extensions may not work properly etc.
Fortunately, our usage of PHPUnit is pretty raw: No extensions except
home-made ones, few hacks… Everything went well. We also have been able
to contribute easily to the extension.

### Mock engines (plural)

atoum comes with [3 mock
engines](http://docs.atoum.org/en/latest/mocking_systems.html):

- Class-like mock engine for classes and interfaces,
- Function mock engine,
- Constant mock engine.

Being able to mock global functions or global constants is an important
feature for us. It suddenly increases the testability of our code! The
following example is fictional, but it's a good illustration. WordPress
is full of global functions, but it is possible to mock them with atoum
like this:

```php
public function test_foo() {
    $this->function->get_userdata = (object) [
        'user_login' => …,
        'user_pass' => …,
        …
    ];
}
```

In one line of code, it was possible to mock the
[`get_userdata`](https://codex.wordpress.org/Function_Reference/get_userdata)
function.

### Runner engines

Being able to isolate test execution is a necessity to avoid flakey
tests, and to increase the trust we put in the test verdicts. atoum
comes with *de facto* 3 runner engines:

- *Inline*, one test case after another in the same process,
- *Isolate*, one test case after another but each time in a new process
  (full isolation),
- *Concurrent*, like *isolate* but tests run concurrently (“at the same
  time”).

I'm not saying PHPUnit doesn't have those features. It is possible to
run tests in a different process each time —with the *isolate* engine—,
but test execution time blows up, and the isolation is not strict. We
don't use it. The *concurrent* runner engine in atoum tends to reduce
the execution time to be close to the *inline* engine, while still
ensuring a strict isolation.

Fun fact: By using atoum and the `atoum/phpunit-extension`, we are able
to run PHPUnit tests concurrently with a strict isolation!

### Code coverage reports

At the time of writing, PHPUnit is not able to generate code coverage
reports containing the Branch- or Path Coverage Criteria data. atoum
supports them natively with the
[`atoum/reports-extension`](https://github.com/atoum/reports-extension)
(including nice graphs, see [the
demonstration](http://atoum.org/reports-extension/)). And we need those
data.

## The difficulties

On paper, most of the pain points sound addressable. It was time to
experiment.

### Integration to the Continuous Integration server

Our CI does not natively support standard test execution report formats.
Thus we had to create the
[`atoum/teamcity-extension`](https://github.com/Hywan/atoum-teamcity-extension/).
[Learn more](@/articles/2017-11-06-atoum-supports-teamcity/index.md) by
reading a blog post I wrote recently. The TeamCity support is native
inside PHPUnit (see the [`--log-teamcity`
option](http://phpunit.readthedocs.io/en/latest/textui.html?highlight=--log-teamcity)).

### Bootstrap test environments

Our bootstrap files are… challenging. It's expected though. Setting up a
functional test environment for a software like WordPress.com is not a
task one can accomplish in 2 minutes. Fortunately, we have been able to
re-use most of the PHPUnit parts.

Today, our unit tests run in complete isolation and concurrently. Our
integration tests, and system tests run in complete isolation but not
concurrently, due to MySQL limitations. We have solutions, but time
needs to be invested.

Generally, even if it works now, it took time to re-organize the
bootstrap so that some parts can be shared between the test runners
(because we didn't switch the whole company to atoum yet, it was an
experiment).

### Documentation and help

Here is an interesting paradox. The majority of the team recognized that
atoum's documentation is better than PHPUnit's, even if some parts must
be rewritten or reworked. *But* developers already know PHPUnit, so they
don't look at the documentation. If they have to, they will instead find
their answers on StackOverflow, or by talking to someone else in the
company, but not by checking the official documentation. atoum does not
have many StackOverflow threads, and few people are atoum users within
the company.

What we have also observed is that when people create a new test, it's a
copy-paste from an existing one. Let's admit this is a common and
natural practice. When a difficulty is met, it's legit to look at
somewhere else in the test repository to check if a similar situation
has been resolved. In our context, that information lacked a little bit.
We tried to write more and more tests, but not fast enough. It should
not be an issue if you have time to try, but in our context, we
unfortunately didn't have this time. The team faced many challenges in
the same period, and the tests we are building are not simple \_Hello,
World!\_s as you might think, so it increases the effort.

To be honest, this was not the biggest difficulty, but still, it is
important to notice.

### Concurrent integration test executions

Due to some MySQL limitations combined with the complexity of our code,
we are not able to run integration (and system) tests concurrently yet.
Therefore it takes time to run them, probably too much in our
development environments. Even if atoum has friendly options to reduce
the debug loop (e.g. see [the `--loop`
option](http://docs.atoum.org/en/latest/mode-loop.html)), the execution
is still slow. The problem can be solved but it requires time, and deep
modifications of our code.

Note that with our PHPUnit tests, no isolation is used. This is wrong.
And thus we have a smaller trust in the test verdict than with atoum.
Almost everyone in the team prefers to have slow test execution but
isolation, rather than fast test execution but no confidence in the test
verdict. So that's partly a difficulty. It's a mix of a positive feature
and a needle in the foot, and a needle we can live with. atoum is not
responsible of this latency: The state of our code is.

## The results

First, let's start by the positive impacts:

- In 2 months, we have observed that the testability of our code has
  been increased by using atoum,
- We have been able to find bugs in our code that were not detected by
  PHPUnit, mostly because atoum checks the type of the data,
- We have been able to migrate “legacy tests” (aka PHPUnit tests) to
  atoum by just moving the files from one directory to another: What a
  smooth migration!
- The *trust* we put in our test verdict has increased thanks to a
  strict test execution isolation.

Now, the negative impacts:

- Even if the testability has been increased, it's not enough. Right
  now, we are looking at refactoring our code. Introducing atoum right
  now was probably too early. Let's refactor first, then use a better
  test toolchain later when things will be cleaner,
- Moving the whole company at once is hard. There are thousands of
  manual tests. The `atoum/phpunit-extension` is not magical. We have to
  come with more solid results, stuff to blow minds. It is necessary to
  set the institutional inertia in motion. For instance, not being able
  to run integration and system tests concurrently slows down the builds
  on the CI; it increases the trust we put in the test verdict, but this
  latency is not acceptable at the company scale,
- All the issues we faced can be addressed, but it needs time. The
  experiment time frame was 2 months. We need 1 or 2 other months to
  solve the majority of the remaining issues. Note that I was kind of
  in-charge of this project, but not full time.

We stop using atoum for *manual tests*. It's likely to be a pause
though. The experiment has shown we need to refactor and clean our code,
then there will be a good chance for atoum to come back. The experiment
has also shown how to increase the testability of our code: Not
everything can be addressed by using another test framework even if it
largely participates. We can focus on those points specifically, because
we know where they are now. Finally, I reckon it has participated in
moving the test infrastructure inside Automattic by showing that
something else exists, and that we can go further.

I said we stopped using atoum “for manual tests”. Yes. Because we also
have *automatically generated tests*. The experiment was not only about
switching to atoum. Many other aspects of the experiment are still
running! For instance, [Kitab](https://github.com/hoaproject/Kitab) is
used for our code documentation. Kitab is able to (i) *render* the
documentation, and (ii) *test* the examples written inside the
documentation. That way the documentation is ensured to be always
up-to-date and working. Kitab generates tests for- and executes tests
with atoum. It was easy to set up: We just had to use the existing test
bootstraps designed for atoum. We also have another tool to [compile
HTTP API Blueprint specifications into executable
tests](https://github.com/Hywan/atoum-apiblueprint-extension). So far,
everyone is happy with those tools, no need to go back, everything is
automat(t)ic. Other tools are likely to be introduced in the future to
automatically generate tests. I want to detail this particular topic in
another blog post.

## Conclusion

Moving to another test framework is a huge decision with many factors.
The fact atoum has `atoum/phpunit-extension` is a time saver.
Nonetheless a new test framework does not mean it will fix all the
testability issues of the code. The benefits of the new test framework
must largely overtake the costs. In our current context, it was not the
case. *atoum solves issues that are not our priorities*. So yes, atoum
can help us to solve important issues, but since these issues are not
priorities, then the move to atoum was too early. During the project, we
gained new automatic test tools, like
[Kitab](https://github.com/hoaproject/Kitab). The experiment is not a
failure. Will we retry atoum? It's very likely. When? I hope in a year.
