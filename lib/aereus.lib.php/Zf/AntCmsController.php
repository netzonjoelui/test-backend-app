<?php
/**
* Controller class used for dealing with ANTAPI actions.
*
* This can be used simply bu creating a controller that extends this class.
*
* @category  Aereus_Zf
* @package   Aereus_Zf_AntApiController
* @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
*/

/**
* Class to be extended
*/
abstract class Aereus_Zf_AntCmsController extends Zend_Controller_Action
{
    public function init()
    {
        // Get configuration
        $config = new Zend_Config_Ini(APPLICATION_PATH.'/configs/application.ini', APPLICATION_ENV);

        // Get api & settings
        $this->antapi = Aereus_Zf_AntApi::getInstance();
        $this->antserver = $config->antapi->server;

        // Get cache
        $this->cache = Zend_Registry::get('cache');

        // Image path
        $this->apiCachePath = APPLICATION_PATH . "/../data/cache/antapi";
        if (!file_exists($this->apiCachePath))
            mkdir($this->apiCachePath);

		$this->cms = $this->antapi->getCms($this->config->antapi->cmsSiteId);

		$this->rootPageId = $this->getRootPageId();

		if (!$this->rootPath)
			$this->rootPath = "/" . $this->rootPageId;

		$this->buildNavigation();
	}

	/**
	 * Setup must set the local root page id $this->rootPageId which needs to match the navigation id
	 */
	abstract protected function getRootPageId();

	/**
	 * Index action will load all dynamic pages
	 */
    public function indexAction()
    {
		// get path
		$path = ($this->_getParam('path')) ? $this->_getParam('path') : $this->rootPageId;

        // action body
		$page = $this->cms->getPage($path);

		$template = $page->getTemplate();
		if ($template)
		{
			// Call class method and pass request params
			if (method_exists($this, $template->getValue("module") . "Action"))
				call_user_func(array($this, $template->getValue("module") . "Action"));
		}
		else
		{
			$this->view->pageId = $page->id;
			$this->view->title = $page->getTitle();
			$this->view->body = $page->renderBody();
		}

		// Create pages leading up to this page
		$currentPage = $this->buildCmsParentPages($page);

		$act = $this->view->navigation()->findActive($this->view->navigation()->getContainer());
		$curDepth = $act['depth'];
		$this->view->subMenuDepth = (count($currentPage->pages)>0) ? ($curDepth + 1) : $curDepth;
    }

	/**
	 * Build navigation dynamically based on CMS pages
	 */
	public function buildNavigation()
	{
		$rootZfPage = $this->view->navigation()->findOneBy('id', $this->rootPageId); 


		// TODO: build this
	}

	/**
	 * Build dynamic navigation pages based on heiarchy of the ministries
	 *
	 * @return Zend_Navigation_Page The last page added
	 */
	protected function buildCmsParentPages($cmspage, $subParentPage = null, $getChildren=false)
	{
		if ($subParentPage == null)
			$parentPage = $this->view->navigation()->findOneBy('id', $this->rootPageId); 
		else
			$parentPage = $subParentPage;

		$cmsParentPage = null;
		if ($cmspage->getValue("parent_id"))
		{
			$cmsParentPage = $this->cms->getPageById($cmspage->getValue("parent_id"));
			$parentPage = $this->buildCmsParentPages($cmsParentPage, $parentPage);
		}


		// Now create child pages if we are at the end of the tree (current page being viewed)
		if ($subParentPage == null)
		{
			if (count($cmspage->getChildPages()))
			{
				// Add this as parent
				$currentPage = $this->addCmsCurrentPage($parentPage, $cmspage);
				$this->addCmsSubPages($parentPage, $cmspage);
			}
			else
			{
				// If there are no children of the last level, then go back a level and pull children
				// But pass a reference to our current page to set the f_active variable
				if ($cmsParentPage)
					$currentPage = $this->addCmsCurrentPage($parentPage, $cmsParentPage, $cmspage->id);
				$num = $this->addCmsSubPages($parentPage, $cmsParentPage, $cmspage->id);
			}
		}
		else
		{
			$currentPage = $this->addCmsCurrentPage($parentPage, $cmspage);
		}

		return $currentPage;
	}

	/**
	 * Add the current page
	 */
	protected function addCmsCurrentPage($zfParent, $cmspage, $activePageId=null)
	{
		// Add dynamic page to zend navigation
		// we are working our way up the tree
		$currentPage = new Zend_Navigation_Page_Uri();
		$currentPage->active = ($activePageId == null || $activePageId == $cmspage->id) ? true : false;
		$currentPage->label = $cmspage->getValue("name");
		$currentPage->uri = $this->rootPath . "/" . $cmspage->getValue("uname");
		$currentPage->id = $cmsPage->id;

		// Prevent the root from being added because it is already added in the navigation.xml file
		if ($cmspage->getValue("uname") != $this->rootPageId)
			$zfParent->addPage($currentPage); 
		else
			$currentPage = $this->view->navigation()->findOneBy('id', $this->rootPageId); 

		return $currentPage;
	}

	/**
	 * Get all subpages for a cms page
	 *
	 * @return int The number of pages added
	 */
	protected function addCmsSubPages($zfPage, $cmsPage, $activePageId=null)
	{
		if (!$cmsPage)
			return 0;

		$pages = $cmsPage->getChildPages();
		foreach ($pages as $page)
		{
			// Add dynamic page to zend navigation
			$zpage = new Zend_Navigation_Page_Uri();
			$zpage->active = ($page->getValue("id") == $activePageId) ? true : false;
			$zpage->label = $page->getValue("name");
			$zpage->uri = $this->rootPath . "/" . $page->getValue("uname");
			$zpage->id = $page->getValue("id");
			$zfPage->addPage($zpage); 
		}

		return count($pages);
	}
}
