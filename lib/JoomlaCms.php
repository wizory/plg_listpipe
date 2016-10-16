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
        if (!class_exists('\JFactory')) { return 1; } // TODO print a message showing we "faked" this in test

        $db = &\JFactory::getDBO();

        $post = new \stdClass;

        // set properties & provide some sane defaults
        $post->id = $id;
        $post->state = 1;

        // publish immediately unless we got a $date to publish
        $post->publish_up = $date ? $date : gmdate("Y-m-d H:i:s");

        $this->log("publishing post with params '" . print_r($post,true) . "'");

        if (!$db->updateObject( '#__content', $post, 'id')) {
            $this->fail($db->stderr());
        }

        return $post->id;
    }

    public function publishCategory($id) {
        if (!class_exists('\JFactory')) { return 1; } // TODO print a message showing we "faked" this in test

        $db = &\JFactory::getDBO();

        $category = new \stdClass;

        $category->id = $id;
        $category->published = 1;

        $this->log("publishing category with params '" . print_r($category,true) . "'");

        if (!$db->updateObject( '#__categories', $category, 'id')) {
            $this->fail($db->stderr());
        }

        return $category->id;
    }

    public function getCategoryForPost($id) {
        // TODO: Implement getCategoryForPost() method.
    }

    public function getCategoryId($name) {
        if (!class_exists('\JFactory')) { return 1; } // TODO print a message showing we "faked" this in test

        $this->log("looking up category id for category name '$name'");

        $db = &\JFactory::getDBO();

        $db->setQuery("select * from #__categories where title=" . $db->quote($name));
        $dbResult = $db->loadAssoc();

        $this->log("got category id '" . $dbResult['id'] . "' for category name '$name'");

        return $dbResult['id'];
    }

    public function insertCategory($name = 'untitled', $parent = 1, $description = '') {
        // TODO: Implement insertCategory() method.
    }

    public function insertPost($data) {
        if (!class_exists('\JFactory')) { return 1; } // TODO print a message showing we "faked" this in test

        $db = &\JFactory::getDBO();

        $post = new \stdClass;

        // set properties & provide some sane defaults
        $post->id = null;
        $post->created_by = $data['user_id'];
        $post->title = $data['title'];
        $post->alias = \JFilterOutput::stringURLSafe($post->title);
        $post->introtext = $data['body'];
        $post->catid = $data['category_id'];
        $post->created = gmdate("Y-m-d H:i:s");
        $post->language = '*'; // TODO does this fix the language issue?
        $post->access = 1; // TODO what is this?

        $this->log("inserting post with params '" . print_r($post,true) . "'");

        if (!$db->insertObject( '#__content', $post, 'id')) {
            $this->fail($db->stderr());
        }

        return $post->id;
    }

    public function getAdminUserId() {
        // TODO: Implement getAdminUserId() method.
    }

    public function findOrCreateCategory($name) {
        $this->log("searching for category: " . $name);
        //look for an existing category with matching name and get the id
        $cat_id = $this->getCategoryId($name);

        $parent_cat_id = $this->config->parent_category_id;

        // otherwise create a new one
        if (!$cat_id) {
            $this->log("'$name' category doesn't exist...creating under parent category with id '$parent_cat_id'");

            $cat_id = $this->joomla25_insert_category(array(
                "cat_name"=>$name,
                "parent_id"=>$parent_cat_id
            ));

            //$this->log(INFO,"created '$cat_name' category with id '$cat_id'");
        } else {
            $this->log("found category with id: " . $cat_id);
        }

        return $cat_id;
    }

    public function getArticleUser() {
        $user_id = $this->config->user_id;

        if (empty($user_id)) { $user_id = $this->getAdminUserId(); }

        return $user_id;
    }

    public function log($message, $severity = JoomlaCms::INFO) {
        if (function_exists('jimport')) {
            jimport('joomla.log.log');

            // translate generic CMS log levels to Joomla log levels
            switch($severity) {
                case JoomlaCms::INFO: $log_level = \JLog::INFO; break;
                case JoomlaCms::WARN: $log_level = \JLog::WARNING; break;
                case JoomlaCms::ERROR: $log_level = \JLog::ERROR; break;
                default: $log_level = \JLog::INFO;
            }
    
            // add the file logger (for all log levels of our plugin)
            \JLog::addLogger(array('text_file' => LOG_FILE), \JLog::ALL, array('plg_wizory_listpipe'));

            \JLog::add(\JText::_($message), $log_level, 'plg_wizory_listpipe');
            
        } else {
            print_r(array('message' => $message, 'severity' => $severity));
        }
    }

    public function fail($message = 'fail') {
        if (!class_exists('\JFactory')) { $this->log('not running under Joomla', JoomlaCms::ERROR); return 'fail'; }

//        $mainframe = &\JFactory::getApplication();

        $this->log("abnormal termination with message '" . $message . "'", JoomlaCms::ERROR);

        exit($message);
//
//
//        echo $message;
//
//        $mainframe->close();
    }

    public function succeed($message = 'success') {
        if (!class_exists('\JFactory')) { $this->log('not running under Joomla', JoomlaCms::WARN); return 'success'; }

        $this->log("normal termination with message '" . $message . "'");

        exit($message);
    }

    // non-interface functions (local joomla-specific helpers...note snake_case)
    function joomla25_insert_category($params) {

        if (!defined('JPATH_ADMINISTRATOR')) { return; } // TODO print a message showing we "faked" this in test

        $com_categories = JPATH_ADMINISTRATOR . '/components/com_categories';
        require_once $com_categories . '/models/category.php';

        $cat_title = $params['cat_name'] or 'untitled';
        $cat_alias = \JFilterOutput::stringURLSafe($cat_title);
        $cat_parent = $params['parent_id'] or 1;
        $cat_desc = $params['description'] or '';

        $config = array('table_path' => $com_categories . '/tables');
        $cat_model = new \CategoriesModelCategory($config);

        $category = array(
            'id' => NULL,
            'parent_id' => $cat_parent,
            'level' => 1,
            'path' => $cat_alias,
            'extension' => 'com_content',
            'title' => $cat_title,
            'alias' => $cat_alias,
            'description' => $cat_desc,
            'published' => 1, # TODO make sure there's not something expecting an unpublished category
            'language' => '*'
        );

        // TODO is this bit necessary? (example of "real" category vs example code...seems to work with neither)
        // {"core.create":{"6":1,"3":1},"core.delete":{"6":1},"core.edit":{"6":1,"4":1},"core.edit.state":{"6":1,"5":1},"core.edit.own":{"6":1,"3":1}}
//        $category['rules'] = array(
//            'core.edit.state' => array(),
//            'core.edit.delete' => array(),
//            'core.edit.edit' => array(),
//            'core.edit.state' => array(),
//            'core.edit.own' => array(1 => true)
//        );

        $status = $cat_model->save($category);

        if (empty($status)) {
            $this->fail($cat_model->getError());
        }

        return $cat_model->getItem()->id;
    }

    public function getRandomPost() {
        $parent = $this->config->parent_category_id;

        // get article and category data
        $query = "SELECT a.*, c.title as 'cat_title', 'Joomla' as type " .
            "FROM #__content a " .
            "LEFT JOIN #__categories c ON c.id = a.catid " .
            "WHERE a.catid in ";

        // for articles with the parent category
        $query .= "( SELECT id FROM #__categories WHERE parent_id in ($parent)) ";

        // that are published
        $query .= " AND a.state = '1'";

        // and return just 1 random one from the list
        $query .= " ORDER BY RAND() LIMIT 1;";

        $this->log($query);

        $db = &\JFactory::getDBO();
        $db->setQuery($query);
        $dbResult = $db->loadAssoc();
        $this->log(print_r($dbResult,true));

        return $dbResult;
    }
}