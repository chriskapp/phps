<?php

namespace Foo\Foo;

use Test\Test;
use Test\Foo as FooBar;

class Bar extends Foo implements Bar, \Bar\Foo, Bar\Foo
{
    /**
     * @var string
     */
    public $prop1 = 'bar';

    /**
     * @var Test
     */
    protected $prop2;

    /**
     * @var FooBar
     */
    private $prop3;

    /**
     * @var \Test\Bar
     */
    private $prop4;

    /**
     * @var int
     */
    public static $prop5;

    /**
     * @return int
     */
    public function method1($arg1, array $arg2, $arg3 = 'foo')
    {
    }

    /**
     * @return Test
     */
    protected function method2($arg1, &$arg2)
    {
    }

    /**
     * @return FooBar
     */
    private function method3()
    {
    }

    /**
     * @return \Test\Bar
     */
    private function method4()
    {
    }
}

