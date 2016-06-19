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
        $this->is_draft = false;
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
            $debug = empty($request['debug']) ? false : $request['debug'];
        } catch (Exception $e) {
            return Listpipe::FAIL;
        }

        // require all non-optional args to be set to non-empty values
        if (empty($draft_key) || empty($approval_key) || empty($blog_posting_id)) { return Listpipe::FAIL; }

        if ($approve_type == 'draft') {
            $this->is_draft = True;
        }

        $content = $this->get(Listpipe::LISTPIPE_API
            . 'action=GetContent'
            . '&DraftKey=' . urlencode($draft_key)
            . '&BlogPostingID=' . urlencode($blog_posting_id)
        );

        if (! $this->contentIsValid($content)) {
            return Listpipe::FAIL; // TODO print error message or something
        }

        $post = processContent($content);

        // send "confirmation ping"
        $this->get(Listpipe::LISTPIPE_API
            . 'action=ConfirmContent'
            . '&DraftKey=' . urlencode($draft_key)
            . '&BlogPostingID=' . urlencode($blog_posting_id)
            . '&PostID=' . urlencode($post['id'])
        );

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
//            $debug = empty($request['debug']) ? false : $request['debug'];
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

    public function get($url) {
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

    // TODO log why content is not valid in various cases
    public function contentIsValid($content) {
        if (empty($content)) { return false; }

        if (substr($content, 0, 4) == 'fail') { return false; }

        if (count(explode(Listpipe::DELIMITER, $content)) < 2) { return false; }

        return True;
    }

    public function processContent($content) {
        $this->cms->log("processing article data '" . print_r($content,true) . "'");

        $content_elements = explode(Listpipe::DELIMITER, $content);

        $post['title'] = $content_elements[0];
        $post['body'] = $content_elements[1];
        // TODO get default category name from $this->cms (might be different in WP, etc.)
        $post['category'] = empty($content_elements[2]) ? 'Uncategorized' : $content_elements[2];

        $post['category_id'] = $this->cms->findOrCreateCategory($post['category']);

        // NOTE previous version didn't publish the category if approveType was draft...might want to see if we can
        // schedule a publish in that case?
        $this->cms->publishCategory($post['category_id']);

        $post['user_id'] = $this->cms->getArticleUser();

        $post['id'] = $this->cms->insertPost($post);

        // TODO what is this random future skew for? To make it look like it's not scheduled maybe?
        $publish_timestamp = strtotime("+ ".rand(0,3000)." seconds");

        $this->cms->publishPost($post['id'], gmdate("Y-m-d H:i:s",$publish_timestamp));

        return $post;
    }

}
