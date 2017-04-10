<?php
/**
 * Aereus API library for CMS
 *
 * This class is basically an alias of the 'cms_site' object in ANT
 *
 * @category  AntApi
 * @package   AntApi_Cms
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Content management class ultiizing the local objects and object lists to create content
 */
class AntApi_Cms extends AntApi_Object
{
	/**
	 * Unique site id
	 *
	 * @var integer
	 */
	public $siteId = null;

	/**
	 * Constructor
	 *
	 * @param string $server ANT server name
	 * @param string $username A valid ANT user name with appropriate permissions
	 * @param string $password ANT user password
	 */
	public function __construct($server, $username, $password, $siteId)
	{
		parent::__construct($server, $username, $password, "cms_site");
		//$this->open($siteId);
		$this->siteId = $siteId;
	}

	/**
	 * Get a page
	 */
	public function getPage($path)
	{
		$page = new AntApi_CmsPage($this->server, $this->username, $this->password, "uname:$path");
		// $page = $this->getPageById($pageId);
		return $page;
	}

	/*
	 * Get snippet
	 */
	public function getSnippet($sid)
	{
		$snippet = new AntApi_Object($this->server, $this->username, $this->password, "cms_snippet");
		$snippet->open($sid);
		return $snippet;
	}

	/*
	 * Render snippet
	 */
	public function renderSnippet($sid)
	{
		return $this->getSnippet($sid)->getValue("data");
	}

	/*
	 * Get page template
	 */
	public function getPageTemplate($tid)
	{
		$templ = new AntApi_Object($this->server, $this->username, $this->password, "cms_page_template");
		$templ->open($tid);
		return $templ;
	}

	/**
	 * Get a page by id
	 *
	 * @param int $pageId The unique id of the page to pull
	 */
	public function getPageById($pageId)
	{
		$page = new AntApi_CmsPage($this->server, $this->username, $this->password, $pageId);
		return $page;
	}
}
