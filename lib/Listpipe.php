<?php

namespace Wizory;

use \Exception;

class Listpipe {

    const FAIL = 'fail';
    const OK = 'success';

    const LISTPIPE_API = 'http://www.listpipe.com/blogs/getContent.php?';
    const DELIMITER = '{-~-}';

    const ACTIONS = ['GetDraft', 'PublishDraft'];

    protected $is_draft;

    public function __construct(CmsInterface $cms) {
        $this->is_draft = False;
        $this->cms = $cms;
    }

    // expects config array and request array (both associative)
    public function handleRequest($request) {
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

        $content = $this->fetch(Listpipe::LISTPIPE_API
            . 'action=GetContent'
            . '&DraftKey=' . urlencode($draft_key)
            . '&BlogPostingID=' . urlencode($blog_posting_id)
        );

        if ($this->contentIsValid($content)) {
            $post = processContent($content);
        }

        # also need to check for debug and handle...(and add logging back)

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

    public function fetch($url) {
        $data = '';

        $this->cms->log("params valid, fetching data from url '$url'");

        // try fopen
        @$handle = fopen($url,"r");

        // if that didn't work, try curl
        if (empty($handle)) {
            try {
                $this->cms->log('using curl to fetch');

                $ch = curl_init();

                curl_setopt($ch,CURLOPT_URL,$url);
                curl_setopt($ch,CURLOPT_HEADER,0);
                curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);

                // attempt to fetch the data
                $data = curl_exec($ch);

                curl_close($ch);

                // if curl failed, we're out of options so bail
            } catch (Exception $e) { $this->cms->fail("curl failed: " .  $e->getMessage()); }

        // fopen worked, fetch the data
        } else {
            $this->cms->log('using fopen to fetch');
            // TODO move block size to a constant
            while (!feof($handle)) { $data .= fread($handle, 8192); }
        }

        // $data should be populated one way or another by now, so close the $handle
        @fclose($handle);

        return $data;
    }

    public function contentIsValid($content) {
        if (empty($content)) { return False; }

        if (substr($content, 0, 4) == 'fail') { return False; }

        return True;
    }

    public function processContent($content) {
        $this->cms->log("processing article data '" . print_r($content,true) . "'");

        list($post['title'], $post['body'], $post['category']) = explode(Listpipe::DELIMITER, $content);

        // TODO get default category name from $this->cms (might be different in WP, etc.)
        if (empty($post['category'])) { $post['category'] = 'Uncategorized'; }

        $this->cms->findOrCreateCategory($post['category']);

    }

}
