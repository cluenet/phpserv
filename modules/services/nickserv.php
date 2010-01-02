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
				
				if ($uid != -1) {
					if (strtolower($d[0]) == 'register') {
						if ($mysql->get($mysql->sql('SELECT * FROM `nickserv` WHERE `nick` = '.$mysql->escape($from)))) {
							$ircd->notice($to,$from,'Your nick is already owned by someone else.');
						} else {
							$data = array
							(
								'id'		=> 'NULL',
								'userid'	=> $uid,
								'nick'		=> $from
							);
							$mysql->insert('nickserv',$data);
							$ircd->notice($to,$from,'Success.  Your nick is now linked to your PHPServ Account.');
						}
					} elseif (strtolower($d[0]) == 'ghost') {
						$data = $mysql->get($mysql->sql('SELECT * FROM `nickserv` WHERE `nick` = '.$mysql->escape($d[1])));
						if ($uid == $data['userid']) {
							$ircd->svskill($d[1],'Connection reset by Ghost.  This user has been Ghostified by '.$from);
							$ircd->notice($to,$from,'Success.');
						} else {
							$ircd->notice($to,$from,'You don\'t own that nick!');
						}
					} elseif (strtolower($d[0]) == 'drop') {
						$data = $mysql->get($mysql->sql('SELECT * FROM `nickserv` WHERE `nick` = '.$mysql->escape($from)));
						if ($uid == $data['userid']) {
							$mysql->sql('DELETE FROM `nickserv` WHERE `nick` = '.$mysql->escape($from));
							$ircd->notice($to,$from,'Success.');
						} else {
							$ircd->notice($to,$from,'You don\'t own that nick!');
						}
					}
				} else {
					if (strtolower($d[0]) == 'identify') {
						$p = explode(':',$d[1]);
						
						if (isset($p[1])) {
							if (isset($p[2])) {
								event('msg',$from,'PHPServ','IDENTIFY '.$p[1].' '.$p[2]);
							} else {
								event('msg',$from,'PHPServ','IDENTIFY '.$p[1].' '.$p[0]);
							}
						}
					} else {
						$ircd->notice('NickServ',$from,'You aren\'t identified to your PHPServ account!');
					}
				}
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
