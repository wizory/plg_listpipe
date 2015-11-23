<?php

use Wizory\Listpipe;

class ListpipeTest extends PHPUnit_Framework_TestCase {
    public function testNothing() {

        // is an empty string empty?  does it make the sound of one hand clapping?
        $this->assertEmpty('');
    }

    public function testClassInstantiation() {
        $listpipe = new Listpipe();

        $this->assertEquals($listpipe->test(), 'foo');
    }
}