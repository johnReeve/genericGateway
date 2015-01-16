<?php

namespace GenericGateway;

/**
 * Class AuthorizeNET
 * @package GenericGateway
 */
class AuthorizeNET implements Gateway {

	const LIVE_URL = 'https://secure.authorize.net/gateway/transact.dll';
	const SANDBOX_URL = 'https://test.authorize.net/gateway/transact.dll';

	public $api_login_id = "";
	public $transaction_key = "";
	public $md5_setting = "";

	private $defaultFormMarkup = "
		<form method='post' action='<% post_url %>'>
			<input type='hidden' name='x_amount' value='<% amount %>'>
			<input type='hidden' name='x_delim_data' value='TRUE'>
			<input type='hidden' name='x_fp_hash' value='<% fp %>'>
			<input type='hidden' name='x_fp_sequence' value='<% transaction_id %>'>
			<input type='hidden' name='x_invoice_num' value='<% transaction_id %>'>
			<input type='hidden' name='x_fp_timestamp' value='<% time %>'>
			<input type='hidden' name='x_login' value='<% api_login_id %>'>
			<input type='hidden' name='x_relay_response' value='TRUE'>
			<input type='hidden' name='x_relay_url' value='<% relay_url %>'>
			<input type='hidden' name='x_version' value='3.1'>
			<input type='hidden' name='x_delim_char' value=','>
			<fieldset>
                <div>
                    <label>Credit Card Number</label>
                    <input type='text' class='text' size='15' name='x_card_num' value='<% x_card_num %>'>
                </div>
                <div>
                    <label>Exp.</label>
                    <input type='text' class='text' size='4' name='x_exp_date' value='<% x_exp_date %>'>
                </div>
                <div>
                    <label>CCV</label>
                    <input type='text' class='text' size='4' name='x_card_code' value='<% x_card_code %>'>
                </div>
            </fieldset>
            <fieldset>
                <div>
                    <label>First Name</label>
                    <input type='text' class='text' size='15' name='x_first_name' value='<% x_first_name %>'>
                </div>
                <div>
                    <label>Last Name</label>
                    <input type='text' class='text' size='14' name='x_last_name' value='<% x_last_name %>'>
                </div>
            </fieldset>
            <fieldset>
                <div>
                    <label>Address</label>
                    <input type='text' class='text' size='26' name='x_address' value='<% x_address %>'>
                </div>
                <div>
                    <label>City</label>
                    <input type='text' class='text' size='15' name='x_city' value='<% x_city %>'>
                </div>
            </fieldset>
            <fieldset>
                <div>
                    <label>State</label>
                    <input type='text' class='text' size='4' name='x_state' value='<% x_state %>'>
                </div>
                <div>
                    <label>Zip Code</label>
                    <input type='text' class='text' size='9' name='x_zip' value='<% x_zip %>'>
                </div>
                <div>
                    <label>Country</label>
                    <input type='text' class='text' size='22' name='x_country' value='<% x_country %>'>
                </div>
            </fieldset>
            <input type='submit' value='BUY' class='submit buy'>
		</form>
	";

	/**
	 * @param array $credentials - holds api keys and whatnot
	 * @param array $options - holds information for constructing the Gateway
	 */
	public function __construct ($credentials = array(), $options= array()) {

		$this->api_login_id = $credentials['api_login'];
		$this->transaction_key = $credentials['transaction_key'];
		$this->md5_setting = $credentials['md5_setting'];
		$this->relay_url = $options['relay_url'];

	}

	/**
	 * returns the markup for the form
	 *
	 * @param $amount - the amount of the transaction
	 * @param $transactionID - an id for the transaction
	 * @param $options
	 *
	 * @return string - the necessary markup for the form
	 */
	public function getPaymentFormMarkup ($amount, $transactionID, $options, $templateString = "") {

		$templateString = $templateString ? $templateString : $this->defaultFormMarkup;

		return  $this->stringFromTemplate(
			$templateString,
			$this->getPaymentFormFieldArray($amount, $transactionID, $options)
		);
	}

	/**
	 * returns an array of fields and keys for a form
	 * @param $amount
	 * @param $transactionID
	 * @param $options
	 *
	 * @return array
	 */
	public function getPaymentFormFieldArray ($amount, $transactionID, $options) {

		$testing      = $options['testing']  ? $options['testing'] : false;
		$time = time();

		$formValueArray = array();
		$formValueArray['post_url'] =  ($testing ? self::SANDBOX_URL : self::LIVE_URL);
		$formValueArray['time'] = $time;
		$formValueArray['amount'] = floatval($amount);
		$formValueArray['fp'] = self::getFingerprint($this->api_login_id, $this->transaction_key, $amount, $transactionID, $time);
		$formValueArray['transaction_id'] = $transactionID;
		$formValueArray['api_login_id'] = $this->api_login_id;
		$formValueArray['relay_url'] = $this->relay_url;

		// it is nice to be able to prefill stuff when we are testing:
		if ($options['prefill']) {
			$formValueArray["x_card_num"] = '6011000000000012';
			$formValueArray["x_exp_date"] = '04/17';
			$formValueArray["x_card_code"] = '782';
			$formValueArray["x_first_name"] = 'John';
			$formValueArray["x_last_name"] = 'Doe';
			$formValueArray["x_address"] = '123 Main Street';
			$formValueArray["x_city"] = 'Boston';
			$formValueArray["x_state"] = 'MA';
			$formValueArray["x_zip"] = '02142';
			$formValueArray["x_country"] = 'US';
		}

		return $formValueArray;
	}


	/**
	 * Generates a fingerprint needed for a hosted order form or DPM.
	 *
	 * @param string $api_login_id    Login ID.
	 * @param string $transaction_key API key.
	 * @param string $amount          Amount of transaction.
	 * @param string $fp_sequence     An invoice number or random number.
	 * @param string $fp_timestamp    Timestamp.
	 *
	 * @return string The fingerprint.
	 */
	private static function getFingerprint($api_login_id, $transaction_key, $amount, $fp_sequence, $fp_timestamp)
	{
		$api_login_id = ($api_login_id ? $api_login_id : (defined('AUTHORIZENET_API_LOGIN_ID') ? AUTHORIZENET_API_LOGIN_ID : ""));
		$transaction_key = ($transaction_key ? $transaction_key : (defined('AUTHORIZENET_TRANSACTION_KEY') ? AUTHORIZENET_TRANSACTION_KEY : ""));
		if (function_exists('hash_hmac')) {
			return hash_hmac("md5", $api_login_id . "^" . $fp_sequence . "^" . $fp_timestamp . "^" . $amount . "^", $transaction_key);
		}
		return bin2hex(mhash(MHASH_MD5, $api_login_id . "^" . $fp_sequence . "^" . $fp_timestamp . "^" . $amount . "^", $transaction_key));
	}

	/**
	 * Creates and returns a response objectto be used by the transaction
	 *
	 * @return AuthorizeNETResponse
	 */
	public function getResponse () {
		return new AuthorizeNETResponse();
	}


	/**
	 * A snippet to send to AuthorizeNet to redirect the user back to the
	 * merchant's server. Use this on your relay response page.
	 *
	 * @param string $redirect_url Where to redirect the user.
	 *
	 * @return string
	 */
	public function getResponseSnippet($redirect_url)
	{
		return "<html><head><script language=\"javascript\">
                <!--
                window.location=\"{$redirect_url}\";
                //-->
                </script>
                </head><body><noscript><meta http-equiv=\"refresh\" content=\"1;url={$redirect_url}\"></noscript></body></html>";
	}

	/**
	 * This is a super-simple template parser that allows us to generate the form a little more flexibly
	 *
	 * @param string $templateString should contain tags of the form <% array_key %> that are replaced
	 * @param array $formElementArray the key:value hash that will fill out the form
	 *
	 * @return mixed|string
	 */
	private function stringFromTemplate ($templateString = "", $formElementArray = array() ) {
		$output = $templateString;
		foreach ($formElementArray as $formElementName => $formElementVal ) {
			$output = str_replace("<% $formElementName %>", $formElementVal , $output);
		}
		// remove any unfilled tags
		$output = preg_replace("/<%(.*?)%>/", "", $output);

		return $output;
	}

}