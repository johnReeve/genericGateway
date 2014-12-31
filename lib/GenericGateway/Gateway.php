<?php

namespace GenericGateway;

interface Gateway {

	// returns markup for a payment form
	// this will be output on the payment page
	// wrapper for AuthorizeNetDPM::getCreditCardForm
	public function getCreditCardForm ($amount, $transactionID, $options);

	// returns a general gateway response object based on the request
	// this object can be used to validate the transaction
	// wraps AuthorizeNetSIM
	public function getResponse ();

	// returns a response snippet to redirect appropriately
	// this is sent to Authorize and will be used to return the user to the site
	// wraps AuthorizeNetDPM::getRelayResponseSnippet
	public function getResponseSnippet ($redirect_url);

}