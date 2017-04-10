<?php
	function GetUserId($db_handle, $user)
	{
		$query = "select id from users where name='$user'";
		$result = pg_query($db_handle, $query);
		if (pg_numrows($result))
		{
			$row = pg_fetch_array($result);
			$id = $row["id"];
		}
		pg_freeresult($result);
		return $id;
	}
	
	function GetCategoryId($db_handle, $name, $user_id)
	{
		$query = "select id from task_categories where name='$name' and (user_id='$user_id' or user_id is null)";
		$result = pg_query($db_handle, $query);
		if (pg_numrows($result))
		{
			$row = pg_fetch_array($result);
			$id = $row["id"];
		}
		else
		{
			if ($name != '')
			{
				// New category, add to table
				$query = "insert into task_categories(user_id, name) values('$user_id', '$name')";
				pg_query($db_handle, $query);
				$id = GetCategoryId($db_handle, $name, $user_id);
			}
		}
		pg_freeresult($result);
		return $id;
	}
	
	function GetCategoryNames($db_handle, $id)
	{
		if ($id)
		{
			$id_array = explode(":", $id);
			foreach($id_array as $id_val)
			{
				$query = "select name from task_categories where id='$id_val'";
				$result = pg_query($db_handle, $query);
				if(pg_numrows($result))
				{
					$row = pg_fetch_array($result);
					if ($name)
						$name .= ":".$row["name"];
					else
						$name = $row["name"];
				}
				pg_freeresult($result);
			}
		}
		return $name;
	}
?>