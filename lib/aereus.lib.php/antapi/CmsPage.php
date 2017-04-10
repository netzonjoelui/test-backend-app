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
class AntApi_CmsPage extends AntApi_Object
{
	/**
	 * Child pages array
	 *
	 * @param AntApi_CmsPage[]
	 */
	private $childPages = null;

	/**
	 * Constructor
	 *
	 * @param string $server ANT server name
	 * @param string $username A valid ANT user name with appropriate permissions
	 * @param string $password ANT user password
	 */
	public function __construct($server, $username, $password, $pageId=null)
	{
		parent::__construct($server, $username, $password, "cms_page");

		if ($pageId)
			$this->open($pageId);
	}

	/**
	 * Get title
	 */
	public function getTitle()
	{
		return $this->getValue("title");
	}

	/**
	 * Render a page
	 */
	public function renderBody()
	{
		$page = $this->getValue("data");
		return $page;
	}

	/**
	 * Get template if there is any
	 *
	 * @return Object("cms_page_template")|false on no template
	 */
	public function getTemplate()
	{
		$tid = $this->getValue("template_id");

		if (!$tid)
			return false;

		$templ = new AntApi_CmsPageTemplate($this->server, $this->username, $this->password, $this->getValue("template_id"));
		return $templ;
	}

	/**
	 * Get child pages
	 *
	 * @return AntApi_CmsPage[]
	 */
	public function getChildPages()
	{
		if (is_array($this->childPages))
			return $this->childPages;

		$this->childPages = array();

		$list = new AntApi_ObjectList($this->server, $this->username, $this->password, "cms_page");
		$list->addCondition("and", "parent_id", "is_equal", $this->getValue("id"));
		$list->getObjects();
		for ($i = 0; $i < $list->getNumObjects(); $i++)
		{
			$obj = $list->getObject($i);
			$this->childPages[] = new AntApi_CmsPage($this->server, $this->username, $this->password, $obj->id);
		}

		return $this->childPages;
	}
}
