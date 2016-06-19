<?php
/**
 * Created by IntelliJ IDEA.
 * User: josh
 * Date: 6/18/16
 * Time: 8:22 PM
 */

namespace Wizory;

define("LOG_FILE",'plg_wizory_listpipe.log');

// handles case during tests where jimport is not available
if (function_exists('jimport')) { jimport('joomla.error.log'); }

class JoomlaCms implements CmsInterface {

    // TODO should this go in the interface? (check back after implementing WordpressCms)
    function __construct($config) {
        $this->config = $config;
    }

    public function publishPost($id, $date=null) {
        // TODO: Implement publishPost() method.
        return True;
    }

    public function publishCategory($id) {
        if (!defined('JFactory')) { return; } // TODO print a message showing we "faked" this in test

        $db = &JFactory::getDBO();

        $category = new stdClass;

        $category->id = $id;
        $category->published = 1;

        $this->log("publishing category with params '" . print_r($category,true) . "'");

        if (!$db->updateObject( '#__categories', $category, 'id')) {
            $this->fail($db->stderr());
            return false;
        }

        return $category->id;
    }

    public function getCategoryForPost($id) {
        // TODO: Implement getCategoryForPost() method.
    }

    public function getCategoryId($name) {
        // TODO: Implement getCategoryId() method.
    }

    public function insertCategory($name = 'untitled', $parent = 1, $description = '') {
        // TODO: Implement insertCategory() method.
    }

    public function insertPost($data) {
        if (!defined('JFactory')) { return; } // TODO print a message showing we "faked" this in test

        $db = &JFactory::getDBO();

        $post = new stdClass;

        // set properties & provide some sane defaults
        $post->id = null;
        $post->created_by = $data['user_id'];
        $post->title = $data['title'];
        $post->alias = JFilterOutput::stringURLSafe($post->title);
        $post->introtext = $data['body'];
        $post->catid = $data['category_id'];
        $post->created = gmdate("Y-m-d H:i:s");
        $post->access = 1; // TODO what is this?

        //$this->log(INFO,"inserting post with params '" . print_r($post,true) . "'");

        if (!$db->insertObject( '#__content', $post, 'id')) {
            $this->fail($db->stderr());
            return false;
        }

        return $post->id;
    }

    public function getAdminUserId() {
        // TODO: Implement getAdminUserId() method.
    }

    public function findOrCreateCategory($name) {
        //look for an existing category with matching name and get the id
        $cat_id = $this->getCategoryId($name);

        $parent_cat_id = $this->config['parent_category_id'];

        // otherwise create a new one
        if (!$cat_id) {
            //$this->log(INFO,"'$cat_name' category doesn't exist...creating under parent category with id '$parent_category_id'");

            $cat_id = $this->joomla25_insert_category(array(
                "cat_name"=>$name,
                "parent_id"=>$parent_cat_id
            ));

            //$this->log(INFO,"created '$cat_name' category with id '$cat_id'");
        }
    }

    public function getArticleUser() {
        $user_id = $this->config['user_id'];

        if (empty($user_id)) { $user_id = $this->getAdminUserId(); }

        return $user_id;
    }

    public function log($message, $severity = INFO, $user = 0) {
        $entry = array('status' => $severity, 'comment' => $message, 'user_id' => $user);

        if (defined('JLog')) {
            $log = &JLog::getInstance(LOG_FILE);

            $log->addEntry($entry);
        } else {
            print_r($entry);
        }
    }

    public function fail($message = '') {
        // TODO: Implement fail() method.
    }

    public function succeed($message = '') {
        // TODO: Implement succeed() method.
    }

    // non-interface functions (local joomla-specific helpers...note snake_case)
    function joomla25_insert_category($params) {

        if (!defined('JPATH_ADMINISTRATOR')) { return; } // TODO print a message showing we "faked" this in test

        $com_categories = JPATH_ADMINISTRATOR . '/components/com_categories';
        require_once $com_categories . '/models/category.php';

        $cat_title = $params['cat_name'] or 'untitled';
        $cat_alias = JFilterOutput::stringURLSafe($cat_title);
        $cat_parent = $params['parent_id'] or 1;
        $cat_desc = $params['description'] or '';

        $config = array('table_path' => $com_categories . '/tables');
        $cat_model = new CategoriesModelCategory($config);

        $catalog = array(
            'id' => NULL,
            'parent_id' => $cat_parent,
            'level' => 1,
            'path' => $cat_alias,
            'extension' => 'com_categories',
            'title' => $cat_title,
            'alias' => $cat_alias,
            'description' => $cat_desc,
            'published' => 1, # TODO make sure there's not something expecting an unpublished category
            'language' => 'All'
        );

        $status = $cat_model->save($catalog);

        if (!$status) {
            JError::raiseWarning(500, JText::_('Unable to create category'));
        }
    }
}