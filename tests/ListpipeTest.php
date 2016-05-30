<?php

use Wizory\Listpipe;

class ListpipeTest extends PHPUnit_Framework_TestCase {

    protected $listpipe;

    protected function setUp() {
        # create a new listpipe object before every test
        $this->listpipe = new Listpipe();
    }
    
    public function testNothing() {
        # is an empty string empty?  does it make the sound of one hand clapping?
        $this->assertEmpty('');
    }

    public function testEmptyGetDraftArgs() {
        $result = $this->listpipe->get_draft('', '', '');
        $this->assertEquals('fail', $result);

        $result = $this->listpipe->get_draft('asdf', '', '');
        $this->assertEquals('fail', $result);

        $result = $this->listpipe->get_draft('', 'asdf', '');
        $this->assertEquals('fail', $result);

        $result = $this->listpipe->get_draft('', '', 'asdf');
        $this->assertEquals('fail', $result);
    }

    public function testGetDraftDefaultApproveType() {
        $this->listpipe->get_draft('foo', 'bar', 'baz');

        $this->assertFalse($this->listpipe->is_draft());
    }

    public function testGetDraftExplicitApproveType() {
        $this->listpipe->get_draft('foo', 'bar', 'baz', 'draft');

        $this->assertTrue($this->listpipe->is_draft());
    }

    # TODO add tests for debug mode



}