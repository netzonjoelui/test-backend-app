<?php
	include("../lib/AntConfig.php");
?>
<!DOCTYPE html> 
<head>
	<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" />
	<link rel="stylesheet" href="http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.css" />
	<script src="http://code.jquery.com/jquery-1.4.3.min.js"></script>
	<script src="http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.js"></script>
	<title><?php echo $settings_company_name; ?> Network Tools [ ANT&#8482; ] - Software Without Limitations</title>
</head>

<script>
	function main()
	{
		alert("test");
		var con = document.getElementById('appCon');
		var dv = document.createElement("div");
		dv.innerHTML = "TEST";
		con.appendChild(dv);
	}
</script>

<body>

<div data-role="page">
    <div data-role="header">
        <h1> Welcome!</h1>
    </div>
    <div data-role="content" id='appCon'>
	<script>
	alert("Test");
	</script>
    </div>
</div>

</body>
</html>
