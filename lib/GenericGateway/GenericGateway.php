<?php

namespace GenericGateway;

class GenericGateway {
	public static function getGateway($credentials, $options) {
		$className = "\\GenericGateway\\" . $options['type'];
		return new $className($credentials);
	}
}