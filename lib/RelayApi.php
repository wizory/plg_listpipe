<?php

namespace Wizory;

class RelayApi {

    public function __construct(CmsInterface $cms) {
        $this->cms = $cms;

        $this->webtools_domain = $this->cms->config->webtools_domain;
        $this->api_key = $this->cms->config->api_key;
    }

    public function update($last_updated) {
        if (isset($last_updated)) {
            $this->cms->log("fetching all articles since $last_updated");

            $posts_url = "http://$this->webtools_domain/wsapi/$this->api_key/posts/?start_date=$last_updated";

        } else {
            $this->cms->log("fetching initial articles...");

            $posts_url = "http://$this->webtools_domain/wsapi/$this->api_key/posts/?limit=25";
        }

        $articles = json_decode($this->get($posts_url));

        $this->publishArticles($articles);

        $this->cms->log("received " . count($articles) . " articles");
    }

    public function publishArticles($articles) {
        $post = array();

        foreach ($articles as $article) {
            $post['category'] = empty($article->category_name) ? 'Uncategorized' : html_entity_decode($article->category_name, ENT_QUOTES);
            $post['category_id'] = $this->cms->findOrCreateCategory($post['category']);

            $this->cms->publishCategory($post['category_id']);

            $post['title'] = html_entity_decode($article->title, ENT_QUOTES);
            $post['body'] = $article->content;

            $post['user_id'] = $this->cms->getArticleUser();

            $post['id'] = $this->cms->insertPost($post);

            $publish_timestamp = $article->post_date;

            $this->cms->publishPost($post['id'], $publish_timestamp);  # TODO search for duplicate by name first
        }
    }

    # TODO move to Util or similar
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
}