<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\Mail\Storage\Folder;

interface FolderInterface
{
    /**
     * get root folder or given folder
     *
     * @param string $rootFolder get folder structure for given folder, else root
     * @return FolderInterface root or wanted folder
     */
    public function getFolders($rootFolder = null);

    /**
     * select given folder
     *
     * folder must be selectable!
     *
     * @param FolderInterface|string $globalName global name of folder or instance for subfolder
     * @throws \Netric\Mail\Storage\Exception\ExceptionInterface
     */
    public function selectFolder($globalName);

    /**
     * get Netric\Mail\Storage\Folder instance for current folder
     *
     * @return FolderInterface instance of current folder
     * @throws \Netric\Mail\Storage\Exception\ExceptionInterface
     */
    public function getCurrentFolder();
}
