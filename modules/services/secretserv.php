<?PHP
	class secretserv {

		function construct() {
//			$this->event_eos();
		}

		function destruct() {

		}

		function event_msg ($from,$to,$message) {
			global $mysql;
			if (strtolower($to) == 'serverbot@'.$mysql->getsetting('server')) {
				$n = explode('@', $to);
				$n = $n[0];
				$d = explode(' ', $message);

				$ircd = &ircd();

				if (strtolower($d[0]) == 'say') {
					$ircd->servnotice($from,'Acknowledged.');
					$ircd->raw('PRIVMSG '.$d[1].' :'.implode(' ',array_slice($d,2)));

/*				} elseif (strtolower($d[0]) == 'kick') {
					$ircd->servnotice($from,'Acknowledged.');
					$ircd->raw('KICK '.$d[1].' '.$d[2].' :'.implode(' ',array_slice($d,3))); */

				} else {
					$ircd->raw('401 '.$from.' '.$n.' :No such nick/channel');
				}
			}
		}

		function event_eos () {

		}
	}

//	class modinit {
                function registerm () {
//                        global $modules;
                        $class = new secretserv;
                        register($class, __FILE__, 'SecretServ Module', 'secretserv');
		}
//	}
?>
