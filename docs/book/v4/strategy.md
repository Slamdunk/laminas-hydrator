# Introduction

You can compose `Laminas\Hydrator\Strategy\StrategyInterface` instances in any of
the hydrators to manipulate the way they behave on `extract()` and `hydrate()`
for specific key/value pairs. The interface offers the following definitions:

```php
namespace Laminas\Hydrator\Strategy;

interface StrategyInterface
{
    /**
     * Converts the given value so that it can be extracted by the hydrator.
     *
     * @param  mixed       $value The original value.
     * @param  null|object $object (optional) The original object for context.
     * @return mixed       Returns the value that should be extracted.
     */
    public function extract($value, ?object $object = null);

    /**
     * Converts the given value so that it can be hydrated by the hydrator.
     *
     * @param  mixed      $value The original value.
     * @param  null|array $data (optional) The original data for context.
     * @return mixed      Returns the value that should be hydrated.
     */
    public function hydrate($value, ?array $data = null);
}
```

This interface is similar to what the `Laminas\Hydrator\ExtractionInterface` and
`Laminas\Hydrator\HydrationInterface` provide; the reason is that strategies
provide a proxy implementation for `hydrate()` and `extract()` on individual
values. For this reason, their return types are listed as mixed, versus as
`array` and `object`, respectively.

## Adding strategies to the hydrators

This package provides the interface `Laminas\Hydrator\Strategy\StrategyEnabledInterface`.
Hydrators can implement this interface, and then call on its `getStrategy()`
method in order to extract or hydrate individual values. The interface has the
following definition:

```php
namespace Laminas\Hydrator\Strategy;

interface StrategyEnabledInterface
{
    /**
     * Adds the given strategy under the given name.
     */
    public function addStrategy(string $name, StrategyInterface $strategy) : void;

    /**
     * Gets the strategy with the given name.
     */
    public function getStrategy(string $name) : StrategyInterface;

    /**
     * Checks if the strategy with the given name exists.
     */
    public function hasStrategy(string $name) : bool;

    /**
     * Removes the strategy with the given name.
     */
    public function removeStrategy(string $name) : void;
}
```

We provide a default implementation of the interface as part of
`Laminas\Hydrator\AbstractHydrator`; it uses an array property to store and
retrieve strategies by name when extracting and hydrating values. Since all
shipped hydrators are based on `AbstractHydrator`, they share these
capabilities.

Additionally, the functionality that consumes strategies within
`AbstractHydrator` also contains checks if a naming strategy is composed, and,
if present, will use it to translate the property name prior to looking up a
  strategy for it.

## Available implementations

### Laminas\\Hydrator\\Strategy\\BackedEnumStrategy

**PHP 8.1+** This strategy coverts scalar values into [Backed Enums](https://www.php.net/manual/en/language.enumerations.backed.php)
and visa versa:

```php
enum Direction: string
{
    case Left  = 'left';
    case Right = 'right';
}

$enumStrategy = new Laminas\Hydrator\Strategy\BackedEnumStrategy(Direction::class);

$case = $enumStrategy->hydrate('right', null);
// enum Direction::Right : string("right");
$direction = $enumStrategy->extract(Direction::Left);
// string(4) "left"
```

### Laminas\\Hydrator\\Strategy\\BooleanStrategy

This strategy converts values into booleans and vice versa. It expects two
arguments at the constructor, which are used to define value maps for `true` and
`false`.

The arguments could be strings:

```php
$boolStrategy = new Laminas\Hydrator\Strategy\BooleanStrategy('1', '0');
```

or integers:

```php
$boolStrategy = new Laminas\Hydrator\Strategy\BooleanStrategy(1, 0);
```

The main difference from [ScalarTypeStrategy](#laminashydratorstrategyscalartypestrategy)
is extracting booleans back to arguments given at the constructor.

### Laminas\\Hydrator\\Strategy\\ClosureStrategy

This is a strategy that allows you to pass in options for:

- `hydrate`, a callback to be called when hydrating a value, and
- `extract`, a callback to be called when extracting a value.

### Laminas\\Hydrator\\Strategy\\DateTimeFormatterStrategy

`DateTimeFormatterStrategy` provides bidirectional conversion between strings
and DateTime instances. The input and output formats can be provided as
constructor arguments.

The strategy allows `DateTime` formats that use `!` to prepend the format, or
`|` or `+` to append it; these ensure that, during hydration, the new `DateTime`
instance created will set the time element accordingly. As a specific example,
`Y-m-d|` will drop the time component, ensuring comparisons are based on a
midnight time value.

Starting in version 3.0, the constructor defines a third, optional argument,
`$dateTimeFallback`.  If enabled and hydration fails, the given string is parsed
by the `DateTime` constructor, as demonstrated below:

```php
// Previous behavior:
$strategy = new Laminas\Hydrator\Strategy\DateTimeFormatterStrategy('Y-m-d H:i:s.uP');
$hydrated1 = $strategy->hydrate('2016-03-04 10:29:40.123456+01'); // Format is the same; returns DateTime instance
$hydrated2 = $strategy->hydrate('2016-03-04 10:29:40+01');        // Format is different; value is not hydrated

// Using new $dateTimeFallback flag; both values are hydrated:
$strategy = new Laminas\Hydrator\Strategy\DateTimeFormatterStrategy('Y-m-d H:i:s.uP', null, true);
$hydrated1 = $strategy->hydrate('2016-03-04 10:29:40.123456+01');
$hydrated2 = $strategy->hydrate('2016-03-04 10:29:40+01');
```

### Laminas\\Hydrator\\Strategy\\DefaultStrategy

The `DefaultStrategy` simply proxies everything through, without performing any
conversion of values.

### Laminas\\Hydrator\\Strategy\\ExplodeStrategy

This strategy is a wrapper around PHP's `implode()` and `explode()` functions.
The delimiter and a limit can be provided to the constructor; the limit will
only be used for `extract` operations.

### Laminas\\Hydrator\\Strategy\\NullableStrategy

- Since 4.1.0

This strategy acts as a decorator around another strategy, allowing extraction and hydration of nullable values.
The constructor accepts two arguments: the strategy to decorate, and a boolean flag indicating whether or not to treat empty values as `null`.
By default, the flag is `false`, indicating only `null` values should be treated as `null`.

Usage of this strategy also ensures a value is extracted or hydrated when it is `null`, instead of being dropped from the representation.

### Laminas\\Hydrator\\Strategy\\ScalarTypeStrategy

> Available since version 4.2.0

This strategy allows extraction and hydration of the scalar types `int`, `float`, `string`, and `bool`.
The constructor accepts one argument, one of the constants:

- `Laminas\Hydrator\Strategy\ScalarTypeStrategy::TYPE_INT` ("int")
- `Laminas\Hydrator\Strategy\ScalarTypeStrategy::TYPE_FLOAT` ("float")
- `Laminas\Hydrator\Strategy\ScalarTypeStrategy::TYPE_STRING` ("string")
- `Laminas\Hydrator\Strategy\ScalarTypeStrategy::TYPE_BOOL` ("bool")

Alternately, you can use one of the named constructors to create the instance via the following static methods:

- `Laminas\Hydrator\Strategy\ScalarTypeStrategy::createToInt()`
- `Laminas\Hydrator\Strategy\ScalarTypeStrategy::createToFloat()`
- `Laminas\Hydrator\Strategy\ScalarTypeStrategy::createToString()`
- `Laminas\Hydrator\Strategy\ScalarTypeStrategy::createToBoolean()`

In each case, calling hydrate() will cast the `$value` provided to it to the appropriate scalar type.

### Laminas\\Hydrator\\Strategy\\StrategyChain

This strategy takes an array of `StrategyInterface` instances and iterates
over them when performing `extract()` and `hydrate()` operations. Each operates
on the return value of the previous, allowing complex operations based on
smaller, single-purpose strategies.

## Writing custom strategies

The following example, while not terribly useful, will provide you with the
basics for writing your own strategies, as well as provide ideas as to where and
when to use them. This strategy simply transforms the value for the defined key
using `str_rot13()` during both the `extract()` and `hydrate()` operations:

```php
class Rot13Strategy implements StrategyInterface
{
    public function extract($value)
    {
        return str_rot13($value);
    }

    public function hydrate($value)
    {
        return str_rot13($value);
    }
}
```

This is the example class with which we want to use the hydrator example:

```php
class Foo
{
    protected $foo = null;
    protected $bar = null;

    public function getFoo()
    {
        return $this->foo;
    }

    public function setFoo($foo)
    {
        $this->foo = $foo;
    }

    public function getBar()
    {
        return $this->bar;
    }

    public function setBar($bar)
    {
        $this->bar = $bar;
    }
}
```

Now, we'll add the `rot13` strategy to the method `getFoo()` and `setFoo($foo)`:

```php
$foo = new Foo();
$foo->setFoo('bar');
$foo->setBar('foo');

$hydrator = new ClassMethodsHydrator();
$hydrator->addStrategy('foo', new Rot13Strategy());
```

When you use the hydrator to extract an array for the object `$foo`, you'll
receive the following:

```php
$extractedArray = $hydrator->extract($foo);

// array(2) {
//     ["foo"]=>
//     string(3) "one"
//     ["bar"]=>
//     string(3) "foo"
// }
```

And when hydrating a new `Foo` instance:

```php
$hydrator->hydrate($extractedArray, $foo)

// object(Foo)#2 (2) {
//   ["foo":protected]=>
//   string(3) "bar"
//   ["bar":protected]=>
//   string(3) "foo"
// }
```
