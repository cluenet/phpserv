<?PHP

//	declare(ticks = 1);
	$__sig_fh__ = fopen('signals.log','a');

	function sig_handler($signo) {

		global $__sig_fh__;
		global $__sig_tmr__;
		fwrite($__sig_fh__,"Caught signal ".$signo."\n");

		switch ($signo) {

			case SIGTERM:
				if (function_exists('event')) { event("signal_term"); }
				posix_kill(posix_getppid(), SIGTERM);
				posix_kill($__sig_tmr__, SIGTERM);
				pcntl_waitpid($__sig_tmr__,$stat);
				exit;
				break;

			case SIGHUP:
				if (function_exists('event')) { event("signal_hup"); }
				posix_kill($__sig_tmr__, SIGTERM);
				pcntl_waitpid($__sig_tmr__,$stat);
				exit;
				break;

			case SIGALRM:
				if (function_exists('event')) { event("timer"); }
				break;

			case SIGCHLD:
				while (pcntl_waitpid(0, $status) != -1) {
					$status = pcntl_wexitstatus($status);
				}
				break;
		}

	}

	pcntl_signal(SIGTERM,   "sig_handler");
	pcntl_signal(SIGHUP,    "sig_handler");
	pcntl_signal(SIGALRM,   "sig_handler");
	pcntl_signal(SIGCHLD,	"sig_handler");

//	pcntl_alarm(1);
//	Here is our timer...
	$pid = posix_getpid();
	$__sig_tmr__ = pcntl_fork();
	if ($__sig_tmr__ == -1) {
		die('Could not fork...');
	} elseif ($__sig_tmr__) {
		unset($pid);
	} else {
		sleep(10);
		while (1) {
			sleep(1);
			posix_kill($pid,SIGALRM) or die();
		}
		die();
	}
?>
