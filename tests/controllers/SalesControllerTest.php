<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../controllers/SalesController.php');
require_once(dirname(__FILE__).'/../../lib/aereus.lib.php/antapi.php');

class SalesControllerTest extends PHPUnit_Framework_TestCase
{
    var $dbh = null;
    var $user = null;
    var $ant = null;
    var $backend = null;

    function setUp() 
    {
        $this->ant = new Ant();
        $this->dbh = $this->ant->dbh;
        $this->user = $this->ant->getUser(USER_SYSTEM);
    }
    
    function tearDown() 
    {
    }
    
    /*function getTests()
    {        
        return array("testInvoiceBill");        
    }*/
    
    /**
     * Test ANT Sales - invoiceBill($params)     
     */
    function testInvoiceBill()
    {        
        // add customer details
        $obj = new CAntObject($this->dbh, "customer", null, $this->user);
        $obj->setValue("first_name", "Some");
        $obj->setValue("last_name", "User");
        $obj->setValue("street", "123 Private Street");
        $obj->setValue("city", "Springfield");
        $obj->setValue("state", "Oregon");
        $obj->setValue("zip", "97477");
        $cid = $obj->save();
        $this->assertTrue($cid > 0);
            
        // add credit card details
        $custapi = new AntApi_Customer(AntConfig::getInstance()->localhost, "administrator", "Password1");
        $custapi->open($cid);
        $ccid = $custapi->addCreditCard("Test Name", "1111111111111111", 11, 2020, "visa");
        $custapi->saveChanges();
        $this->assertTrue($ccid > 0);
        
        // create customer invoice
        $objInvoice = new CAntObject($this->dbh, "invoice", null, $this->user);
        $objInvoice->setValue("name", "UnitTest CustomerInvoice");
        $objInvoice->setValue("customer_id", $cid);
        $invoiceId = $objInvoice->save();
        $this->assertTrue($invoiceId > 0);
        
        // instantiate Sales Controller
        $salesController = new SalesController($this->ant, $this->user);
        $salesController->debug = true;
        
        // test invoice bill
        $params['billmethod'] = "credit";
        $params['ccid'] = $ccid;
        $params['customer_id'] = $cid;
        $params['invoice_id'] = $invoiceId;
        $params['testmode'] = 1;
        $result = $salesController->invoiceBill($params);
        $this->assertTrue($result);
        
        // clear data
        $obj->removeHard();
    }
    
    /**
     * Test ANT Sales - customerGetCcards($params)
     * TO DO
     */
    function testTustomerGetCcards()
    {
        // add customer details
        $obj = new CAntObject($this->dbh, "customer", null, $this->user);
        $obj->setValue("name", "UnitTest CustomerName");        
        $cid = $obj->save();
        $this->assertTrue($cid > 0);
            
        // add credit card details
        $custapi = new AntApi_Customer(AntConfig::getInstance()->localhost, "administrator", "Password1");
        $custapi->open($cid);
        $ccid = $custapi->addCreditCard("Test Name", "1234123412341235", 01, 2012, "visa");
        $custapi->saveChanges();
        //$this->assertTrue($ccid > 0);
        
        // instantiate Sales Controller
        $salesController = new SalesController($this->ant, $this->user);
        $salesController->debug = true;
        
        // test customer get credit cards
        $params['customer_id'] = $cid;
        $result = $salesController->customerGetCcards($params);
        $this->assertTrue(is_array($result));
        $this->assertEquals($result[0]['id'], $ccid);
        //$this->assertEquals($result[0]['last_four'], "1235");
        //$this->assertEquals($result[0]['type'], "visa");        
        
        // clear data
        $obj->removeHard();
    }
    
    /**
     * Test ANT Sales - invoiceSaveDetail($params)
     */
    function testInvoiceSaveDetail()
    {        
        // add customer details
        $objCustomer = new CAntObject($this->dbh, "customer", null, $this->user);
        $objCustomer->setValue("name", "UnitTest CustomerName");        
        $cid = $objCustomer->save();
        $this->assertTrue($cid > 0);
        
        // create customer invoice
        $objInvoice = new CAntObject($this->dbh, "invoice", null, $this->user);
        $objInvoice->setValue("name", "UnitTest CustomerInvoice");
        $objInvoice->setValue("customer_id", $cid);
        $invoiceId = $objInvoice->save();
        $this->assertTrue($invoiceId > 0);
                
        // instantiate Sales Controller
        $salesController = new SalesController($this->ant, $this->user);
        $salesController->debug = true;
        
        // test customer save invoice detail
        $params['invoice_id'] = $invoiceId;
        $params['entries'] = array("UnitTest Entries");
        $params['ent_quantity_0'] = 1;
        $params['ent_name_0'] = "UnitTest InvoiceDetails";        
        $params['ent_amount_0'] = 100;
        $result = $salesController->invoiceSaveDetail($params);
        $this->assertEquals($result, 1);
        
        // clear data
        $objInvoice->removeHard();
        $objCustomer->removeHard();
    }
    
    /**
     * Test ANT Sales - orderGetDetail($params)
     */
    function testOrderGetDetail()
    {
        // instantiate sales order controller
        $salesController = new SalesController($this->ant, $this->user);
        $salesController->debug = true;
        
        // create sales order data
        $objSalesOrder = new CAntObject($this->dbh, "sales_order", null, $this->user);
        $objSalesOrder->setValue("name", "UnitTest SalesOrder");
        $oid = $objSalesOrder->save();
        $this->assertTrue($oid > 0);
        
        // create product data
        $objProduct = new CAntObject($this->dbh, "product", null, $this->user);
        $objProduct->setValue("name", "UnitTest Prodcut");
        $pid = $objProduct->save();
        $this->assertTrue($pid > 0);
        
        $params['order_id'] = $oid;
        $params['entries'] = array("UnitTest Entries");
        $params['ent_quantity_0'] = 1;
        $params['ent_name_0'] = "UnitTest SalesOrderDetails";
        $params['ent_amount_0'] = 1;
        $params['ent_pid_0'] = $pid;
        
        // create order detail
        $result = $salesController->orderSaveDetail($params);
        $this->assertTrue($result > 0);
        $this->assertEquals($result, $oid);
        
        // test order get detail
        $params['order_id'] = $oid;
        $result = $salesController->orderGetDetail($params);
        $this->assertTrue(is_array($result));
        $this->assertEquals($result[0]['quantity'], $params['ent_quantity_0']);
        $this->assertEquals($result[0]['amount'], $params['ent_amount_0']);
        $this->assertEquals($result[0]['name'], $params['ent_name_0']);
        
        
        // clear data
        $objSalesOrder->removeHard();
        $objProduct->removeHard();
    }
    
    /**
     * Test ANT Sales - orderSaveDetail($params)
     */
    function testOrderSaveDetail()
    {
        // instantiate sales order controller
        $salesController = new SalesController($this->ant, $this->user);
        $salesController->debug = true;
        
        // create sales order data
        $objSalesOrder = new CAntObject($this->dbh, "sales_order", null, $this->user);
        $objSalesOrder->setValue("name", "UnitTest SalesOrder");
        $oid = $objSalesOrder->save();
        $this->assertTrue($oid > 0);
        
        // create product data
        $objProduct = new CAntObject($this->dbh, "product", null, $this->user);
        $objProduct->setValue("name", "UnitTest Prodcut");
        $pid = $objProduct->save();
        $this->assertTrue($pid > 0);
        
        $params['order_id'] = $oid;
        $params['entries'] = array("UnitTest Entries");
        $params['ent_quantity_0'] = 1;
        $params['ent_name_0'] = "UnitTest SalesOrderDetails";
        $params['ent_amount_0'] = 1;
        $params['ent_pid_0'] = $pid;
        $ret = $salesController->orderSaveDetail($params);
        $this->assertTrue($ret > 0);
        
        // clear data
        $objSalesOrder->removeHard();
        $objProduct->removeHard();
    }
}
