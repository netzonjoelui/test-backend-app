<?php
/**
* User Files application actions
*
* @depricated This file is here only for legacy. It will eventually be removed and has been replaced with the AntfsController class
*/
require_once(dirname(__FILE__).'/../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../lib/CAntFs.awp');
require_once(dirname(__FILE__).'/../userfiles/file_functions.awp');
require_once(dirname(__FILE__).'/../lib/Controller.php');

/**
* Class for controlling User Files
*/
class UserFileController extends Controller
{    
    public function __construct($ant, $user)
    {
        $this->ant = $ant;
        $this->user = $user;        
    }
    
    /**
    * Create new folder
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function newFolder($params)
    {
        $dbh = $this->ant->dbh;
        $root = (is_numeric($params['root'])) ? $params['root'] : null;
        $antfs = new CAntFs($dbh, $this->user, $root);
        
        $path = stripslashes(rawurldecode($params['path']));        
        $nf_name = ($params['name']) ? stripslashes(rawurldecode($params['name'])) : "New Folder";

        if ($path)
        {
            $max_times = 200;
            for ($i = 0; $i < $max_times; $i++)
            {
                $name = ($i == 0) ? $nf_name : $nf_name."($i)"; // Make sure there are no duplicates

                if (!$antfs->folderExists($path."/".$name))
                {
                    $folder = $antfs->openFolder($path."/".$name, true);                    
                    $ret = array("folder_id" => $folder->id, "folder_name" => rawurlencode($folder->name));
                    break;
                }
            }
        }
        else
            $ret = array("error"=>"path is a required param");
            
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Add Folder
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function addFolder($params)
    {
        $dbh = $this->ant->dbh;
        $CATID = $params['cid'];

        if ($params['name'] && $CATID)
        {
            $result = $dbh->Query("insert into user_file_categories(name, user_id, parent_id) 
                             values('".rawurldecode($params['name'])."', 
                             ".db_CheckNumber(UserFilesCategoryOwner($dbh, $CATID)).", '$CATID');
                             select currval('user_file_categories_id_seq') as id;");
                                         
            if ($dbh->GetNumberRows($result))
                $ret = $dbh->GetValue($result, 0, "id");
        }
        else
            $ret = array("error"=>"cid and name are required params");
            
        $this->sendOutputJson($ret);        
        return $ret;
    }
    
    /**
    * Delete Field Id
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function deleteFileId($params)
    {
        $dbh = $this->ant->dbh;
        $root = (is_numeric($params['root'])) ? $params['root'] : null;
        $antfs = new CAntFs($dbh, $this->user, $root);
        
        $fid = $params['fid'];

        if ($fid)
        {
            $ret = $antfs->removeFileById($fid);
            if ($ret)
                $ret = "1";
            else
                $ret = array("error"=>"Error occurred while deleting file id.");
        }
        else
            $ret = array("error"=>"cid and name are required params");
            
        $this->sendOutputJson($ret);        
        return $ret;
    }
    
    /**
    * Delete Files
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function deleteFiles($params)
    {
        $dbh = $this->ant->dbh;
        $root = (is_numeric($params['root'])) ? $params['root'] : null;
        $antfs = new CAntFs($dbh, $this->user, $root);
        
        $ret["folder"] = array();
        if (is_array($params['folders']) && count($params['folders']))
        {
            foreach ($params['folders'] as $fid)
            {
                $antfs->delFolderById($fid);
                $ret["folder"][] = $fid;
            }            
        }

        $ret["file"] = array();
        if (is_array($params['files']) && count($params['files']))
        {
            foreach ($params['files'] as $fid)
            {
                $antfs->removeFileById($fid);                
                $ret["file"][] = $fid;
            }            
        }
        
        $this->sendOutputJson($ret);        
        return $ret;
    }
    
    /**
    * Move Files
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function moveFiles($params)
    {
        $dbh = $this->ant->dbh;
        $root = (is_numeric($params['root'])) ? $params['root'] : null;
        $antfs = new CAntFs($dbh, $this->user, $root);
        
        $folder_id = $params['folder_id'];
        $path = stripslashes(rawurldecode($params['path']));
        
        if ($folder_id)
        {
            if (is_array($params['files']) && count($params['files']))
            {
                foreach ($params['files'] as $fid)
                {
                    $antfs->moveFileById($fid, null, $folder_id);
                }
                $ret = "1";
            }
            if (is_array($params['folders']) && count($params['folders']) && $path)
            {
                foreach ($params['folders'] as $fid)
                {
                    $folder = $antfs->openFolderById($fid);

                    if (!$antfs->folderExists($path."/".$folder->name))
                    {
                        $antfs->moveFolderById($fid, null, $folder_id);
                        $ret = "1";
                    }
                }
            }
        }
        else
            $ret = array("error"=>"folder_id is a required param.");
            
        $this->sendOutputJson($ret);        
        return $ret;
    }
    
    /**
    * Undelete Files
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function undeleteFiles($params)
    {
        $dbh = $this->ant->dbh;
        
        $ret["folder"] = array();
        if (is_array($params['folders']) && count($params['folders']))
        {
            foreach ($params['folders'] as $fid)
            {
                UserFilesUndeleteCategory($dbh, $fid, $this->user->id);
                $ret["folder"][] = $fid;
            }
        }

        $ret["file"] = array();
        if (is_array($params['files']) && count($params['files']))
        {
            foreach ($params['files'] as $fid)
            {
                UserFilesUndelete($dbh, $fid, $this->user->id);                
                $ret["file"][] = $fid;
            }
        }
        
        $this->sendOutputJson($ret);        
        return $ret;
    }
    
    /**
    * Rename Files
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function renameFile($params)
    {
        $dbh = $this->ant->dbh;
        $root = (is_numeric($params['root'])) ? $params['root'] : null;
        $antfs = new CAntFs($dbh, $this->user, $root);
        
        $name = stripslashes($params['name']);
        $fid = stripslashes($params['file_id']);
        
        if ($name && $fid)        
        {
            $ret = $antfs->moveFileById($fid, $name);
            $ret = 1;
        }            
        else
            $ret = array("error"=>"name and file_id are required params.");
            
        $this->sendOutputJson($ret);        
        return $ret;
    }
    
    /**
    * Rename Folder
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function renameFolder($params)
    {
        $dbh = $this->ant->dbh;
        $root = null;
        
        if(isset($params['root']) && is_numeric($params['root']))
            $root = $params['root'];
        
        $antfs = new CAntFs($dbh, $this->user, $root);
        
        $name = stripslashes($params['name']);
        $fid = stripslashes($params['folder_id']);
        $path = stripslashes(rawurldecode($params['path']));
        
        if ($name && $fid && $path)
        {
            if (!$antfs->folderExists($path."/".$name))
            {
                $ret = $antfs->moveFolderById($fid, $name);
                $ret = "1";
            }
            else
                $ret = array("error"=>"Error occurred while renaming folder.");
        }
        else
            $ret = array("error"=>"name, path and folder_id are required params.");
            
        $this->sendOutputJson($ret);        
        return $ret;
    }
    
    /**
    * Create File
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function createFile($params)
    {
        $dbh = $this->ant->dbh;
        $root = null;
        
        if(isset($params['root']) && is_numeric($params['root']))
            $root = $params['root'];
            
        $antfs = new CAntFs($dbh, $this->user, $root);
        
        $name = stripslashes($params['name']);
        $type  = stripslashes($params['type']);
        $path = stripslashes(rawurldecode($params['path']));
        
        if ($name && $type && $path)
        {
            $folder = $antfs->openFolder($path);
            if ($folder)
            {
                $result = $dbh->Query("insert into user_files(file_title, file_size, file_type, category_id, time_updated) 
                            values('".$dbh->Escape($name)."', '0', '".$dbh->Escape($type)."', '".$folder->id."', 'now');
                             select currval('user_files_id_seq') as id;");
                                         
            if ($dbh->GetNumberRows($result))
                $ret = $dbh->GetValue($result, 0, "id");
            }
        }
        else
            $ret = array("error"=>"name, path and type are required params.");
            
        $this->sendOutputJson($ret);        
        return $ret;
    }
    
    /**
    * Get Folder Permission Link
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function getFolderPermissionsLink($params)
    {
        $dbh = $this->ant->dbh;
        $root = (is_numeric($params['root'])) ? $params['root'] : null;
        $antfs = new CAntFs($dbh, $this->user, $root);
        
        $fid = stripslashes($_POST['folder_id']);
        if ($fid)
        {
            $folder = $antfs->openFolderById($fid);
            if ($folder->dacl->id)
            {
                $ret = $folder->dacl->getEditLink();
            }
            else
                $ret = array("error"=>"Error occurred while getting folder permission link.");
        }
        else
            $ret = array("error"=>"folder_id is a required param.");
            
        $this->sendOutputJson($ret);        
        return $ret;
    }
}
