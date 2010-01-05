<?PHP

/**
 * The MySQL table 'schema' for this bot: (table name `chanserv`)
 *
 * id INT NOT NULL AUTO_INCREMENT PRIMARY KEY
 * channel VARCHAR(32) NOT NULL UNIQUE KEY
 * owner INT
 */

	class chanserv {
		function construct() {
			$this->event_eos('a');
			if(ismod('commandutils'))
				$this->event_commandutils_load();
		}

		function event_module_loaded($file) {
			if (realpath($file) == realpath(__FILE__))
				if(!ismod('commandutils'))
					load('modules/misc/commandutils.php');
		}

		function event_commandutils_load() {
			if (!ismod('commandutils')) {
				logit('Huh?  We were told commandutils was ready, but it does not exist.');
				return;
			}

			$cu = getmod('commandutils');
			$cu->registercommand($this, 'auth', 'REGISTER', '<channel> - Registers your channel on ChanServ');
			$cu->registercommand($this, 'auth', 'DROP', '<channel> - Drops the channel so other people can register it');
			$cu->registercommand($this, 'auth', 'OP', '<channel> [nick] - Ops you (or the given nick) on the given channel if you have access.');
			$cu->registercommand($this, 'auth', 'INFO', '<channel> - Returns some basic info on the channel.');
			$cu->registercommand($this, 'auth', 'DEOP', '<channel> [nick] - Deops you (or the given nick) on the given channel if you have access.');
		}

		function destruct() {
			$ircd = &ircd();
			$ircd->quit('ChanServ', 'La Gone!');
		}

		function command_auth_register($from, $to, $rest, $extra) {
			global $mysql;
			$ircd = &ircd();

			if (!isset($rest[0])) {
				$ircd->notice($to, $from, 'You need to supply a channel name.');
			} else {
				if ($mysql->get($mysql->sql('SELECT * FROM `chanserv` WHERE `channel` = ' . $mysql->escape($rest[0])))) {
					$ircd->notice($to, $from, 'That channel is already owned by someone else.');
				} else {
					$data = array(
						'id'		=> 'NULL',
						'channel'	=> $rest[0],
						'owner'		=> $extra['uid']
					);
					$mysql->insert('chanserv', $data);
					$ircd->notice($to, $from, 'Success. The channel ' . $rest[0] . ' is now registered.');
				}
			}
		}

		// TODO: Better ACL.
		function is_allowed($uid, $channel, $perm) {
			global $mysql;
			if ($mysql->get($mysql->sql('SELECT * FROM `chanserv` WHERE `channel`  = ' . $mysql->escape($channel)
					. 'AND `owner` = ' . $mysql->escape($uid))))
				return true;
			else
				return false;
		}

		function command_auth_op($from, $to, $rest, $extra) {
			$ircd = &ircd();

			if (!isset($rest[0])) {
				$ircd->notice($to, $from, 'You need to supply a channel name.');
			} else {
				if ($this->is_allowed($extra['uid'], $rest[0], 'op')) {
					$user = (isset($rest[1]) ? $rest[1] : $from);
					$ircd->svsmode('ChanServ', $rest[0], '+o ' . $user);
				} else {
					$ircd->notice($to, $from, 'Access denied.');
				}
			}
		}

		function command_auth_deop($from, $to, $rest, $extra) {
			$ircd = &ircd();

			if (!isset($rest[0])) {
				$ircd->notice($to, $from, 'You need to supply a channel name.');
			} else {
				if ($this->is_allowed($extra['uid'], $rest[0], 'deop')) {
					$user = (isset($rest[1]) ? $rest[1] : $from);
					$ircd->svsmode('ChanServ', $rest[0], '-o ' . $user);
				} else {
					$ircd->notice($to, $from, 'Access denied.');
				}
			}
		}

		function command_auth_info($from, $to, $rest, $extra) {
			global $mysql;
			$ircd = &ircd();

			if (!isset($rest[0])) {
				$ircd->notice($to, $from, 'You need to supply a channel name.');
			} else {
				$data = $mysql->get(
					$mysql->sql(
						'SELECT `access`.`user` FROM `access`, `chanserv` WHERE `chanserv`.`channel` = ' . $mysql->escape($rest[0])
						. 'AND `access`.`id` = `chanserv`.`owner`'));
				if ($data) {
					$ircd->notice($to, $from, '---- Info ----');
					$ircd->notice($to, $from, 'Channel: ' . $rest[0]);
					$ircd->notice($to, $from, 'Owner: ' . $data['user']);
					$ircd->notice($to, $from, '---- End Info ----');
				} else {
					$ircd->notice($to, $from, 'That channel doesn\'t exist');
				}
			}
		}

		function command_auth_drop($from, $to, $rest, $extra) {
			$ircd = &ircd();

			if (!isset($rest[0])) {
				$ircd->notice($to, $from, 'You need to supply a channel name.');
			} else {
				if ($this->is_allowed($extra['uid'], $rest[0], 'drop')) {
					$mysql->sql('DELETE FROM `chanserv` WHERE `channel` = ' . $mysql->escape($rest[0]));
					$ircd->notice($to, $from, 'Channel ' . $rest[0] . ' dropped.');
				} else {
					$ircd->notice($to, $from, 'Access denied.');
				}
			}
		}

		function event_msg($from, $to, $message) {
			global $mysql;
			$ircd = &ircd();

			if ((strtolower($to) == 'chanserv') or (strtolower($to) == 'chanserv@'.strtolower($mysql->getsetting('server')))) {
				$to = explode('@', $to, 2);
				$to = $to[0];

				$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = ' . $mysql->escape($from)));
				$uid = $nickd['loggedin'];

				if($uid == -1)
					$ircd->notice($to,$from,'You must be identified to use ChanServ.');
				else
					getmod('commandutils')->parsecommand($this, 'auth', $from, $to, $message, array('uid' => $uid, 'nickd' => $nickd));
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
