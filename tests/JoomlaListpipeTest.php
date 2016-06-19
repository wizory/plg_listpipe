<?php

use Wizory\Listpipe;
use Wizory\JoomlaCms;

class ListpipeTest extends PHPUnit_Framework_TestCase {

    protected $listpipe;

    protected function setUp() {
        $config = ['parent_category_id' => 23, 'user_id' => 7];

        $cms = new JoomlaCms($config);
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
    public function testEmptyPublishDraftArgs() {  // NOTE this is the listpipe "ping" (call to publish with no args)
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
        $result = $this->listpipe->handleRequest($request);

        $this->assertEquals('fail', $result);
    }

    // TODO figure out how to fake a successful content request back to listpipe
//    public function testHandleGoodRequest() {
//        $request = [ 'action' => 'GetDraft', 'DraftKey' => 'foo', 'ApprovalKey' => 'bar', 'BlogPostingID' => '42' ];
//
//        $result = $this->listpipe->handleRequest($request);
//
//        $this->assertEquals('success', $result);
//    }

    // contentIsValid
    public function testEmptyContent() {
        $content = '';

        $result = $this->listpipe->contentIsValid($content);

        $this->assertFalse($result);
    }

    public function testFailedContent() {
        $content = 'fail';

        $result = $this->listpipe->contentIsValid($content);

        $this->assertFalse($result);
    }

    public function testAdditionalFailedContent() {
        $content = 'failure is *always* an option :P';

        $result = $this->listpipe->contentIsValid($content);

        $this->assertFalse($result);
    }

    public function testGoodContentTwoElements() {
        $content = 'title{-~-}body';

        $result = $this->listpipe->contentIsValid($content);

        $this->assertTrue($result);
    }

    public function testGoodContentThreeElements() {
        $content = 'title{-~-}body{-~-}category';

        $result = $this->listpipe->contentIsValid($content);

        $this->assertTrue($result);
    }


    // processContent
    public function testEmptyProcessContent() {
        $content = 'title{-~-}body';

        $result = $this->listpipe->processContent($content);
    }

    # TODO add tests for debug mode



}