<?php 
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("contacts/contact_functions.awp");
	require_once("customer/customer_functions.awp");
	require_once("lib/Email.php");
	require_once("email/email_functions.awp");
	require_once("lib/CAntObject.php");
	require_once("customer/CCustomer.php");
	require_once("lib/aereus.lib.php/CAntCustomer.php");
	require_once("lib/aereus.lib.php/CAntOpportunity.php");
	require_once("lib/CPageShellPublic.php");
	require_once("lib/Sweetcaptcha.php");
	
	$dbh = $ANT->dbh;
	$ACCOUNT = $ANT->accountId;

	$HOME_SITE = "http://www.netric.com";
	$RETPAGE = ($_GET['r']) ? base64_decode(($_GET['r'])) : '';
	$EDITION = $_GET['edition'];
	$FWD = "";

	ini_set("max_execution_time", "7200");	
	ini_set("max_input_time", "7200");	

	$TEMPLATES = array(
						"default"=>array("icon"=>"", "temp_company"=>"", 
										 "account_id"=>"", "template_database"=>"ant_template", "reseller"=>false),

						"jls"=>array("icon"=>"/files/671654", "temp_company"=>"John L. Scott Real Estate", 
									 "account_id"=>"15920", "template_database"=>"ant_template_jls", "reseller"=>false),

						"my1source"=>array("icon"=>"", "temp_company"=>"", 
											"account_id"=>"16899", "template_database"=>"ant_template", "reseller"=>true, 
											"reseller_ant"=>"myonesource.ant.aereus.com"),

						"test7"=>array("icon"=>"", "temp_company"=>"", 
											"account_id"=>"17047", "template_database"=>"ant_template", "reseller"=>true, 
											"reseller_ant"=>"test7.ant.aereus.com")
					  );

	$template = $TEMPLATES['default'];

	$page = new CPageShellPublic("Create New Account", "home");
	$page->opts['print_subnav'] = false;
	$page->scripts[] = "https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js";
	$g_links = array();
	$g_links[] = array($HOME_SITE.$RETPAGE, 'website', 'Return To Website', null);
	$page->PrintShell();
?>
<script language='javascript'>
	var g_acnameok = false;
	var g_usernameok = false;

	function checkAcName(name)
	{
		var acc_dv = document.getElementById('account_status');
		// -1 = no name
		// 0 = ok
		// 1 = already used
		// 2 = bad name
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.cbData.con = acc_dv;
        ajax.onload = function(ret)
		{
            switch (ret)
            {
            case -1:
                this.cbData.con.innerHTML = "Please enter a name";
                g_acnameok = false;
                break;
            case 0:
                this.cbData.con.innerHTML = "Account name is available";
                g_acnameok = true;
                break;
            case 1:
                this.cbData.con.innerHTML = "Account name has already been used";
                g_acnameok = false;
                break;
            case 2:
                this.cbData.con.innerHTML = "Please only use a-z and 0-9";
                g_acnameok = false;
                break;
            }
        };
        ajax.exec("/signup/json_actions.php?function=check_account",
                    [["account_name", name]]);
	}	

	var check_rpc = null;
	function checkMyName(name)
	{
		var acc_dv = document.getElementById('name_status');
		// -1 = no name
		// 0 = ok
		// 1 = already used
		// 2 = bad name
        
		// Stop previous requests
		if (check_rpc && check_rpc.loading)
			check_rpc.abort();
		
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.cbData.con = acc_dv;
        ajax.onload = function(ret)
        {
            switch (ret)
            {
            case -1:
                this.cbData.con.innerHTML = "Please enter a name";
                g_usernameok = false;
                break;
            case 0:
                this.cbData.con.innerHTML = "User name is available";
                g_usernameok = true;
                break;
            case 1:
                this.cbData.con.innerHTML = "User name has already been used";
                g_usernameok = false;
                break;
            case 2:
                this.cbData.con.innerHTML = "Please only use a-z and 0-9";
                g_usernameok = false;
                break;
            }
        };
        ajax.exec("/signup/json_actions.php?function=check_user_name",
                    [["username", name]]);
	}

	function validateSignupForm()
	{
		if (document.signup.first_name.value=='')
		{
			alert('Please enter your first name before continuing!');
			return false;
		}
		if (document.signup.last_name.value=='')
		{
			alert('Please enter your last name before continuing!');
			return false;
		}
		if (document.signup.username.value=='')
		{
			alert('Please enter a user name before continuing!');
			return false;
		}
		if (document.signup.password.value=='')
		{
			alert('Please enter a password before continuing!');
			return false;
		}
		if (document.signup.password.value!=document.signup.password_verify.value)
		{
			alert('Passwords do not match. Please re-enter your password!');
			return false;
		}
		if (document.signup.email.value=='')
		{
			alert('Please enter your email address before continuing!');
			return false;
		}

		// Professional and enterprise values
		if (document.signup.account_name.value=='' || !g_acnameok)
		{
			alert('Please enter your account name before continuing!');
			document.signup.account_name.focus();
			return false;
		}

		if (document.signup.num_users.value=='')
		{
			alert('Please enter the number of users who will be using this account');
			document.signup.num_users.focus();
			return false;
		}

		if (document.signup.company.value=='')
		{
			alert('Please enter your company name');
			document.signup.company.focus();
			return false;
		}

		if (!g_usernameok)
		{
			alert('There is a problem with the user name you selected. Please try a different name!');
			document.signup.username.focus();
			return false;
		}


		if (document.signup.iagree.checked==false)
		{
			alert('You must agree to the Terms of Service before continuing!');
			return false;
		}

		var dlg = new CDialog();
		var dv_load = document.createElement('div');
		alib.dom.styleSet(dv_load, "text-align", "center");
		alib.dom.styleSet(dv_load, "background-color", "white");
		alib.dom.styleSet(dv_load, "border", "1px solid blue");
		dv_load.innerHTML = "Creating account, this step can take a while...";
		dlg.statusDialog(dv_load, 350, 100);

		return true;
	}    

	function toggleReadMote(id, less)
	{
		var more = document.getElementById(id);
		var lnk =  document.getElementById(id+"_lnk");

		if (less)
		{
			more.style.display = "none";
			lnk.spid = id;
			lnk.onclick = function() { toggleReadMote(this.spid); };
			lnk.innerHTML = "More";
		}
		else
		{
			more.style.display = "inline";
			lnk.spid = id;
			lnk.onclick = function() { toggleReadMote(this.spid, true); };
			lnk.innerHTML = "Less";
		}
	}
</script>
<?php
	// Perform validataion
	$processForm = true;
	$errorMessage = "";
	if ($_POST['sign_up'])
	{
		/*
		// Check captcha value
		$scValues = array('sckey' => $_POST['sckey'], 'scvalue' => $_POST['scvalue'], 'scvalue2' => $_POST['scvalue2']);
		if ($sweetcaptcha->check($scValues) != "true") 
		{
			$processForm = false;
			$errorMessage = "Human Verification Failed. Please Try Again";
		}
		 */
	}

	if ($_POST['sign_up'] && $processForm)
	{
		$data = array();
		$data['template_database'] = "ant_template";
		$data['reseller'] = false;
		$data['first_name'] = $_POST['first_name'];
		$data['last_name'] = $_POST['last_name'];
		$data['email'] = $_POST['email'];
		$data['phone'] = $_POST['phone'];
		$data['zip'] = $_POST['zip'];
		$data['company'] = $_POST['company'];
		$data['username'] = $_POST['username'];
		$data['password'] = $_POST['password'];
		$data['account_name'] = $_POST['account_name'];
		$data['num_users'] = $_POST['num_users'];
		$data['promotion_code'] = $_POST['promotion_code'];
		$data['ant_cust_svr'] = AntConfig::getInstance()->localhost;

		$wp = new WorkerMan($dbh);
		$ret = $wp->run("antsystem/create_account", serialize($data));

		// Print login information
		echo "<div class='g12'>";
		echo "<h1>Account Creation In Process</h1>";
		echo "<p class='success'>";
		echo "Your account is now being created. This process will take a few moments. When finished your login information will be emailed to you.<br /><br />";
		echo "<strong>NOTE: To assure you get the confirmation, please add ".AntConfig::getInstance()->email['noreply']." to your \"Safe Senders\" list.</strong><br /><br />";
		echo "Your account URL will be: 
				<a href='http://".$_POST['account_name'].".".AntConfig::getInstance()->localhost_root."'>http://".$_POST['account_name'].".".AntConfig::getInstance()->localhost_root."</a>";
		echo "<br />This is your unique account site. Please bookmark or write the address down for future reference.";
		echo "<br /><br />";
		echo "Your user name is: <span style='font-weight:bold;'>".$_POST['username']."</span></p>";
		echo "</div>";
	}
	else
	{
		// Left
		// ---------------------------------------------------------
		echo "<div class='g9 pr1'>";

		// Edition
		echo "<h1>".ucfirst($_GET['edition'])." Edition</h1>";

		echo "<h4>Signing up is quick and easy. Simply fill out the form below and click \"Sign Up\"</h4>";

		if ($errorMessage)
			echo "<p class='error'>$errorMessage</p>";

		echo "<form method='post' name='signup' action='/signup/$EDITION".$FWD."' onsubmit=\"return validateSignupForm();\">";

		// Forward edition
		echo "<input type='hidden' name='edition' value='".$_GET['edition']."'>";

		foreach ($template as $vname=>$vval)
			echo "<input type='hidden' name=\"$vname\" value=\"$vval\" />";

		echo "<table border='0' cellpadding='0' cellspacing='0'>";

		echo "<tr><td colspan='2'>";
		echo "<h3 class='bu'>Your Information</h3>";
		echo "</td></tr>";

		echo "<tr>
				<td class='formLabel' style='width:170px;'>First Name</td>
				<td>
					<input type='text' name='first_name' value=\"" . $_POST['first_name'] . "\" style='width:250px;'> 
					<span class='required'>(required)</span>
				</td>
			  </tr>";
		echo "<tr>
				<td class='formLabel'>Last Name</td>
				<td>
					<input type='text' name='last_name' value=\"" . $_POST['last_name'] . "\" style='width:250px;'> 
					<span class='required'>(required)</span>
				</td>
			  </tr>";
		echo "<tr>
				<td class='formLabel'>Email</td>
				<td>
					<input type='text' name='email' value=\"" . $_POST['email'] . "\" style='width:250px;'> 
					<span class='required'>(required)</span>
				</td>
			  </tr>";
		echo "<tr>
				<td class='formLabel'>Phone</td>
				<td><input type='text' name='phone' value=\"" . $_POST['phone'] . "\" style='width:250px;'></td>
			  </tr>";
		echo "<tr>
				<td class='formLabel'>Zip</td>
				<td><input type='text' name='zip' value=\"" . $_POST['zip'] . "\" style='width:250px;'></td>
			  </tr>";
		if ($template['temp_company'])
		{
			echo "<tr>
					<td class='formLabel'>Company/Organization</td>
					<td><input type='hidden' name='company' value=\"".$template['temp_company']."\"><h5>".$template['temp_company']."</h5></td>
				  </tr>";
		}
		else
		{
			echo "<tr>
					<td class='formLabel'>Company/Organization</td>
					<td>
						<input type='text' name='company' value=\"" . $_POST['company'] . "\" style='width:250px;'> 
						<span class='required'>(required)</span>
					</td>
				  </tr>";
		}

		// User name
		echo "<tr><td colspan='2'><br /></td></tr>";

		echo "<tr><td colspan='2'>";
		echo "<h3 class='bu'>Your User Name</h3>";
		echo "</td></tr>";
		echo "<tr>
				<td class='formLabel' >Username</td>
				<td><input type='text' name='username' value=\"" . $_POST['username'] . "\" onkeypress=\"return filterInput(2, event, false, '.');\" onkeyup=\"checkMyName(this.value);\">";
		echo " <span id='name_status'>(Recommended: firstname.lastname)</span></td>
			  </tr>";
		echo "<tr>
				<td class='formLabel'>Password</td>
				<td><input type='password' name='password'></td>
			  </tr>";
		echo "<tr>
				<td class='formLabel'>Re-enter Password</td>
				<td><input type='password' name='password_verify'> Enter your password again for verification</td>
			  </tr>";

		// Company Information
		echo "<tr><td colspan='2'><br /></td></tr>";
		echo "<tr><td colspan='2' style='padding-bottom:0;'>";
		echo "<h3 class='bu'>Your Site</h3>";
		echo "<p class='notice'>
				Each organization has a unique web address. The name should be easy to remember and cannot 
				contain any spaces or special characters (only numbers and letters).
			  </p>";
		echo "</td></tr>";

		echo "<tr>
				<td class='formLabel'>Site</td>
				<td>
					http://<input type='text'  value=\"" . $_POST['account_name'] . "\" name='account_name' 
							onkeypress=\"return filterInput(2, event, false);\" onkeyup=\"checkAcName(this.value)\" 
							style='width:100px;'>.".AntConfig::getInstance()->localhost_root."
					<div id='account_status'>Suggested: company name or acronym.</div>
				</td>
			  </tr>";
		if ($_POST['account_name'])
			echo "<script type='text/javascript'>checkAcName(document.signup.account_name.value);</script>";
		echo "<tr>
				<td class='formLabel'>Number Of Users</td>
				<td><input type='text' name='num_users' value='1' size=3><br />How many people will be using this software?</td>
			  </tr>";
		echo "<tr>
				<td class='formLabel'>Promotion Code</td>
				<td><input type='text' name='promotion_code' style='width:100px;' value='30DAYSFREE'> (optional)</td>
			  </tr>";

		/*
		echo "<tr>
				<td class='formLabel'>Human Verification</td>
				<td>".$sweetcaptcha->get_html()."</td>
			  </tr>";
		 */

		echo "<tr><td colspan='2'><br /></td></tr>";
		echo "<tr>
				<td colspan='2'>
					<input type='checkbox' name='iagree' value='agree'> I have read and agree to the <a target='_blank' href='$HOME_SITE/about/tos'>Terms of Service</a> and the <a target='_blank' href='$HOME_SITE/about/privacy'>Privacy Policy</a>
				</td>
			  </tr>";

		echo "<tr><td colspan='2'><br /></td></tr>";

		echo "<tr>
				<td colspan='2' style='text-align:center;'><input type='submit' name='sign_up' value='Sign Up'></td>
			  </tr>";

		echo "</table>";
		echo "</form>";

		echo "</div>";

		echo "</div>";

		// Right
		// -----------------------------------------------
		echo "<div class='g3'>";
		if ($template['icon'])
		{
			echo "<div style='text-align:center;'>";
			echo "<img src='".$template['icon']."'>";
			echo "</div>";
		}
		echo "<h2>No Risk</h2>";
		echo "<p class='success'>Try this incredibly powerful suite of tools with absolutely no obligation. No credit card is required, if you find it to be useful and valuable - we believe you will - then you will be given the opportunity to continue your subscription month-to-month after your trial period has expired.</p>";
		// Simple
		echo "<h2>Simple</h2>";
		echo "<p>The biggest obstacle to implementing a new set of tools as advanced as this is adoption. <span id='simple_more' style='display:none;'>Often times the learning curve for a new set of software can be pretty steep and the end-result is users don't bother. We have undergone extensive usability studies and researched how people interact with information to try and make each tool as easy to use as possible. Because each module - groupware, crm, cms, etc. - os built on the same platform, once one application is learned, then all other applications become second nature. This consitency is invaluable to helping people adapt to a new environment with the end goal of greatly increased productivity.</span> <a id='simple_more_lnk' href='javascript:void(0);' onclick=\"toggleReadMote('simple_more')\">More</a></p>";

		// Powerful
		echo "<h2>Powerful</h2>";
		echo "<p>People and organizations are constantly on the move. <span id='powerful_more' style='display:none;'>It is imperative that your toolset has the scalability and flexibility to keep up with not only your current needs, but your future needs however big or small. We took care to make each tool powerful enough to handle the immediate needs of an organization as small as one individual but maintained the flexibility to grow to an organization of hundreds of thousands of people without having to switch software platforms.</span> <a id='powerful_more_lnk' href='javascript:void(0);' onclick=\"toggleReadMote('powerful_more')\">More</a></p>";

		// Secure
		echo "<h2>Secure</h2>";
		echo "<p>Privacy and security matter now more than ever. <span id='secure_more' style='display:none;'>We value your privacy and have submitted ourselves to the highest security standards to assure your information is only available to inviduals you intend. Each account is completely atonymous with all data being stored in a separate database and protected behind high-powered firewalls, state of the art intrusion detection, 512 bit SSL encryption, and countless other security measures.</span> <a id='secure_more_lnk' href='javascript:void(0);' onclick=\"toggleReadMote('secure_more')\">More</a></p>";

		echo "</div>";
	}

?>
