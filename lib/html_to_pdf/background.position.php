<?php
class BackgroundPositionValue 
{
	var $x;
	var $y;
	var $x_percentage;
	var $y_percentage;

	function BackgroundPositionValue($value) 
	{
		$this->x_percentage = $value[0][1];
		$this->x = $this->x_percentage ? $value[0][0] : units2pt($value[0][0]);

		$this->y_percentage = $value[1][1];
		$this->y = $this->y_percentage ? $value[1][0] : units2pt($value[1][0]);
	}

	function is_default() 
	{
		return 
			$this->x == 0 &&
			$this->x_percentage &&
			$this->y == 0 &&
			$this->y_percentage;
	}

	function to_ps() 
	{
		return
			"<< /x << ".
			"/percentage ".($this->x_percentage ? "true":"false")." ".
			"/value ".$this->x." ".
			">> /y << ".
			"/percentage ".($this->x_percentage ? "true":"false")." ".
			"/value ".$this->x." ".
			">> >>";
	}
}
?>
