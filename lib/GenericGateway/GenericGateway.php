<?php

namespace GenericGateway;

/**
 * Class GenericGateway
 *
 * a factory that will find the correct gateway object, create it, and return it
 *
 * @package GenericGateway
 *
 */

class GenericGateway {
	public static function getGateway($credentials, $options) {
		$className = "\\GenericGateway\\" . $options['type'];
		return new $className($credentials, $options);
	}
}