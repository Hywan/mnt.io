+++
title = "Finite-State Machine as a Type System illustrated with a store product"
date = "2017-08-09"
+++

Hello fellow coders!

In this article, I would like to talk about how to implement a
[Finite-State
Machine](https://en.wikipedia.org/wiki/Finite-state_machine) (FSM) with
the PHP type system. The example is a store product (in an e-commerce
solution for instance), something we are likely to meet once in our
lifetime. Our goal is to simply **avoid impossible states and
transitions**.

I am in deep love with [Type
theory](https://en.wikipedia.org/wiki/Type_theory), however I will try
to keep the formulas away from this article to focus on the code.
Moreover, you might be aware that the PHP *runtime* type system is
somewhat very permissive and “poor” (this is not a formal definition),
hopefully some tricks can help us to express nice constraints.

## The Product FSM

A product in a store might have the following states:

- Active: Can be purchased,
- Inactive: Has been cancelled or discontinued (a discontinued product
  can no longer be purchased),
- Purchased and renewable,
- Purchased and not renewable,
- Purchased and cancellable.

The transitions between these states can be viewed as a [Finite-State
Machine](https://en.wikipedia.org/wiki/Finite-state_machine) (FSM).

\[caption id="attachment_919" align="aligncenter"
width="294"\]![AyxEp2j8B4hCLIZEI4p9By_CIrT8IymfJkNYYjQALT3LjLDmv784qquALWfA1QL5oHc9nQbAN4u8mQBKlDHo9QWoPv18Vbvogcv-Mfe2GZrKWmj8EZaHA9-ZnEMC8GG0](images/ayxep2j8b4hclizei4p9by_cirt8iymfjknyyjqalt3ljldmv784qqualwfa1ql5ohc9nqban4u8mqbkldho9qwopv18vbvogcv-mfe2gzrkwmj8ezaha9-znemc8gg0.png)
Product FSM ([editable
source](http://www.plantuml.com/plantuml/uml/POv92eKm44JtVOecIo4NS25Ejn0NJUnynTYHjEBr7H1S_5N1grTGfMwKjdq9eFCaAoLQK4SQuZ793pq5PO0kwz7Q0YkedgmnHxFkoOsaRA3-O6BXmxnQSvCeZctq7Z__7RHtxUZgWu7c7q79CbpFJW00)).\[/caption\]

We read this graph as: A product is in the state `A`. If the `purchase`
action is called, then it transitions to the state `B`. If the
`once-off purchase` action is called, then it transitions to the state
`C`. From the state `B`, if the `renew` action is called, it remains in
the same state. If the `cancel` action is called, it transitions to the
`D` state. Same for the `C` to `D` states.

Our goal is to respect this FSM. Invalid actions must be impossible to
do.

## Finite-State Machine as a Type System

Having a FSM is a good thing to define the states and the transitions
between them: It is formal and clear. However, it is tested at runtime,
not at compile-time, i.e. `if` statements are required to test if the
state of a product can transition into another state, or else throw an
exception, and this is decided at runtime. Note that PHP does not really
have a compile-time because it is an online compiler (learn more by
reading [Tagua VM, a safe PHP virtual
machine](https://speakerdeck.com/hywan/tagua-vm-a-safe-php-virtual-machine),
at slide 29). Our goal is to prevent illegal/invalid states at
parse-/compile-time so that the PHP virtual machine, IDE or static
analysis tools can prove the state of a product without executing PHP
code.

Why is this important? Imagine that we decide to change a product to be
once-off purchasable instead of purchasable, then we can no longer renew
it. We replace an interface on this product, and boom, the IDE tells us
that the code is broken in *x* places. It **detects impossible scenarios
ahead of code execution**.

No more talking. Here is the code.

### The mighty product

    /**
     * A product.
     */
    interface Product { }

A product is a class implementing the `Product` interface. It allows to
type a generic product, with no regards about its state.

### Active and inactive

    /**
     * A product that is active.
     */
    interface Active extends Product
    {
        public function getProduct(): self;
    }

    /**
     * A product that has been cancelled, or not in stock.
     */
    interface Inactive extends Product
    {
        public function getProduct(): self;
    }

The `Active` and `Inactive` interfaces are useful to create constraints
such as:

- A product can be purchased only if it is active, and
- A product is inactive if and only if it has been cancelled,
- To finally conclude that an inactive product can no longer be
  purchased, nor renewed, nor cancelled.

Basically, it defines the axiom (initial state) and the final states of
our FSM.

The `getProduct(): self` trick will make sense later. It helps to
express the following constraint: “A valid product cannot be invalid,
and vice-versa”, i.e. both interfaces cannot be implemented by the same
value.

### Purchase, renew, and cancel

    /**
     * A product that can be purchased.
     */
    interface Purchasable extends Active
    {
        public function purchase(): Renewable;
    }

Only an active product can be purchased. The action is `purchase` and it
generates a product that is renewable. `purchase` transitions from the
state `A` to `B` (regarding the graph above).

    /**
     * A product that can be cancelled.
     */
    interface Cancellable extends Active
    {
        public function cancel(): Inactive;
    }

Only an active product can be cancelled. The action is `cancel` and it
generates an inactive product, so it transitions from the state `B` to
`D`.

    /**
     * A product that can be renewed.
     */
    interface Renewable extends Cancellable
    {
        public function renew(): self;
    }

A renewable product is also cancellable. The action is `renew` and this
is a reflexive transition from the state `B` to `B`.

    /**
     * A product that can be once-off purchased, i.e. it can be purchased but not
     * renewed.
     */
    interface PurchasableOnce extends Active
    {
        public function purchase(): Cancellable;
    }

Finally, a once-off purchasable product has one action: `purchase` that
produces a `Cancellable` product, and it transitions from the state `A`
to `C`.

### Take a breath

\[caption id="attachment_918" align="aligncenter"
width="487"\]![AyxEp2j8B4hCLIZEI4p9By_CIrT8IymfJkNYAYv9B4bLS4mkoInBLQZcKW22QArO1LrTEmL7CCyHp7PIi59G2YWjIiv8B4vCoacriYg0S5ALmAgS4Ag2KlDIoo5gYa1C9IHZdD6CySzBHZ6g5kOWpxn4P2T1Z7S1wNPE1Eh9oO5Oa0pcG6nm9g2c5W](images/ayxep2j8b4hclizei4p9by_cirt8iymfjknyayv9b4bls4mkoinblqzckw22qaro1lrteml7ccyhp7pii59g2ywjiiv8b4vcoacriyg0s5almags4ag2kldioo5gya1c9ihzdd6cyszbhz6g5kowpxn4p2t1z7s1wnpe1eh9oo5oa0pcg6nm9g2c5w.png)
Detailed product FSM ([editable
source](http://www.plantuml.com/plantuml/uml/XP2n3i8m34JtV8NL4Qa_a04r3GiJY1KncDJGY3HKIKe7r7ydJOGeB7ZoISzt9rS-e0j3PvY_QTkZmmvyYyrzrFOA5pIU68jC810gw0V1aq6Su-e4PRa6aPH8UmqSYltWL8iUpuOuxxEW8b7rXpgGfJ4pRbwBXOtyiTbPzMKrlGjjBIuhPVlDV2qntXYJ0rGIEK7-OIPMaMtYEru0)).\[/caption\]

So far we have defined interfaces, but the FSM is not implemented yet.
**Interfaces only define constraints** in our type system. An interface
provides a constraint but also **defines type capabilities**: **What
operations can be performed on a value implementing a particular
interface**.

### SecretProduct

Let's consider the `SecretProduct` as a new super secret product that
will revolutionise our store:

    /**
     * The `SecretProduct` class is:
     *
     *   * A product,
     *   * Active,
     *   * Purchasable.
     *
     * Note that in this implementation, the `SecretProduct` instance is mutable: Every
     * action happens on the same `SecretProduct` instance. It makes sense because
     * having 2 instances of the same product with different states might be error-prone
     * in most scenarios.
     */
    class SecretProduct implements Active, Purchasable
    {
        public function getProduct(): Active
        {
            return $this;
        }

        /**
         * Purchase the product will return an active product that is renewable,
         * and also cancellable.
         */
        public function purchase(): Renewable
        {
            return new class ($this->getProduct()) implements Renewable {
                protected $product;

                public function __construct(SecretProduct $product)
                {
                    $this->product = $product;
                    // Do the purchase.
                }

                public function getProduct(): Active
                {
                    return $this->product;
                }

                public function renew(): Renewable
                {
                    // Do the renew.
                    return $this;
                }

                public function cancel(): Inactive
                {
                    return new class ($this->getProduct()) implements Inactive {
                        protected $product;

                        public function __construct(SecretProduct $product)
                        {
                            $this->product = $product;
                            // Do the cancel.
                        }

                        public function getProduct(): Inactive
                        {
                            return $this->product;
                        }
                    };
                }
            };
        }
    }

The `SecretProduct` is a product that is active and purchasable. PHP
verifies that the `Active::getProduct` method is implemented, and that
the `Purchasable::purchase` method is implemented too.

When this latter is called, it returns an object implementing the
`Renewable` interface (which is also a cancellable active product). The
object in this context is an instance of an anonymous class implementing
the `Renewable` interface. So the `Active::getProduct`,
`Renewable::renew`, and `Cancellable::cancel` methods must be
implemented.

Having an anonymous class is not required at all, this is just simpler
for the example. A named class may even be better from the testing point
of view.

Note that:

- The real purchase action is performed in the constructor of the
  anonymous class: This is not a hard rule, this is just convenient; it
  can be done in the method before returning the new instance,
- The real renew action is performed in the `renew` method before
  returning `$this`,
- And the real cancel action is performed in… we have to dig a little
  bit more (the principle is exactly the same though):
  - The `Cancellable::cancel` method must return an object implementing
    the `Inactive` interface.
  - It generates an instance of an anonymous class implementing the
    `Inactive` interface, and the real cancel action is done in the
    constructor.

### Assert possible and impossible actions

Let's try some valid and invalid actions. Those followings are
**possible actions**:

    assert((new SecretProduct())->purchase()                             instanceof Product);
    assert((new SecretProduct())->purchase()->renew()                    instanceof Product);
    assert((new SecretProduct())->purchase()->cancel()                   instanceof Product);
    assert((new SecretProduct())->purchase()->renew()->renew()->cancel() instanceof Product);

It is possible to purchase a product, then renew it zero or many times,
and finally to cancel it. It matches the FSM!

Those followings are **impossible actions**:

    (new SecretProduct())->renew();
    (new SecretProduct())->cancel();
    (new SecretProduct())->purchase()->cancel()->purchase();
    (new SecretProduct())->purchase()->cancel()->renew();
    (new SecretProduct())->purchase()->purchase();
    (new SecretProduct())->purchase()->cancel()->cancel();

It is impossible:

- To renew or to cancel a product that has not been purchased,
- To purchase or renew a product that has been cancelled,
- To purchase a product more than once,
- To cancel a product more than once.

Those followings are **impossible implementations**:

    class SecretProduct implements Active, Purchasable, PurchasableOnce { }

A product cannot be purchasable and once-off purchasable at the same
time, because `Purchasable::purchase` is not compatible with
`PurchasableOnce::purchase`.

    class SecretProduct implements Inactive, Cancellable { }

An inactive product cannot be purchased nor renewed nor cancelled
because `Active::getProduct` and `Inactive::getProduct` are not
compatible.

Wow, that's great garantees isn't it? **PHP will raise fatal errors for
impossible actions or impossible states**. No warnings or notices: Fatal
errors. Most of them are correctly inferred by IDE, so… follow the red
crosses in your IDE.

## Restoring a product

One major thing is missing: The state of a product is stored in the
database. When loading the product, we must be able to get an instance
of a product at its previous state. To avoid repeating code, we will use
traits. Rebuilding the state of a product is “just” (it really is) a
composition of traits.

Note: In these examples, we are using anonymous classes and traits. It
is possible to achieve the same behavior with final named classes. Also
we are using a repository, which is convenient for this article, but not
necessarily the best solution.

### Repository

The following `ProductRepository\load` function is just here to give you
an idea of how it works.

    namespace ProductRepository;

    function load(int $id, string $state): Product
    {
        // Load the product from the database with `$id`.
        //
        // The states can be `Renewable`, `Cancellable`, or `Inactive` (check
        // the FSM to double-check). Products that have not been purchased
        // are not in the database.

        // Fake minimal active product.
        $product = new class implements Active {
            public function getProduct(): Active {
                return $this;
            }
        };

        switch ($state) {
            // State B.
            case Renewable::class:
                return new class ($product) implements Renewable {
                    use ActiveProduct;
                    use RenewableProduct;
                    use CancellableProduct;
                };

            // State C.
            case Cancellable::class:
                return new class ($product) implements Cancellable {
                    use ActiveProduct;
                    use CancellableProduct;
                };

            // State D.
            case Inactive::class:
                return new class ($product) implements Inactive {
                    use InactiveProduct;
                };

            // Invalid state.
            default:
                throw new RuntimeException('Invalid product state.');
        }
    }

### Traits

The code must look familiar because this is just a split from the
`SecretProduct` implementation.

    trait ActiveProduct
    {
        protected $product;

        public function __construct(Product $product)
        {
            $this->product = $product;
        }

        public function getProduct(): Active
        {
            return $this->product;
        }
    }

    trait RenewableProduct
    {
        public function renew(): Renewable
        {
            // Do the renew.
            return $this;
        }
    }

    trait CancellableProduct
    {
        public function cancel(): Inactive
        {
            return new class ($this->getProduct()) implements Inactive {
                protected $product;

                public function __construct(Product $product)
                {
                    $this->product = $product;
                    // Do the cancel.
                }

                public function getProduct(): Inactive
                {
                    return $this->product;
                }
            };
        }
    }

    trait InactiveProduct
    {
        protected $product;

        public function __construct(Product $product)
        {
            $this->product = $product;
        }

        public function getProduct(): Inactive
        {
            return $this->product;
        }
    }

### Assert possible and impossible actions

The **possible actions** are:

    $product = ProductRepository\load(42, Renewable::class);

    assert($product           instanceof Product);
    assert($product->renew()  instanceof Product);
    assert($product->cancel() instanceof Product);

Product 42 is assumed to be in the state `B` (`Renewable::class`), so we
can renew and cancel it.

Those followings are **impossible actions**:

    $product = ProductRepository\load(42, Renewable::class);

    $product->purchase();
    $product->cancel()->cancel();

It is impossible to purchase the product 42 because it is in state `B`,
so it has already been purchased. It is impossible to cancel a product
twice.

**Same garantees apply here**!

## Conclusion

It is possible to re-implement `SecretProduct` with the traits we have
defined for the `ProductRepository`, or to use named classes. I let this
as an easy wrap up exercise for the reader.

The real conclusion is that we have **successfully implemented the
Finite-State Machine of a product with a Type System**. It is impossible
to have an invalid implementation that violates the constraints, such as
an inactive renewable product. PHP detects it immediately at runtime.
Invalid actions are also impossible, such as purchasing a product twice,
or renewing a once-off purchased product. It is also detected by PHP.

All violations take the form of PHP fatal errors.

The product repository is an example of how to restore a product at a
particular state, with the help of the defined interfaces, and new small
and simple traits.

## One more thing

It is possible to integrate product categories in this type system (like
bundles). It is more complex, but possible.

I would highly recommend these following readings:

- [What to know before debating type
  systems](http://blogs.perl.org/users/ovid/2010/08/what-to-know-before-debating-type-systems.html)
  to have an overview of different systems,
- [Rust's Type System is
  Turing-Complete](https://sdleffler.github.io/RustTypeSystemTuringComplete/)
  to see how powerful a type system can be,
- [Fear Not the Machine of
  State!](https://speakerdeck.com/willroth/fear-not-the-machine-of-state)
  to see how to integrate an FSM into an object without using a type
  system.

I would like to particularly emphasize a paragraph from the first
article:

> So what is a type? The only true definition is this: a type is a
> **label** used by a type system to **prove** some property of the
> **program's behavior**. If the type checker can assign types to the
> whole program, then it succeeds in its proof; otherwise it fails and
> points out why it failed.

Seeing types as labels is a very smart way of approaching them.

I would like to thanks [Marco Pivetta](https://ocramius.github.io/) for
the reviews!
