<?php
	/**
	* @package Gateway Tests
	*/
	include "FatZebra.class.php";
	/**
	* The gateway URL to test against
	*/
	define("GW_URL", "https://gateway.sandbox.fatzebra.com.au");

	/**
	* The gateway tests
	*/	
	class GatewayTest extends PHPUnit_Framework_TestCase {
		/**
		* Test a valid purchase
		*/
		public function test_valid_transaction() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$req = new FatZebra\PurchaseRequest(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012346", "05/2013", 123);
			$result = $gw->purchase($req);

			$this->assertTrue($result->successful);
			$this->assertTrue($result->response->successful);
			$this->assertEquals($result->response->message, "Approved");
		}

		/**
		* Test a declining purchase
		*/
		public function test_failing_transaction() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$req = new FatZebra\PurchaseRequest(100.05, "UNITTEST" . rand(), "Jim Smith", "5555555555554444", "05/2013", 123);
			$result = $gw->purchase($req);

			$this->assertTrue($result->successful);
			$this->assertFalse($result->response->successful);
			$this->assertEquals($result->response->message, "Declined");
		}

		/**
		* Test a purchase with an invalid card number
		*/
		public function test_failing_transaction_invalid_card() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$req = new FatZebra\PurchaseRequest(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012345", "05/2013", 123);
			$result = $gw->purchase($req);

			$this->assertFalse($result->successful);
			$this->assertFalse($result->response->successful);
			$this->assertEquals($result->errors[0], "Card number is invalid");	
		}

		/**
		* Test fetching a purchase
		*/
		public function test_fetch_valid_transaction() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$req = new FatZebra\PurchaseRequest(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012346", "05/2013", 123);
			$result = $gw->purchase($req);

			$purch = $gw->get_purchase($result->response->id);
			$this->assertTrue($purch->successful);
			$this->assertTrue($purch->response->successful);
			$this->assertEquals($purch->response->message, "Approved");
		}

		/**
		* Test fetching an invalid purchase
		*/
		public function test_fetch_invalid_transaction() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$purch = $gw->get_purchase("12345");
			$this->assertFalse($purch->successful);
			$this->assertEquals($purch->errors[0], "Could not find Purchase");
		}

		/**
		* Test a refund
		*/
		public function test_refund() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$purch_request = new FatZebra\PurchaseRequest(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012346", "05/2013", 123);
			$result = $gw->purchase($purch_request);

			$refund_result = $gw->refund($result->response->id, 50.00, "UNITTEST" . rand());

			$this->assertTrue($result->successful);
			$this->assertTrue($result->response->successful);
		}

		/**
		* Test refunding with an invalid transaction ID
		*/
		public function test_invalid_refund() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;
      
      $result = $gw->refund("12345", 100.00, "ERRORTEST");

			$this->assertFalse($result->successful);
			$this->assertEquals($result->errors[0], "Original transaction is required");
		}

		/**
		* Test fetching a refund
		*/
		public function test_fetch_refund() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$purch_request = new FatZebra\PurchaseRequest(100.00, "UNITTEST" . rand(), "Jim Smith", "5123456789012346", "05/2013", 123);
			$result = $gw->purchase($purch_request);

			$refund_result = $gw->refund($result->response->id, 50.00, "UNITTEST" . rand());
			$fetch_result = $gw->get_refund($refund_result->response->id);
			
			$this->assertTrue($fetch_result->successful);
			$this->assertTrue($fetch_result->response->successful);
		}

		/**
		* Test tokenizing a credit card
		*/
		public function test_tokenization() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$result = $gw->tokenize("Billy Blanks", "5123456789012346", "05/2013", "123");

			$this->assertTrue($result->successful);
			$this->assertEquals($result->response->card_holder, "Billy Blanks");
			$this->assertEquals($result->response->card_number, "512345XXXXXX2346");
		}

		/**
		* Test tokenizing an invalid card
		*/
		public function test_failing_tokenization() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$result = $gw->tokenize("Billy Blanks", "5123456789012345", "05/2013", "123");

			$this->assertFalse($result->successful);
			$this->assertEquals($result->errors[0], "Card number is invalid");
		}

		/**
		* Testing a token purchase
		*/
		public function test_purchase_with_token() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$card = $gw->tokenize("Billy Blanks", "5123456789012346", "05/2013", "123");

			$result = $gw->token_purchase($card->response->token, 100.00, "UNITTEST" . rand(), 123);

			$this->assertTrue($result->successful);
			$this->assertTrue($result->response->successful);
			$this->assertEquals($result->response->message, "Approved");
		}

		/**
		* Test a token purchase without a CVV
		*/
		public function test_purchase_with_token_no_cvv() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$card = $gw->tokenize("Billy Blanks", "5123456789012346", "05/2013", 123);

			$result = $gw->token_purchase($card->response->token, 100.00, "UNITTEST" . rand());

			$this->assertTrue($result->successful);
			$this->assertTrue($result->response->successful);
			$this->assertEquals($result->response->message, "Approved");
		}		

		/**
		* Test a token purchase with an invalid token
		*/
		public function test_purchase_with_invalid_token() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$result = $gw->token_purchase("TOK123", 100.00, "UNITTEST" . rand());

			$this->assertFalse($result->successful);
			$this->assertEquals($result->errors[0], "Card TOK123 could not be found");
		}		

		/**
		* Test creating a customer
		*/
		public function test_create_customer() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$result = $gw->create_customer("Mark", "Smith", "mark" . rand() . "@smith.com", "UNITTEST" . rand(), "Mark Smith", "5123456789012346", "05/2013", 123);

			$this->assertTrue($result->successful);
			$this->assertNotNull($result->response->id);
		}		

		/**
		* Test creating a subscription
		*/
		public function test_create_subscription() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$customer_ref = "UNITTEST" . rand();
			$plan_ref = "PLAN" . rand();
			$subscription_ref = "SUB" . rand();

			$cust = $gw->create_customer("Mark", "Smith", "mark" . rand() . "@smith.com", $customer_ref, "Mark Smith", "5123456789012346", "05/2013", 123);
			$gw->create_plan("test", 100, $plan_ref, "test plan");


			date_default_timezone_set("Australia/Sydney");
			$result = $gw->create_subscription($cust->response->id, $plan_ref, "Weekly", strtotime("+1 day"), $subscription_ref, true);

			$this->assertTrue($result->successful);
			$this->assertNotNull($result->response->id);
		}

		/**
		* Test pausing a subscription
		*/
		public function test_cancel_subscription() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$customer_ref = "UNITTEST" . rand();
			$plan_ref = "PLAN" . rand();
			$subscription_ref = "SUB" . rand();

			$cust = $gw->create_customer("Mark", "Smith", "mark" . rand() . "@smith.com", $customer_ref, "Mark Smith", "5123456789012346", "05/2013", 123);
			$gw->create_plan("test", 100, $plan_ref, "test plan");


			date_default_timezone_set("Australia/Sydney");
			$sub = $gw->create_subscription($cust->response->id, $plan_ref, "Weekly", strtotime("+1 day"), $subscription_ref, true);

			$result = $gw->cancel_subscription($sub->response->id);
			$this->assertTrue($result->successful);
			$this->assertNotNull($result->response->id);
			$this->assertFalse($result->response->is_active);
			
		}

		/**
		* Test resuming a subscription
		*/
		public function test_resume_subscription() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$customer_ref = "UNITTEST" . rand();
			$plan_ref = "PLAN" . rand();
			$subscription_ref = "SUB" . rand();

			$cust = $gw->create_customer("Mark", "Smith", "mark" . rand() . "@smith.com", $customer_ref, "Mark Smith", "5123456789012346", "05/2013", 123);
			$gw->create_plan("test", 100, $plan_ref, "test plan");


			date_default_timezone_set("Australia/Sydney");
			$sub = $gw->create_subscription($cust->response->id, $plan_ref, "Weekly", strtotime("+1 day"), $subscription_ref, true);

			$result = $gw->resume_subscription($sub->response->id);
			$this->assertTrue($result->successful);
			$this->assertNotNull($result->response->id);
			$this->assertTrue($result->response->is_active);
			
			
		}

		/**
		* Test creating a plan
		*/
		public function test_create_plan() {
			$gw = new FatZebra\Gateway("TEST", "TEST", true, GW_URL);
	        $gw->timeout = 30;

			$result = $gw->create_plan("test", 100, "PLAN" . rand(), "test plan");
			$this->assertTrue($result->successful);
			$this->assertNotNull($result->response->id);
		}
	}

?>
