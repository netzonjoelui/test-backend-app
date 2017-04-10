<?php
/**
* Datacenter application actions
*/
require_once("lib/aereus.lib.php/CChart.php");
require_once("datacenter/datacenter_functions.awp");
require_once("calendar/calendar_functions.awp");
require_once("contacts/contact_functions.awp");
require_once("customer/customer_functions.awp");
require_once("lib/Object/Report.php");

/**
* Class for controlling Datacenter functions
*/
class DatacenterController extends Controller
{    

    /**
    * Create Database
	* @depricated
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    public function createDatabase($params)
    {
        $dbh = $this->ant->dbh;
        
        $name = rawurldecode($params['dbname']);
        $sys_template = rawurldecode($params['sys_template']);
        if ($name && $this->user->id)
        {
            $result = $dbh->Query("insert into dc_databases(name, user_id) values('".$dbh->Escape($name)."', '" . $this->user->id . "');
                                     select currval('dc_databases_id_seq') as id;");
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);

                $dbh->Query("CREATE SCHEMA zudb_".$row['id'].";");
                $ret = $row['id'];
                
                if ($sys_template)
                {
                    include("databacenter/templates_system.awp");

                    foreach ($ADC_TEMPLATES[$sys_template] as $query)
                        $dbh->Query($query);
                }
            }
        }
        else
            $ret = array("error"=>"dbname is a required param");

        echo json_encode($ret);
        return $ret;
    }
    */
    
    /**
    * Save Database
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function saveDatabase($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = rawurldecode($params['dbid']);
        $name = rawurldecode($params['name']);
        $scope = rawurldecode($params['scope']);
        $f_publish = rawurldecode($params['f_publish']);
        if ($dbid)
        {
            $dbh->Query("update dc_databases set f_publish='".(($f_publish=='t')?'t':'f')."', 
                            name='".$dbh->Escape($name)."', scope='".(($scope)?$scope:'user')."' where id='$dbid'");

            if ($params['folders_add'] && is_array($params['folders_add']))
            {
                foreach ($params['folders_add'] as $fldid)
                    $dbh->Query("insert into dc_database_folders(database_id, folder_id, name) values('$dbid', '$fldid', '$fldid');");
            }

            if ($params['folders_remove'] && is_array($params['folders_remove']))
            {
                foreach ($params['folders_remove'] as $fldid)
                    $dbh->Query("delete from dc_database_folders where database_id='$dbid' and folder_id='$fldid';");
            }

            if ($params['calendars_remove'] && is_array($params['calendars_remove']))
            {
                foreach ($params['calendars_remove'] as $calid)
                {
                    $dbh->Query("delete from calendars where id='$calid';");
                    $dbh->Query("delete from dc_database_calendars where database_id='$dbid' and calendar_id='$calid';");
                }
            }

            $ret = 1;
        }
        else
            $ret = array("error"=>"dbid is a required param");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Delete Database
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function deleteDatabase($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = rawurldecode($params['dbid']);
        if ($dbid && $this->user->id)
        {
            // TODO: Security
            $dbh->Query("delete from dc_databases where id='$dbid' and user_id='". $this->user->id ."'");
            $dbh->Query("DROP SCHEMA zudb_".$dbid." cascade;");
            $ret = $dbid;
        }
        else
            $ret = array("error"=>"dbid is a required param");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Create Calendar
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function createCalendar($params)
    {        
        $dbh = $this->ant->dbh;
        
        $dbid = rawurldecode($params['dbid']);
        $name = rawurldecode($params['name']);
        if ($dbid && $name && $this->user->id)
        {
            $result = $dbh->Query("insert into calendars(name, def_cal, date_created, global_share, user_id) 
                                    values('".rawurldecode($name)."', 'f', 'now', 't', '" . $this->user->id . "');
                                    select currval('calendars_id_seq') as id;");
            if ($dbh->GetNumberRows($result))
            {
                $calid = $dbh->GetValue($result, 0, "id");

                if ($calid)
                {                    
                    $dbh->Query("insert into dc_database_calendars(database_id, calendar_id) values('$dbid', '$calid');");
                    $ret = $calid;
                }
                else
                    $ret = array("error"=>"An error occured while saving calendar.");
                
            }
        }
        else
            $ret = array("error"=>"dbid and name are required params");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Create Table
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function createTable($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = rawurldecode($params['dbid']);
        $name = rawurldecode($params['tblname']);
        if ($dbid && $this->user->id)
        {
            // TODO: Security
            $dbh->Query("CREATE TABLE zudb_".$dbid.".$name () WITH OIDS");
            $ret = $name;
        }
        else
            $ret = array("error"=>"dbid and name are required params");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Create Object
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function createObject($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = rawurldecode($params['dbid']);
        $name = rawurldecode($params['oname']);
        if ($dbid && $this->user->id && $name)
        {
            // TODO: Security
            $tname = "zudb_".$dbid.".".$name."s";
            $dbh->Query("insert into dc_database_objects(name, database_id) values('$name', '".$dbid."');");
            $dbh->Query("CREATE TABLE $tname (id serial, CONSTRAINT ".$name."s_pkey PRIMARY KEY (id)) WITH OIDS");
            $dbh->Query("insert into  app_object_types(name, title, object_table) values('".$dbid.".".$name."', '$name', '".$tname."');");
            $ret = 1;
        }
        else
            $ret = array("error"=>"dbid and oname are required params");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Table Get Primary Key
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function tableGetPkey($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = rawurldecode($params['dbid']);
        $table = rawurldecode($params['tname']);
        if ($dbid && $table)
        {
            $col = $dbh->IsPrimaryKey($table, null, "zudb_".$dbid);
            $ret = $col;
        }
        else
            $ret = array("error"=>"dbid and tname are required params");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Delete Table
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function deleteTable($params)
    {
        $dbh = $this->ant->dbh;
        
        $tname = rawurldecode($params['tname']);
        $dbid = $params['dbid'];
        if ($tname && $dbid)
        {
            $dbh->Query("DROP TABLE zudb_".$dbid.".".$tname);
            $ret = $tname;
        }
        else
            $ret = array("error"=>"dbid and tname are required params");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Delete Object
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function deleteObject($params)
    {
        $dbh = $this->ant->dbh;
        
        $oname = rawurldecode($params['oname']);
        $dbid = $params['dbid'];
        if ($oname && $dbid)
        {
            $tname = "zudb_".$dbid.".".$oname."s";
            $dbh->Query("delete from dc_database_objects where name='$oname' and database_id='$dbid'");
            $dbh->Query("DROP TABLE $tname cascade");
            $dbh->Query("delete from app_object_types where name='".$dbid.".".$oname."'");
            $ret = 1;
        }
        else
            $ret = array("error"=>"dbid and oname are required params");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Create Column
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function createColumn($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = rawurldecode($params['dbid']);
        $tname = rawurldecode($params['tname']);
        $type = rawurldecode($params['type']);
        $cname = rawurldecode($params['cname']);
        $notes = rawurldecode($params['notes']);
        $constraint = rawurldecode($params['constraint']);
        if ($dbid && $this->user->id && $tname && $type && $cname)
        {
            // Create col in table under datacenter schema
            $dbh->Query("alter table zudb_".$dbid.".$tname add column ".$cname." ".$type.";");
            if ($notes)
                $dbh->AddColumnComment("zudb_".$dbid.".".$tname, $cname, $notes);

            if ("pkey" == $constraint)
                $dbh->Query("alter table zudb_".$dbid.".$tname add CONSTRAINT ".$tname."_pkey PRIMARY KEY (".$cname.")");
                
            $ret  = 1;
        }
        else
            $ret = array("error"=>"dbid, tname, type, and cname are required params");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Delete Column
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function deleteColumn($params)
    {
        $dbh = $this->ant->dbh;
        
        $colname = rawurldecode($params['cname']);
        $tablename = rawurldecode($params['tname']);
        $dbid = rawurldecode($params['dbid']);
        if ($dbid && $tablename && $colname)
        {
            $dbh->Query("alter table zudb_".$dbid.".".$tablename." drop column ".$colname);
            $ret = $dbid;
        }
        else
            $ret = array("error"=>"dbid, cname and tnameare required params");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Save Query
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function saveQuery($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = $params['dbid'];
        $query = rawurldecode($params['query']);
        $name = rawurldecode($params['name']);
        if ($dbid)
        {
            $result = $dbh->Query("insert into dc_database_queries(name, query, database_id) 
                                   values('".$dbh->Escape($name)."', '".$dbh->Escape($query)."', '$dbid');
                                     select currval('dc_database_queries_id_seq') as id;");
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);
                $ret = $row['id'];
            }
            $dbh->FreeResults($result);
        }
        else
            $ret = array("error"=>"dbid, cname and tnameare required params");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Save Query Changes
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function saveQueryChanges($params)
    {
        $dbh = $this->ant->dbh;
        
        $qid = $params['qid'];
        $dbid = $params['dbid'];
        $query = rawurldecode($params['query']);
        $name = rawurldecode($params['name']);
        if ($dbid)
        {
            $result = $dbh->Query("update dc_database_queries set name='".$dbh->Escape($name)."', 
                                    query='".$dbh->Escape($query)."' where id = '$qid'");
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);
                $ret = $qid;
            }
            $dbh->FreeResults($result);
        }
        else
            $ret = array("error"=>"dbid is a required param");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * delete Query
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function deleteQuery($params)
    {
        $dbh = $this->ant->dbh;
        
        $qid = $params['qid'];
        if ($qid)
        {
            $dbh->Query("delete from dc_database_queries where id='$qid'");
            $ret = $qid;
        }
        else
            $ret = array("error"=>"qid is a required param");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Create User
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function createUser($params)
    {
        $dbh = $this->ant->dbh;
        
        $uname = rawurldecode($params['name']);
        $upass = rawurldecode($params['password']);
        $dbid = $params['dbid'];

        if ($dbid && $uname && $upass)
        {
            $result = $dbh->Query("insert into dc_database_users(name, password, database_id) 
                                   values('".$dbh->Escape($uname)."', '".$dbh->Escape($upass)."', '$dbid');
                                   select currval('dc_database_users_id_seq') as id;");
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);
                $ret = $row['id'];
            }
        }
        else
            $ret = array("error"=>"dbid, name, and password are required params");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Delete User
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function deleteUser($params)
    {
        $dbh = $this->ant->dbh;
        
        $uid = $params['uid'];
        if ($uid)
        {
            $dbh->Query("delete from dc_database_users where id='$uid'");
            $ret = $uid;
        }
        else
            $ret = array("error"=>"dbid, name, and password are required params");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Change User Password
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function changeUserPassword($params)
    {
        $dbh = $this->ant->dbh;
        
        $uid = $params['uid'];
        $upass = rawurldecode($params['password']);
        if ($uid && $upass)
        {
            $dbh->Query("update dc_database_users set password='".$dbh->Escape($upass)."' where id='$uid'");
            $ret = $uid;
        }
        else
            $ret = array("error"=>"uid and password are required params");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Report - Create Temporary Graph
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function reportCreateTmpGraph($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = $params['dbid'];
        if ($dbid)
        {
            $dbh->Query("delete from dc_database_report_graphs where database_id='$dbid' and name='~tmpgraph'");

            $result = $dbh->Query("insert into dc_database_report_graphs(database_id, name, caption, subcaption, xaxisname, yaxisname) 
                                   values('$dbid', '~tmpgraph', 'My graph', 'My subcaption', 'Series', 'Data');
                                   select currval('dc_database_report_graphs_id_seq') as id;");
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);
                $ret = $row['id'];
            }
        }
        else
            $ret = array("error"=>"dbid is a required param");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Report- Graph Save Query
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function reportGraphSaveQuery($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = $params['dbid'];
        $gid = $params['gid'];
        $query = rawurldecode($params['query']);
        if ($dbid && $gid)
        {
            // Clear saved columns
            $dbh->Query("delete from dc_database_report_graph_cols where graph_id='$gid'");
            $result = $dbh->Query("update dc_database_report_graphs 
                                    set query='".$dbh->Escape($query)."' 
                                    where id='$gid' and database_id='$dbid';");
            $ret = $gid;
        }
        else
            $ret = array("error"=>"dbid and gid are required params");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Report- Graph U Column
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function reportGraphUcol($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = $params['dbid'];
        $gid = $params['gid'];
        $colname = rawurldecode($params['colname']);
        $type = rawurldecode($params['type']);
        if ($type && $gid && $colname)
        {
            $tbl = "dc_database_report_graph_cols";

            $dbh->Query("delete from $tbl where 
                         name='".$dbh->Escape($colname)."' and graph_id='$gid'");
            
            // Try to get a unique color
            $color = "";
            if ($type == 2)
            {
                foreach ($dc_graphcolors as $cname=>$ccode)
                {
                    if (!($dbh->GetNumberRows($dbh->Query("select color from $tbl where type='2' and graph_id='$gid' and color='$ccode'"))))
                        $color = $ccode;
                }
                
                if ($color == "")
                    $color == $dc_graphcolors[0];
            }
            $result = $dbh->Query("insert into $tbl(graph_id, name, type, color)
                                   values('$gid', '".$dbh->Escape($colname)."', '$type', '$color')");

            $ret = $gid;
        }
        else
            $ret = array("error"=>"dbid, type and gid are required params");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Report- Graph Get Object
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function reportGraphGetObj($params)
    {
        $dbh = $this->ant->dbh;
        
        $gid = $params['gid'];
        $width = $params['width'];
        $height = $params['height'];
        if ($width || $height)
            $ret = dc_getGetObj($dbh, $gid, $width, $height);
        else
            $ret = dc_getGetObj($dbh, $gid);

        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Report- Graph Save Caption
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function reportGraphSaveCaption($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = $params['dbid'];
        $gid = $params['gid'];
        $caption = rawurldecode($params['caption']);
        $subcaption = rawurldecode($params['subcaption']);
        $xaxisname = rawurldecode($params['xaxisname']);
        $yaxisname = rawurldecode($params['yaxisname']);
        $numberPrefix = rawurldecode($params['numberPrefix']);
        $decimalPrecision = is_numeric($params['decimalPrecision']) ? $params['decimalPrecision'] : 0;
        if ($dbid && $gid)
        {
            $result = $dbh->Query("update dc_database_report_graphs set 
                                    caption='".$dbh->Escape($caption)."',
                                    subcaption='".$dbh->Escape($subcaption)."',
                                    xaxisname='".$dbh->Escape($xaxisname)."',
                                    yaxisname='".$dbh->Escape($yaxisname)."',
                                    number_prefix='".$dbh->Escape($numberPrefix)."',
                                    decimal_precision='$decimalPrecision'
                                    where id='$gid' and database_id='$dbid';");
            $ret = $gid;
        }
        else
            $ret = array("error"=>"dbid and gid are required params");

        echo json_encode($ret);            
        return $ret;
    }
    
    /**
    * Report- Graph Set
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function reportGraphSet($params)
    {
        $dbh = $this->ant->dbh;

        $dbid = $params['dbid'];
        $gid = $params['gid'];
        $graph = rawurldecode($params['graph']);
        if ($dbid && $gid && $graph)
        {
            $result = $dbh->Query("update dc_database_report_graphs set 
                                    graph_name='".$dbh->Escape($graph)."'
                                    where id='$gid' and database_id='$dbid';");
            $ret = $gid;
        }
        else
            $ret = array("error"=>"dbid and gid are required params");
            
        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Report- Graph Get Types
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function reportGetGraphTypes($params)
    {
        if ($params['gtype'])
        {
            $ret = array();
            $chart = new CChart();
            
            $graphs = $chart->getListOfGraphs($params['gtype']);
            $lastCat = "";
            $num = count($graphs);
            for ($i = 0; $i < $num; $i++)
            {
                $graph = $graphs[$i];                
                $ret[] = array("name" => $graph['name'], "title" => $graph['title'], "category" => $graph['category']);
            }
        }
        else
            $ret = array("error"=>"gtype is a required param");
            
        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Report- Delete Graph
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function reportDeleteGraph($params)
    {
        $dbh = $this->ant->dbh;
        
        $gid = $params['rid'];
        if ($gid)
        {
            $result = $dbh->Query("delete from dc_database_report_graphs where id='$gid'"); 
            $ret = $gid;
        }
        else
            $ret = array("error"=>"rid is a required param");
            
        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Report- Delete Graph
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function report_rename_graph($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = $params['dbid'];
        $gid = $params['gid'];
        $name = rawurldecode($params['name']);
        if ($dbid && $gid && $name)
        {
            $result = $dbh->Query("update dc_database_report_graphs set 
                                    name='".$dbh->Escape($name)."'
                                    where id='$gid' and database_id='$dbid';");
            $ret = $gid;
        }
        else
            $ret = array("error"=>"dbid and gid are required params");
            
        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Report- Set Single Color
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function reportSetSingleColor($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = $params['dbid'];
        $gid = $params['gid'];
        $index = $params['index'];
        $color = rawurldecode($params['color']);
        if ($dbid && $gid && $color && is_numeric($index))
        {
            $dbh->Query("update dc_database_report_graphs set series_colors[$index] = '$color' where id='$gid'");
            $ret = $gid;
        }
        else
            $ret = array("error"=>"dbid, index, color, and gid are required params");
            
        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Report- Set MS Color
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function reportSetMsColor($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = $params['dbid'];
        $gid = $params['gid'];
        $colname = $params['colname'];
        $color = rawurldecode($params['color']);
        if ($dbid && $gid && $color && $colname)
        {
            $dbh->Query("update dc_database_report_graph_cols set 
                         color='$color' where graph_id='$gid' and name='".$dbh->Escape($colname)."'");
            $ret = $gid;
        }
        else
            $ret = array("error"=>"dbid, gid, colname, and color are required params");
            
        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Report- Save
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function reportSave($params)
    {
        $dbh = $this->ant->dbh;
        $ant_obj = new CAntObject($dbh, "report", $params['rid']);
        $ofields = $ant_obj->def->getFields();
        foreach ($ofields as $fname=>$field)
        {
            if ($field->type!='fkey_multi')
            {
                $ant_obj->setValue($fname, $params[$fname]);
            }
        }
        $ant_obj->setValue("owner_id", $this->user->id);
        $ret = $ant_obj->save();
        
        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Report- Graph Get Colors
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function reportGraphGetColors($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = $params['dbid'];
        $gid = $params['gid'];
        if ($dbid && $gid)
        {
            $ret = array();
            if (dc_graphIsMultiSeries($dbh, $gid))
            {                
                // Select data columns
                $result = $dbh->Query("select name, color from dc_database_report_graph_cols where graph_id='$gid' and type='2'");
                $num = $dbh->GetNumberRows($result);
                for ($i = 0; $i < $num; $i++)
                {
                    $row = $dbh->GetNextRow($result, $i);
                    $ret[] = array("name" => $row['name'], "color" => $row['color'], "report_set_ms_color" => "report_set_ms_color");                    
                }
                $dbh->FreeResults($result);
            }
            else
            {                
                // Select data columns
                $result = $dbh->Query("select series_num from dc_database_report_graphs where id='$gid'");
                if ($dbh->GetNumberRows($result))
                {
                    $row = $dbh->GetNextRow($result, 0);
                    $num = $row['series_num'];
                    $dbh->FreeResults($result);
                    
                    for ($i = 0; $i < $num; $i++)
                    {
                        $color = dc_graphGetSeriesIndexColor($dbh, $gid, $i);
                        if (!$color)
                        {
                            $color = dc_graphGetNextColor();
                            $dbh->Query("update dc_database_report_graphs set series_colors[$i] = '$color' where id='$gid'");
                        }
                        
                        $ret[] = array("name" => "Series ".($i+1), "color" => $color, "report_set_ms_color" => "report_set_single_color");
                    }
                }
            }
        }
        else
            $ret = array("error"=>"dbid and gid are required params");
            
        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Report- Graph Get Colors
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function reportGraphGetOptions($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = $params['dbid'];
        $gid = $params['gid'];
        if ($dbid && $gid)
        {
            $ret = array();
            $result = $dbh->Query("select graph_name from dc_database_report_graphs where id='$gid'");
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);

                $chart = new CChart();
                $options = $chart->getGraphOptions($row['graph_name']);
                                
                foreach ($options as $opt)
                {
                    $val = dc_graphGetOption($dbh, $gid, $opt[0]);
                    $ovals = array();
                    if (is_array($opt[3]) && count($opt[3]))
                    {
                        foreach ($opt[3] as $valoptname=>$valoptval)
                            $ovals[] = array("valoptname" => $valoptname, "valoptval" => $valoptval);
                    }
                        
                    $ret[] = array("opt0" => $opt[0], "opt1" => $opt[1], "val" => $val, "opt2" => $opt[2], "ovals" => $ovals);
                }
            }
            $dbh->FreeResults($result);            
        }
        else
            $ret = array("error"=>"dbid and gid are required params");
            
        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Report- Graph Set Colors
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function reportGraphSetOption($params)
    {
        $dbh = $this->ant->dbh;
        
        $dbid = $params['dbid'];
        $gid = $params['gid'];
        $name = rawurldecode($params['name']);
        $value = rawurldecode($params['value']);
        if ($dbid && $gid)
        {
            dc_graphSetOption($dbh, $gid, $name, $value);
        }
        else
            $ret = array("error"=>"dbid and gid are required params");
            
        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Dashboard- Add Graph Report Graph
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function dashboardAddRptGraph($params)
    {
        $dbh = $this->ant->dbh;
        
        $rid = $params['rid'];
        if ($rid)
        {
            $result = $dbh->Query("select indx from dc_dashboard where col='0' and user_id='" . $this->user->id . "'
                                   order by indx DESC limit 1");
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);
                $indx = $row['indx'];
            }
            $dbh->Query("insert into dc_dashboard(user_id, indx, graph_id, col) values('" . $this->user->id . "', '".($indx + 1)."', '$rid', '0');");
            
            $ret = $rid;
        }
        else
            $ret = array("error"=>"rid is a required param");
            
        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Dashboard- Delete Graph Report Graph
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function dashboardDelRptGraph($params)
    {
        $dbh = $this->ant->dbh;
        
        $eid = $params['eid'];
        if ($eid)
        {
            $result = $dbh->Query("delete from dc_dashboard where id='$eid' and user_id='" . $this->user->id . "'");
            $ret = $eid;
        }
        else
            $ret = array("error"=>"rid is a required param");
            
        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Dashboard- Save Laoyout
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function dashboardSaveLayout($params)
    {
        $dbh = $this->ant->dbh;
        
        $num = rawurldecode($params['num_cols']);
        if ($num)
        {
            for ($i = 0; $i < $num; $i++)
            {
                $items = rawurldecode($params['col_'.$i]);
                if ($items)
                {
                    $widgets = explode(":", $items);

                    if (is_array($widgets))
                    {
                        for ($j = 0; $j < count($widgets); $j++)
                        {
                            $dbh->Query("update dc_dashboard set indx='$j', col='$i' where user_id='" . $this->user->id . "' and id='".$widgets[$j]."';");
                        }
                    }
                }
            }            
        }
        $ret = "done";
            
        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Dashboard- Save Laoyout Resize
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function dashboardSaveLayoutResize($params)
    {
        $num = rawurldecode($params['num_cols']);
        if ($num)
        {
            for ($i = 0; $i < $num; $i++)
                UserSetPref($dbh, $this->user->id, "datacenter/dashboard/col".$i."_width", rawurldecode($params["col_".$i]));
        }
        $ret = "done";
            
        echo json_encode($ret);
        return $ret;
    }
    
    /**
    * Report- Save
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function saveReportData($params)
    {
        $dbh = $this->ant->dbh;
        $reportType = null;
        $objType = null;
        $cubePath = null;
        
        if(isset($params['reportType']))
            $reportType = $params['reportType'];
            
        if(isset($params['objType']))
            $objType = $params['objType'];
            
        if(isset($params['cubePath']))
            $cubePath = $params['cubePath'];
        
        if($reportType == "dataware" && empty($cubePath)) // Object Report Type
            $ret = array("error" => "Invalid cube path.");
        else
        {
            $reportObject = new CAntObject_Report($dbh, null, $this->user);
            $reportObject->setValue("obj_type", $objType);
            $reportObject->setValue("dataware_cube", $cubePath);
            $reportObject->setValue("owner_id", $this->user->id);
            $reportId = $reportObject->save();
            
            if($reportId)
                $ret = $reportId;
            else
                $ret = array("error" => "Error occured when saving report.");
        }
        
        $this->sendOutput($ret);
        return $ret;
    }
    
    /**
    * Get report data
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function getReportData($params)
    {
        $ret = array();
        $dbh = $this->ant->dbh;
        
        $ret['reportData'] = array();
        $ret['filters'] = array();
        $ret['dimensions'] = array();
        $ret['measures'] = array();
        
        $id = $params['id'];
        if($id)
        {
            // Instantiate report Object
            $reportObject = new CAntObject_Report($dbh, $id, $this->user);
            
            // Get Report Details
            $ret['reportData'] = $reportObject->getDetails();
            
            // Report Filters
            $ret['filters'] = $reportObject->getFilters();
            
            // Report Dimensions
            $ret['dimensions'] = $reportObject->getDimensions();
            
            // Get Report Measures
            $ret['measures'] = $reportObject->getMeasures();
        }
            
        // Get current user theme
        $ret['theme'] = $this->user->themeName;
        
        $this->sendOutput($ret);
        return $ret;
    }
    
    /**
    * Updates the report table
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function updateReportData($params)
    {
        $dbh = $this->ant->dbh;
        $id = $params['id'];
        
        if($id > 0)
        {
            if(empty($params['f_row_totals']))
                $params['f_row_totals'] = "f";
            
            if(empty($params['f_column_totals']))
                $params['f_column_totals'] = "f";
                
            if(empty($params['f_sub_totals']))
                $params['f_sub_totals'] = "f";
            
            // Instantiate report Object
            $reportObject = new CAntObject_Report($dbh, $id, $this->user);            
            
            if(isset($params['table_type']))
                $reportObject->tableType = $params['table_type'];
            
            // Basic data 
            $reportObject->setValue("f_display_table", "f");
            $reportObject->setValue("f_display_chart", "t");
            
            if(isset($params['name']))
                $reportObject->setValue("name", $dbh->Escape($params['name']));
                
            if(isset($params['custom_report']))
                $reportObject->setValue("custom_report", $dbh->Escape($params['custom_report']));
            
            // Chart data - data is more limited so we can store it directly in the object. I have created all these columns in the report object.
            if(isset($params['chart_type']))
                $reportObject->setValue("chart_type", $dbh->Escape($params['chart_type']));
                
            if(isset($params['chart_dim1']))
                $reportObject->setValue("chart_dim1", $dbh->Escape($params['chart_dim1']));
                
            if(isset($params['chart_dim1_grp']))
                $reportObject->setValue("chart_dim1_grp", $dbh->Escape($params['chart_dim1_grp']));
                
            if(isset($params['chart_dim2']))
                $reportObject->setValue("chart_dim2", $dbh->Escape($params['chart_dim2']));
            
            if(isset($params['chart_dim2_grp']))    
                $reportObject->setValue("chart_dim2_grp", $dbh->Escape($params['chart_dim2_grp']));
                
            if(isset($params['chart_measure']))
                $reportObject->setValue("chart_measure", $dbh->Escape($params['chart_measure']));
            
            if(isset($params['chart_measure_agg']))
                $reportObject->setValue("chart_measure_agg", $dbh->Escape($params['chart_measure_agg']));
            
            // Table data 
            if(isset($params['table_type']))
                $reportObject->setValue("table_type", $dbh->Escape($params['table_type']));
            
            // Add Report Filter
            if(isset($params['filters']))
            {
                foreach ($params['filters'] as $filterIdx)
                {
                    $reportObject->addReportFilter($params["filter_blogic_$filterIdx"], 
                                                    $params["filter_field_$filterIdx"], 
                                                    $params["filter_operator_$filterIdx"], 
                                                    $params["filter_value_$filterIdx"],
                                                    $params["filter_id_$filterIdx"]);
                }
            }
            
            
            // Add Report Dimension
            if(isset($params['dimensions']))
            {
                foreach ($params['dimensions'] as $dimIdx)
                {
                    $reportObject->addReportDim($params["dimension_name_$dimIdx"], 
                                                    $params["dimension_sort_$dimIdx"], 
                                                    $params["dimension_format_$dimIdx"], 
                                                    $params["f_column_$dimIdx"],
                                                    $params["f_row_$dimIdx"],
                                                    $params["dimension_id_$dimIdx"]);
                }
            }
            
            // Add Report Measure
            if(isset($params['measures']))
            {
                foreach ($params['measures'] as $measIdx)
                {
                    $reportObject->addReportMeasure($params["measure_name_$measIdx"],
                                                    $params["measure_aggregate_$measIdx"],
                                                    $params["measure_id_$measIdx"]);
                }
            }
            
            $reportObject->save();
            
            // Update these fields manually for now, until they are associated to the report object
            $query = "update reports set                        
                        f_row_totals = '" . $dbh->Escape($params['f_row_totals']) . "',
                        f_column_totals = '" . $dbh->Escape($params['f_column_totals']) . "',
                        f_sub_totals = '" . $dbh->Escape($params['f_sub_totals']) . "'
                        where id = " . $dbh->EscapeNumber($id);
            $dbh->Query($query);
            
            $ret = $id;
        }
        else
            $ret = array("error" => "Invalid report Id.");        
        
        $this->sendOutput($ret);
        return $ret;
    }
    
    public function deleteReport($params)
    {
        $dbh = $this->ant->dbh;
        $id = $params['id'];
        if($id > 0)
        {
            $reportObject = new CAntObject_Report($dbh, $id, $this->user);
            $reportObject->removeHard();
            $ret = 1;
        }
        else
            $ret = array("error" => "Invalid Report Id.");

        $this->sendOutput($ret);
        return $ret;
    }
}
