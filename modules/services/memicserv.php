<?PHP
	class memicserv {
		function construct() {
			$this->event_eos();
		}

		function destruct() {
			$ircd = &ircd();
			$ircd->quit('memicserv', 'Unloaded.');
		}

		function event_msg ($from,$to,$message) {
			global $settings;
			$ircd = &ircd();
			if (substr($to,0,1) == "#") {
				$ircd->msg('memicserv', $to, $from.' said: '.$message);	
			}
		}

		function event_eos () {
			global $mysql;
			$ircd = &ircd();

			$ircd->addnick($mysql->getsetting('server'),'MemicServ','Services','Services.OpticPhase.Org','Bot Service');
			$ircd->join('MemicServ','#services');
		}
	}

	function registerm () {
		$class = new memicserv;
		register($class, __FILE__, 'MemicServ Module', 'memicserv');
	}
?>
