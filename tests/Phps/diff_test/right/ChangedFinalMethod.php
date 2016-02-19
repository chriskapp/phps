<?php

namespace Foo;

abstract class ChangedFinalMethod
{
    public function test1()
    {
    }

    protected function test2()
    {
    }

    final public function test3()
    {
    }
}
