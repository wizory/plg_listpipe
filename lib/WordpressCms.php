<?php
/**
 * Created by IntelliJ IDEA.
 * User: josh
 * Date: 6/18/16
 * Time: 8:23 PM
 */

namespace Wizory;

// TODO implementation left as an exercise to the reader ;)

class WordpressCms implements CmsInterface {

    public function publishPost($id, $date=null)
    {
        // TODO: Implement publishPost() method.
    }

    public function publishCategory($id)
    {
        // TODO: Implement publishCategory() method.
    }

    public function getCategoryForPost($id)
    {
        // TODO: Implement getCategoryForPost() method.
    }

    public function getCategoryId($name)
    {
        // TODO: Implement getCategoryId() method.
    }

    public function insertCategory($name = 'untitled', $parent = 1, $description = '')
    {
        // TODO: Implement insertCategory() method.
    }

    public function insertPost($data)
    {
        // TODO: Implement insertPost() method.
    }

    public function getAdminUserId()
    {
        // TODO: Implement getAdminUserId() method.
    }

    public function log($message, $severity = 'INFO', $user = 0)
    {
        // TODO: Implement log() method.
    }

    public function fail($message = '')
    {
        // TODO: Implement fail() method.
    }

    public function succeed($message = '')
    {
        // TODO: Implement succeed() method.
    }

    public function getRandomPost()
    {
        // TODO: Implement getRandomPost() method.
    }
}