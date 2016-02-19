<?php

namespace Foo;

abstract class ChangedAbstractMethod
{
    abstract public function test1();

    abstract protected function test2();

    public function test3()
    {
    }
}
