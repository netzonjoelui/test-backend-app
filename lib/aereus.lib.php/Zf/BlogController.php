<?php
/**
* Controller class used to create blogs from a netric feed
*
* This can be used simply by creating a controller that extends this class.
*
* @category  Aereus_Zf
* @package   Aereus_Zf_BlogController
* @copyright Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
*/

class Aereus_Zf_BlogController extends Zend_Controller_Action
{
	/**
	 * Setup the blog
	 */
    public function init()
    {
		// Get configuration
        $this->config = new Zend_Config_Ini(APPLICATION_PATH.'/configs/application.ini', APPLICATION_ENV);

        // Get api & settings
		$this->antapi = Aereus_Zf_AntApi::getInstance();

        // Get cache if it exists
        $this->cache = Zend_Registry::get('cache');

		// URI base
		$this->uriBase = "http://" . $_SERVER['SERVER_NAME'];

		// Set blog feed id. TODO: this may be a variable of the site rather than a config file...
		if ($this->config->antapi->blogId)
			$this->blogFeedId = $this->config->antapi->blogId;
		else
			throw new Exception("The id of the feed is not defined in the config file");

		// Set blog api class
		$this->blog = $this->antapi->getBlog($this->blogFeedId);

		// Get cache
		$this->cache = Zend_Registry::get('cache');
    }

	/**
	 * Display index of blog posts
	 */
    public function indexAction()
    {
		$activecat = null;

		// Check for feed
		if ($this->_getParam("cat") == "feed")
			return $this->feedAction();
	
		// Get categories
		if (($categories = $this->cache->load('blog_categories')) === false)
		{
			$categories = $this->blog->getCategories();
			$this->cache->save($categories, 'blog_categories');
		}
		foreach ($categories as $category)
		{
			if ($this->_getParam("cat")==$category->name)
				$activecat = $category;
		}

		// Add dynamic pages to navigation for breadcrumbs
		// --------------------------------------------------------------------
		if ($activecat)
		{
			$navController = $this->view->navigation()->findOneBy('id', 'blogmain'); 
			$page = new Zend_Navigation_Page_Uri();
			$page->active = true;
			$page->label = $activecat->name;
			$page->uri = "/blog/".$this->_getParam("cat");
			$navController->addPage($page); 
		}

        // Set title and active category
		$this->view->title = $this->config->antapi->blogTitle;
		$this->view->blogTitle = ($activecat) ?  $activecat->name." Posts" : "Aereus Blog Posts";
		$this->view->activeCategory = $activecat;

		// Send categories to view
		$this->view->postCategories = array();
		foreach ($categories as $category)
		{
			$cat = array();
			$cat['id'] = $category->id;
			$cat['name'] = $category->name;
			$cat['link'] = "/blog/".$category->name;
			$this->view->postCategories[] = $cat;
		}

		// Get posts
		// -------------------------------------------------------------	
		$this->view->posts = $this->getPosts($activecat);
    }

	/**
	 * Display and individual blog post
	 */
    public function postAction()
    {
		// Get categories
		if (($categories = $this->cache->load('blog_categories')) === false)
		{
			$categories = $this->blog->getCategories();
			$this->cache->save($categories, 'blog_categories');
		}
		foreach ($categories as $category)
		{
			if ($this->_getParam("cat")==$category->name)
				$activecat = $category;
		}
		// Send categories to view
		$this->view->postCategories = array();
		foreach ($categories as $category)
		{
			$cat = array();
			$cat['id'] = $category->id;
			$cat['name'] = $category->title;
			$cat['link'] = "/blog/".$category->name;
			$this->view->postCategories[] = $cat;
		}

		if ($this->_getParam("uid"))
		{
			$cache_id = "blog_post_".str_replace("-", "_", $this->_getParam("uid"));
			$post = null;
			$postData = array();
			if (($postData = $this->cache->load($cache_id)) === false)
			{
				if (!$post)
					$post = $this->blog->getPostById("uname:".$this->_getParam("uid"));
				$postData['id'] = $post->getValue('id');
				$postData['title'] = $post->getValue('title');
				$postData['body'] = $post->getValue('data');
				$postData['author'] = $post->getAuthor();
				$postData['image'] = $post->getValue('image');
				$postData['snippet'] = $post->getShortDescription();
				$postData['time_entered'] = $post->getValue("time_entered");

				// Post expires in 3 minutes only to protect agains extreme spikes in traffic
				$this->cache->save($postData, $cache_id, array("feed_".$this->blog->fid), 90);
			}

			// Populate view vars
			// --------------------------------------------------------------------
			$this->view->id = $postData['id'];
			$this->view->title = $postData['title'];
			$this->view->metaDescription = $postData['snippet'];
			$this->view->body = $postData['body'];
			$this->view->author = $postData['author'];
			$this->view->date = ($postData['time_entered']) ? $postData['time_entered'] : "";
			$this->view->image = ($postData['image']) ? $postData['image'] : "";
			if ($this->view->image)
				$this->view->metaImage = 'http://' . $_SERVER['SERVER_NAME'] . "/antapi/image/fid/" . $this->view->image;
			$this->view->loginLink = $this->_helper->url('index', 'auth', null, array('rp'=>base64_encode($this->getRequest()->getRequestUri())));

			// Add dynamic pages to navigation fo breadcrumbs
			// --------------------------------------------------------------------
			$navController = $this->view->navigation()->findOneBy('id', 'blogmain'); 

			if ($this->_getParam("cat"))
			{
				$page = new Zend_Navigation_Page_Uri(); 
				$page->active = true;
				$page->label = $this->_getParam("cat");
				$page->uri = "/blog/".$this->_getParam("cat");
				$navController->addPage($page); 
				$navController = $page;
			}

			$page = new Zend_Navigation_Page_Uri(); 
			$page->label = $this->view->title;
			$page->uri = "/blog";
			if ($this->_getParam("cat"))
				$page->uri .= "/".$this->_getParam("cat");
			else
				$page->uri .= "/" . (($this->config->antapi->blogPostsPath) ? $this->config->antapi->blogPostsPath : "posts");
			$page->uri .= "/".$this->_getParam("uid");
			$page->active = true;
			$navController->addPage($page); 
		}
		else
		{
			$this->view->title = "Post Not Found";
			$this->view->body = "The blog post is not available";
		}
    }

	/**
	 * Output feed
	 */
	public function feedAction()
	{
		$this->view->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);

		// Create feed writer
		$feed = new Zend_Feed_Writer_Feed();
		$feed->setTitle($this->config->antapi->blogTitle);
		$feed->setDescription($this->config->antapi->blogDescription);
		$feed->setLink('http://' . $_SERVER['SERVER_NAME']);
		$feed->setFeedLink('http://' . $_SERVER['SERVER_NAME'] . "/blog/feed", 'rss');
		/*
		$feed->addAuthor(array(
			'name'  => 'joe',
			'email' => 'sky.stebnicki@aereus.com',
			'uri'   => 'http://www.aereus.com',
		));
		 */
		//$feed->setDateModified(time());

		// Get posts
		// -------------------------------------------------------------
		$posts = $this->getPosts();

		/**
		* Add one or more entries. Note that entries must
		* be manually added once created.
		*/
		foreach ($posts as $post)
		{
			$entry = $feed->createEntry();
			$entry->setTitle($post["title"]);
			$entry->setLink("http://" . $_SERVER['SERVER_NAME'] . $post["link"]);
			$entry->addAuthor(array(
				'name'  => ($post["author"]) ? $post["author"] : "Unknown",
			));
			//$entry->setDateModified(time());
			//$entry->setDateCreated(time());
			$entry->setDescription($post["data"]);
			$entry->setContent($post["data"]);
			$feed->addEntry($entry);
		}

		 
		/**
		* Render the resulting feed to Atom 1.0 and assign to $out.
		* You can substitute "atom" with "rss" to generate an RSS 2.0 feed.
		 */
		$this->getResponse()->setHeader('Content-Type', 'text/xml');
		echo $feed->export('rss');
    }

	/**
	 * Get and cache posts
	 *
	 * @param stCld $activeCat Optional currently active category with id an name properties
	 * @return array of posts
	 */
	private function getPosts($activecat=null)
	{
		$posts = array();
		$linkRoot = "/blog/" . (($this->config->antapi->blogPostsPath) ? $this->config->antapi->blogPostsPath : "posts");

		$cache_id = ($activecat) ? 'blog_posts_'.$activecat->id : 'blog_posts_all';
		if (($posts = $this->cache->load($cache_id)) === false)
		{
			if ($activecat)
				$this->blog->addCondition("and", "categories", "is_equal", $activecat->id);
			$this->blog->addSortOrder("time_entered", "desc");

			$num = $this->blog->getPosts(0, 20);
			for ($i = 0; $i < $num; $i++)
			{
				$post = $this->blog->getPost($i);

				$posts[] = array('id' =>$post->pid,
                                            'title'=>$post->getValue("title"), 
                                            'author'=>$post->getAuthor(), 
											'link'=>$linkRoot.'/'.$post->getValue("uname"),
                                            'image'=>$post->getValue("image"), 
                                            'data'=>$post->getValue("data"), 
                                            'time_entered'=> date("m/d/Y h:m", strtotime($post->getValue("time_entered"))), 
											'snippet'=>$post->getShortDescription());
			}

			// We cache for 3 minutes only to protect against extreme load spikes
			$this->cache->save($posts, $cache_id, array("feed_".$this->blog->fid), 90);
		}

		if (!$posts)
			$posts = array(); // make empty

		return $posts;
	}
}
