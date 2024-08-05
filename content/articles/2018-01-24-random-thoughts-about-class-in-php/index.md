+++
title = "Random thoughts about `::class` in PHP"
date = "2018-01-24"
[taxonomies]
keywords=["php"]
+++

> The special **`::class`** constant allows for fully qualified class
> name resolution at compile, this is useful for namespaced classes.

I'm quoting [the PHP
manual](http://php.net/manual/en/language.oop5.constants.php). But
things can be funny sometimes. Let's go through some examples.

- ```php
  use A\B as C;

  $_ = C::class;
  ```

  resolves to `A\B`, which is perfect 🙂

- ```php
  class C {
      public function f() {
          $_ = self::class;
      }
  }
  ```

  resolves to `C`, which is perfect 😀

- ```php
  class C {}

  class D extends C {
      public function f() {
          $_ = parent::class;
      }
  }
  ```

  resolves to `C`, which is perfect 😄

- ```php
  class C {
      public static function f() {
          $_ = static::class;
      }
  }

  class D extends C {}

  D::f();
  ```

  resolves to `D`, which is perfect 😍

- ```php
  'foo'::class
  ```

  resolves to `'foo'`, which is… huh? 🤨

- ```php
  "foo"::class
  ```

  resolves to `'foo'`, which is… expected somehow 😕

- ```php
  $a = 'oo';
  "f{$a}"::class
  ```

  generates a parse error 🙃

- ```php
  PHP_VERSION::class
  ```

  resolves to `'PHP_VERSION'`, which is… strange: It resolves to the
  fully qualified name of the constant, not the *class* 🤐

`::class` is very useful to get rid off of the `get_class` or the
`get_called_class` functions, or even the `get_class($this)` trick. This
is something truly useful in PHP where entities are referenced as
strings, not as symbols. `::class` on constants makes sense, but the
name is no longer relevant. And finally, `::class` on single quote
strings is absolutely useless; on double quotes strings it is a source
of error because the value can be dynamic (and remember, `::class` is
resolved at compile time, not at run time).
