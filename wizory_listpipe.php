<?php
/**
 * Joomla! System plugin for ListPipe content generation
 *
 * @author Wizory (support@wizory.com)
 * @version 2.0.0
 * @copyright Copyright 2010
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://wizory.com
 */

/* pass debug=true to enable debug mode
   pass category=empty to simulate missing category in debug mode
   e.g. ?action=GetDraft&DraftKey=1&ApprovalKey=1&BlogPostingID=1&debug=true
*/

// TODO break listpipe_get_content code down into smaller functions for clarity
// TODO document expected $contents format (or find existing docs and point to them)

// Log levels/file
define("INFO",0);
define("ERROR", 1);
define("LOG_FILE",'plg_wizory_listpipe.log');

// Joomla or DIE!
defined( '_JEXEC' ) or die( 'Restricted access' );
 
jimport('joomla.plugin.plugin');
 
class plgSystemWizory_ListPipe extends JPlugin {
		
	// Plugin constructor
	function plgSystemWizory_ListPipe(&$subject, $params) {
		parent::__construct($subject,$params);		
	}

	// Joomla-ish version of WordPress add_action('plugins_loaded','listpipe_get_content'); 
	public function onAfterRoute() {
		$this->listpipe_get_content();
	}
	
	// Shamelessly "ported" from the 2.5 version of the WordPress ListPipe plugin (latest version at time of writing)
	function listpipe_get_content() {
				
		switch(JRequest::getVar('action')) {
			/**
			Get the data
			*/											
			// handle draft request (initiated by remote system)
			case 'GetDraft':
				$draft_key = JRequest::getVar('DraftKey');
				$approval_key = JRequest::getVar('ApprovalKey');
				$blog_posting_id = JRequest::getVar('BlogPostingID');

				// get the approval type and set a boolean
				$approve_type = JRequest::getVar('ApproveType');
				$is_draft = $approve_type == "draft";
												
				$this->log(INFO,"GetDraft action called with 'DraftKey=$draft_key, ApprovalKey=$approval_key, " .
								"BlogPostingID=$blog_posting_id, ApproveType=$approve_type'");

				// require draft key, approval key, and blog posting id
				if (!empty($draft_key) && !empty($approval_key) && !empty($blog_posting_id)) {

					// if we're in DEBUG mode skip the validation and fetching and just fake the $data part
					if (JRequest::getVar('debug') == 'true') {
						// 2 cases
						if (JRequest::getVar('category') == 'empty') {
							// simulate empty category
							$data = 'ListPipe Article Title 2{-~-}This is the body of the article/post';
						} else {
							// simulate category passed in
							$data = 'ListPipe Article Title{-~-}This is the body of the article/post{-~-}ListPipe Article Category';
						}
						
						$this->log(INFO,"DEBUG enabled, faking fetch with data='$data'");	
					} else {						
						$data = "";
						$content_file = "http://www.listpipe.com/blogs/getContent.php?action=GetContent&" .
							"DraftKey=" . urlencode($draft_key) . "&" .
							"BlogPostingID=" . urlencode($blog_posting_id)
						;
					
						$this->log(INFO,"params valid, fetching data from url '$content_file'");
					
						// try fopen
						@$handle = fopen($content_file,"r");
				
						// if that didn't work, try curl
						if (empty($handle)) {
							try {
								$this->log(INFO,"using curl to fetch");
								
								$ch = curl_init();
						
								curl_setopt($ch,CURLOPT_URL,$content_file);
								curl_setopt($ch,CURLOPT_HEADER,0);
								curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
						
								// attempt to fetch the data
								$data = curl_exec($ch);
						
								curl_close($ch);
						
							// if curl failed, we're out of options so bail
							} catch (Exception $e) { $this->fail("curl failed: " .  $e->getMessage()); }
				
						// fopen worked, fetch the data
						} else {
							$this->log(INFO,"using fopen to fetch");
							// TODO move block size to a constant
							while (!feof($handle)) { $data .= fread($handle, 8192); }
						}
				
						// $data should be populated one way or another by now, so close the $handle
						@fclose($handle);
						
					}
					/**
					Process the data
					*/
					
					// do some sanity checks of the data
					if (empty($data)) {
						$this->fail("data retrieved from " . $content_file . " is empty");
					} elseif (substr($data,0,4) == 'fail') {
						$this->fail("data retrieved from " . $content_file . " begins with 'fail', aborting");
					}

					$this->log(INFO,"processing article data '" . print_r($data,true) . "'");
					
					// data is sane as far as we can tell so create posts from it
					$contents = explode("{-~-}", $data);
					
					// TODO put this with the other validation checks and move to separate function(s)
					if(count($contents) == 2 || count($contents) == 3) {

						// get the category id for this entry (or create one if it doesn't exist)
						// TODO move to a separate function
						$cat_name = $contents[2];
						$parent_category_id = $this->params->get('parent_category_id');
						
						// joomla requires a category so set a default if we didn't get one
						if (empty($cat_name) || ! is_string($cat_name)) {
							$cat_name = 'Uncategorized';
						}
						
						$this->log(INFO,"category name is '$cat_name'");
						
						//look for an existing category with matching name and get the id
						$cat_id = $this->get_cat_id($cat_name);
						
						// otherwise create a new one
						if (!$cat_id) {
							$this->log(INFO,"'$cat_name' category doesn't exist...creating under parent category with id '$parent_category_id'");
															
							$cat_id = $this->joomla25_insert_category(array(
								"cat_name"=>$cat_name,
								"parent_id"=>$parent_category_id
							));
							
							$this->log(INFO,"created '$cat_name' category with id '$cat_id'");
							
					 	}
						
						// publish the category if approval type parameter indicates we should and cat_id isn't NULL
						// TODO publish this only when the article is published (wf joomla core category publish scheduling)
						if (! $is_draft and $cat_id) {
							$this->joomla_publish_category($cat_id) or 
								$this->fail("unable to publish category with id '" . $cat_id . "'");
						}
						
						// get user to post as (either set in UI or first super admin)
						$user_id = $this->params->get('user_id');
						
						if (! $user_id) { $user_id = $this->get_admin_user_id(); }
						
						$post_title = $contents[0];
						$post_content = $contents[1];
						
						// TODO move to a fxn
						// get random future time
						$publish_timestamp = strtotime("+ ".rand(0,3000)." seconds");
						
						// set article properties
						$post_props = array(
							"post_author"=>$user_id,
							// post_status?
							// post_type?
							"post_title"=> $post_title,
							"post_content"=>$post_content,
							// obviously the category we just created earlier :D
							// this differs in that wp allows/requires an array here
							"post_category"=>$cat_id,
							// creation date of post (not publish date)
							// TODO move date format to a constant (or move to fxn)
							"post_date"=>gmdate("Y-m-d H:i:s"),
							// this is when the post actually shows up on the site
							//"publish_up"=>gmdate("Y-m-d H:i:s",$publish_timestamp)
							// post_date_gmt?
						);

						// create the article
						$post_id = $this->joomla_insert_post($post_props);
						
						// validate it was created or fail
						if (empty($post_id)) {
							$this->fail("Can't create post '" . $post_title . "'");
						}

						// publish the article if approval type parameter indicates we should
						if (! $is_draft) {
							$this->joomla_publish_post($post_id, gmdate("Y-m-d H:i:s",$publish_timestamp)) 
							or $this->fail("unable to publish post with id " . $post_id);
						}
						
						// if $is_draft ... store the approval key?  appears to be a secondary key used to validate
						// that the origin of the post is the listpipe system and is used in the original code as a secondary
						// check when publishing posts later.  if they key is missing or invalid, the post is not published
						// going to omit this feature for now (need to determine where to store this kind of thing in joomla)
						
						// TODO add above feature in if needed (see original wp listpipe code for details)
						
						if (JRequest::getVar('debug') == 'true') {
							$data = 'confirm success';
							$this->log(INFO,"DEBUG enabled, faking confirmation ping with '$data'");	
						} else {
							// send confirmation_ping to ListPipe with postId, check the return, and we're DONE!
							$data = '';
							$confirmation_ping =
								"http://www.listpipe.com/blogs/getContent.php?action=ConfirmContent&".
								"DraftKey=" . urlencode($draft_key) . "&" .
								"BlogPostingID=" . urlencode($blog_posting_id) . "&" .
								"PostID=" . $post_id
							;
							
							$this->log(INFO,"post created, generating confirmation ping via url '$confirmation_ping'");
						
							// except for the url above, this code is exactly like the opening code that contacts listpipe
							// TODO move comms code to reusable fxn
						
							// try fopen
							@$handle = fopen($confirmation_ping,"r");
				
							// if that didn't work, try curl
							if (empty($handle)) {
								try {
									$this->log(INFO,"using curl to fetch");
									$ch = curl_init();
						
									curl_setopt($ch,CURLOPT_URL,$confirmation_ping);
									curl_setopt($ch,CURLOPT_HEADER,0);
									curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
						
									// attempt to fetch the data
									$data = curl_exec($ch);
						
									curl_close($ch);
						
								// if curl failed, we're out of options so bail
								} catch (Exception $e) { $this->fail("curl failed: " .  $e->getMessage()); }
				
							// fopen worked, fetch the data
							} else {
								$this->log(INFO,"using fopen to fetch");
								// TODO move block size to a constant
								while (!feof($handle)) { $data .= fread($handle, 8192); }
							}
				
							// $data should be populated one way or another by now, so close the $handle
							@fclose($handle);
						}
						
						// exit one way or another
						if ($data == 'confirm success') {
							$this->succeed('success');
						} else {
							$this->fail("Bad response on confirmation ping: '" . $data . "'");
						}
					
					} else { $this->fail("count($contents) not 2 or 3 '" . $contents . "'"); }
					
				} else { $this->fail("DraftKey, ApprovalKey, and BlogPostingID parameters missing"); }
				break;

			/**
			Publish the data
			*/
			case 'PublishDraft':
				$post_id = JRequest::getVar('pid');
				$approval_key = JRequest::getVar('key');

				$this->log(INFO,"PublishDraft action called with 'pid=$post_id, key=$approval_key'");
				
				// publish the post
				$this->joomla_publish_post($post_id) or $this->fail("unable to publish post with id '" . $post_id . "'");
				
				// also ensure the category is published (new categories are by default unpublished)
				$this->joomla_publish_category($this->get_cat_id_for_post($post_id)) or 
					$this->fail("unable to publish category for post with id '" . $post_id . "'");
				succeed("success");
				break;
		} // end world's longest switch
	}
	
	/**
	Functions
	*/
	
	// publish a post given the id (optional date to schedule future publish)
	function joomla_publish_post($post_id, $date=NULL) {
		$db = &JFactory::getDBO();
		
		$post = new stdClass;

		// set properties & provide some sane defaults
		$post->id = $post_id;
		$post->state = 1;
		
		// publish immediately unless we got a $date to publish
		$post->publish_up = $date ? $date : gmdate("Y-m-d H:i:s");
		
		$this->log(INFO,"publishing post with params '" . print_r($post,true) . "'");
		
		if (!$db->updateObject( '#__content', $post, 'id')) {
			$this->fail($database->stderr());
			return false;
		}
		
		return $post->id;
	}
	
	// publish a category given the id
	function joomla_publish_category($cat_id) {
		$db = &JFactory::getDBO();
		
		$category = new stdClass;

		// set properties & provide some sane defaults
		$category->id = $cat_id;
		$category->published = 1;
		
		$this->log(INFO,"publishing category with params '" . print_r($category,true) . "'");
		
		if (!$db->updateObject( '#__categories', $category, 'id')) {
			$this->fail($database->stderr());
			return false;
		}
		
		return $category->id;
	}
	
	// return the category id given the post id
	function get_cat_id_for_post($post_id) {
		$this->log(INFO,"looking up category id for post id '$post_id'");
		
		$db = &JFactory::getDBO();
						
		$db->setQuery("select * from #__content where id=" . $db->quote($post_id));
		$dbResult = $db->loadAssoc();
		
		$this->log(INFO,"got category id '" . $dbResult['catid'] . "' for post id '$post_id'");
		
		return $dbResult['catid'];
	}
	
	// return the category id given the name/title (even upublished, mimics wordpress get_cat_id function)
	function get_cat_id($title) {
		$this->log(INFO,"looking up category id for category name '$title'");
		
		$db = &JFactory::getDBO();
						
		$db->setQuery("select * from #__categories where title=" . $db->quote($title));
		$dbResult = $db->loadAssoc();

		$this->log(INFO,"got category id '" . $dbResult['id'] . "' for category name '$title'");

		return $dbResult['id'];
	}
	
	// create new category given an assoc. array of properties (mimics wordpress wp_insert_category function)
	// returns false on error, id of new category otherwise (unlike wp fxn you don't have a choice :D )
	// uses existing wp key names unless it's joomla specific (in most cases those omit the prefix)
	function joomla_insert_category($params) {
		$db = &JFactory::getDBO();
		
		$category = new stdClass;

		// set properties & provide some sane defaults
		$category->id = NULL;
		$category->title = $params['cat_name'] or 'untitled';
		$category->alias = JFilterOutput::stringURLSafe($category->title);
		$category->parent_id = $params['parent_id'] or 1;
		$category->description = $params['description'] or '';
		
		$this->log(INFO,"inserting category with params '" . print_r($category,true) . "'");
		
		if (!$db->insertObject( '#__categories', $category, 'id')) {
			$this->fail($database->stderr());
			return false;
		}
		
		return $category->id;
	}

	# joomla 2.5 version of joomla_insert_category (which doesn't quite work for some reason)
	function joomla25_insert_category($params) {
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
	
	// create a new post given an assoc. array of properties (mimics wordpress wp_insert_post function)
	// returns false on error, id of new post otherwise (unlike wp fxn you don't have a choice :D )
	// uses existing wp key names unless it's joomla specific (in most cases those omit the prefix)
	// posts are always created unpublished, use joomla_publish_post to publish (no whinging!)
	function joomla_insert_post($params) {
		$db = &JFactory::getDBO();

		$post = new stdClass;

		// set properties & provide some sane defaults
		$post->id = NULL;
		$post->created_by = $params['post_author'];
		$post->title = $params['post_title'] or 'untitled';
		$post->alias = JFilterOutput::stringURLSafe($post->title);
		$post->introtext = $params['post_content'];
		$post->catid = $params['post_category'] or 1;
		$post->created = $params['post_date'];
		$post->access = 1;

		$this->log(INFO,"inserting post with params '" . print_r($post,true) . "'");

		if (!$db->insertObject( '#__content', $post, 'id')) {
			$this->fail($database->stderr());
			return false;
		}

		return $post->id;
	}
	
	// return the id of the *first* super admin user
	function get_admin_user_id() {
		$db = &JFactory::getDBO();
						
		$db->setQuery("select * from #__users where usertype='Super Administrator'");
		$dbResult = $db->loadAssoc();
		
		$this->log(INFO,"found id '" . $dbResult['id'] . "' for first super administrator user");
		
		return $dbResult['id'];
	}
	
	// log messages using the joomla standard logging facility
	function log($status = 0,$comment, $userId = 0)
    {
        jimport('joomla.error.log');

        $log = &JLog::getInstance(LOG_FILE);
    
        $log->addEntry(array('status' => $status, 'comment' => $comment, 'user_id' => $userId));
    }
	
	// die the joomla way
	function fail($message = '') {
		$mainframe =& JFactory::getApplication();
		$this->log(ERROR,"abnormal termination with message '" . $message . "'");
		echo "[ERROR] " . $message;
		$mainframe->close();
	}
	
	// exit successfully
	function succeed($message = '') {
		$this->log(INFO,"normal termination with message '" . $message . "'");
		exit($message);
	}
}
?>
