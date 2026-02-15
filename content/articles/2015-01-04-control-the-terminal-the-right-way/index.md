+++
title = "Control the terminal, the right way"
date = "2015-01-04"
description = "Nowadays, there are plenty of terminal emulators in the wild. Each one has a specific way to handle controls. How many colours does it support? How to control the style of a character? How to control more than style, like the cursor or the window? In this article, we are going to explain and show in action the right ways to control your terminal with a portable and an easy to maintain API. We are going to talk about `stat`, `tput`, `terminfo`, `hoa/console`… but do not be afraid, it's easy and fun!"
[taxonomies]
keywords=["terminal", "console"]
[extra]
pinned = true
+++

Nowadays, there are plenty of terminal emulators in the wild. Each one has
a specific way to handle controls. How many colours does it support? How to
control the style of a character? How to control more than style, like the
cursor or the window? In this article, we are going to explain and show in
action the right ways to control your terminal with a portable and an easy
to maintain API. We are going to talk about `stat`, `tput`, `terminfo`, `hoa/
console`… but do not be afraid, it's easy and fun!

## Introduction

Terminals. They are the ancient interfaces, still not old fashioned yet.
They are fast, efficient, work remotely with a low bandwidth, secured
and very simple to use.

A terminal is a canvas composed of columns and lines. Only one character
fits at a position. According to the terminal, we have some features
enabled; for instance, a character might be stylized with a colour, a
decoration, a weight etc. Let's consider the former. A colour belongs to
a palette, which contains either 2, 8, 256 or more colours. One may
wonder:

- How many colours does a terminal support?
- How to control the style of a character?
- How to control more than style, like the cursor or the window?

Well, this article is going to explain how a terminal works and how we
interact with it. We are going to talk about terminal capabilities,
terminal information (stored in database) and
[`Hoa\Console`](http://github.com/hoaproject/Console),
a PHP library that provides advanced terminal controls.

## The basis of a terminal

A terminal, or a console, is an interface that allows to interact with
the computer. This interface is textual. Like a graphical interface,
there are inputs: The keyboard and the mouse, and ouputs: The screen or
a file (a real file, a socket, a FIFO, something else…).

There is a ton of terminals. The most famous ones are:

- [xterm](http://invisible-island.net/xterm/xterm.html),
- [iTerm2](http://iterm2.com/),
- [urxvt](http://software.schmorp.de/pkg/rxvt-unicode.html),
- [TeraTerm](http://ttssh2.sourceforge.jp/).

Whatever the terminal you use, inputs are handled by programs (or
processus) and outputs are produced by these latters. We said outputs
can be the screen or a file. Actually, everything is a file, so the
screen is also a file. However, the user is able to use
[redirections](http://gnu.org/software/bash/manual/bashref.html#Redirections)
to choose where the ouputs must go.

Let's consider the `echo` program that prints all its options/arguments
on its output. Thus, in the following example, `foobar` is printed on
the screen:

```console
$ echo 'foobar'
```

And in the following example, `foobar` is redirected to a file called
`log`:

```console
$ echo 'foobar' > log
```

We are also able to redirect the output to another program, like `wc`
that counts stuff:

```console
$ echo 'foobar' | wc -c
7
```

Now we know there are 7 characters in `foobar`… no! `echo` automatically
adds a new-line (`\n`) after each line; so:

```console
$ echo -n 'foobar' | wc -c
6
```

This is more correct!

## Detecting type of pipes

Inputs and outputs are called **pipes**. Yes, trivial, this is nothing
more than basic pipes!

There are 3 standard pipes:

- `STDIN`, standing for the standard input pipe,
- `STDOUT`, standing for the standard output pipe and
- `STDERR`, standing for the standard error pipe (also an output one).

If the output is attached to the screen, we say this is a “direct
output”. Why is it important? Because if we stylize a text, this is
**only for the screen**, not for a file. A file should receive regular
text, not all the decorations and styles.

Hopefully, the [`Hoa\Console\Console`
class](https://github.com/hoaproject/Console/blob/master/Source/Console.php)
provides the `isDirect`, `isPipe` and `isRedirection` static methods to
know whether the pipe is respectively direct, a pipe or a redirection
(damn naming…!). Thus, let `Type.php` be the following program:

```php
<?php

echo 'is direct:      ';
var_dump(Hoa\Console\Console::isDirect(STDOUT));

echo 'is pipe:        ';
var_dump(Hoa\Console\Console::isPipe(STDOUT));

echo 'is redirection: ';
var_dump(Hoa\Console\Console::isRedirection(STDOUT));
```

Now, let's test our program:

```console
$ php Type.php
is direct:      bool(true)
is pipe:        bool(false)
is redirection: bool(false)

$ php Type.php | xargs -I@ echo @
is direct:      bool(false)
is pipe:        bool(true)
is redirection: bool(false)

$ php Type.php > /tmp/foo; cat !!$
is direct:      bool(false)
is pipe:        bool(false)
is redirection: bool(true)
```

The first execution is very classic. `STDOUT`, the standard output, is
direct. The second execution redirects the output to another program,
then `STDOUT` is of kind pipe. Finally, the last execution redirects the
output to a file called `/tmp/foo`, so `STDOUT` is a redirection.

How does it work? We use [`fstat`](http://php.net/fstat) to read the
`mode` of the file. The underlying `fstat` implementation is defined in
C, so let's take a look at the [documentation of
`fstat(2)`](http://man.cx/fstat%282%29). `stat` is a C structure that
looks like:

```c
struct stat {
    dev_t    st_dev;              /* device inode resides on             */
    ino_t    st_ino;              /* inode's number                      */
    mode_t   st_mode;             /* inode protection mode               */
    nlink_t  st_nlink;            /* number of hard links to the file    */
    uid_t    st_uid;              /* user-id of owner                    */
    gid_t    st_gid;              /* group-id of owner                   */
    dev_t    st_rdev;             /* device type, for special file inode */
    struct timespec st_atimespec; /* time of last access                 */
    struct timespec st_mtimespec; /* time of last data modification      */
    struct timespec st_ctimespec; /* time of last file status change     */
    off_t    st_size;             /* file size, in bytes                 */
    quad_t   st_blocks;           /* blocks allocated for file           */
    u_long   st_blksize;          /* optimal file sys I/O ops blocksize  */
    u_long   st_flags;            /* user defined flags for file         */
    u_long   st_gen;              /* file generation number              */
}
```

The value of `mode` returned by the PHP `fstat` function is equal to
`st_mode` in this structure. And `st_mode` has the following bits:

```c
#define S_IFMT   0170000 /* type of file mask                */
#define S_IFIFO  0010000 /* named pipe (fifo)                */
#define S_IFCHR  0020000 /* character special                */
#define S_IFDIR  0040000 /* directory                        */
#define S_IFBLK  0060000 /* block special                    */
#define S_IFREG  0100000 /* regular                          */
#define S_IFLNK  0120000 /* symbolic link                    */
#define S_IFSOCK 0140000 /* socket                           */
#define S_IFWHT  0160000 /* whiteout                         */
#define S_ISUID  0004000 /* set user id on execution         */
#define S_ISGID  0002000 /* set group id on execution        */
#define S_ISVTX  0001000 /* save swapped text even after use */
#define S_IRWXU  0000700 /* RWX mask for owner               */
#define S_IRUSR  0000400 /* read permission, owner           */
#define S_IWUSR  0000200 /* write permission, owner          */
#define S_IXUSR  0000100 /* execute/search permission, owner */
#define S_IRWXG  0000070 /* RWX mask for group               */
#define S_IRGRP  0000040 /* read permission, group           */
#define S_IWGRP  0000020 /* write permission, group          */
#define S_IXGRP  0000010 /* execute/search permission, group */
#define S_IRWXO  0000007 /* RWX mask for other               */
#define S_IROTH  0000004 /* read permission, other           */
#define S_IWOTH  0000002 /* write permission, other          */
#define S_IXOTH  0000001 /* execute/search permission, other */
```

Awesome, we have everything we need! We mask `mode` with `S_IFMT` to get
the file data. Then we just have to check whether it is a named pipe
`S_IFIFO`, a character special `S_IFCHR` etc. Concretly:

- `isDirect` checks that the mode is equal to `S_IFCHR`, it means it is
  attached to the screen (in our case),
- `isPipe` checks that the mode is equal to `S_IFIFO`: This is a special file
  that behaves like a FIFO stack (see the
  [documentation of `mkfifo(1)`][`mkfifo`]), everything which is written is
  directly read just after and the reading order is defined by the writing order
  (first-in, first-out!),
- `isRedirection` checks that the mode is equal to `S_IFREG` , `S_IFDIR` ,
  `S_IFLNK` , `S_IFSOCK` or `S_IFBLK` , in other words: All kind of files on
  which we can apply a redirection. Why? Because the `STDOUT` (or another
  `STD_*_` pipe) of the current processus is defined as a file pointer to the
  redirection destination and it can be only a file, a directory, a link, a
  socket or a block file.

I encourage you to read the [implementation of the
`Hoa\Console\Console::getMode`
method](https://github.com/hoaproject/Console/blob/master/Source/Console.php).

So yes, this is useful to enable styles on text but also to define the
default verbosity level. For instance, if a program outputs the result
of a computation with some explanations around, the highest verbosity
level would output everything (the result and the explanations) while
the lowest level would output only the result. Let's try with the
`toUpperCase.php` program:

```php
<?php

$verbose = Hoa\Console\Console::isDirect(STDOUT);
$string  = $argv[1];
$result  = (new Hoa\String\String($string))->toUpperCase();

if(true === $verbose) {
    echo $string, ' becomes ', $result, ' in upper case!', "\n";
} else {
    echo $result, "\n";
}
```

Then, let's execute this program:

```console
$ php toUpperCase.php 'Hello world!'
Hello world! becomes HELLO WORLD! in upper case!
```

And now, let's execute this program with a pipe:

```console
$ php toUpperCase.php 'Hello world!' | xargs -I@ echo @
HELLO WORLD!
```

Useful and very simple, isn't it?

## Terminal capabilities

We can control the terminal with the inputs, like the keyboard, but we
can also control the outputs. How? With the text itself. Actually, an
output does not contain only the text but it includes **control
functions**. It's like HTML: Around a text, you can have an element,
specifying that the text is a link. It's exactly the same for terminals!
To specify that a text must be in red, we must add a control function
around it.

Hopefully, these control functions have been standardized in the
[ECMA-48](http://www.ecma-international.org/publications/files/ECMA-ST/Ecma-048.pdf)
document: Control Functions for Coded Character Set. However, not all
terminals implement all this standard, and for historical reasons, some
terminals use slightly different control functions. Moreover, some
information do not belong to this standard (because this is out of its
scope), like: How many colours does the terminal support? or does the
terminal support the meta key?

Consequently, each terminal has a list of **capabilities**. This list is
splitted in **3 categories**:

- boolean capabilities,
- number capabilities,
- string capabilities.

For instance:

- the “does the terminal support the meta key” is a boolean capability called
  `meta_key` where its value is `true` or `false`,
- the “number of colours supported by the terminal” is a… number capability
  called `max_colors` where its value can be `2`, `8`, `256` or more,
- the “clear screen control function” is a string capability called
  `clear_screen` where its value might be `\e[H\e[2J`,
- the “move the cursor one column to the right” is also a string capability
  called `cursor_right` where its value might be `\e[C` .

All the capabilities can be found in the [documentation of
`terminfo(5)`](http://www.freebsd.org/cgi/man.cgi?query=terminfo&sektion=5)
or in the [documentation of
xcurses](http://pubs.opengroup.org/onlinepubs/7908799/xcurses/terminfo.html).
I encourage you to follow these links and see how rich the terminal
capabilities are!

## Terminal information

Terminal capabilities are stored as **information** in **databases**.
Where are these databases located? In files with a binary format.
Favorite locations are:

- `/usr/share/terminfo`,
- `/usr/share/lib/terminfo`,
- `/lib/terminfo`,
- `/usr/lib/terminfo`,
- `/usr/local/share/terminfo`,
- `/usr/local/share/lib/terminfo`,
- etc.
- or the `TERMINFO` or `TERMINFO_DIRS` environment variables.

Inside these directories, we have a tree of the form: `_xx_/_name_`,
where `_xx_` is the ASCII value in hexadecimal of the first letter of
the terminal name `_name_`, or `_n_/_name_` where `_n_` is the first
letter of the terminal name. The terminal name is stored in the `TERM`
environment variable. For instance, on my computer:

```console
$ echo $TERM
xterm-256color
$ file /usr/share/terminfo/78/xterm-256color
/usr/share/terminfo/78/xterm-256color: Compiled terminfo entry
```

We can use the [`Hoa\Console\Tput`
class](https://github.com/hoaproject/Console/blob/master/Source/Tput.php)
to retrieve these information. The `getTerminfo` static method allows to
get the path of the terminal information file. The `getTerm` static
method allows to get the terminal name. Finally, the whole class allows
to parse a terminal information database (it will use the file returned
by `getTerminfo` by default). For instance:

```php
<?php

$tput = new Hoa\Console\Tput();
var_dump($tput->count('max_colors'));

/**
 * Will output:
 *     int(256)
 */
```

On my computer, with `xterm-256color`, I have 256 colours, as expected.
If we parse the information of `xterm` and not `xterm-256color`, we will
have:

``` php
$tput = new Hoa\Console\Tput(Hoa\Console\Tput::getTerminfo('xterm'));
var_dump($tput->count('max_colors'));

/**
 * Will output:
 *     int(8)
 */
```

## The power in your hand: Control the cursor

Let's summarize. We are able to parse and know all the terminal
capabilities of a specific terminal (including the one of the current
user). If we would like a powerful terminal API, we need to control the
basis, like the cursor.

Remember. We said that the terminal is a canvas of columns and lines.
The cursor is like a pen. We can move it and write something. We are
going to (partly) see how the [`Hoa\Console\Cursor`
class](https://github.com/hoaproject/Console/blob/master/Source/Cursor.php)
works.

### I like to move it!

The `moveTo` static method allows to move the cursor to an absolute
position. For example:

```php
<?php

Hoa\Console\Cursor::moveTo($x, $y);
```

The control function we use is `cursor_address`. So all we need to do is
to use the `Hoa\Console\Tput` class and call the `get` method on it to
get the value of this string capability. This is a parameterized one: On
`xterm-256color`, its value is `e[%i%p1%d;%p2%dH`. We replace the
parameters by `$x` and `$y` and we output the result. That's all! We are
able to move the cursor on an absolute position on **all terminals**!
This is the right way to do.

We use the same strategy for the `move` static method that moves the
cursor relatively to its current position. For example:

```php
<?php

Hoa\Console\Cursor::move('right up');
```

We split the steps and for each step we read the appropriated string
capability using the `Hoa\Console\Tput` class. For `right`, we read the
`parm_right_cursor` string capability, for `up`, we read
`parm_up_cursor` etc. Note that `parm_right_cursor` is different of
`cursor_right`: The first one is used to move the cursor a certain
number of times while the second one is used to move the cursor only one
time. With performances in mind, we should use the first one if we have
to move the cursor several times.

The `getPosition` static method returns the position of the cursor. This
way to interact is a little bit different. We must write a control
function on the output, and then, the terminal replies on the input.
[See the implementation by
yourself](https://github.com/hoaproject/Console/blob/master/Source/Cursor.php).

```php
<?php

print_r(Hoa\Console\Cursor::getPosition());

/**
 * Will output:
 *     Array
 *     (
 *         [x] => 7
 *         [y] => 42
 *     )
 */
```

In the same way, we have the `save` and `restore` static methods that
save the current position of the cursor and restore it. This is very
useful. We use the `save_cursor` and `restore_cursor` string
capabilities.

Also, the `clear` static method splits some parts to clear. For each
part (direction or way), we read from `Hoa\Console\Tput` the
appropriated string capabilities: `clear_screen` to clear all the
screen, `clr_eol` to clear everything on the right of the cursor,
`clr_eos` to clear everything bellow the cursor etc.

```php
<?php

Hoa\Console\Cursor::clear('left');
```

See what we learnt in action:

```php
<?php

echo 'Foobar', "\n",
     'Foobar', "\n",
     'Foobar', "\n",
     'Foobar', "\n",
     'Foobar', "\n";

           Hoa\Console\Cursor::save();
sleep(1);  Hoa\Console\Cursor::move('LEFT');
sleep(1);  Hoa\Console\Cursor::move('↑');
sleep(1);  Hoa\Console\Cursor::move('↑');
sleep(1);  Hoa\Console\Cursor::move('↑');
sleep(1);  Hoa\Console\Cursor::clear('↔');
sleep(1);  echo 'Hahaha!';
sleep(1);  Hoa\Console\Cursor::restore();

echo "\n", 'Bye!', "\n";
```

The result is presented in the following figure.

<figure>

  ![Moving a cursor in the terminal](./cursor_move.gif)

  <figcaption>

  Saving, moving, clearing and restoring the cursor with `Hoa\Console`.

  </figcaption>

</figure>

The resulting API is portable, clean, simple to read and very easy to
maintain! This is the right way to do.

To get more information, please [read the
documentation](http://hoa-project.net/Literature/Hack/Console.html#Cursor "Documentation of Hoa\Console\Cursor").

### Colours and decorations

Now: Colours. This is mainly the reason why I decided to write this
article. We see the same and the same libraries, again and again, doing
only colours in the terminal, but unfortunately not in the right way 😞.

A terminal has a palette of colours. Each colour is indexed by an
integer, from 0 to potentially +∞ . The size of the palette is described
by the `max_colors` number capability. Usually, a palette contains 1, 2,
8, 256 or 16 million colours.

<figure>

  ![`xterm-256color` palette](./xterm_256color_chart.svg)

  <figcaption>

  The `xterm-256color` palette ([source](https://commons.wikimedia.org/wiki/File:Xterm_256color_chart.svg "Source of the `xterm-256color` palette")).

  </figcaption>

</figure>

So first thing to do is to check whether we have more than 1 colour. If
not, we must not colorize the given text. Next, if we have less than
256 colours, we have to convert the style into a palette containing
8 colours. Same with less than 16 million colours, we have to convert
into 256 colours.

Moreover, we can define the style of the foreground or of the background
with respectively the `set_a_foreground` and `set_a_background` string
capabilities. Finally, in addition to colours, we can define other
decorations like bold, underline, blink or even inverse the foreground
and the background colours.

One thing to remember is: With this capability, we only define the style
at a given “pixel” and it will apply on the following text. In this
case, it is not exactly like HTML where we have a beginning and an end.
Here we only have a beginning. Let's try!

```php
<?php

Hoa\Console\Cursor::colorize('underlined foreground(yellow) background(#932e2e)');
echo 'foo';
Hoa\Console\Cursor::colorize('!underlined background(normal)');
echo 'bar', "\n";
```

The API is pretty simple: We start to underline the text, we set the
foreground to yellow and we set the background to `#932e2e`  . Then we
output something. We continue with cancelling the underline decoration
in addition to resetting the background. Finally we output something
else. Here is the result:

<figure>

  ![A styled text in the terminal](./colour.png)

  <figcaption>

  Fun with `Hoa\Console\Cursor::colorize`.

  </figcaption>

</figure>

What do we observe? My terminal does not support more than 256 colours.
Thus, `#932e2e` is **automatically converted into the closest colour**
in my actual palette! This is the right way to do.

For fun, you can change the colours in the palette with the
`Hoa\Console\Cursor::changeColor` static method. You can also change the
style of the cursor, like `▋`, `_` or `|`.

To get more information, please [read the
documentation](http://hoa-project.net/Fr/Literature/Hack/Console.html#Content "Documentation of Hoa\Console\Cursor").

## The power in your hand: Readline

A more complete usage of `Hoa\Console\Cursor` and even
`Hoa\Console\Window` is the [`Hoa\Console\Readline`
class](http://central.hoa-project.net/Resource/Library/Console/Readline/Readline.php)
that is a powerful readline. More than autocompleters, history, key
bindings etc., it has an advanced use of cursors. See this in action:

<figure>

  ![Play with autocompleters](./readline_autocompleters.gif)

  <figcaption>

  An autocompletion menu, made with `Hoa\Console\Cursor` and
  `Hoa\Console\Window`.

  </figcaption>

</figure>

We use `Hoa\Console\Cursor` to move the cursor or change the colours and
`Hoa\Console\Window` to get the dimensions of the window, scroll some
text in it etc. I encourage you to read the implementation.

To get more information, please [read the
documentation](http://hoa-project.net/Literature/Hack/Console.html#Readline "Documentation of Hoa\Console\Readline").

## The power in your hand: Sound 🎵

Yes, even sound is defined by terminal capabilities. The famous bip is
given by the `bell` string capability. You would like to make a bip?
Easy:

```php
<?php

$tput = new Hoa\Console\Tput();
echo $tput->get('bell');
```

That's it!

## Bonus: Window

As a bonus, a quick demo of `Hoa\Console\Window` because it's fun.

The video shows the execution of the following code:

```php
<?php

Hoa\Console\Window::setSize(80, 35);
var_dump(Hoa\Console\Window::getPosition());

foreach(
    [
        [100, 100], [150, 150], [200, 100], [200, 80],
        [200,  60], [200, 100]
    ]
    as list($x, $y)
) {
    sleep(1);  Hoa\Console\Window::moveTo($x, $y);
}

sleep(2);  Hoa\Console\Window::minimize();
sleep(2);  Hoa\Console\Window::restore();
sleep(2);  Hoa\Console\Window::lower();
sleep(2);  Hoa\Console\Window::raise();
```

We resize the window, we get its position, we move the window on the
screen, we minimize and restore it, and finally we put it behind all
other windows just before raising it.

<iframe src="https://player.vimeo.com/video/115901611?title=0&amp;byline=0&amp;portrait=0&amp;badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479" frameborder="0" allow="autoplay; fullscreen; picture-in-picture; clipboard-write" style="aspect-ratio: 16/9; width: 100%;" title="Hoa\Console\Window in action">
</iframe>

To get more information, please [read the
documentation](http://hoa-project.net/Literature/Hack/Console.html#Window "Documentation of Hoa\Console\Window").

## Conclusion

In this article, we saw how to control the terminal by: Firstly,
detecting the type of pipes, and secondly, reading and using the
terminal capabilities. We know where these capabilities are stored and
we saw few of them in action.

This approach ensures your code will be **portable**, easy to maintain
and **easy to use**. The portability is very important because, like
browsers and user devices, we have a lot of terminal emulators released
in the wild. We have to care about them.

I encourage you to take a look at the [`Hoa\Console`
library](http://github.com/hoaproject/Console) and to contribute to make
it even more awesome 😄.

[`mkfifo`]: http://www.freebsd.org/cgi/man.cgi?query=mkfifo&sektion=1
