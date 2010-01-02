<?PHP
	error_reporting(0);
	function error_handler ($errno, $errstr, $errfile, $errline) {
		switch ($errno) {
			default:
				$EXIT = FALSE;
				logit('Error: ID: '.$errno.' Message: '.$errstr.' File: '.$errfile.' Line: '.$errline);;
				break;
		}
	}
	set_error_handler("error_handler", E_ALL);
	function exception_handler ($exception) {
		$EXIT = FALSE;
		logit(print_r($exception,1));
		break;
	}
	set_exception_handler("exception_handler");

?>

