<?php

namespace Foo\Foo;

use Test\Test;
use Test\Foo as FooBar;

class Bar extends Foo implements Bar, \Bar\Foo, Bar\Foo
{
    /**
     * @return int
     */
    public function test1($arg1, array $arg2, $arg3 = 'foo')
    {
    }

    /**
     * @return Test
     */
    protected function test2($arg1, &$arg2)
    {
    }

    /**
     * @return FooBar
     */
    private function test3()
    {
    }

    /**
     * @return \Test\Bar
     */
    private function test4()
    {
    }
}
