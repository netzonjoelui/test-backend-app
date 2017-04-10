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
class AntApi_CmsPageTemplate extends AntApi_Object
{
	/**
	 * Child pages array
	 *
	 * @param AntApi_CmsPage[]
	 */
	private $childPages = array();

	/**
	 * Constructor
	 *
	 * @param string $server ANT server name
	 * @param string $username A valid ANT user name with appropriate permissions
	 * @param string $password ANT user password
	 */
	public function __construct($server, $username, $password, $pageId=null)
	{
		parent::__construct($server, $username, $password, "cms_page_template");

		if ($pageId)
			$this->open($pageId);
	}
}
