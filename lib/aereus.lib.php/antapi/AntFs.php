<?php
/**
 * Aereus API Library for working the ant file system
 *
 * @category  AntApi
 * @package   AntFs
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Class representing the AntFs interface
 */
class AntApi_AntFs
{
	/**
     * ANT server
     *
     * @var string
     */
	private $server;

	/**
     * Valid ANT user name
     *
     * @var string
     */
	private $username;

	/**
     * ANT user password
     *
     * @var string
     */
	private $password;

	/**
     * Class constructor
	 *
	 * @param string $server ANT server name
	 * @param string $username A valid ANT user name with appropriate permissions
	 * @param string $password ANT user password
     */
	function __construct($server, $username, $password) 
	{
		$this->username = $username;
		$this->password = $password;
		$this->server = $server;
	}

	/**
	 * Get all images in a folder and return as XML
	 *
	 * @return string Slideshow xml
	 */
	public function getSlideshowXml($folderId)
	{
		$olist = new AntApi_ObjectList($this->server, $this->username, $this->password, "file");
		$olist->addCondition("and", "folder_id", "is_equal", $folderId);
		$olist->getObjects($offset, 100); // get 100 objects at a time to reduce bandwidth
		$num = $olist->getNumObjects();
		for ($i = 0; $i < $num; $i++)
		{
			$obj = $olist->getObject($i);

			$ret = "";
			if (!$inline)
			{
				$ret .= "<gallery>";
				$ret .= "<album title=\"\" description=\"\">";
			}

			$num = count($this->m_files);
			for ($i = 0; $i < $num; $i++)
			{
				$file = $this->m_files[$i];
				$link = ($file['keywords']) ? $file['keywords'] : $file['url'];

				$ret .= "<img src=\"".$file['url']."\" caption=\"".$file['title']."\" link=\"".$link."\" target=\"_blank\" />";
			}

			if (!$inline)
			{
				$ret .= "</album>";
				$ret .= "</gallery>";
			}
		}
	}
}
