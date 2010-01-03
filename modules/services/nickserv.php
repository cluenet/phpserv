<?PHP
	class nickserv {
		function construct () {
			$this->event_eos('a');
		}

		function destruct () {
			$ircd = &ircd();
			$ircd->quit('NickServ','La Gone!');
		}

		function event_msg ($from,$to,$message) {
			global $mysql;
			$ircd = &ircd();
			
			if ((strtolower($to) == 'nickserv') or (strtolower($to) == 'nickserv@'.strtolower($mysql->getsetting('server')))) {
				$to = explode('@', $to, 2);
				$to = $to[0];
				$d = explode(' ', $message);
				
				$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
				$uid = $nickd['loggedin'];
				
				if ($strtolower($d[0]) == 'help') {
					$ircd->notice($to,$from,'Hi, I\'m NickServ! I will keep other users from stealing your nickname.');
					$ircd->notice($to,$from,'Below is a list of supported commands:');
					$ircd->notice($to,$from,'REGISTER                    - Link your nick to your PHPserv account.');
					$ircd->notice($to,$from,'IDENTIFY <password>[:user]  - Identify to your PHPserv account.');
					$ircd->notice($to,$from,'GHOST <user>                - Kill a user on your nickname.');
					$ircd->notice($to,$from,'DROP                        - Unlink your nick from your PHPserv account.');
					$ircd->notice($to,$from,' ');
					$ircd->notice($to,$from,'Enjoy my services! I live to serve!');
					return 1;
				}

				if ($uid != -1) {
					if (strtolower($d[0]) == 'register') {
						if ($mysql->get($mysql->sql('SELECT * FROM `nickserv` WHERE `nick` = '.$mysql->escape($from)))) {
							$ircd->notice($to,$from,'Your nick is already owned by someone else.');
							return 0;
						} else {
							$data = array
							(
								'id'		=> 'NULL',
								'userid'	=> $uid,
								'nick'		=> $from
							);
							$mysql->insert('nickserv',$data);
							$ircd->notice($to,$from,'Success. Your nick is now linked to your PHPServ Account.');
							return 1;
						}
					} elseif (strtolower($d[0]) == 'ghost') {
						$data = $mysql->get($mysql->sql('SELECT * FROM `nickserv` WHERE `nick` = '.$mysql->escape($d[1])));
						if ($uid == $data['userid']) {
							$ircd->svskill($d[1],'Connection reset by NickServ. This user has been Ghostified by '.$from);
							$ircd->notice($to,$from,'Ghost busted. :D');
							return 1;
						} else {
							$ircd->notice($to,$from,'You don\'t own that nick!');
							return 0;
						}
					} elseif (strtolower($d[0]) == 'drop') {
						$data = $mysql->get($mysql->sql('SELECT * FROM `nickserv` WHERE `nick` = '.$mysql->escape($from)));
						if ($uid == $data['userid']) {
							$mysql->sql('DELETE FROM `nickserv` WHERE `nick` = '.$mysql->escape($from));
							$ircd->notice($to,$from,'Nick '.$from.' dropped.');
							return 1;
						} else {
							$ircd->notice($to,$from,'You don\'t own that nick!');
							return 0;
						}
					}
				} else {
					if (strtolower($d[0]) == 'identify' || strtolower($d[0]) == 'id') {
						$p = explode(':',$d[1]);
						
						if (isset($p[1])) {
							if (isset($p[2])) {
								event('msg',$from,'PHPServ','IDENTIFY '.$p[1].' '.$p[2]);
							} else {
								event('msg',$from,'PHPServ','IDENTIFY '.$p[1].' '.$p[0]);
							}
							return 0;
						}
					} else {
						$ircd->notice('NickServ',$from,'You aren\'t identified to your PHPServ account!');
						return 0;
					}
				}
				$ircd->notice('NickServ',$from,'Unknown command "'.$d[0].'". Try "/msg NickServ HELP" instead.');
			}
		}

		function event_kill ($from,$to,$reason) {
			global $mysql;
			$ircd = &ircd();
			
			if (strtolower($to) == 'nickserv') {
				$ircd->addnick($mysql->getsetting('server'),'NickServ','Services','Services.ClueNet.Org','Nick Service');
				$ircd->join('NickServ','#services');
				$ircd->svskill($from,'Killing service bots isn\'t a smart idea.');
			}
		}

		function event_eos ($a) {
			global $mysql;
			$ircd = &ircd();

			$ircd->addnick($mysql->getsetting('server'),'NickServ','Services','Services.ClueNet.Org','Nick Service');
			$ircd->join('NickServ','#services');
		}

	}

	function registerm () {
		$class = new nickserv;
		register($class, __FILE__, 'NickServ Module', 'nickserv');
	}
?>
