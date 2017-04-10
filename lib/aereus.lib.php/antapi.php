<?php
/*======================================================================================
	
	Module:		antapi	

	Purpose:	API library interface for interacting with ANT through PHP.

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2011 Aereus Corporation. All rights reserved.

	SECURITY:	Make sure you either manually define what fields to sync with the addLocalField function or
				call the API with a user that has limited access to important fields.
	
	Depends:	

	Usage:		

	Variables:	$ALIB_OBJAPI_LCLSTORE = pgsql|mysql|elastic|mongodb - if empty then this class is not used

======================================================================================*/
define("ANTAPI_ROOT", dirname(__FILE__));
//require_once(ANTAPI_ROOT.'/antapi/lib/AntConfig.php');
require_once(ANTAPI_ROOT.'/antapi/Object.php');
require_once(ANTAPI_ROOT.'/antapi/ObjectStore.php');
require_once(ANTAPI_ROOT.'/antapi/ObjectList.php');
require_once(ANTAPI_ROOT.'/antapi/Wiki.php');
require_once(ANTAPI_ROOT.'/antapi/InfoCenter.php');
require_once(ANTAPI_ROOT.'/antapi/SalesOrder.php');
require_once(ANTAPI_ROOT.'/antapi/Invoice.php');
require_once(ANTAPI_ROOT.'/antapi/ShoppingCart.php');
require_once(ANTAPI_ROOT.'/antapi/ProductCatalog.php');
require_once(ANTAPI_ROOT.'/antapi/ProductList.php');
require_once(ANTAPI_ROOT.'/antapi/Product.php');
require_once(ANTAPI_ROOT.'/antapi/Customer.php');
require_once(ANTAPI_ROOT.'/antapi/Cms.php');
require_once(ANTAPI_ROOT.'/antapi/CmsPage.php');
require_once(ANTAPI_ROOT.'/antapi/CmsPageTemplate.php');
require_once(ANTAPI_ROOT.'/antapi/Blog.php');
require_once(ANTAPI_ROOT.'/antapi/BlogPost.php');
require_once(ANTAPI_ROOT.'/antapi/ContentFeed.php');
require_once(ANTAPI_ROOT.'/antapi/InfoCenter.php');
require_once(ANTAPI_ROOT.'/antapi/Api.php');
require_once(ANTAPI_ROOT.'/antapi/OlapCube.php');
require_once(ANTAPI_ROOT.'/antapi/AuthenticateUser.php');
require_once(ANTAPI_ROOT.'/antapi/Searcher.php');
?>
