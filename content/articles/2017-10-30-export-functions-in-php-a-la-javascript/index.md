+++
title = "Export functions in PHP à la Javascript"
date = "2017-10-30"
+++

Warning: This post is totally useless. It is the result of a fun private
company thread.

## Export functions in Javascript

In Javascript, a file can export functions like this:

```javascript
export function times2(x) {
    return x * 2;
}
```

And then we can import this function in another file like this:

```javascript
import {times2} from 'foo';

console.log(times2(21)); // 42
```

Is it possible with PHP?

## Export functions in PHP

Every entity is public in PHP: Constant, function, class, interface, or
trait. They can live in a namespace. So exporting functions in PHP is
absolutely useless, but just for the fun, let's keep going.

A PHP file can return an integer, a real, an array, an anonymous
function, anything. Let's try this:

```php
return function (int $x): int {
    return $x * 2;
};
```

And then in another file:

```php
$times2 = require 'foo.php';
var_dump($times2(21)); // int(42)
```

Great, it works.

What if our file returns more than one function? Let's use an array
(which has most hashmap properties):

```php
return [
    'times2' => function (int $x): int {
        return $x * 2;
    },
    'answer' => function (): int {
        return 42;
    }
];
```

To choose what to import, let's use [the `list`
intrinsic](https://github.com/php/php-langspec/blob/master/spec/10-expressions.md#list-intrinsic).
It has several forms: With or without key matching, long (`list(…)`) and
short syntax (`[…]`). Because we are modern, we will use the short
syntax with key matching to selectively import functions:

```php
['times2' => $mul] = require 'foo.php';

var_dump($mul(21)); // int(42)
```

Notice that `times2` has been aliased to `$mul`. What a feature!

Is it useful? Absolutely not. Is it fun? For me it is.
