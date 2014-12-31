<?php

namespace GenericGateway;

class AuthorizeNETResponse
{

	// For ARB transactions
	public $subscription_id;
	public $subscription_paynum;

	const APPROVED = 1;
	const DECLINED = 2;
	const ERROR = 3;
	const HELD = 4;

	public $approved;
	public $declined;
	public $error;
	public $held;
	public $response_code;
	public $response_subcode;
	public $response_reason_code;
	public $response_reason_text;
	public $authorization_code;
	public $avs_response;
	public $transaction_id;
	public $invoice_number;
	public $description;
	public $amount;
	public $method;
	public $transaction_type;
	public $customer_id;
	public $first_name;
	public $last_name;
	public $company;
	public $address;
	public $city;
	public $state;
	public $zip_code;
	public $country;
	public $phone;
	public $fax;
	public $email_address;
	public $ship_to_first_name;
	public $ship_to_last_name;
	public $ship_to_company;
	public $ship_to_address;
	public $ship_to_city;
	public $ship_to_state;
	public $ship_to_zip_code;
	public $ship_to_country;
	public $tax;
	public $duty;
	public $freight;
	public $tax_exempt;
	public $purchase_order_number;
	public $md5_hash;
	public $card_code_response;
	public $cavv_response; // cardholder_authentication_verification_response
	public $account_number;
	public $card_type;
	public $split_tender_id;
	public $requested_amount;
	public $balance_on_card;
	public $response; // The response string from AuthorizeNet.


	/**
	 * Constructor.
	 *
	 * @param string $api_login_id
	 * @param string $md5_setting For verifying an Authorize.Net message.
	 */
	public function __construct($api_login_id = false, $md5_setting = false)
	{
		$this->api_login_id = ($api_login_id ? $api_login_id : (defined('AUTHORIZENET_API_LOGIN_ID') ? AUTHORIZENET_API_LOGIN_ID : ""));
		$this->md5_setting = ($md5_setting ? $md5_setting : (defined('AUTHORIZENET_MD5_SETTING') ? AUTHORIZENET_MD5_SETTING : ""));
		$this->response = $_POST;

		// Set fields without x_ prefix
		foreach ($_POST as $key => $value) {
			$name = substr($key, 2);
			$this->$name = $value;
		}

		// Set some human readable fields
		$map = array(
			'invoice_number' => 'x_invoice_num',
			'transaction_type' => 'x_type',
			'zip_code' => 'x_zip',
			'email_address' => 'x_email',
			'ship_to_zip_code' => 'x_ship_to_zip',
			'account_number' => 'x_account_number',
			'avs_response' => 'x_avs_code',
			'authorization_code' => 'x_auth_code',
			'transaction_id' => 'x_trans_id',
			'customer_id' => 'x_cust_id',
			'md5_hash' => 'x_MD5_Hash',
			'card_code_response' => 'x_cvv2_resp_code',
			'cavv_response' => 'x_cavv_response',
		);
		foreach ($map as $key => $value) {
			$this->$key = (isset($_POST[$value]) ? $_POST[$value] : "");
		}

		$this->approved = ($this->response_code == self::APPROVED);
		$this->declined = ($this->response_code == self::DECLINED);
		$this->error    = ($this->response_code == self::ERROR);
		$this->held     = ($this->response_code == self::HELD);
	}

	/**
	 * Verify the request is AuthorizeNet.
	 *
	 * @return bool
	 */
	public function isAuthorizeNet()
	{
		return count($_POST) && $this->md5_hash && ($this->generateHash() == $this->md5_hash);
	}

	/**
	 * Generates an Md5 hash to compare against Authorize.Net's.
	 *
	 * @return string Hash
	 */
	public function generateHash()
	{
		$amount = ($this->amount ? $this->amount : "0.00");
		return strtoupper(md5($this->md5_setting . $this->api_login_id . $this->transaction_id . $amount));
	}
}