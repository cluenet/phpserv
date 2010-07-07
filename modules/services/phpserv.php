<?PHP
	class phpserv {
		function event_msg ($from,$to,$message) {
			global $mysql;
			if ((strtolower($to) == 'phpserv') or (strtolower($to) == 'phpserv@'.strtolower($mysql->getsetting('server')))) {
				$to = explode('@', $to, 2);
				$to = $to[0];
				$args = explode(' ', $message);
				$cmd = strtolower(array_shift($args));

//				global $mysql;
//				global $modules;
				$ircd = &ircd();

				$access = $mysql->getaccess($from);
				switch ($cmd) {
					case "aml":
						if ($access > 900) {
							$ircd->smo('o', "*** Services -- from {$from}: Loading modules for AutoModuleLoad level {$args[0]}");
							aml($args[0]);
						}
						else $self->reply_noaccess($from);
						break;
					case "modload":
						if ($access > 900) {
							load(implode(' ', $args));
						}
						else $self->reply_noaccess($from);
						break;
					case "modunload":
						if ($access > 900) {
							unload(implode(' ', $args));
						}
						else $self->reply_noaccess($from);
						break;
					case "modreload":
						if ($access > 900) {
							reload(implode(' ', $args));
						}
						else $self->reply_noaccess($from);
						break;

					case "svnup":
						if ($access > 900) {
							if (pcntl_fork() == 0) {
								$svn = popen('svn up 2>&1', 'r');
								while (!feof($svn)) {
									$ircd->notice('PHPServ',$from,fgets($svn,512));
								}
								pclose($svn);
								die();
							}
						}
						else $self->reply_noaccess($from);
						break;

					case "kill":
						if ($access > 600) {
							$victim = array_shift($args);
							$reason = implode(" ", $args);
							$ircd->svskill($victim, "PHPServ kill by {$from} ({$reason})");
						}
						else $self->reply_noaccess($from);
						break;

					case "nick":
						if ($access > 600) {
							$old = array_shift($args);
							$new = array_shift($args);
							$ircd->svsnick($old, $new);
							$ircd->smo("o", "\002Abuse: {$from}\002 used SVSNICK {$old} {$new}");
						}
						else $self->reply_noaccess($from);
						break;

					case "mode":
						if ($access > 600) {
							$target = array_shift($args);
							$modes = implode(" ", $args);
							$ircd->mode("PHPServ", $target, $modes);
							$ircd->notice($mysql->getsetting('server'), $target, "{$from} used PHPServ MODE {$target} {$modes}");
							$ircd->smo("o", "\002Abuse: {$from}\002 used MODE {$target} {$modes}");
						}
						else $self->reply_noaccess($from);
						break;

					case "userops":
						if ($access > 100) {
							$text = implode(" ", $args);
							$ircd->smo("w", "\${$from}\$ {$args}");
						}
						else $self->reply_noaccess($from);
						break;

					case "abuseops":
						if ($access > 0) {
							$text = implode(" ", $args);
							$ircd->smo("o", "*** AbuseOps -- from {$from}: {$text}");
						}
						else $self->reply_noaccess($from);
						break;

					case "setlevel":
						if ($access > 0) {
							$target = array_shift($args);
							$target_level = (int) array_shift($args);

							if ($x = $mysql->get($mysql->sql('SELECT * FROM `access` WHERE `user` = '.$mysql->escape($target))) {
								$current_level = $x['level'];
								if ($access > $current_level) {
									if ($access >= $target_level) {
										$mysql->setaccess($x['id'], $target_level);
										$ircd->notice("PHPServ", $from, "Success.");
									}
									else {
										$ircd->notice("PHPServ", $from, "Failed: You may not set a user higher than yourself.");
									}
								}
								else {
									$ircd->notice("PHPServ", $from, "Failed: You may not modify a user with a higher level then yourself.");
								}
							} else {
								$ircd->notice("PHPServ", $from, "Failed: User does not exist.");
							}
						}
						else $self->reply_noaccess($from);
						break;

					case "logout":
						if ($access > 0) {
							if ($mysql->logoutaccess($from)) {
								$ircd->swhois($from);
								$ircd->notice("PHPServ", $from, "Logged out of PHPServ.");
							}
							else {
								$ircd->notice("PHPServ", $from, "Failed.");
							}
						}
						else {
							$ircd->notice("PHPServ", $from, "Failed: You are not logged in!");
						}
						break;

					case "setpass":
						if ($access > 0) {
							$newpass = array_shift($args);
							if ($x = $mysql->get($mysql->sql('SELECT `loggedin` FROM `users` WHERE `nick` = '.$mysql->escape($from)))) {
								$mysql->setaccesspassword($x['loggedin'], $newpass);
								$ircd->notice("PHPServ", $from, "Password changed.");
							} else {
								$ircd->notice("PHPServ", $from, "You don't exist, go away!");
							}
						}
						else $self->reply_noaccess($from);
						break;

					case "identify":
					case "id":
						if ($access <= 0) {
							$username = array_shift($args);
							$password = array_shift($args);
							if (strlen($username) and strlen($password)) {
								if ($mysql->loginaccess($from, $username, $password)) {
									$userinfo = $mysql->get($mysql->sql('SELECT `loggedin` FROM `users` WHERE `nick` = '.$mysql->escape($from)));
									$ircd->swhois($from, "is identified to PHPServ as {$username} (uid={$user['loggedin']})");
									$ircd->notice("PHPServ", $from, "Successfully logged in as {$username}");
								}
								else $self->reply_noaccess($from);
								break;
							}
							else $ircd->notice("PHPServ", $from, "Usage: IDENTIFY username password");
						}
						else $ircd->notice("PHPServ", $from, "You are already logged in.");
						break;

					case "register":
						if ($access <= 0) {
							$username = array_shift($args);
							$password = array_shift($args);
							if (strlen($username) and strlen($password)) {
								if ($mysql->get($mysql->sql('SELECT `user` FROM `access` WHERE `user` = '.$mysql->escape($username)))) {
									$ircd->notice("PHPServ", $from, "Error: User already exists.");
								}
								else {
									$mysql->addaccess($username, $password, 1);
									$ircd->notice("PHPServ", $from, "Registration successful. Welcome to ClueNet!");
								}
							}
						}
						else $ircd->notice("PHPServ", $from, "You are already logged in.");
						break;

					case "help":
						$ircd->notice("PHPServ", $from, "\002---- PHPServ commands ----\002");
						if ($access > 900) {
							$ircd->notice("PHPServ", $from, "MODLOAD <module>           - Load PHPServ module");
							$ircd->notice("PHPServ", $from, "MODUNLOAD <module>");
							$ircd->notice("PHPServ", $from, "MODRELOAD <module>");
							$ircd->notice("PHPServ", $from, "SVNUP");
						}
						if ($access > 600) {
							$ircd->notice("PHPServ", $from, "KILL <victim> <reason>     - SVSKILL");
							$ircd->notice("PHPServ", $from, "NICK <victim> <newnick>    - SVSNICK");
							$ircd->notice("PHPServ", $from, "MODE <target> <modestring> - SVSMODE");
						}
						if ($access > 100) {
							$ircd->notice("PHPServ", $from, "USEROPS <text>");
						}
						if ($access > 0) {
							$ircd->notice("PHPServ", $from, "SETPASS <newpass>          - Change yourpassword");
							$ircd->notice("PHPServ", $from, "LOGOUT                     - Log out of PHPServ");
						}
						if ($access < 0) {
							$ircd->notice("PHPServ", $from, "IDENTIFY <user> <pwd>      - Log in to PHPServ");
							$ircd->notice("PHPServ", $from, "REGISTER <user> <pwd>      - Create a new PHPServ account");
						}
						break;

				case "svninfo":
					if (pcntl_fork() == 0) {
						$svn = popen('svn info 2>&1', 'r');
						while (!feof($svn)) {
							$ircd->notice('PHPServ', $from, fgets($svn, 512));
						}
						pclose($svn);
						die();
					}
					break;

				case "xyzzy":
					$ircd->notice("PHPServ", $from, "Nothing happens.");
					$ircd->swhois($from, "is awesome");
					break;
				}
			}
		}

		function reply_noaccess($nick) {
			$ircd = &ircd();
			$ircd->notice("PHPServ", $nick, "Access denied.");
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
			$ircd->smo('o', '*** SERVICES *** Received SIGTERM, dieing in a fire.');
			$ircd->squit($mysql->getsetting('server'), 'SIGTERM');
		}

		function event_signal_hup () {
			global $mysql;
			$ircd = &ircd();
			$ircd->smo('o', '*** SERVICES *** Received SIGHUP, restarting.');
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
//						  global $modules;
						$class = new phpserv;
						register($class, __FILE__, 'PHPServ Module', 'phpserv');
		}
//	}
?>
