+++
title = "Announcing the first Java library to run WebAssembly: Wasmer JNI"
date = "2020-05-13"
description = "This article presents the `wasmer-java` project: the first Java library to run WebAssembly."
[taxonomies]
keywords=["rust", "webassembly", "java", "runtime", "binding"]
[extra]
pinned = true
+++

*This is a copy of [an article I wrote for
Wasmer](https://medium.com/wasmer/announcing-the-first-java-library-to-run-webassembly-wasmer-jni-89e319d2ac7c).*

------------------------------------------------------------------------

[WebAssembly](https://webassembly.org/) is a portable binary format.
That means the same file can run anywhere.

> To uphold this bold statement, each language, platform and system must
> be able to run WebAssembly — as fast and safely as possible.

People who are familiar with Wasmer are used to this kind of
announcement! Wasmer is written in Rust, and comes with an additional
native C API. But you can use it in a lot of other languages. After
having announced libraries to use Wasmer, and thus WebAssembly, in:

- [**PHP** with the `ext/wasm`
  extension](https://github.com/wasmerio/php-ext-wasm),

- [**Python** with the `wasmer`
  library](https://github.com/wasmerio/python-ext-wasm),

- [**Ruby** with the `wasmer`
  library](https://github.com/wasmerio/ruby-ext-wasm),

- [**Go** with the `wasmer`
  library](https://github.com/wasmerio/go-ext-wasm) (see [Announcing
  the fastest WebAssembly runtime for
  Go: `wasmer`](https://medium.com/wasmer/announcing-the-fastest-webassembly-runtime-for-go-wasmer-19832d77c050),
  and even

- [**Postgres** with the `wasmer`
  library](https://github.com/wasmerio/postgres-ext-wasm) (see
  [Announcing the first Postgres extension to run
  WebAssembly](https://medium.com/wasmer/announcing-the-first-postgres-extension-to-run-webassembly-561af2cfcb1)),

- and many other contributions in
  [.NET/C#](https://github.com/migueldeicaza/WasmerSharp),
  [R](https://github.com/dirkschumacher/wasmr) and
  [Elixir](https://github.com/tessi/wasmex)…

…we are jazzed to announce that **[Wasmer has now landed in
Java](https://github.com/wasmerio/java-ext-wasm)**!

Let’s discover the Wasmer JNI library together.

## Installation

The Wasmer JNI (*Java Native Interface*) library is based on the [Wasmer
runtime](https://github.com/wasmerio/wasmer), which is written in
[Rust](https://www.rust-lang.org/), and is compiled to a shared library.
For your convenience, we produce one JAR (*Java Archive*) per
architecture and platform. By now, the following are supported,
consistently tested, and pre-packaged (available in
[Bintray](https://bintray.com/wasmer/wasmer-jni/wasmer-jni) and [Github
Releases](https://github.com/wasmerio/java-ext-wasm/releases)):

- `amd64-darwin` for macOS, x86 64bits,

- `amd64-linux` for Linux, x86 64 bits,

- `amd64-windows` for Windows, x86 64 bits.

More architectures and more platforms will be added in the near future.
If you need a specific one, [feel free to
ask](https://github.com/wasmerio/java-ext-wasm/issues/new?assignees=&labels=%F0%9F%8E%89+enhancement&template=---feature-request.md&title=)!
However, it is possible to [produce your own JAR for your own platform
and
architecture](https://github.com/wasmerio/java-ext-wasm#development).

The JAR files are named as follows:
`wasmer-jni-$(architecture)-$(os)-$(version).jar`. Thus, to include
Wasmer JNI as a dependency of your project (assuming you use
[Gradle](http://gradle.org/)), write for instance:

```
dependencies {
    implementation "org.wasmer:wasmer-jni-amd64-linux:0.2.0"
}
```

JAR are hosted on the Bintray/JCenter repository under the
`[wasmer-jni](https://bintray.com/wasmer/wasmer-jni/wasmer-jni)`
project. They are also attached to our [Github releases as
assets](https://github.com/wasmerio/java-ext-wasm/releases).

## Calling a WebAssembly function from Java

As usual, let’s start with a simple Rust program that we will compile to
WebAssembly, and then execute from Java.

```rust
#[no_mangle]
pub extern fn sum(x: i32, y: i32) -> i32 {
    x + y
}
```

After compilation to WebAssembly, we get a file like [this
one](https://github.com/wasmerio/java-ext-wasm/raw/master/examples/simple.wasm),
named `simple.wasm`.

The following Java program executes the `sum` exported function by
passing `5` and `37` as arguments:

```java
import org.wasmer.Instance;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Paths;

class SimpleExample {
    public static void main(String[] args) throws IOException {
        // Read the WebAssembly bytes.
        byte[] bytes = Files.readAllBytes(Paths.get("simple.wasm"));

        // Instantiate the WebAssembly module.
        Instance instance = new Instance(bytes);

        // Get the `sum` exported function, call it by passing 5 and 37, and get the result.
        Integer result = (Integer) instance.exports.getFunction("sum").apply(5, 37)[0];

        assert result == 42;

        instance.close();
    }
}
```

Great! We have successfully executed a Rust program, compiled to
WebAssembly, in Java. As you can see, it is pretty straightforward. The
API is very similar to the standard JavaScript API, or the other API we
have designed for PHP, Python, Go, Ruby etc.

The assiduous reader might have noticed the `[0]` in `.apply(5, 37)[0]`
pattern. A WebAssembly function can return zero to many values, and in
this case, we are reading the first one.

> Note: Java values passed to WebAssembly exported functions are
> automatically downcasted to WebAssembly values. Types are inferred at
> runtime, and casting is done automatically. Thus, a WebAssembly
> function acts as any regular Java function.

Technically, an exported function is a *functional interface* as defined
by the Java Language Specification (i.e. it is a
`[FunctionalInterface](https://docs.oracle.com/javase/8/docs/api/java/lang/FunctionalInterface.html)`).
Thus, it is possible to write the following code where `sum` is an
actual function (of kind `org.wasmer.exports.Function`):

```java
import org.wasmer.Instance;
import org.wasmer.exports.Function;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Paths;

class SimpleExample {
    public static void main(String[] args) throws IOException {
        // Read the WebAssembly bytes.
        byte[] bytes = Files.readAllBytes(Paths.get("simple.wasm"));

        // Instantiate the WebAssembly module.
        Instance instance = new Instance(bytes);

        // Declare the `sum` function, as a regular Java function.
        Function sum = instance.exports.getFunction("sum");

        // Call `sum`.
        Integer result = (Integer) sum.apply(1, 2)[0];

        assert result == 3;

        instance.close();
    }
}
```

But a WebAssembly module not only exports functions, it also exports
memory.

## Reading the memory

A WebAssembly instance has one or more linear memories, a contiguous and
byte-addressable range of memory spanning from offset 0 and extending up
to a varying memory size, represented by the `org.wasmer.Memory` class.
Let’s see how to use it. Consider the following Rust program:

```rust
#[no_mangle]
pub extern fn return_hello() -> *const u8 {
    b"Hello, World!\0".as_ptr()
}
```

The `return_hello` function returns a pointer to the statically
allocated string. The string exists in the linear memory of the
WebAssembly module. It is then possible to read it in Java:

```java
import org.wasmer.Instance;
import org.wasmer.Memory;

import java.io.IOException;
import java.nio.ByteBuffer;
import java.nio.file.Files;
import java.nio.file.Paths;

class MemoryExample {
    public static void main(String[] args) throws IOException {
        // Read the WebAssembly bytes.
        byte[] bytes = Files.readAllBytes(Paths.get("memory.wasm"));

        // Instantiate the WebAssembly module.
        Instance instance = new Instance(bytes);

        // Get a pointer to the statically allocated string returned by `return_hello`.
        Integer pointer = (Integer) instance.exports.getFunction("return_hello").apply()[0];

        // Get the exported memory named `memory`.
        Memory memory = instance.exports.getMemory("memory");

        // Get a direct byte buffer view of the WebAssembly memory.
        ByteBuffer memoryBuffer = memory.buffer();

        // Prepare the byte array that will hold the data.
        byte[] data = new byte[13];

        // Let's position the cursor, and…
        memoryBuffer.position(pointer);

        // … read!
        memoryBuffer.get(data);

        // Let's encode back to a Java string.
        String result = new String(data);

        // Hello!
        assert result.equals("Hello, World!");

        instance.close();
    }
}
```

As we can see, the `Memory` API provides a `buffer` method. It returns a
[*direct* byte
buffer](https://docs.oracle.com/javase/8/docs/api/java/nio/ByteBuffer.html)
(of kind `java.nio.ByteBuffer`) view of the memory. It’s a standard API
for any Java developer. We think it’s best to not reinvent the wheel and
use standard API as much as possible.

The WebAssembly memory is dissociated from the JVM memory, and thus from
the garbage collector.

> You can read [the Greet
> Example](https://github.com/wasmerio/java-ext-wasm/blob/master/examples/GreetExample.java)
> to see a more in-depth usage of the `Memory` API.

## More documentation

The project comes with a `Makefile`. The `make javadoc` command will
generate a traditional local Javadoc for you, in the
`build/docs/javadoc/index.html` file.

In addition, the project’s `README.md` file has an [API of
the `wasmer` library
Section](https://github.com/wasmerio/java-ext-wasm#api-of-the-wasmer-library).

Finally, the project comes with [a set of
examples](https://github.com/wasmerio/java-ext-wasm/tree/master/examples).
Use the `make run-example EXAMPLE=Simple` to run the
`SimpleExample.java` example for instance.

## Performance

WebAssembly aims at being safe, but also fast. Since Wasmer JNI is the
*first* Java library to execute WebAssembly, we can’t compare to prior
works in the Java ecosystem. However, you might know that Wasmer comes
with 3 backends: Singlepass, Cranelift and LLVM. We’ve even written an
article about it: [A WebAssembly Compiler
tale](https://medium.com/wasmer/a-webassembly-compiler-tale-9ef37aa3b537).
The Wasmer JNI library uses the Cranelift backend for the moment, which
offers the best compromise between compilation-time and execution-time.

## Credits

Asami ([d0iasm](https://twitter.com/d0iasm) on Twitter) has improved
this project during its internship at Wasmer under my guidance. She
finished the internship before the release of the Wasmer JNI project,
but she deserves credits for pushing the project forward! Good work
Asami!

This is an opportunity to remind everyone that we hire anywhere in the
world. Asami was working from Japan while I am working from Switzerland,
and the rest of the team is from US, Spain, China etc. Feel free to
contact me ([@mnt_io](https://twitter.com/mnt_io) or
[@syrusakbary](https://twitter.com/syrusakbary) on Twitter) if you want
to join us on this big adventure!

## Conclusion

Wasmer JNI is a library to execute WebAssembly directly in Java. It
embeds the WebAssembly runtime
[Wasmer](https://github.com/wasmerio/wasmer). The first releases provide
the core API with `Module`, `Instance`, and `Memory`. It comes
pre-packaged as a JAR, one per architecture and per platform.

The source code is open and hosted on Github at
[wasmerio/java-ext-wasm](https://github.com/wasmerio/java-ext-wasm).
We are constantly improving the project, so if you have feedback,
issues, or feature requests please open an issue in the repository, or
reach us on Twitter at [@wasmerio](https://twitter.com/wasmerio) or
[@mnt_io](https://twitter.com/mnt_io).

We look forward to see what you build with this!
