<?php

use Wizory\Listpipe;
use Wizory\JoomlaCms;

class ListpipeTest extends PHPUnit_Framework_TestCase {

    protected $listpipe;

    protected function setUp() {
        $cms = new JoomlaCms();
        # create a new listpipe object before every test
        $this->listpipe = new Listpipe($cms);
    }
    
    public function testNothing() {
        # is an empty string empty?  does it make the sound of one hand clapping?
        $this->assertEmpty('');
    }

    // getDraft
    public function testEmptyGetDraftArgs() {
        $result = $this->listpipe->getDraft(null);
        $this->assertEquals('fail', $result);

        $result = $this->listpipe->getDraft('asdf');
        $this->assertEquals('fail', $result);
    }

    public function testGetDraftDefaultApproveType() {
        $this->listpipe->getDraft('foo', 'bar', 'baz');

        $this->assertFalse($this->listpipe->isDraft());
    }

    public function testGetDraftExplicitApproveType() {
        $request = [ 'action' => 'GetDraft', 'DraftKey' => 'foo', 'ApprovalKey' => 'bar', 'BlogPostingID' => '42',
            'ApproveType' => 'draft' ];

        $this->listpipe->getDraft($request);

        $this->assertTrue($this->listpipe->isDraft());
    }

    // publishDraft
    public function testEmptyPublishDraftArgs() {
        $result = $this->listpipe->publishDraft(null);

        $this->assertEquals('fail', $result);
    }

    public function testGoodPublishDraftArgs() {
        $request = [ 'pid' => '123', 'key' => '456'];

        $result = $this->listpipe->publishDraft($request);

        $this->assertEquals('success', $result);
    }

    // handleRequest
    public function testHandleBadRequest() {
        $request = [ 'action' => 'foo', 'DraftKey' => 'foo', 'ApprovalKey' => 'bar', 'BlogPostingID' => '42',
            'ApproveType' => 'draft' ];
        $result = $this->listpipe->handleRequest(null, $request);

        $this->assertEquals('fail', $result);
    }

    public function testHandleGoodRequest() {
        $request = [ 'action' => 'GetDraft', 'DraftKey' => 'foo', 'ApprovalKey' => 'bar', 'BlogPostingID' => '42' ];

        $result = $this->listpipe->handleRequest(null, $request);

        $this->assertEquals('success', $result);
    }

    # TODO add tests for debug mode



}