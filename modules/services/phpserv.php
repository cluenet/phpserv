<?PHP
	class phpserv {
		function event_msg ($from,$to,$message) {
			global $mysql;
			if ((strtolower($to) == 'phpserv') or (strtolower($to) == 'phpserv@'.strtolower($mysql->getsetting('server')))) {
				$to = explode('@', $to, 2);
				$to = $to[0];
				$d = explode(' ', $message);

//				global $mysql;
//				global $modules;
				$ircd = &ircd();

				if ($mysql->getaccess($from) > 0) {
					if ($mysql->getaccess($from) > 900) {

						if (strtolower($d[0]) == 'kill') {
							$ircd->svskill($d[1],'PHPServ Kill by '.$from.' ('.implode(' ', array_slice($d, 2)).')');

						} elseif (strtolower($d[0]) == 'nick') {
							$ircd->svsnick($d[1],$d[2]);
							$ircd->smo('o', "\002Abuse: ".$from."\002 used SVSNICK ".$d[1].' '.$d[2]);

						} elseif (strtolower($d[0]) == 'mode') {
							$ircd->mode('PHPServ',$d[1],implode(' ', array_slice($d, 2)));
							$ircd->notice($mysql->getsetting('server'),$d[1],$from.' used PHPServ Mode '.$d[1].' '.implode(' ', array_slice($d, 2)));
							$ircd->notice('PHPServ',$from,'Success.');
							$ircd->smo('o', "\002Abuse: ".$from."\002 used MODE ".$d[1].' '.implode(' ', array_slice($d, 2)));

						} elseif (strtolower($d[0]) == 'abuseops') {
							print_r($d);
							$ircd->smo('o', '*** AbuseOPs -- from '.$from.': '.implode(' ', array_slice($d, 1)));

						} elseif (strtolower($d[0]) == 'userops') {
							$ircd->smo('w', '$'.$from.'$ '.implode(' ', array_slice($d, 1)));

						} elseif (strtolower($d[0]) == 'modload') {
							load(implode(' ', array_slice($d, 1)));

						} elseif (strtolower($d[0]) == 'modunload') {
							unload(implode(' ', array_slice($d, 1)));

						} elseif (strtolower($d[0]) == 'modreload') {
							reload(implode(' ', array_slice($d, 1)));

						} elseif (strtolower($d[0]) == 'svnup') {
							if (pcntl_fork() == 0) {
								$svn = popen('svn up 2>&1', 'r');
								while (!feof($svn)) {
									$ircd->notice('PHPServ',$from,fgets($svn,512));
								}
								pclose($svn);
								die();
							}
						}
					}
					if (strtolower($d[0]) == 'setlevel') {
						if ($x = $mysql->get($mysql->sql('SELECT * FROM `access` WHERE `user` = '.$mysql->escape($d[1])))) {
							$clvl = $x['level'];
							$tlvl = $d[2];
							$ulvl = $mysql->getaccess($from);
							if ($ulvl > $clvl) {
								if ($tlvl < $ulvl) {
									$mysql->setaccess($x['id'],$tlvl);
									$ircd->notice('PHPServ',$from,'Success.');
								} else {
									$ircd->notice('PHPServ',$from,'Failed.  You may not set a user higher than yourself.');
								}
							} else {
								$ircd->notice('PHPServ',$from,'Failed.  You may not modify a user with a higher level then yourself.');
							}
						} else {
							$ircd->notice('PHPServ',$from,'Failed.  User does not exist.');
						}
					} elseif (strtolower($d[0]) == 'logout') {
						if ($mysql->logoutaccess($from)) { $ircd->notice('PHPServ',$from,'Success.'); $ircd->svsmode('PHPServ',$from,'+d -1 '); }
						else { $ircd->notice('PHPServ',$from,'Failure.'); }
					} elseif (strtolower($d[0]) == 'setpass') {
						if ($x = $mysql->get($mysql->sql('SELECT `loggedin` FROM `users` WHERE `nick` = '.$mysql->escape($from)))) {
							$mysql->setaccesspassword($x['loggedin'],$d[1]);
							$ircd->notice('PHPServ',$from,'Setpass processed successfully.');
						} else {
							$ircd->notice('PHPServ',$from,'Error while processing setpass:  You don\'t exist, go away!');
						}
					}

				} else {
					if (strtolower($d[0]) == 'identify' || strtolower($d[0]) == 'id') {
						if (isset($d[1]) and isset($d[2]) and $mysql->loginaccess($from,$d[1],$d[2])) {
							$ircd->notice('PHPServ',$from,'Identify processed successfully.');
							event('identify',$from);
							$user = $mysql->get($mysql->sql('SELECT `loggedin` FROM `users` WHERE `nick` = '.$mysql->escape($from)));
							$ircd->svsmode('PHPServ',$from,'+d ' . $user['loggedin']);
						} else { $ircd->notice('PHPServ',$from,'Error while processing identify.'); }
					} elseif (strtolower($d[0]) == 'register') {
						if ($mysql->get($mysql->sql('SELECT `user` FROM `access` WHERE `user` = '.$mysql->escape($d[1])))) {
							$ircd->notice('PHPServ',$from,'Error while processing registration: User already exists.');
						} else {
							$mysql->addaccess($d[1],$d[2],1);
							$ircd->notice('PHPServ',$from,'Registration processed successfully.');
						}
					}
				}
				if (strtolower($d[0]) == 'help') {
					$ircd->notice('PHPServ',$from,'PHPServ help:');
					$ircd->notice('PHPServ',$from,'REGISTER <user> <pass> - Registers a username with PHPServ.');
					$ircd->notice('PHPServ',$from,'IDENTIFY <user> <pass> - Identifies you to a username in PHPServ.');
					$ircd->notice('PHPServ',$from,'SETPASS  <pass>        - Changes your password if you are identified.');
					$ircd->notice('PHPServ',$from,'LOGOUT                 - Does the reverse of IDENTIFY if you are identified.');
				}
			}
		}

		function event_eos ($a) {
//			global $settings;
//			global $modules;
			global $mysql;
			$ircd = &ircd();

			$ircd->addnick($mysql->getsetting('server'),'PHPServ','Services','phpserv.cluenet.org','PHP Service');
			$ircd->join('PHPServ','#services');
		}

		function event_signal_term () {
			global $mysql;
			$ircd = &ircd();
			$ircd->smo('o', '*** SERVICES *** HELP! (sigterm)');
			$ircd->squit($mysql->getsetting('server'), 'SIGTERM');
		}

		function event_signal_hup () {
			global $mysql;
			$ircd = &ircd();
			$ircd->smo('o', '*** SERVICES *** RESTARTING! (sighup)');
			$ircd->squit($mysql->getsetting('server'), 'RESTARTING');
//			die('SIGHUP');
		}

		function event_kill ($from,$to,$reason) {
			global $mysql;
			$ircd = &ircd();

			if (strtolower($to) == 'phpserv') {
				$ircd->addnick($mysql->getsetting('server'),'PHPServ','Services','phpserv.cluenet.org','PHP Service');
				$ircd->join('PHPServ','#services');
				$ircd->svskill($from,'Killing service bots isn\'t a smart idea.');
			}
		}

	}

//	class modinit {
                function registerm () {
//                        global $modules;
                        $class = new phpserv;
                        register($class, __FILE__, 'PHPServ Module', 'phpserv');
		}
//	}
?>
