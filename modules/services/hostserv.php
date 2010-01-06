<?PHP

	class hostserv {
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
			
			$cu->registercommand($this, 'auth', 'ON','Activates your assigned vHost.');
			// Disabled /hs off because it's pointless and abusable. Wrote the code anyway...
			//$cu->registercommand($this, 'auth', 'OFF','Deactivates your vHost.');
			$cu->registercommand($this, 'auth', 'REQUEST','<vhost> - Requests a vHost from HostServ.');
			$cu->registercommand($this, 'auth', 'DEL','Deletes your vHost.');

			$cu->registercommand($this, 'setter', 'ON','Activates your assigned vHost.');
			//$cu->registercommand($this, 'setter', 'OFF','Deactivates your vHost.');
			$cu->registercommand($this, 'setter', 'REQUEST','<vhost> - Requests a vHost from HostServ.');
			$cu->registercommand($this, 'setter', 'SET','<nick> <vhost> - Assigns a vHost on a user.');
			$cu->registercommand($this, 'setter', 'ACTIVATE','<nick> - Activates a requested vHost.');
			$cu->registercommand($this, 'setter', 'ACCEPT','<nick> - Alias to ACCEPT.');
			$cu->registercommand($this, 'setter', 'WAITING','Lists requested vHosts.');
			$cu->registercommand($this, 'setter', 'REJECT','<nick> - Rejects a waiting vHost.');
		}
		
		function command_auth_request($from,$to,$rest,$extra) {
			global $mysql;
			$ircd = &ircd();
			
			$vhost = $rest[0];
			$uid = $extra['uid'];
			if ($ircd->isValidHost($vhost)) {
				$mysql->insert('hostserv',array('uid' => $uid,'host' => $vhost,'active' => 0));
				$ircd->notice('HostServ',$from,'Queued vHost "'.$vhost.'" for oper verification.');
				$ircd->msg('HostServ','#services','New vHost requested by '.$from.' (Account '.$extra['nickd']['user'].')');
			} else {
				$ircd->notice('HostServ',$from,'Invalid hostname. Please try again.');
			}
		}
		
		function command_auth_on($from,$to,$rest,$extra) {
			global $mysql;
			$ircd = &ircd();
			
			$data = $mysql->get($mysql->sql('SELECT * FROM `hostserv` WHERE `active` = 1 AND `uid` = '.$mysql->escape($extra['uid'])));
			$active = $data['active'];
			$vhost = $data['host'];
			
			// Do they have a vHost?
			if ($active == 0 || $active == false) {
				// They don't have a vHost. Why?
				if ($active == 0) {
					$ircd->notice('HostServ',$from,'Your vHost ('.$vhost.') is awaiting verification.');
				} else {
					$ircd->notice('HostServ',$from,'There is no vHost assigned to this account.');
				}
			} else {
				// They have a vHost, set it.
				$ircd->chghost('HostServ',$from,$vhost);
				$ircd->notice('HostServ',$from,'Your displayed host is now "'.$vhost.'".');
			}
		}
		
		function command_auth_off($from,$to,$rest,$extra) {
			global $mysql;
			$ircd = &ircd();
			
			$data = $mysql->get($mysql->sql('SELECT `active` FROM `hostserv` WHERE `active` = 1 AND `uid` = '.$mysql->escape($extra['uid'])));
			$active = $data['active'];
			
			if ($active == false) {
				$ircd->notice('HostServ',$from,'There is no vHost assigned to this account.');
			} else {
				$ircd->remhost('HostServ',$from);
			}
		}
		
		// You can use $mysql->sql('UPDATE `hostserv` SET `active` = 1 WHERE `uid` = '.$mysql->escape($userid)); to activate them.
		// You can use $mysql->sql('DELETE FROM `hostserv` WHERE `uid` = '.$mysql->escape($userid)); to reject one.
		// You can use $mysql->get($mysql->sql('SELECT `host` FROM `hostserv` WHERE `active` = 1 AND `uid` = '.$mysql->escape($userid))); to get an array containing one element, the host of the user ... or false if no record exists.

		function command_setter_on($from,$to,$rest,$extra) {
			return $this->command_auth_on($from,$to,$rest,$extra);
		}
		
		function command_setter_request($from,$to,$rest,$extra) {
			return $this->command_auth_request($from,$to,$rest,$extra);
		}
		
		function command_setter_set($from,$to,$rest,$extra) {
			global $mysql;
			$ircd = &ircd();
			
			if ($ircd->isValidHost($rest[1]) == 0) {
				$ircd->notice('HostServ',$from,'Invalid hostname. Try again later.');
				return 0;
			}
			if ($mysql->get($mysql->sql('SELECT `user` FROM `access` WHERE `user` = '.$mysql->escape($rest[0]))) == false) {
				$ircd->notice('HostServ',$from,$rest[0].' is not a PHPserv account.');
				return 0;
			}
			if ($mysql->get($mysql->sql('SELECT `host` FROM `hostserv` WHERE `active` = 1 AND `uid` = '.$mysql->escape($extra['uid'])))) {
				$mysql->insert('hostserv',array('uid' => $uid,'host' => $vhost,'active' => 0));
			} else {
				$mysql->sql('UPDATE `hostserv` SET `host` = '.$mysql->escape($rest[0]).', `active` = 1 WHERE `uid` = '.$mysql->escape($extra['uid']));
			}
			$ircd->notice('HostServ',$from,'vHost "'.$rest[1].'" assigned to '.$rest[0].'.');
			$ircd->chghost('HostServ',$rest[0],$rest[1]);
		}

		function command_setter_waiting($from,$to,$rest,$extra) {
			global $mysql;
			$ircd = &ircd();
			
			// Copypasta query from Cobi
			$result = $mysql->sql('SELECT `u`.`user` AS `username`,`hs`.`host` AS `vhost` FROM `hostserv` AS `hs`, `access` as `u` WHERE `u`.`id` = `hs`.`uid` AND `hs`.`active` = 0');
			$ircd->notice('HostServ',$from,'List of waiting vHosts:');
			while ($row = $mysql->get($result)) {
				$ircd->notice('HostServ',$from,$row['user'].' - '.$row['host']);
			}
			$ircd->notice('HostServ',$from,'End of waiting list.');
		}

		function command_setter_del($from,$to,$rest,$extra) {
			global $mysql;
			$ircd = &ircd();
			
			$data = $mysql->get($mysql->sql('SELECT `host` FROM `hostserv` WHERE `active` = 1 AND `uid` = '.$mysql->escape($extra['uid'])));
			if ($data == false) {
				$ircd->notice('HostServ',$from,$rest[0].' does not have a vHost!');
			} else {
				$this->delhost($extra['uid']);
				$ircd->remhost('HostServ',$rest[0]);
				$ircd->notice('HostServ',$from,'Deleted vHost for '.$rest[0]);
			}
		}
		
		function command_setter_reject($from,$to,$rest,$extra) {
			global $mysql;
			$ircd = &ircd();
			
			$data = $mysql->get($mysql->sql('SELECT `host` FROM `hostserv` WHERE `active` = 0 AND `uid` = '.$mysql->escape($extra['uid'])));
			if ($data == false) {
				$ircd->notice('HostServ',$from,$rest[0].' did not request a vHost!');
			} else {
				$this->delhost($extra['uid']);
				$ircd->notice('HostServ',$from,'vHost for '.$rest[0].' rejected.');
			}
		}
		
		function command_setter_activate($from,$to,$rest,$extra) {
			global $mysql;
			$ircd = &ircd();
			
			$data = $mysql->get($mysql->sql('SELECT `host` FROM `hostserv` WHERE `active` = 0 AND `uid` = '.$mysql->escape($extra['uid'])));
			if ($data = false) {
				$ircd->notice('HostServ',$from,$rest[0].' did not request a vHost!');
			} else {
				$mysql->sql('UPDATE `hostserv` SET `host` = '.$mysql->escape($rest[0]).', `active` = 1 WHERE `uid` = '.$mysql->escape($extra['uid']));
				$ircd->notice('HostServ',$from,'vHost for '.$rest[0].' activated.');
				$ircd->chghost('HostServ',$rest[0],$data['host']);
			}
		}
		
		function command_auth_del($from,$to,$rest,$extra) {
			global $mysql;
			$ircd = &ircd();
			
			$data = $mysql->get($mysql->sql('SELECT `host` FROM `hostserv` WHERE `uid` = '.$mysql->escape($extra['uid'])));
			if ($data == false) {
				$ircd->notice('HostServ',$from,'You don\'t have a vHost!');
			} else {
				$this->delhost($extra['uid']);
				$ircd->remhost('HostServ',$from);
			}
		}
		
		function event_identify($nick,$uid) {
			global $mysql;
			$ircd = &ircd();
			
			$hostd = $mysql->get($mysql->sql('SELECT * FROM `hostserv` WHERE `uid` = '.$mysql->escape($uid)));
			if ($hostd == false || $hostd['active'] == 0) {
				return;
			} else {
				$ircd->chghost('HostServ',$nick,$hostd['host']);
				$ircd->notice('HostServ',$nick,'Your vHost of '.$hostd['host'].' is now activated.');
			}
		}
			
		function delhost($uid) {
			global $mysql;
			$ircd = &ircd();
			
			// $uid = the UID's host to delete
			$mysql->sql('DELETE FROM `hostserv` WHERE `uid` = '.$mysql->escape($uid));
			return 1;
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
				
				$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
				$uid = $nickd['loggedin'];
				
				if ($uid == -1) {
					$ircd->notice('HostServ',$from,'You need to be identified to PHPserv to use HostServ.');
					return 0;
				}
				
				if ($mysql->getaccess($from) > 49) { $what = 'setter'; }
				else { $what = 'auth'; }
				
				getmod('commandutils')->parsecommand($this,$what, $from, $to, $message, array('uid' => $uid,'nickd' => $nickd));
			}
		}

		function event_kill($from, $to, $reason) {
			global $mysql;
			$ircd = &ircd();

			if (strtolower($to) == 'hostserv') {
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
		$class = new hostserv;
		register($class, __FILE__, 'HostServ Module', 'hostserv');
	}
?>