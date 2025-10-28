+++
title = "Generate strings based on regular expressions"
date = "2014-09-30"
description = "During my PhD thesis, I have partly worked on the problem of the automatic accurate test data generation. In order to be complete and self-contained, I have addressed all kinds of data types, including strings. This article aims at showing how to generate accurate and relevant strings under several constraints."
[taxonomies]
keywords=["data", "generation", "string", "regular expression", "algorithm"]
[extra]
pinned = true
+++

During my PhD thesis, I have partly worked on the problem of the automatic
accurate test data generation. In order to be complete and self-contained, I
have addressed all kinds of data types, including strings. This article aims at
showing how to generate accurate and relevant strings under several constraints.

## What is a regular expression?

We are talking about formal language theory here. In the known world,
there are four kinds of languages. More formally, in 1956, the [Chomsky
hierarchy](https://en.wikipedia.org/wiki/Chomsky_hierarchy) has been
formulated, classifying grammars (which define languages) in four
levels:

1. unrestricted grammars, matching langages known as Turing languages, no
   restriction,
2. context-sensitive grammars, matching contextual languages,
3. context-free grammars, matching algebraic languages, based on stacked
   automata,
4. regular grammars, matching regular languages.

Each level includes the next level. The last level is the “weaker”,
which must not sound negative here. [Regular
expressions](https://en.wikipedia.org/wiki/Regular_expression) are used
often because of their simplicity and also because they solve most
problems we encounter daily.

A regular expression is a small language with very few operators and,
most of the time, a simple semantics. For instance `ab(c|d)` means: a
word (a data) starting by `ab` and followed by `c` or `d`. We also have
quantification operators (also known as repetition operators), such as
`?`, `*` and `+`. We also have `{_x_,_y_}` to define a repetition
between `_x_` and `_y_`. Thus, `? ` is equivalent to `{0,1}`, `*` to
`{0,}` and `+` to `{1,}`. When `_y_` is missing, it means +∞, so
unbounded (or more exactly, bounded by the limits of the machine). So,
for instance `ab(c|d){2,4}e?` means: a word starting by `ab`, followed
2, 3 or 4 times by `c` or `d` (so `cc`, `cd`, `dc`, `ccc`, `ccd`, `cdc`
and so on) and potentially followed by `e`.

The goal here is not to teach you regular expressions but this is kind
of a tiny reminder. There are plenty of regular languages. You might
know [POSIX regular
expression](http://www.unix.com/man-page/Linux/7/regex/) or [Perl
Compatible Regular Expressions (PCRE)](http://pcre.org/). Forget the
first one, please. The syntax and the semantics are too much limited.
PCRE is the regular language I recommend all the time.

Behind every formal language there is a graph. A regular expression is compiled
into a [Finite State Machine (FSM)](https://en.wikipedia.org/wiki/). I am not
going to draw and explain them, but it is interesting to know that behind a
regular expression there is a basic automaton. No magic.

### Why focussing regular expressions?

This article focuses on regular languages instead of other kind of
languages because we use them very often (even daily). I am going to
address context-free languages in another article, be patient young
padawan. The needs and constraints with other kind of languages are not
the same and more complex algorithms must be involved. So we are going
easy for the first step.

## Understanding PCRE: lex and parse them

The [`Hoa\Compiler` library](https://github.com/hoaproject/Compiler) provides
both LL(1) LL(k) compiler-compilers. The
[documentation](http://hoa-project.net/Literature/Hack/Compiler.html)
describes how to use it. We discover that the LL(k)
compiler comes with a grammar description language called PP. What does
it mean? It means for instance that the grammar of the PCRE can be
written with the PP language and that `Hoa\Compiler\Llk` will transform
this grammar into a compiler. That's why we call them “compiler of
compilers”.

Fortunately, the [`Hoa\Regex` library](https://github.com/hoaproject/Regex)
provides the grammar of the PCRE language in the
[`hoa://Library/Regex/Grammar.pp`](https://github.com/hoaproject/Regex/blob/master/Source/Grammar.pp)
file. Consequently, we are able to analyze regular expressions written
in the PCRE language! Let's try in a shell at first with the
`hoa compiler:pp` tool:

```sh
$ echo 'ab(c|d){2,4}e?' | hoa compiler:pp hoa://Library/Regex/Grammar.pp 0 --visitor dump
>  #expression
>  >  #concatenation
>  >  >  token(literal, a)
>  >  >  token(literal, b)
>  >  >  #quantification
>  >  >  >  #alternation
>  >  >  >  >  token(literal, c)
>  >  >  >  >  token(literal, d)
>  >  >  >  token(n_to_m, {2,4})
>  >  >  #quantification
>  >  >  >  token(literal, e)
>  >  >  >  token(zero_or_one, ?)
```

We read that the whole expression is composed of a single concatenation
of two tokens: `a` and `b`, followed by a quantification, followed by
another quantification. The first quantification is an alternation of (a
choice betwen) two tokens: `c` and `d`, between 2 to 4 times. The second
quantification is the `e` token that can appear zero or one time. Pretty
simple.

The final output of the `Hoa\Compiler\Llk\Parser` class is an [Abstract
Syntax Tree (AST)](https://en.wikipedia.org/wiki/Abstract_syntax_tree).
The documentation of `Hoa\Compiler` explains all that stuff, you should read
it. The LL(k) compiler is cut out into very distinct layers in order to improve
hackability. Again, the documentation teach us we have [four levels in the
compilation
process](http://hoa-project.net/Literature/Hack/Compiler.html#Compilation_process):
lexical analyzer, syntactic analyzer, trace and AST. The lexical analyzer (also
known as lexer) transforms the textual data being analyzed into a sequence of
tokens (formally known as lexemes). It checks whether the data is composed of
the good pieces. Then, the syntactic analyzer (also known as parser) checks that
the order of tokens in this sequence is correct (formally we say that it derives
the sequence, see the [Matching words
section](http://hoa-project.net/Literature/Hack/Compiler.html#Matching_words)
to learn more).

Still in the shell, we can get the result of the lexical analyzer by
using the `--token-sequence` option; thus:

```sh
$ echo 'ab(c|d){2,4}e?' | hoa compiler:pp hoa://Library/Regex/Grammar.pp 0 --token-sequence
  #  …  token name   token value  offset
-----------------------------------------
  0  …  literal      a                 0
  1  …  literal      b                 1
  2  …  capturing_   (                 2
  3  …  literal      c                 3
  4  …  alternation  |                 4
  5  …  literal      d                 5
  6  …  _capturing   )                 6
  7  …  n_to_m       {2,4}             7
  8  …  literal      e                12
  9  …  zero_or_one  ?                13
 10  …  EOF                           15
```

This is the sequence of tokens produced by the lexical analyzer. The
tree is not yet built because this is the first step of the compilation
process. However this is always interesting to understand these
different steps and see how it works.

Now we are able to analyze any regular expressions in the PCRE format!
The result of this analysis is a tree. You know what is fun with trees?
[Visiting them](https://en.wikipedia.org/wiki/Visitor_pattern).

## Visiting the AST

Unsurprisingly, each node of the AST can be visited thanks to the [`Hoa\Visitor`
library](http://github.com/hoaproject/Visitor). Here is an example with the
“dump” visitor:

```php
<?php

use Hoa\Compiler;
use Hoa\File;

// 1. Load grammar.
$compiler = Compiler\Llk\Llk::load(
    new File\Read('hoa://Library/Regex/Grammar.pp')
);

// 2. Parse a data.
$ast = $compiler->parse('ab(c|d){2,4}e?');

// 3. Dump the AST.
$dump = new Compiler\Visitor\Dump();
echo $dump->visit($ast);
```

This program will print the same AST dump we have previously seen in the
shell.

How to write our own visitor? A visitor is a class with a single `visit`
method. Let's try a visitor that pretty print a regular expression, i.e.
transform:

```
ab(c|d){2,4}e?
```

into:

```
a
b
(
    c
    |
    d
){2,4}
e?
```

Why a pretty printer? First, it shows how to visit a tree. Second, it
shows the structure of the visitor: we filter by node ID (`#expression`,
`#quantification`, `token` etc.) and we apply respective computations. A
pretty printer is often a good way for being familiarized with the
structure of an AST.

Here is the class. It catches only useful constructions for the given
example:

```php
<?php

use Hoa\Visitor;

class PrettyPrinter implements Visitor\Visit {
    public function visit(
        Visitor\Element $element,
        &$handle = null,
        $eldnah  = null
    ) {
        static $_indent = 0;

        $out = null;
        $nodeId = $element->getId();

        switch($nodeId) {
            // Reset indentation and…
            case '#expression':
                $_indent = 0;

            // … visit all the children.
            case '#quantification':
                foreach($element->getChildren() as $child)
                    $out .= $child->accept($this, $handle, $eldnah);
              break;

            // One new line between each children of the concatenation.
            case '#concatenation':
                foreach($element->getChildren() as $child)
                    $out .= $child->accept($this, $handle, $eldnah) . "\n";
              break;

            // Add parenthesis and increase indentation.
            case '#alternation':
                $oout = [];

                $pIndent = str_repeat('    ', $_indent);
                ++$_indent;
                $cIndent = str_repeat('    ', $_indent);

                foreach($element->getChildren() as $child)
                    $oout[] = $cIndent . $child->accept($this, $handle, $eldnah);

                --$_indent;
                $out .= $pIndent . '(' . "\n" .
                        implode("\n" . $cIndent . '|' . "\n", $oout) . "\n" .
                        $pIndent . ')';
              break;

            // Print token value verbatim.
            case 'token':
                $tokenId = $element->getValueToken();
                $tokenValue = $element->getValueValue();

                switch($tokenId) {

                    case 'literal':
                    case 'n_to_m':
                    case 'zero_or_one':
                        $out .= $tokenValue;
                       break;

                    default:
                        throw new RuntimeException(
                            'Token ID ' . $tokenId . ' is not well-handled.'
                        );
                }
              break;

            default:
                throw new RuntimeException(
                    'Node ID ' . $nodeId . ' is not well-handled.'
                );
        }

        return $out;
    }
}
```

And finally, we apply the pretty printer on the AST like previously
seen:

```php
<?php

$compiler = Compiler\Llk\Llk::load(
    new File\Read('hoa://Library/Regex/Grammar.pp')
);
$ast = $compiler->parse('ab(c|d){2,4}e?');
$prettyprint = new PrettyPrinter();
echo $prettyprint->visit($ast);
```

*Et voilà !*

Now, put all that stuff together!

## Isotropic generation

We can use `Hoa\Regex` and `Hoa\Compiler` to get the AST of any regular
expressions written in the PCRE format. We can use `Hoa\Visitor` to
traverse the AST and apply computations according to the type of nodes.
Our goal is to generate strings based on regular expressions. What kind
of generation are we going to use? There are plenty of them: uniform
random, smallest, coverage based…

The simplest is isotropic generation, also known as random generation.
But random says nothing: what is the repartition, or do we have any
uniformity? Isotropic means each choice will be solved randomly and
uniformly. Uniformity has to be defined: does it include the whole set
of nodes or just the immediate children of the node? Isotropic means we
consider only immediate children. For instance, a node `#alternation`
has _c_ immediate children, the probability _C_ to choose one child is:

<math xmlns="http://www.w3.org/1998/Math/MathML">
  <semantics>
    <mrow>
      <mi>P</mi>
      <mo stretchy="false">(</mo><mi>C</mi><mo stretchy="false">)</mo>
      <mo>=</mo>
      <mfrac>
        <mn>1</mn>
        <mi>c</mi>
      </mfrac>
    </mrow>
    <annotation encoding="application/x-tex">P(C) = \frac{1}{c}</annotation>
  </semantics>
</math>

Yes, simple as that!

We can use the [`Hoa\Math` library](https://github.com/hoaproject/Math) that
provides the `Hoa\Math\Sampler\Random` class to sample uniform random integers
and floats. Ready?

### Structure of the visitor

The structure of the visitor is the following:

```php
<?php

use Hoa\Visitor;
use Hoa\Math;

class IsotropicSampler implements Visitor\Visit {
    protected $_sampler = null;

    public function __construct(Math\Sampler $sampler) {
        $this->_sampler = $sampler;

        return;
    }

    public function visit(
        Visitor\Element $element,
        &$handle = null,
        $eldnah = null
    ) {
        switch($element->getId()) {
            // …
        }
    }
}
```

We set a sampler and we start visiting and filtering nodes by their node
ID. The following code will generate a string based on the regular
expression contained in the `$expression` variable:

```php
<?php

$expression  = '…';
$ast = $compiler->parse($expression);
$generator = new IsotropicSampler(new Math\Sampler\Random());
echo $generator->visit($ast);
```

We are going to change the value of `$expression` step by step until
having `ab(c|d){2,4}e?`.

### Case of `#expression`

A node of type `#expression` has only one child. Thus, we simply return
the computation of this node:

```php
<?php

case '#expression':
    return $element->getChild(0)->accept($this, $handle, $eldnah);
  break;
```

### Case of `token`

We consider only one type of token for now: `literal`. A literal can
contain an escaped character, can be a single character or can be `.`
(which means everything). We consider only a single character for this
example (spoil: the whole visitor already exists). Thus:

```php
<?php

case 'token':
    return $element->getValueValue();
  break;
```

Here, with `$expression = 'a';` we get the string `a`.

### Case of `#concatenation`

A concatenation is just the computation of all children joined in a
single piece of string. Thus:

```php
<?php

case '#concatenation':
    $out = null;

    foreach($element->getChildren() as $child)
        $out .= $child->accept($this, $handle, $eldnah);

    return $out;
  break;
```

At this step, with `$expression = 'ab';` we get the string `ab`. Totally
crazy.

### Case of `#alternation`

An alternation is a choice between several children. All we have to do
is to select a child based on the probability given above. The number of
children for the current node can be known thanks to the
`getChildrenNumber` method. We are also going to use the sampler of
integers. Thus:

```php
<?php

case '#alternation':
    $childIndex = $this->_sampler->getInteger(
        0,
        $element->getChildrenNumber() - 1
    );

    return $element->getChild($childIndex)
                   ->accept($this, $handle, $eldnah);
  break;
```

Now, with `$expression = 'ab(c|d)';` we get the strings `abc` or `abd`
at random. Try several times to see by yourself.

### Case of `#quantification`

A quantification is an alternation of concatenations. Indeed, `e{2,4}`
is strictly equivalent to `ee|eee|eeee`. We have only two
quantifications in our example: `?` and `{_x_,_y_}`. We are going to
find the value for `_x_` and `_y_` and then choose at random between
these bounds. Let's go:

```php
<?php

case '#quantification':
    $out = null;
    $x = 0;
    $y = 0;

    // Filter the type of quantification.
    switch($element->getChild(1)->getValueToken()) {
        // ?
        case 'zero_or_one':
            $y = 1;
          break;

        // {x,y}
        case 'n_to_m':
            $xy = explode(
                ',',
                trim($element->getChild(1)->getValueValue(), '{}')
            );
            $x  = (int) trim($xy[0]);
            $y  = (int) trim($xy[1]);
          break;
    }

    // Choose the number of repetitions.
    $max = $this->_sampler->getInteger($x, $y);

    // Concatenate.
    for($i = 0; $i < $max; ++$i) {
        $out .= $element->getChild(0)->accept($this, $handle, $eldnah);
    }

    return $out;
  break;
```

Finally, with `$expression = 'ab(c|d){2,4}e?';` we can have the
following strings: `abdcce`, `abdc`, `abddcd`, `abcde` etc. Nice isn't
it? Want more?

```php
<?php

for($i = 0; $i < 42; ++$i) {
    echo $generator->visit($ast), "\n";
}

/**
 * Could output:
 *     abdce
 *     abdcc
 *     abcdde
 *     abcdcd
 *     abcde
 *     abcc
 *     abddcde
 *     abddcce
 *     abcde
 *     abcc
 *     abdcce
 *     abcde
 *     abdce
 *     abdd
 *     abcdce
 *     abccd
 *     abdcdd
 *     abcdcce
 *     abcce
 *     abddc
 */
```

## Performance

This is difficult to give numbers because it depends of a lot of parameters:
your machine configuration, the PHP VM, if other programs run etc. But I have
generated 1 million strings in less than 25 seconds on my machine (an old
MacBook Pro), which is pretty reasonable.

## Conclusion and surprise

So, yes, now we know how to generate strings based on regular
expressions! Supporting all the PCRE format is difficult. That's why the
[`Hoa\Regex` library](https://github.com/hoaproject/Regex) provides
the `Hoa\Regex\Visitor\Isotropic` class that is a more advanced visitor.
This latter supports classes, negative classes, ranges, all
quantifications, all kinds of literals (characters, escaped characters,
types of characters —`\w`, `\d`, `\h`…—) etc. Consequently, all you have
to do is:

```php
<?php

use Hoa\Regex;

// …
$generator = new Regex\Visitor\Isotropic(new Math\Sampler\Random());
echo $generator->visit($ast);
```

This algorithm is used in
[Praspel](https://github.com/hoaproject/Praspel), a
specification language I have designed during my PhD thesis. More
specifically, this algorithm is used inside realistic domains. I am not
going to explain it today but it allows me to introduce the “surprise”.

### Generate strings based on regular expressions in atoum

[atoum](http://atoum.org/) is an awesome unit test framework. You can
use the [`Atoum\PraspelExtension`
extension](https://github.com/hoaproject/Contributions-Atoum-PraspelExtension)
to use Praspel and therefore realistic domains inside atoum. You can use
realistic domains to validate **and** to generate data, they are
designed for that. Obviously, we can use the `Regex` realistic domain.
This extension provides several features including `sample`,
`sampleMany` and `predicate` to respectively generate one datum,
generate many data and validate a datum based on a realistic domain. To
declare a regular expression, we must write:

```php
<?php

$regex = $this->realdom->regex('/ab(c|d){2,4}e?/');
```

And to generate a datum, all we have to do is:

```php
<?php

$datum = $this->sample($regex);
```

For instance, imagine you are writing a test called `test_mail` and you
need an email address:

```php
<?php

public function test_mail() {
    $this
        ->given(
            $regex   = $this->realdom->regex('/[\w\-_]+(\.[\w\-\_]+)*@\w\.(net|org)/'),
            $address = $this->sample($regex),
            $mailer  = new \Mock\Mailer(…),
        )
        ->when($mailer->sendTo($address))
        ->then
            ->…
}
```

Easy to read, fast to execute and help to focus on the logic of the test
instead of test data (also known as fixtures). Note that most of the
time the regular expressions are already in the code (maybe as
constants). It is therefore easier to write and to maintain the tests.

I hope you enjoyed this first part of the series :-)! This work has been
published in the International Conference on Software Testing,
Verification and Validation: [Grammar-Based Testing using Realistic
Domains in PHP](https://hal.science/hal-00931662/file/EDGB12.pdf).
