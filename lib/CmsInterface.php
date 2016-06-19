<?php
/**
 * Created by IntelliJ IDEA.
 * User: josh
 * Date: 6/18/16
 * Time: 8:06 PM
 */

namespace Wizory;

// Log levels/file
define("INFO",0);
define("ERROR", 1);

interface CmsInterface {
    // publish a post given the id (optional date to schedule future publish)
    public function publishPost($id, $date=null);

    // publish a category given its id
    public function publishCategory($id);

    // return the category id for a post given the post's id
    public function getCategoryForPost($id);

    // return the category id given its name
    public function getCategoryId($name);

    // NOTE we might want a function that will either add or lookup the category that matches a name and return the id

    // add a new category given optional parameters
    public function insertCategory($name='untitled', $parent=1, $description='');

    // add a new post given author, publish date, and the content (and some optional parameters)
    public function insertPost($data);

    // return the id of the "admin" user
    public function getAdminUserId();

    // log a system message given message and optional severity and user id
    public function log($message, $severity=INFO, $user=0);

    // fail the request/action with an optional message (TODO is the message logged or returned?)
    public function fail($message='');

    // succeed the request/action with an optional message (TODO is the message logged or returned?)
    public function succeed($message='');
}