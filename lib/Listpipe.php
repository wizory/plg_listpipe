<?php

namespace Wizory;

use \Exception;

class Listpipe {

    const DELIMITER = '{-~-}';

    const ACTIONS = ['GetDraft', 'PublishDraft', 'PushPost', 'GetContent', 'ConfirmContent'];

    protected $is_draft;

    public function __construct(CmsInterface $cms) {
        $this->is_draft = false;
        $this->cms = $cms;

        $this->listpipe_url = $this->cms->config->listpipe_url;
    }

    // expects config array and request array (both associative)
    public function handleRequest($request) {
        switch ($request['action']) {
            case 'GetDraft': $this->cms->succeed($this->getDraft($request)); break;
            case 'PublishDraft': $this->cms->succeed($this->publishDraft($request)); break;
            case 'PushPost': $this->cms->succeed($this->pushPost($request)); break;
            case 'GetContent': $this->cms->succeed($this->getContent($request)); break;
            case 'ConfirmContent': $this->cms->succeed($this->confirmContent($request)); break;
            default: return $this->cms->fail();
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
            return $this->cms->fail("Couldn't set parameters from request: " . $e->getMessage());
        }

        // require all non-optional args to be set to non-empty values
        if (empty($draft_key) || empty($approval_key) || empty($blog_posting_id)) {
            return $this->cms->fail("Required value not set in request: " . print_r($request, true));
        }

        if ($approve_type == 'draft') {
            $this->is_draft = True;
        }

        $content = $this->get($this->listpipe_url
            . '?action=GetContent'
            . '&DraftKey=' . urlencode($draft_key)
            . '&BlogPostingID=' . urlencode($blog_posting_id)
        );

        if (! $this->contentIsValid($content)) {
            return $this->cms->fail("invalid content: " . $content); // TODO print error message or something
        }

        $post = $this->processContent($content);

        // send "confirmation ping"
        $this->get($this->listpipe_url
            . '?action=ConfirmContent'
            . '&DraftKey=' . urlencode($draft_key)
            . '&BlogPostingID=' . urlencode($blog_posting_id)
            . '&PostID=' . urlencode($post['id'])
        );

        return $this->cms->succeed();
    }

    public function publishDraft($request) {
        try {
            $post_id = $request['pid'];
            $approval_key = $request['key'];
        } catch (Exception $e) {
            return $this->cms->fail();
        }

        // TODO validate approval key (currently *any* approval key will allow publishing)

        // require all non-optional args to be set to non-empty values
        if (empty($post_id) || empty($approval_key)) { return $this->cms->fail(); }

        if ($this->cms->publishPost($post_id)) { return $this->cms->succeed(); }

        return $this->cms->fail();

        // TODO finish implementing this function
//        try {
//            $draft_key = $request['DraftKey'];
//            $approval_key = $request['ApprovalKey'];
//            $blog_posting_id = $request['BlogPostingID'];
//            $approve_type = empty($request['ApproveType']) ? '' : $request['ApproveType'];
//            $debug = empty($request['debug']) ? false : $request['debug'];
//        } catch (Exception $e) {
//            return $this->cms->fail();
//        }
//
//        // require all non-optional args to be set to non-empty values
//        if (empty($draft_key) || empty($approval_key) || empty($blog_posting_id)) { return $this->cms->fail(); }
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
            . '&ApprovalKey=x'
            . '&BlogPostingID=x'
            . '&ApproveType=publish';

        $this->cms->log("initiating remote article push to url: $url");

        $content = $this->get($url);

        $this->cms->log("received reply from remote: $content");

        // TODO check $content for 'fail' or 'success' and act accordingly

        return $this->cms->succeed();
    }

    public function getContent($request) {
        // TODO lookup a random category and article in the locally configured parent category and return that
        // TODO s/Wilisonville/Wilsonville/g
        // TODO make above functionality externalized?  Need variable length config element with pairs of search/replace
        return <<<EOF
Lance's Superior Auto Vehicle Maintenance Tips and Videos: Wiper Blades - Signs of Wear{-~-}<object width="400" height="320" data="http://autonettv.com/media/player.swf" type="application/x-shockwave-flash"><param name="flashvars" value="file=https://s3.amazonaws.com/autonettv.com/media/Wiper_Blades_DD_01_Signs_of_Wear_WS_Zen.mp4&image=https://s3.amazonaws.com/autonettv.com/media/Wiper Blades_DD_01 - Signs of Wear_WS_Zen.jpg&autostart=false" /><param name="src" value="http://autonettv.com/media/player.swf" /><param name="AllowScriptAccess" value="always"/><video width="400" height="320" controls="controls" poster="https://s3.amazonaws.com/autonettv.com/media/Wiper Blades_DD_01 - Signs of Wear_WS_Zen.jpg"><source src="https://s3.amazonaws.com/autonettv.com/media/Wiper_Blades_DD_01_Signs_of_Wear_WS_Zen.mp4" type="video/mp4"/></video></object><p>90% of our driving decisions are based on vision. Anything that impedes your vision can influence your <a title="wiper blades Wilsonville" href="">driving safety</a> &ndash; including a dirty windshield. In that sense, your wiper blades are a vital safety system. Most Wilsonville auto owners wait until their wipers have failed before they replace them. Then they find themselves in a driving situation when they really need forward visibility only to discover that their wipers are worn or maybe even torn and can&rsquo;t clear snow or rain from the windshield.</p>
<p>As a key safety system, wiper blades should be replaced BEFORE they fail. Test your wipers at least once a month. If they are not doing the job, your Honest Lance's Superior Auto tech can replace them for you. <br /><br /> Give us a call<br /><br /><strong> Lance's Superior Auto<br /> 503-682-8522<br /> 30775-D SW Boones Ferry Road<br /> Wilsonville, Oregon 97070<br /> </strong></p>{-~-}Windshield Wipers
EOF;
    }

    public function confirmContent($request) {
        $this->cms->succeed();
    }

    public function isDraft() {
        return $this->is_draft;
    }

    public function get($url) {
        $data = '';

        $this->cms->log("http GET for url '$url'");

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
            } catch (Exception $e) { return $this->cms->fail("curl failed: " .  $e->getMessage()); }

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
        if (empty($content)) {
            $this->cms->log("content is empty", CmsInterface::ERROR);
            return false;
        }

        if (substr($content, 0, 4) == 'fail') {
            $this->cms->log("content starts with 'fail'", CmsInterface::ERROR);
            return false;
        }

        if (count(explode(Listpipe::DELIMITER, $content)) < 2) {
            $this->cms->log("content is missing elements", CmsInterface::ERROR);
            return false;
        }

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
