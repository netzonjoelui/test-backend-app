<?php
/**
 * Dashboard object
 *
 * The main purpose of this class is to extend the standard ANT Object to include
 * functions for dashboards
 *
 * @category  CAntObject
 * @package   Dashboard
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Object extensions for managing dashboards
 */
class CAntObject_Dashboard extends CAntObject
{
	/**
	 * Widgets used with this dashboard
	 *
	 * @var array(array(id, widget))
	 */
	public $widgets = null;

	/**
	 * Layout array
	 *
	 * This array builds the layout structure and meta data for the dashboard
	 *
	 * @var array
	 */
	public $layout = array();
    
    /**
     * Ids of the newly saved widget
     *
     * @var Array
     */
    public $savedWidgetsId = array();

	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh	An active handle to a database connection
	 * @param int $eid 			The event id we are editing - this is optional
	 * @param AntUser $user		Optional current user
	 */
	function __construct($dbh, $eid=null, $user=null)
	{
		parent::__construct($dbh, "dashboard", $eid, $user);
	}

	/**
	 * Set layout before save
	 */
	protected function beforesaved()
	{
		$this->setValue("layout", json_encode($this->layout));
	}

	/**
	 * Function used for derrived classes to hook save event
	 *
	 * This is called after CAntObject base saves all properties
	 */
	protected function saved()
	{
		// Save widgets if new ones have been added
		$this->saveWidgets();
	}

	/**
	 * Function used for derrived classes to hook onload event
	 *
	 * This is called after CAntObject base loads all properties
	 */
	protected function loaded()
	{
		if ($this->getValue("layout"))
			$this->layout = json_decode($this->getValue("layout"), true);
	}

	/**
	 * Save widget layout
	 */
	private function saveWidgets()
	{
		for ($col = 0; $col < count($this->widgets); $col++)
		{
			for ($i = 0; $i < count($this->widgets[$col]); $i++)
			{
				if (empty($this->widgets[$col][$i]['id']))
				{
					$sql = "INSERT INTO dashboard_widgets(dashboard_id, widget, col, pos)
							VALUES('" . $this->id . "', '" . $this->widgets[$col][$i]['widget'] . "', '$col', '$i');
							SELECT currval('dashboard_widgets_id_seq') as id;";
					$result = $this->dbh->Query($sql);
					if ($this->dbh->GetNumberRows($result))
                    {
                        $widgetId = $this->dbh->GetValue($result, 0, "id");
                        $this->widgets[$col][$i]['id'] = $widgetId;
                        $this->savedWidgetsId[] = $widgetId;
                    }
				}
			}
		}
	}

	/**
	 * Load widgets
	 */
	private function loadWidgets()
	{
		if (!$this->id)
			return false;

		// Initialize array - reset if already populated
		$this->widgets = array();
		$cols = 0;

		$sql = "SELECT id, widget, col, pos, data FROM dashboard_widgets WHERE dashboard_id='" . $this->id . "'";
		$sql .= " ORDER BY col, pos";
		$result = $this->dbh->Query($sql);
		$num = $this->dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $this->dbh->GetRow($result, $i);

			if ($row['col'] > $cols)
				$cols = $row['col'];

			$this->widgets[$row['col']][] = array(
				"id" => $row['id'],
				"widget" => $row['widget'],
				"data" => $row['data'],
			);
		}
		$this->dbh->FreeResults($result);


		// Make sure arrays are initialized
		for ($i = 0; $i < $cols; $i++)
		{
			if (!isset($this->widgets[$i]) || !is_array($this->widgets[$i]))
				$this->widgets[$i] = array();
		}

		return count($this->widgets);
	}
    
    /**
     * Gets the widget info
     *
     * @param int $widgetId     The id of the widget
     * @return array('id', 'title', 'class_name', 'type', 'description'), false on failure
     */
    public function getWidgetInfo($widgetId)
    {
        if(!$widgetId)
            return false;
        else
        {
            $sql = "SELECT * FROM app_widgets WHERE id='$widgetId'";
            $result = $this->dbh->Query($sql);
            return $this->dbh->GetRow($result);
        }
    }

	/**
	 * Get array of widgets in an array of columns
	 *
	 * @return column_array[widgets[],]
	 */
	public function getWidgets()
	{
		// Initialize widgets if not already loaded
		if ($this->widgets == null)
			$this->loadWidgets();

		return $this->widgets;
	}

	/**
	 * Update the layout of widgets
	 *
	 * This function takes a multi-dimensional array and reorganizes the layout
	 * of the widgets given the structure defined in the array. The dimension are for each
	 * column just like the strucutre of the widgets array. The second dimension is an
	 * array of widget ids in order.
	 *
	 * @return bool true on success, false on failure
	 */
	public function updateLayout($layoutArray)
	{
		// Initialize widgets if not already loaded
		if ($this->widgets == null)
			$this->loadWidgets();

		$newLayout = array();
		for ($col = 0; $col < count($layoutArray); $col++)
		{
			if (!isset($newLayout[$col]))
				$newlayout[$col] = array();

			// Loop through widgets
			for ($i = 0; $i < count($layoutArray[$col]); $i++)
			{
				$widget = $this->getWidgetById($layoutArray[$col][$i]);


				if (is_array($widget))
				{
					$newLayout[$col][$i] = array(
						"id" => $widget['id'],
						"widget" => $widget['widget'],
						"data" => $widget['data'],
					);
                    
                    // Update the position of widgets
                    $query = "update dashboard_widgets set pos='$i', col='$col' where id='{$widget['id']}' and dashboard_id='" . $this->id . "';";
                    $this->dbh->Query($query);
				}
			}
		}

		// Update the widgets array to reflect the new layout
		$this->widgets = $newLayout;

		return $this->widgets;
	}

	/**
	 * Get the layout array
	 *
	 * The layout array is structured as follows:
	 *
	 * <code>
	 * array(
	 *  	// for each column
	 * 		array(
	 * 			'width'=>'200px',
	 * 			'widgets'=>array(
	 * 				array('id', 'widget', 'data') // for each widget
	 * 			),
	 * 		),
	 * )
	 * </code>
	 *
	 * @return array
	 */
	public function getLayout()
	{
        $numCol = $this->getValue("num_columns");
        
        if(!$numCol)
            $numCol = 2; // Default the number of columns to 2 if not set
            
        $numCol -= 1; // index 0
            
        $ret = array();
        
        // Initialize the columns
        for($x = 0; $x <= $numCol; $x++)
            $ret[$x] = array();

		// Initialize widgets if not already loaded
		if ($this->widgets == null)
			$this->loadWidgets();
        
        foreach($this->widgets as $col=>$widgets)
		{
            if($col > $numCol) // If col is greater than the number of column set, put the widgets on the first column
                $col = 0;
            
			if (!isset($ret[$col]))
			{
				$ret[$col] = array();
				$ret[$col]['widgets'] = array();
			}

			// Get additional column params like width
			if (array_key_exists($col, $this->layout)  && is_array($this->layout[$col]))
			{
				foreach ($this->layout[$col] as $pname=>$pval)
					$ret[$col][$pname] = $this->layout[$col][$pname];
			}

			// Loop through widgets
            foreach($widgets as $idx=>$widget)
			{
                // if widget index already taken due to transferring of widgets to other columns,
                // We need to create another index to avoid conflict
                if(isset($ret[$col]['widgets'][$idx])) 
                {
                    $idx = ($idx * $idx) + rand(50, 99); // This will ensure that it wont have the same index with other widgets
                }
                
				$ret[$col]['widgets'][$idx] = array("id" => $widget['id'], "widget" => $widget['widget'], "data" => $widget['data']);
			}
		}

		return $ret;
	}

	/**
	 * Get widget by id
	 *
	 * @param int $id The instance id of the widget
	 * @return array('id', 'widget', 'data', 'col', 'pos'), false on failure
	 */
	public function getWidgetById($id)
	{
		// Initialize widgets if not already loaded
		if ($this->widgets == null)
			$num = $this->loadWidgets();

		for ($col = 0; $col < count($this->widgets); $col++)
		{
			// Loop through widgets
			for ($i = 0; $i < count($this->widgets[$col]); $i++)
			{
				if ($this->widgets[$col][$i]['id'] == $id)
					return $this->widgets[$col][$i];
			}
		}

		return false;
	}

	/**
	 * Append a widget to a column of this dashboard
	 *
	 * @param string $widgetName The name of the widget
	 * @param int $col The column to add it to
	 */
	public function addWidget($widgetName, $col=0)
	{
		// Initialize widgets if not already loaded
		if ($this->widgets == null)
			$this->loadWidgets();

		$curNumCols = $this->getValue("num_columns");
		if (!$curNumCols)
			$curNumCols = 1;

		if ($col > ($curNumCols - 1))
			$this->setValue("num_columns", ($col + 1)); // Extend num cols if added beyond range
            
        $newWidget = array("id" => null, "widget" => $widgetName, "data" => null);
		$this->widgets[$col][] = $newWidget;
        
        return $newWidget;
	}

	/**
	 * Import layout from an application dashboard
	 *
	 * @param string $appDashName The unique name of the dashboard to import
	 */
	public function importAppDashLayout($appDashName)
	{
		if (file_exists(AntConfig::getInstance()->application_path . "/applications/dashboards/layouts/".$appDashName.".php"))
		{
			// Layout array will be in $layout variable
			$layout = null;
			include(AntConfig::getInstance()->application_path . "/applications/dashboards/layouts/".$appDashName.".php");

			if (is_array($layout))
			{
				$this->setValue("num_columns", count($layout));

				for ($col = 0; $col < count($layout); $col++)
				{
					foreach ($layout[$col] as $pname=>$pval)
					{
						switch ($pname)
						{
						case 'widgets':
							for ($pos = 0; $pos < count($layout[$col]['widgets']); $pos++)
								$this->addWidget($layout[$col]['widgets'][$pos], $col);
							break;
						default:
							$this->setColumnParam($col, $pname, $pval);
							break;
						}
					}
				}
			}
		}
	}

	/**
	 * Set column param
	 *
	 * @param int $col The column offset - starting with 0
	 * @param string $name The name of the param to set
	 * @param string $value The value to set param to
	 */
	public function setColumnParam($col, $name, $value)
	{
		if (!isset($this->layout[$col]))
			$this->layout[$col] = array();

		$this->layout[$col][$name] = $value;
	}
    
    /**
    * Remove the dashboard widget
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function removeWidget($dwid)
    {
        $dbh = $this->dbh;
        
        if($dwid)
        {
            $query = "delete from dashboard_widgets where id = '$dwid'";
            $dbh->Query($query);
            return $dwid;
        }
        else
            return false;
    }
    
    /**
    * Saves the widget data
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function saveData($dwid, $data)
    {
        $dbh = $this->dbh;
        if($dwid > 0)
        {
            $query = "update dashboard_widgets set data = '" . $dbh->escape($data) . "' where id = '$dwid'";
            $dbh->Query($query);
            return $dwid;
        }
        else
            return false;
    }
}
