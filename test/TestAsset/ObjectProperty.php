<?php

declare(strict_types=1);

namespace LaminasTest\Hydrator\TestAsset;

use AllowDynamicProperties;

#[AllowDynamicProperties]
class ObjectProperty
{
    /** @var string */
    public $foo;

    /** @var string */
    public $bar;

    /** @var string */
    public $blubb;

    /** @var string */
    public $quo;

    /** @var string */
    protected $quin;

    public function __construct()
    {
        $this->foo   = 'bar';
        $this->bar   = 'foo';
        $this->blubb = 'baz';
        $this->quo   = 'blubb';
        $this->quin  = 'five';
    }

    public function get(string $name): string
    {
        return $this->$name;
    }
}
