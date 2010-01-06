<?PHP
	class logger {
		private static $lf;
		private $conn = false;

		function construct () {
			$this->lf = fopen('phpserv.log','a');
			if (!$this->lf) { die("Uh-oh, can't open the log file..."); }
			global $connected;
			if ($connected) { $this->event_eos(); }
		}

		function l2f ($d) {
			/* log to file */
			fwrite($this->lf,$d."\n");
		}

		function log ($str) {
			/* compatibility */
			if(strpos($str,'stream_select(): unable to select [4]: Interrupted system call') == false)
				$this->l2c($str);
		}

		function l2c ($str) {
			/* log to channel */
			if ($this->conn) {
				$ircd = &ircd();
				$d = explode("\n",$str);
				foreach ($d as $x) {
					$ircd->msg('SVSLOG','#services',$x);
				}
			} else {
				$this->l2f($str);
			}
		}

		function event_raw_in ($str) {
			if( fnmatch( '*serv :identify *', strtolower( $str ) ) )
				return;
			$this->l2f('IN  -- '.$str);
		}

		function event_raw_out ($str) {
			$this->l2f('OUT -- '.$str);
		}

		function event_eos ($a) {
			global $mysql;
			$ircd = &ircd();
			$ircd->addnick($mysql->getsetting('server'),'SVSLOG','Services','phpserv.cluenet.org','SVSLOGGER');
			$this->conn = true;
			$ircd->join('SVSLOG','#services');
			logit('[Logger] Now logging to #services.');
		}

		function event_kill ($from,$to,$reason) {
			global $mysql;
			$ircd = &ircd();

			if (strtolower($to) == 'svslog') {
				$ircd->addnick($mysql->getsetting('server'),'SVSLOG','Services','phpserv.cluenet.org','SVSLOGGER');
				$ircd->join('SVSLOG','#services');
				logit('[Logger] Now resuming logging to #services.');
			}
		}

	}

	function registerm () {
		$class = new logger;
		register($class, __FILE__, 'Combined Logger Module', 'log');
	}
?>
