<?php

namespace Wizory;

use \Exception;

class Listpipe {

    const FAIL = 'fail';
    const OK = 'success';

    const actions = ['GetDraft', 'PublishDraft'];

    protected $is_draft;

    public function __construct(CmsInterface $cms) {
        $this->is_draft = False;
        $this->cms = $cms;
    }

    // expects config array and request array (both associative)
    public function handleRequest($config, $request) {
        switch ($request['action']) {
            case 'GetDraft': return $this->getDraft($request); break;
            case 'PublishDraft': return $this->publishDraft($request); break;
            default: return Listpipe::FAIL;
        }
    }

    public function getDraft($request) {
        try {
            $draft_key = $request['DraftKey'];
            $approval_key = $request['ApprovalKey'];
            $blog_posting_id = $request['BlogPostingID'];
            $approve_type = empty($request['ApproveType']) ? '' : $request['ApproveType'];
            $debug = empty($request['debug']) ? False : $request['debug'];
        } catch (Exception $e) {
            return Listpipe::FAIL;
        }

        // require all non-optional args to be set to non-empty values
        if (empty($draft_key) || empty($approval_key) || empty($blog_posting_id)) { return Listpipe::FAIL; }

        if ($approve_type == 'draft') {
            $this->is_draft = True;
        }

        return Listpipe::OK;
    }

    public function publishDraft($request) {
        try {
            $post_id = $request['pid'];
            $approval_key = $request['key'];
        } catch (Exception $e) {
            return Listpipe::FAIL;
        }

        // TODO validate approval key (currently *any* approval key will allow publishing

        // require all non-optional args to be set to non-empty values
        if (empty($post_id) || empty($approval_key)) { return Listpipe::FAIL; }

        if ($this->cms->publishPost($post_id)) { return Listpipe::OK; }

        return Listpipe::FAIL;
//        try {
//            $draft_key = $request['DraftKey'];
//            $approval_key = $request['ApprovalKey'];
//            $blog_posting_id = $request['BlogPostingID'];
//            $approve_type = empty($request['ApproveType']) ? '' : $request['ApproveType'];
//            $debug = empty($request['debug']) ? False : $request['debug'];
//        } catch (Exception $e) {
//            return Listpipe::FAIL;
//        }
//
//        // require all non-optional args to be set to non-empty values
//        if (empty($draft_key) || empty($approval_key) || empty($blog_posting_id)) { return Listpipe::FAIL; }
//
//        if ($approve_type == 'draft') {
//            $this->is_draft = True;
//        }
//
//        return Listpipe::OK;
    }

    public function isDraft() {
        return $this->is_draft;
    }

}
