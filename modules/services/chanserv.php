<?PHP
	class chanserv {
		function construct() {
			$this->event_eos('a');
		}

		function destruct() {
			$ircd = &ircd();
			$ircd->quit('ChanServ', 'La Gone!');
		}

		function event_msg($from, $to, $message) {
			global $mysql;
			$ircd = &ircd();

			if ((strtolower($to) == 'chanserv') or (strtolower($to) == 'chanserv@'.strtolower($mysql->getsetting('server')))) {
				$to = explode('@', $to, 2);
				$to = $to[0];
				$d = explode(' ', $message);

				$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = ' . $mysql->escape($from)));
				$uid = $nickd['loggedin'];

				$command = strtolower($d[0]);

				if ($command != 'help' && $uid == -1) {
					$ircd->notice($to, $from, 'You need to be identified to PHPserv to do that.');
					return 0;
				}

				switch($command) {
				case 'help':
					$ircd->notice($to, $from, 'Hi, I\'m ChanServ! I will keep other users from stealing your channel(s).');
					$ircd->notice($to, $from, 'Below is a list of supported commands:');
					$ircd->notice($to, $from, 'REGISTER <channel>    - Registers your channel on ChanServ');
					$ircd->notice($to, $from, 'OP <channel> [nick]   - Ops you (or the given nick) on the given channel if you have access.');
					$ircd->notice($to, $from, 'DEOP <channel> [nick] - Deops you (or the given nick) on the given channel if you have access.');
					$ircd->notice($to, $from, 'DROP <channel>        - Drops the channel so other people can register it');
					$ircd->notice($to, $from, ' ');
					$ircd->notice($to, $from, 'Enjoy my services! I live to serve!');
					break;

				case 'register':
					if (!isset($d[1])) {
						$ircd->notice($to, $from, 'You need to supply a channel name.');
					} else {
						if ($mysql->get($mysql->sql('SELECT * FROM `chanserv` WHERE `channel` = ' . $mysql->escape($d[1])))) {
							$ircd->notice($to, $from, 'That channel is already owned by someone else.');
							return 0;
						} else {
							$data = array (
								'id'		=> 'NULL',
								'channel'	=> $d[0],
								'owner'		=> $uid
							);
							$mysql->insert('chanserv', $data);
							$ircd->notice($to, $from, 'Success. The channel ' . $d[1] . ' is now registered.');
							return 1;
						}
					}
					break;

				case 'op':
				case 'deop':
					if (!isset($d[1])) {
						$ircd->notice($to, $from, 'You need to supply a channel name.');
					} else {
						// TODO: Better ACL.
						$data = $mysql->get($mysql->sql('SELECT * FROM `chanserv` WHERE `channel` = ' . $mysql->escape($d[1])));
						if ($uid == $data['owner']) {
							$ircd->svsmode('ChanServ', $d[1], ($command == 'op' ? '+o ' : '-o ') . (isset($d[2]) ? $d[2] : $from));
							return 1;
						} else {
							$ircd->notice($to, $from, 'You don\'t have the access to do that.');
							return 0;
						}
					}
					break;

				case 'drop':
					if (!isset($d[1])) {
						$ircd->notice($to, $from, 'You need to supply a channel name.');
						return 0;
					} else {
						$data = $mysql->get($mysql->sql('SELECT * FROM `chanserv` WHERE `channel` = ' . $mysql->escape($d[1])));
						if ($uid == $data['owner']) {
							$mysql->sql('DELETE FROM `chanserv` WHERE `channel` = ' . $mysql->escape($d[1]));
							$ircd->notice($to, $from, 'Channel ' . $d[1] . ' dropped.');
							return 1;
						} else {
							$ircd->notice($to, $from, 'You don\'t own that channel!');
							return 0;
						}
					}
					break;

				default:
					$ircd->notice($to, $from, 'Unknown command "' . $d[0] . '". Try "/msg ChanServ HELP" instead.');
					break;
				}
			}
		}

		function event_kill($from, $to, $reason) {
			global $mysql;
			$ircd = &ircd();

			if (strtolower($to) == 'chanserv') {
				$ircd->addnick($mysql->getsetting('server'), 'ChanServ', 'Services', 'Services.ClueNet.Org', 'Channel Service');
				$ircd->join('ChanServ', '#services');
				$ircd->svskill($from, 'Killing service bots isn\'t a smart idea.');
			}
		}

		function event_eos($a) {
			global $mysql;
			$ircd = &ircd();

			$ircd->addnick($mysql->getsetting('server'), 'ChanServ', 'Services', 'Services.ClueNet.Org', 'Channel Service');
			$ircd->join('ChanServ', '#services');
		}
	}

	function registerm() {
		$class = new chanserv;
		register($class, __FILE__, 'ChanServ Module', 'chanserv');
	}
