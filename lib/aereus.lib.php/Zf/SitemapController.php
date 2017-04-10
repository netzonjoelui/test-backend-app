<?php
/**
* Controller class used for creating dynamic google sitemaps
*
* This can be used simply bu creating a controller that extends this class.
* The extended class may override the addModules function which will be used to
* add entries to the sitemap that do not exist in the navigation like the blog.
*
* @category  Aereus_Zf
* @package   Aereus_Zf_SitemapController
* @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
*/

/**
* Class to be extended
*/
class Aereus_Zf_SitemapController extends Zend_Controller_Action
{
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
	}

    /**
     * Create sitemap
     */
    public function indexAction()
    {
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);

		header("Content-type: application/xml");

		// Check if we can load form cache - should be cached for 8 hours
		if ($this->cache)
		{
			$buf = $this->cache->load("sitemap_main");
			if ($buf)
			{
				echo $buf;
				return;
			}
		}

		$ret = '<?xml version="1.0" encoding="UTF-8"?>';
		$ret .= "\n";
		$ret .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		$ret .= "\n";

		// Walk through the dom tree of the navigation
		$pages = $this->view->navigation()->getPages();
		if (count($pages))
			$ret .= $this->printNavigationPages($pages);

		// Call module actions
		$ret .= $this->callModuleFunctions();

		$ret .= '</urlset>';

		if ($this->cache)
			$this->cache->save($ret, "sitemap_main", array("feed_139"), 28800); // expires in 8 hours

		echo $ret;
    }

	/**
	 * Used to redirect requsts to /sitemap.xml to the index of this controller
	 *
	 * The below needs to be added to the application configuration
	 * resources.router.routes.sitemap.type = "Zend_Controller_Router_Route_Static"
	 * resources.router.routes.sitemap.route = "sitemap.xml"
	 * resources.router.routes.sitemap.defaults.controller = "sitemap"
	 * resources.router.routes.sitemap.defaults.action = "redirect"
	 */
	public function redirectAction()
	{
	  $this->_redirect('/sitemap');
	}

	/**
	 * Walk through dom tree and print pages out
	 *
	 * @param Zend_Navigation_Page[] $pages Array of zend navigation pages
	 * @return string xml part for this page level
	 */
	private function printNavigationPages($pages)
	{
		$ret = "";

		foreach ($pages as $page)
		{
			$uri = $this->uriBase;

			// Ignore absolute uri's
			if (strpos($page->uri, "http://") !== false || strpos($page->uri, "https://") !== false)
				continue;

			if ($page->uri)
			{
				$uri .= $page->uri;
			}
			else
			{
				if ($page->module)
					$uri .= "/" . $page->module;
				if ($page->controller && ($page->controller != "index" || ($page->controller == "index" && $page->action != "index")))
					$uri .= "/" . $page->controller;
				if ($page->action && $page->action != "index")
					$uri .= "/" . $page->action;
			}

			// Set default modified indicator
			//$changeFreq = "weekly";
			$changeFreq = "daily";

			// If home/index page then add the trailing slash
			if ($uri == $this->uriBase)
			{
				$uri .= "/"; // Put on index
				//$changeFreq = "daily";
			}

			$ret .= '<url>';
			$ret .= "<loc>" . $uri . "</loc>";
			$ret .= "<lastmod>" . date("Y-m-d") . "</lastmod>";
			$ret .= "<changefreq>$changeFreq</changefreq>";
			$ret .= '</url>';
			
			// Print subpages
			if (count($page->pages))
				$ret .= $this->printNavigationPages($page->pages);
		}

		return $ret;
	}	

	/**
	 * Escape loc string
	 *
	 * @return string Escaped string
	 */
	public function escapeLoc($str)
	{
		return str_replace(array("&", "<", ">", "\"", "'"),
					   array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;"), $str);
	}

	/**
	 * Print extended module functions that begin with printModule*
	 *
	 * @return string xml for each module
	 */
	private function callModuleFunctions()
	{
		$ret = "";

		$classMethods = get_class_methods($this);

		$preName = "printModule";
		$preLen = strlen($preName);

		foreach ($classMethods as $methodName)
		{
			if (substr($methodName, 0, $preLen) == $preName)
			{
				$ret .= call_user_func(array(&$this, $methodName));
			}
		}

		return $ret;
	}
}
