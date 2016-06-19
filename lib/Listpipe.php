<?php

namespace Wizory;

use \Exception;

class Listpipe {

    const LISTPIPE_API = 'http://www.listpipe.com/blogs/getContent.php?';
    const DELIMITER = '{-~-}';

    const ACTIONS = ['GetDraft', 'PublishDraft', 'PushPost', 'GetContent', 'ConfirmContent'];

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
            case 'PushPost': return $this->pushPost($request); break;
            case 'GetContent': return $this->getContent($request); break;
            case 'ConfirmContent': return $this->confirmContent($request); break;
            default: $this->cms->fail();
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
            $this->cms->fail();
        }

        // require all non-optional args to be set to non-empty values
        if (empty($draft_key) || empty($approval_key) || empty($blog_posting_id)) { $this->cms->fail(); }

        if ($approve_type == 'draft') {
            $this->is_draft = True;
        }

        $content = $this->get(Listpipe::LISTPIPE_API
            . 'action=GetContent'
            . '&DraftKey=' . urlencode($draft_key)
            . '&BlogPostingID=' . urlencode($blog_posting_id)
        );

        if (! $this->contentIsValid($content)) {
            $this->cms->fail(); // TODO print error message or something
        }

        $post = processContent($content);

        // send "confirmation ping"
        $this->get(Listpipe::LISTPIPE_API
            . 'action=ConfirmContent'
            . '&DraftKey=' . urlencode($draft_key)
            . '&BlogPostingID=' . urlencode($blog_posting_id)
            . '&PostID=' . urlencode($post['id'])
        );

        $this->cms->succeed();
    }

    public function publishDraft($request) {
        try {
            $post_id = $request['pid'];
            $approval_key = $request['key'];
        } catch (Exception $e) {
            $this->cms->fail();
        }

        // TODO validate approval key (currently *any* approval key will allow publishing)

        // require all non-optional args to be set to non-empty values
        if (empty($post_id) || empty($approval_key)) { $this->cms->fail(); }

        if ($this->cms->publishPost($post_id)) { $this->cms->succeed(); }

        $this->cms->fail();

        // TODO finish implementing this function
//        try {
//            $draft_key = $request['DraftKey'];
//            $approval_key = $request['ApprovalKey'];
//            $blog_posting_id = $request['BlogPostingID'];
//            $approve_type = empty($request['ApproveType']) ? '' : $request['ApproveType'];
//            $debug = empty($request['debug']) ? false : $request['debug'];
//        } catch (Exception $e) {
//            $this->cms->fail();
//        }
//
//        // require all non-optional args to be set to non-empty values
//        if (empty($draft_key) || empty($approval_key) || empty($blog_posting_id)) { $this->cms->fail(); }
//
//        if ($approve_type == 'draft') {
//            $this->is_draft = True;
//        }
//
//        return Listpipe::OK;
    }

    /**
     * pushes a random article to an alternate site
     * call via a URL like: http://site.com/index.php?action=PushPost&url=http%3A%2F%2Fothersite.com
     * @param $request
     */
    public function pushPost($request) {
        $url = $request['url']
            . '/index.php?action=GetDraft'
            . '&DraftKey=x'
            . '&ApprovalKey=z'
            . '&BlogPostingID=0'
            . '&ApproveType=publish';

        $this->cms->log("initiating remote article push to url: $url");

        $content = $this->get($url);

        $this->cms->log("received reply from remote: $content");
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
