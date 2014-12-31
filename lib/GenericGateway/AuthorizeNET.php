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

	public function __construct ($credentials) {

		$this->api_login_id = $credentials['api_login'];
		$this->transaction_key = $credentials['transaction_key'];
		$this->md5_setting = $credentials['md5_setting'];
		$this->relay_url = $credentials['relay_url'];

	}

	public function getCreditCardForm ($amount, $transactionID, $options) {

		$amount = floatval($amount);

		$testing = $options['testing']  ? $options['testing'] : false;
		$prefill = $options['prefill']  ? $options['prefill'] : false;

		return $this->cardForm(
			$amount,
			$transactionID,
			$testing,
			$prefill
		);

	}

	private function cardForm($amount, $transactionID, $test_mode = true, $prefill = true) {

		$time = time();
		$fp = self::getFingerprint($this->api_login_id, $this->transaction_key, $amount, $transactionID, $time);
		$post_url = ($test_mode ? self::SANDBOX_URL : self::LIVE_URL);

		$form = "
        <form method='post' action='$post_url'>
			<input type='hidden' name='x_amount' value='$amount'>
			<input type='hidden' name='x_delim_data' value='TRUE'>
			<input type='hidden' name='x_fp_hash' value='$fp'>
			<input type='hidden' name='x_fp_sequence' value='$transactionID'>
			<input type='hidden' name='x_fp_timestamp' value='$time'>
			<input type='hidden' name='x_login' value='$this->api_login_id'>
			<input type='hidden' name='x_relay_response' value='TRUE'>
			<input type='hidden' name='x_relay_url' value='$this->relay_url'>
			<input type='hidden' name='x_version' value='3.1'>
			<input type='hidden' name='x_delim_char' value=','>" .
            '<fieldset>
                <div>
                    <label>Credit Card Number</label>
                    <input type="text" class="text" size="15" name="x_card_num" value="'.($prefill ? '6011000000000012' : '').'"></input>
                </div>
                <div>
                    <label>Exp.</label>
                    <input type="text" class="text" size="4" name="x_exp_date" value="'.($prefill ? '04/17' : '').'"></input>
                </div>
                <div>
                    <label>CCV</label>
                    <input type="text" class="text" size="4" name="x_card_code" value="'.($prefill ? '782' : '').'"></input>
                </div>
            </fieldset>
            <fieldset>
                <div>
                    <label>First Name</label>
                    <input type="text" class="text" size="15" name="x_first_name" value="'.($prefill ? 'John' : '').'"></input>
                </div>
                <div>
                    <label>Last Name</label>
                    <input type="text" class="text" size="14" name="x_last_name" value="'.($prefill ? 'Doe' : '').'"></input>
                </div>
            </fieldset>
            <fieldset>
                <div>
                    <label>Address</label>
                    <input type="text" class="text" size="26" name="x_address" value="'.($prefill ? '123 Main Street' : '').'"></input>
                </div>
                <div>
                    <label>City</label>
                    <input type="text" class="text" size="15" name="x_city" value="'.($prefill ? 'Boston' : '').'"></input>
                </div>
            </fieldset>
            <fieldset>
                <div>
                    <label>State</label>
                    <input type="text" class="text" size="4" name="x_state" value="'.($prefill ? 'MA' : '').'"></input>
                </div>
                <div>
                    <label>Zip Code</label>
                    <input type="text" class="text" size="9" name="x_zip" value="'.($prefill ? '02142' : '').'"></input>
                </div>
                <div>
                    <label>Country</label>
                    <input type="text" class="text" size="22" name="x_country" value="'.($prefill ? 'US' : '').'"></input>
                </div>
            </fieldset>
            <input type="submit" value="BUY" class="submit buy">
        </form>';
		return $form;
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


	// returns a general gateway response object based on the request
	// this object can be used to validate the transaction
	// wraps AuthorizeNetSIM
	public function getResponse () {
		return new AuthorizeNETResponse();
	}

	// returns a response snippet to redirect appropriately
	// this is sent to Authorize and will be used to return the user to the site
	// wraps AuthorizeNetDPM::getRelayResponseSnippet
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



}