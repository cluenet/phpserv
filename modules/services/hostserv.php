<?PHP

	class hostserv {
		function construct() {
			$this->event_eos('a');
		}

		function event_module_loaded($file) {
			if (realpath($file) == realpath(__FILE__))
				load('modules/misc/commandutils.php');
		}

		function event_commandutils_load() {
			if (!ismod('commandutils')) {
				logit('Huh?  We were told commandutils was ready, but it does not exist.');
				return;
			}

			$cu = getmod('commandutils');
		}

		function destruct() {
			$ircd = &ircd();
			$ircd->quit('HostServ', 'La Gone!');
		}

		function event_msg($from, $to, $message) {
			global $mysql;
			$ircd = &ircd();

			if ((strtolower($to) == 'hostserv') or (strtolower($to) == 'hostserv@'.strtolower($mysql->getsetting('server')))) {
				$to = explode('@', $to, 2);
				$to = $to[0];

				$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = ' . $mysql->escape($from)));
				$uid = $nickd['loggedin'];

				if($uid == -1)
					$ircd->notice($to,$from,'You must be identified to use HostServ.');
				else
					getmod('commandutils')->parsecommand($this, 'auth', $from, $to, $message, array('uid' => $uid, 'nickd' => $nickd));
			}
		}

		function event_kill($from, $to, $reason) {
			global $mysql;
			$ircd = &ircd();

			if (strtolower($to) == 'chanserv') {
				$ircd->addnick($mysql->getsetting('server'), 'HostServ', 'Services', 'Services.ClueNet.Org', 'vHost Service');
				$ircd->join('HostServ', '#services');
				$ircd->chghost('HostServ',$from, 'bot.killer');
			}
		}

		function event_eos($a) {
			global $mysql;
			$ircd = &ircd();

			$ircd->addnick($mysql->getsetting('server'), 'HostServ', 'Services', 'Services.ClueNet.Org', 'vHost Service');
			$ircd->join('HostServ', '#services');
		}
	}

	function registerm() {
		$class = new chanserv;
		register($class, __FILE__, 'HostServ Module', 'hostserv');
	}
