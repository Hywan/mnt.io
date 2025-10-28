+++
title = "Rüsh Release"
date = "2014-09-15"
description = "Since 2 years, at [Hoa](http://hoa-project.net/), we are looking for the perfect release process. Today, we have finalized the last thing related to this new process: we have found a name. It is called **Rüsh Release**, standing for *Rolling Ünd ScHeduled Release*."
[taxonomies]
keywords=["release", "versioning", "hoa"]
+++

Since 2 years, at [Hoa](http://hoa-project.net/), we are looking for the
perfect release process. Today, we have finalized the last thing related
to this new process: we have found a name. It is called **Rüsh
Release**, standing for *Rolling Ünd ScHeduled Release*.

The following explanations are useful from the user point of view, not
from the developer point of view. It means that we do not explain all
the branches and the workflow between all of them. We will settle for
the user final impact.

## Rolling Release

On one hand, Hoa is not and will never be finished. We will never reach
the “Holy 1.0 Grail”. So, one might reckon that Hoa is rolling-released?
Let's dive into this direction. There are plenty [rolling release
types](https://en.wikipedia.org/wiki/Rolling_release) out there, such
as:

- partially rolling,
- fully rolling,
- truly rolling,
- pseudo-rolling,
- optionally rolling,
- cyclically rolling,
- and synonyms…

I am not going to explain all of them. All you need to know is that Hoa
is partly and truly rolling released, or *part-* and \_true-\_rolling
released for short. Why? Firstly, “Part-rolling project has a subset of
software packages that are not rolling”. If we look at Hoa only, it is
fully rolling but Hoa depends on PHP virtual machines to be executed,
which are not rolling released (for the most popular ones at least).
Thus, Hoa is partly rolling released. Secondly, “True-rolling
\[project\] are developed solely using a rolling release software
development model”, which is the case of Hoa. Consequently and finally,
the `master` branch is the final public branch, it means that it
**always** contains the latest version, and users constantly fetch
updates from it.

## Versioning

Sounds good. On the other hand, the majority of programs that are using
Hoa use tools called dependency managers. The most popular in PHP is
[Composer](http://getcomposer.org/). This is a fantastic tool but with a
little spine that hurts us a lot: it does not support rolling release!
Most of the time, dependency managers work with version numbers, mainly
of the form `_x_._y_._z_`, with a specific semantics for `_x_`, `_y_`
and `_z_`. For instance, some people have agreed about
[semver](http://semver.org/), standing for *Semantic Versioning*.

Also, we are not extremist. We understand the challenges and the needs
behind versioning. So, how to mix both: rolling release and versioning?
Before answering this question, let's progress a little step forward and
learn more about an alternative versioning approach.

### Scheduled-based release

Scheduled-based, also known as date-based, release allows to define
releases at regular periods of time. This approach is widely adopted for
projects that progress quickly, such as Firefox or PHP (see the [PHP
RFC: Release Process](https://wiki.php.net/rfc/releaseprocess) for
example). For Firefox, every 6 weeks, a new version is released. Note
that we should say *a new update* to be honest: the term *version* has
an unclear meaning here.

The scheduled-based release seems a good candidate to be mixed with
rolling release, isn't it?

## Rüsh Release

Rüsh Release is a mix between part- and true-rolling release and
scheduled-based release. The `master` branch is part- and true-rolling
release, but with a semi-automatically versioning:

- each 6 weeks, if at least one new patch has been merged into the `master`, a
  new version is created,
- before 6 weeks, if several critical or significant patches have been applied,
  a new version is created.

What is the version format then? We have proposed `_YY_{2,4}._mm_._dd_`,
starting from 2000, our “Rüsh Epoch”.

Nevertheless, we are not **infallible** and we can potentially break
backward compatibility. It never happened but we have to face it. This
is a problem because neither the part- and true-rolling release nor the
scheduled-based release holds the information that the backward
compatibility has been broken. Therefore, the `master` branch must have
a **compatibility number** `_x_`, starting from 1 with step of 1.
Consequently, the new and last version format is
`_x_._Y_{2,4}._mm_._dd_`. For today for instance, it is `1.14.09.15`.

With the Rüsh Release process, we can freely rolling release our
libraries while ensuring the safety and embracing the pros of
versioning.

So, now, you will be able to change your `composer.json` files from:

```json
{
  "require": {
    "hoa/websocket": "dev-master"
  },
  "minimum-stability": "dev"
}
```

to ([learn more about the tilde
operator](https://getcomposer.org/doc/01-basic-usage.md#next-significant-release-tilde-operator-)):

``` json
{
    "require": {
        "hoa/websocket": "~1.0"
    }
}
```

\o/
