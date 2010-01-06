<?PHP
	class botcontroller {
		private static $bots;
		private static $curbot;
		private static $curbotctr;


		function construct() {
			$this->event_eos();
		}

		function destruct() {
			$ircd = &ircd();
			foreach ($this->bots as $nick => $tmp) {
				$ircd->quit($nick, 'Module Unloaded.');
			}
			unset($this->bots);
			$ircd->quit('BotServ', 'Module Unloaded.');
		}

		function event_kill ($from,$to,$reason) {
			global $mysql;
			$ircd = &ircd();

			if (strtolower($to) == 'botserv') {
				$ircd->addnick($mysql->getsetting('server'),'BotServ','Services','Services.ClueNet.org','Bot Service');
				$ircd->join('BotServ','#services');
				$ircd->join('BotServ','#bots');
				$ircd->mode('BotServ','#services','+oa BotServ BotServ');
//				$ircd->mode('BotController','#bots','+oa BotServ BotServ');
				$ircd->svso($from,'-');
			} elseif (isset($this->bots[strtolower($to)])) {
				$this->initbot(strtolower($to));
				$ircd->svso($from,'-');
			}
		}


		function event_join ($from,$to) {
			global $mysql;
			$cmd = '__e_join';

			foreach ($this->bots as $bot) {
				if (strtolower($bot['channel']) == strtolower($to)) {
					$botid = $mysql->get($mysql->sql('SELECT `id` FROM `botserv_bots` WHERE `nick` = '.$mysql->escape($bot['nick'])));
					$botid = $botid['id'];
					if ($x = $mysql->get($mysql->sql('SELECT * FROM `botserv_cmds` WHERE `botid` = '.$mysql->escape($botid).' AND `cmd` = '.$mysql->escape($cmd)))) {
						$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
						$uid = $nickd['loggedin'];
						$user = $mysql->get($mysql->sql('SELECT * FROM `access` WHERE `id` = '.$mysql->escape($uid)));
						$level = $user['level'];
						$user = $user['user'];
						$blvl = $mysql->get($mysql->sql('SELECT * FROM `botserv_acc` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)));
						$blvl = $blvl['level'];
						if ($mysql->get($mysql->sql('SELECT * FROM `botserv_cos` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)))) { $coowner = 1; } else { $coowner = 0; }
						if ($bot['owner'] == $uid) { $owner = 1; } else { $owner = 0; }
						$vars = array
						(
							'user'	=> $user,
							'uid'	=> $uid,
							'nickd'	=> $nickd,
							'level'	=> $level,
							'owner'	=> $owner,
							'coowner'=>$coowner,
							'blvl'	=> $blvl,
							'trig'	=> $bot['trig']
						);
						$this->sboteval($from,$cmd,'',$to,$bot['nick'],$x['data'],$vars);
					}
				}
			}
		}

		function event_part ($from,$to,$reason) {
			global $mysql;
			$cmd = '__e_part';
			foreach ($this->bots as $bot) {
				if (strtolower($bot['channel']) == strtolower($to)) {
					$botid = $mysql->get($mysql->sql('SELECT `id` FROM `botserv_bots` WHERE `nick` = '.$mysql->escape($bot['nick'])));
					$botid = $botid['id'];
					if ($x = $mysql->get($mysql->sql('SELECT * FROM `botserv_cmds` WHERE `botid` = '.$mysql->escape($botid).' AND `cmd` = '.$mysql->escape($cmd)))) {
						$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
						$uid = $nickd['loggedin'];
						$user = $mysql->get($mysql->sql('SELECT * FROM `access` WHERE `id` = '.$mysql->escape($uid)));
						$level = $user['level'];
						$user = $user['user'];
						$blvl = $mysql->get($mysql->sql('SELECT * FROM `botserv_acc` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)));
						$blvl = $blvl['level'];
						if ($mysql->get($mysql->sql('SELECT * FROM `botserv_cos` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)))) { $coowner = 1; } else { $coowner = 0; }
						if ($bot['owner'] == $uid) { $owner = 1; } else { $owner = 0; }
						$vars = array
						(
							'user'	=> $user,
							'uid'	=> $uid,
							'nickd'	=> $nickd,
							'level'	=> $level,
							'reason'=> $reason,
							'owner'	=> $owner,
							'coowner'=>$coowner,
							'blvl'	=> $blvl,
							'trig'	=> $bot['trig']
						);
						$this->sboteval($from,$cmd,$reason,$to,$bot['nick'],$x['data'],$vars);
					}
				}
			}
		}

		function event_kick ($from,$who,$to,$reason) {
			global $mysql;
			$ircd = &ircd();
			$cmd = '__e_kick';
			foreach ($this->bots as $bot) {
				if (strtolower($bot['channel']) == strtolower($to)) {
					$botid = $mysql->get($mysql->sql('SELECT `id` FROM `botserv_bots` WHERE `nick` = '.$mysql->escape($bot['nick'])));
					$botid = $botid['id'];
					if ($x = $mysql->get($mysql->sql('SELECT * FROM `botserv_cmds` WHERE `botid` = '.$mysql->escape($botid).' AND `cmd` = '.$mysql->escape($cmd)))) {
						$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
						$uid = $nickd['loggedin'];
						$user = $mysql->get($mysql->sql('SELECT * FROM `access` WHERE `id` = '.$mysql->escape($uid)));
						$level = $user['level'];
						$user = $user['user'];
						$blvl = $mysql->get($mysql->sql('SELECT * FROM `botserv_acc` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)));
						$blvl = $blvl['level'];
						if ($mysql->get($mysql->sql('SELECT * FROM `botserv_cos` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)))) { $coowner = 1; } else { $coowner = 0; }
						if ($bot['owner'] == $uid) { $owner = 1; } else { $owner = 0; }
						$vars = array
						(
							'user'	=> $user,
							'uid'	=> $uid,
							'nickd'	=> $nickd,
							'level'	=> $level,
							'who'	=> $who,
							'reason'=> $reason,
							'owner'	=> $owner,
							'coowner'=>$coowner,
							'blvl'	=> $blvl,
							'trig'	=> $bot['trig']
						);
						$this->sboteval($from,$cmd,$reason,$to,$bot['nick'],$x['data'],$vars);
					}
				}
				if (strtolower($who) == strtolower($bot['nick'])) {
					$ircd->join($who,$to);
					if (strtolower($bot['channel']) == strtolower($to)) {
						$ircd->mode($who,$to,'+oa '.$who.' '.$who);
					} else {
						$ircd->mode($who,$to,'+v '.$who);
					}
					if (!isset($this->bots[strtolower($from)])) {
//						$ircd->kick($who,$to,$from,'Thou Shalt Not Kick Teh Bot!');
					}
				}
			}
		}

		function event_mode ($from,$to,$mode) {
			global $mysql;
			$cmd = '__e_mode';
			foreach ($this->bots as $bot) {
				if (strtolower($bot['channel']) == strtolower($to)) {
					$botid = $mysql->get($mysql->sql('SELECT `id` FROM `botserv_bots` WHERE `nick` = '.$mysql->escape($bot['nick'])));
					$botid = $botid['id'];
					if ($x = $mysql->get($mysql->sql('SELECT * FROM `botserv_cmds` WHERE `botid` = '.$mysql->escape($botid).' AND `cmd` = '.$mysql->escape($cmd)))) {
						$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
						$uid = $nickd['loggedin'];
						$user = $mysql->get($mysql->sql('SELECT * FROM `access` WHERE `id` = '.$mysql->escape($uid)));
						$level = $user['level'];
						$user = $user['user'];
						$blvl = $mysql->get($mysql->sql('SELECT * FROM `botserv_acc` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)));
						$blvl = $blvl['level'];
						if ($mysql->get($mysql->sql('SELECT * FROM `botserv_cos` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)))) { $coowner = 1; } else { $coowner = 0; }
						if ($bot['owner'] == $uid) { $owner = 1; } else { $owner = 0; }
						$vars = array
						(
							'user'	=> $user,
							'uid'	=> $uid,
							'nickd'	=> $nickd,
							'level'	=> $level,
							'mode'	=> $mode,
							'owner'	=> $owner,
							'coowner'=>$coowner,
							'blvl'	=> $blvl,
							'trig'	=> $bot['trig']
						);
						$this->sboteval($from,$cmd,$mode,$to,$bot['nick'],$x['data'],$vars);
					}
				}
			}
		}

		function event_topic ($from,$to,$newtopic) {
			global $mysql;
			$ircd = &ircd();
			$cmd = '__e_topic';
			foreach ($this->bots as $bot) {
				if (strtolower($bot['channel']) == strtolower($to)) {
					$botid = $mysql->get($mysql->sql('SELECT `id` FROM `botserv_bots` WHERE `nick` = '.$mysql->escape($bot['nick'])));
					$botid = $botid['id'];
					if ($x = $mysql->get($mysql->sql('SELECT * FROM `botserv_cmds` WHERE `botid` = '.$mysql->escape($botid).' AND `cmd` = '.$mysql->escape($cmd)))) {
						$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
						$uid = $nickd['loggedin'];
						$user = $mysql->get($mysql->sql('SELECT * FROM `access` WHERE `id` = '.$mysql->escape($uid)));
						$level = $user['level'];
						$user = $user['user'];
						$blvl = $mysql->get($mysql->sql('SELECT * FROM `botserv_acc` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)));
						$blvl = $blvl['level'];
						if ($mysql->get($mysql->sql('SELECT * FROM `botserv_cos` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)))) { $coowner = 1; } else { $coowner = 0; }
						if ($bot['owner'] == $uid) { $owner = 1; } else { $owner = 0; }
						$vars = array
						(
							'user'	=> $user,
							'uid'	=> $uid,
							'nickd'	=> $nickd,
							'level'	=> $level,
							'topic'	=> $newtopic,
							'owner'	=> $owner,
							'coowner'=>$coowner,
							'blvl'	=> $blvl,
							'trig'	=> $bot['trig']
						);
						$this->sboteval($from,$cmd,$mode,$to,$bot['nick'],$x['data'],$vars);
					}
				}
			}
		}

		function event_msg ($from,$to,$message) {
			$d = explode(' ', $message);

			global $mysql;
			$ircd = &ircd();

			if (strtolower($to) == 'botserv') {
				if ($mysql->getaccess($from) > 100) {
					if (strtolower($d[0]) == 'bot') {	
						if (strtolower($d[1]) == 'add') {
							// We're adding a bot. Let's make sure we're not adding a bot over a nickserv user
							global $modules;
							if (isset($modules['nickserv'])) {
								// NickServ is loaded, let's do the checks
								if ($mysql->get($mysql->sql('SELECT * FROM `nickserv` WHERE `nick` = '.$mysql->escape($d[2])))) {
									// Found something
									$ircd->notice('BotServ',$from,'"'.$d[2].'" is registered with NickServ!');
									return 0;
								}
							// NickServ isn't loaded. Nothing to do here.
							}
							// nick!user@host validity checks here

							// 0Bot 1Add 2<nick> 3<ident> 4<host> 5<owner> 6<channel>
							if (preg_match('/[^-a-z\d.]/i',$d[3]) == 1) {
								// There are probably a few valid characters that this matches, but those would be rather unclueful
								$ircd->notice('BotServ',$from,'Illegal characters in the ident. Please try again.');
								return 0;
							}
							if (preg_match('/[^-a-z\d.]/i',$d[4]) == 1) {
								$ircd->notice('BotServ',$from,'Illegal characters in the hostname. Please try again.');
								return 0;
							}
							if ($ircd->isValidNick($d[2]) != 1) {
								$ircd->notice('BotServ',$from,'Illegal nickname. I\'m calling the cops.');
								return 0;
							}
/*
							if (isValidNick($d[5]) != 1) {
								$ircd->notice('BotController',$from,'Illegal owner nickname. Try using their current nick instead? :D');
								return 0;
							}
*/ // Owners don't follow IRC nick rules... Yet.
							// Everything is sane. Add the bot.
							$this->addbot($d[2],$d[3],$d[4],$d[5],$d[6]);
							$ircd->notice('BotServ',$from,$d[2].' ('.$d[3].'@'.$d[4].') created for '.$d[5].' on '.$d[6]);
	
						} elseif (strtolower($d[1]) == 'del') {
							if ($this->bots[strtolower($d[2])]) {
								$this->delbot($d[2]);

							} else {
								$ircd->notice('BotServ', $from, $d[2].' is not a BotServ bot!');

							}
						}
					} elseif (strtolower($d[0]) == 'template') {
						if (strtolower($d[1]) == 'add') {
								$data = array
								(
									'id'	=> 'NULL',
									'cmd'	=> $d[2],
									'data'	=> implode(" ",array_splice($d,3))
								);
								$mysql->insert('botserv_cmdtpls',$data);
								$ircd->notice('BotServ', $from, 'Command template "'.$d[2].'" added.');
						} elseif (strtolower($d[1]) == 'del') {
								$mysql->sql('DELETE FROM `botserv_cmdtpls` WHERE `cmd` = '.$mysql->escape($d[2]));
								$ircd->notice('BotServ', $from, 'Command template "'.$d[2].'" deleted.');
						}
					} elseif (strtolower($d[0]) == 'help') {
						if (!isset($d[1])) {
							$ircd->notice($to,$from,'--- Commands overview ---');
							$ircd->notice($to,$from,'Bot                   Manages the bot list.');
							$ircd->notice($to,$from,'Template         Manages the template list.');
							$ircd->notice($to,$from,'For more information on these topics:');
							$ircd->notice($to,$from,'/msg BotServ Help Topic');
							$ircd->notice($to,$from,'--- End of help ---');
						} elseif (strtolower($d[1]) == 'bot') {
							if (!isset($d[2])) {
								$ircd->notice($to,$from,'--- BotServ Help - Bot ---');
								$ircd->notice($to,$from,'Add                   Add a bot to BotServ.');
								$ircd->notice($to,$from,'Del              Remove a bot from BotServ.');
								$ircd->notice($to,$from,'For more information on these topics:');
								$ircd->notice($to,$from,'/msg BotServ Help Bot Topic');
								$ircd->notice($to,$from,'--- End of help ---');
							} elseif (strtolower($d[2]) == 'add') {
								$ircd->notice($to,$from,'--- BotServ Help - Bot Add ---');
								$ircd->notice($to,$from,'Creates a BotServ bot.');
								$ircd->notice($to,$from,'Syntax: /msg BotServ Bot Add <nick> <ident> <host> <owner> <channel>');
								$ircd->notice($to,$from,'<nick> is the nick that the bot will use.');
								$ircd->notice($to,$from,'<ident> is the ident that the bot will use.');
								$ircd->notice($to,$from,'<host> is the host that the bot will use.');
								$ircd->notice($to,$from,"<owner> is the owner's OperServ UserName.");
								$ircd->notice($to,$from,'<channel> is the channel that the bot will operate on.');
								$ircd->notice($to,$from,'--- End of help ---');
							} elseif (strtolower($d[2]) == 'del') {
								$ircd->notice($to,$from,'--- BotServ Help - Bot Del ---');
								$ircd->notice($to,$from,'Removes a BotServ bot.');
								$ircd->notice($to,$from,'Syntax: /msg BotServ Bot Del <nick>');
								$ircd->notice($to,$from,'<nick> is the nick that the bot is using.');
								$ircd->notice($to,$from,'--- End of help ---');
							}
						} elseif (strtolower($d[1]) == 'template') {
							if (!isset($d[2])) {
								$ircd->notice($to,$from,'--- BotServ Help - Template ---');
								$ircd->notice($to,$from,'Add              Add a template to BotServ.');
								$ircd->notice($to,$from,'Del         Remove a template from BotServ.');
								$ircd->notice($to,$from,'For more information on these topics:');
								$ircd->notice($to,$from,'/msg BotServ Help Template Topic');
								$ircd->notice($to,$from,'--- End of help ---');
							} elseif (strtolower($d[2]) == 'add') {
								$ircd->notice($to,$from,'--- BotServ Help - Template Add ---');
								$ircd->notice($to,$from,'Adds a template to BotServ bots.');
								$ircd->notice($to,$from,'Syntax: /msg BotServ Template Add <command> <script>');
								$ircd->notice($to,$from,'<command> is the command of the template to add.');
								$ircd->notice($to,$from,'<script> is the script of the template to add.');
								$ircd->notice($to,$from,'--- End of help ---');
							} elseif (strtolower($d[2]) == 'del') {
								$ircd->notice($to,$from,'--- BotServ Help - Template Del ---');
								$ircd->notice($to,$from,'Removes a template from BotServ bots.');
								$ircd->notice($to,$from,'Syntax: /msg BotServ Template Del <command>');
								$ircd->notice($to,$from,'<command> is the command of the template to remove.');
								$ircd->notice($to,$from,'--- End of help ---');
							}
						}
					}
				}
			}

			$unick = strtolower($to);
			if (isset($this->bots[$unick])) {
				$bot = $this->bots[strtolower($to)];
				$userid = $mysql->get($mysql->sql('SELECT `loggedin` FROM `users` WHERE `nick` = '.$mysql->escape($from)));
				$userid = $userid['loggedin'];
				if ($mysql->get($mysql->sql('SELECT * FROM `botserv_cos` WHERE `uid` = '.$mysql->escape($userid).' AND `botid` = '.$mysql->escape($bot['botid'])))) { $coowner = 1; } else { $coowner = 0; }
				if (($bot['owner'] == $userid) or ($coowner == 1)) {
					$botid = $mysql->get($mysql->sql('SELECT `id` FROM `botserv_bots` WHERE `nick` = '.$mysql->escape($to)));
					$botid = $botid['id'];
					if (strtolower($d[0]) == 'command') {
						if (strtolower($d[1]) == 'add') {
							if ($mysql->get($mysql->sql('SELECT `id` FROM `botserv_cmds` WHERE `cmd` = '.$mysql->escape($d[2]).' AND `botid` = '.$mysql->escape($botid)))) {
								$ircd->notice($to,$from,'Command already exists.');
							} else {
								$data = array
								(
									'id'	=> 'NULL',
									'botid'	=> $botid,
									'cmd'	=> $d[2],
									'data'	=> implode(" ",array_splice($d,3))
								);
								$mysql->insert('botserv_cmds',$data);
								$ircd->notice($to,$from,'Command "'.$d[2].'" added.');
							}
						} elseif (strtolower($d[1]) == 'del') {
							if ($mysql->get($mysql->sql('SELECT `id` FROM `botserv_cmds` WHERE `cmd` = '.$mysql->escape($d[2]).' AND `botid` = '.$mysql->escape($botid)))) {
								$mysql->sql('DELETE FROM `botserv_cmds` WHERE `cmd` = '.$mysql->escape($d[2]).' AND `botid` = '.$mysql->escape($botid));
								$ircd->notice($to,$from,'Command "'.$d[2].'" deleted.');
							} else {
								$ircd->notice($to,$from,'No such command "'.$d[2].'".');
							}
						} elseif (strtolower($d[1]) == 'show') {
							if ($mysql->get($mysql->sql('SELECT `id` FROM `botserv_cmds` WHERE `cmd` = '.$mysql->escape($d[2]).' AND `botid` = '.$mysql->escape($botid)))) {
								$data = $mysql->get($mysql->sql('SELECT `data` FROM `botserv_cmds` WHERE `cmd` = '.$mysql->escape($d[2]).' AND `botid` = '.$mysql->escape($botid)));
								$ircd->notice($to,$from,$data['data']);
							} else {
								$ircd->notice($to,$from,'No such command "'.$d[2].'".');
							}
						} elseif (strtolower($d[1]) == 'list') {
							$ircd->notice($to,$from,'List of commands:');
							$result = $mysql->sql('SELECT `cmd` FROM `botserv_cmds` WHERE `botid` = '.$mysql->escape($botid));
							while ($x = $mysql->get($result)) {
								$ircd->notice($to,$from,$x['cmd']);
							}
						} elseif (strtolower($d[1]) == 'template') {
							if (strtolower($d[2]) == 'list') {
								$ircd->notice($to,$from,'List of templates:');
								$result = $mysql->sql('SELECT `cmd` FROM `botserv_cmdtpls`');
								while ($x = $mysql->get($result)) {
									$ircd->notice($to,$from,$x['cmd']);
								}
							} elseif (strtolower($d[2]) == 'show') {
								if ($mysql->get($mysql->sql('SELECT `id` FROM `botserv_cmdtpls` WHERE `cmd` = '.$mysql->escape($d[3])))) {
									$data = $mysql->get($mysql->sql('SELECT `data` FROM `botserv_cmdtpls` WHERE `cmd` = '.$mysql->escape($d[3])));
									$ircd->notice($to,$from,$data['data']);
								} else {
								$ircd->notice($to,$from,'No such template "'.$d[2].'".');
								}
							} elseif (strtolower($d[2]) == 'add') {
								if ($mysql->get($mysql->sql('SELECT `id` FROM `botserv_cmdtpls` WHERE `cmd` = '.$mysql->escape($d[3])))) {
									$data = $mysql->get($mysql->sql('SELECT * FROM `botserv_cmdtpls` WHERE `cmd` = '.$mysql->escape($d[3])));
									if ($mysql->get($mysql->sql('SELECT `id` FROM `botserv_cmds` WHERE `cmd` = '.$mysql->escape($d[3]).' AND `botid` = '.$mysql->escape($botid)))) {
										$ircd->notice($to,$from,'Command already exists.');
									} else {
										$data = array
										(
											'id'	=> 'NULL',
											'botid'	=> $botid,
											'cmd'	=> $data['cmd'],
											'data'	=> $data['data']
										);
										$mysql->insert('botserv_cmds',$data);
										$ircd->notice($to,$from,'Added command "'.$d[3].'".');
									}
								}
							}
						}
					} elseif (strtolower($d[0]) == 'access') {
						if (strtolower($d[1]) == 'add') {
							$uid = $mysql->get($mysql->sql('SELECT `id` FROM `access` WHERE `user` = '.$mysql->escape($d[2])));
							$uid = $uid['id'];
							if ($clvl = $mysql->get($mysql->sql('SELECT `level` FROM `botserv_acc` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)))) {
								$ircd->notice($to,$from,'User '.$d[2].' is already level '.$clvl['level'].' on me! Use del then add to change his level.');
								// Uh, or we can delete it and add them again?
								// We could, but I like the current method.  It doesn't assume to know what the user wants.
								//$mysql->sql('DELETE FROM `botserv_acc` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid));
							} else {
								$data = array
								(
									'id'	=> 'NULL',
									'botid'	=> $botid,
									'uid'	=> $uid,
									'level'	=> $d[3]
								);
								$mysql->insert('botserv_acc',$data);
								$ircd->notice($to,$from,$d[2].' added to my access list at level '.$d[3].'.');
							}
						} elseif (strtolower($d[1]) == 'del') {
							$uid = $mysql->get($mysql->sql('SELECT `id` FROM `access` WHERE `user` = '.$mysql->escape($d[2])));
							$uid = $uid['id'];
							if ($clvl = $mysql->get($mysql->sql('SELECT `level` FROM `botserv_acc` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)))) {
								$mysql->sql('DELETE FROM `botserv_acc` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid));
								$ircd->notice($to,$from,'Deleted '.$d[2].' from my access list.');
							} else {
								$ircd->notice($to,$from,$d[2].' doesn\'t have access to me!');
							}
						} elseif (strtolower($d[1]) == 'list') {
							$ircd->notice($to,$from,'Here are all the users who have access to me:');
							$result = $mysql->sql('SELECT `botserv_acc`.`level`,`access`.`user` FROM `botserv_acc`,`access` WHERE `botserv_acc`.`botid` = '.$mysql->escape($botid).' AND `access`.`id` = `botserv_acc`.`uid`');
							while ($d = $mysql->get($result)) {
								$ircd->notice($to,$from,$d['user'].' has level '.$d['level'].' access to me.');
							}
						}
					} elseif (strtolower($d[0]) == 'coowner') {
						if (strtolower($d[1]) == 'add') {
							$uid = $mysql->get($mysql->sql('SELECT `id` FROM `access` WHERE `user` = '.$mysql->escape($d[2])));
							$uid = $uid['id'];
							if ($clvl = $mysql->get($mysql->sql('SELECT * FROM `botserv_cos` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)))) {
								$ircd->notice($to,$from,'User '.$d[2].' is already a coowner on me!');
							} else {
								$data = array
								(
									'id'	=> 'NULL',
									'botid'	=> $botid,
									'uid'	=> $uid
								);
								$mysql->insert('botserv_cos',$data);
								$ircd->notice($to,$from,$d[2].' added to the co-owners list.');
							}
						} elseif (strtolower($d[1]) == 'del') {
							$uid = $mysql->get($mysql->sql('SELECT `id` FROM `access` WHERE `user` = '.$mysql->escape($d[2])));
							$uid = $uid['id'];
							if ($clvl = $mysql->get($mysql->sql('SELECT * FROM `botserv_cos` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)))) {
								$mysql->sql('DELETE FROM `botserv_cos` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid));
								$ircd->notice($to,$from,'Deleted '.$d[2].' from the co-owners list.');
							} else {
								$ircd->notice($to,$from,$d[2].' doesn\'t have co-owner access to me!');
							}
						} elseif (strtolower($d[1]) == 'list') {
							$ircd->notice($to,$from,'Here are all the users who have co-owner access to me:');
							$result = $mysql->sql('SELECT `access`.`user` FROM `botserv_cos`,`access` WHERE `botserv_cos`.`botid` = '.$mysql->escape($botid).' AND `access`.`id` = `botserv_cos`.`uid`');
							while ($d = $mysql->get($result)) {
								$ircd->notice($to,$from,$d['user'].' has level co-owner access to me.');
							}
						}
					} elseif (strtolower($d[0]) == 'set') {	
						if (strtolower($d[1]) == 'trigger') {
							if (($d[2] == '.') or ($d[2] == '-') or ($d[2] == '+') or ($d[2] == '!') or ($d[2] == '`') or ($d[2] == '~') or ($d[2] == '*') or ($d[2] == ';') or ($d[2] == '\'') or ($d[2] == ',')) {
								$mysql->sql('UPDATE `botserv_bots` SET `trig` = '.$mysql->escape($d[2]).' WHERE `id` = '.$mysql->escape($botid));
								$this->bots[strtolower($to)]['trig'] = $d[2];
								$ircd->notice($to,$from,'Success.');
							} else {
								$ircd->notice($to,$from,'Trigger must be one of: . - + ! ` ~ * ; \'');
							}
						}
					} elseif (strtolower($d[0]) == 'help') {
						if (!isset($d[1])) {
							$ircd->notice($to,$from,'--- Commands overview ---');
							$ircd->notice($to,$from,'Command     Manages the bot\'s script list.');
							$ircd->notice($to,$from,'Access        Manages the bot\'s user list.');
							$ircd->notice($to,$from,'Set         Manages the bot\'s settings.');
							$ircd->notice($to,$from,'For more information on these topics:');
							$ircd->notice($to,$from,'/msg '.$to.' Help Topic');
							$ircd->notice($to,$from,'--- End of help ---');
						} elseif (strtolower($d[1]) == 'command') {
							if (!isset($d[2])) {
								$ircd->notice($to,$from,'--- Bot Help - Command ---');
								$ircd->notice($to,$from,'Add               Add a script to your bot.');
								$ircd->notice($to,$from,'Del          Remove a script from your bot.');
								$ircd->notice($to,$from,'Show            Get a script from your bot.');
								$ircd->notice($to,$from,'List          List all scripts on your bot.');
								$ircd->notice($to,$from,'Template         View/use script templates.');
								$ircd->notice($to,$from,'For more information on these topics:');
								$ircd->notice($to,$from,'/msg '.$to.' Help Command Topic');
								$ircd->notice($to,$from,'--- End of help ---');
							} elseif (strtolower($d[2]) == 'add') {
								$ircd->notice($to,$from,'--- Bot Help - Command Add ---');
								$ircd->notice($to,$from,'Adds a script to your bot.');
								$ircd->notice($to,$from,'Syntax: /msg '.$to.' Command Add <command> <script>');
								$ircd->notice($to,$from,'<command> is the command that you wish to add a script for.');
								$ircd->notice($to,$from,'Commands are issued by typing <trigger><command> in the channel.');
								$ircd->notice($to,$from,'For example: .slap Cobi');
								$ircd->notice($to,$from,'-');
								$ircd->notice($to,$from,'There are a few special commands:');
								$ircd->notice($to,$from,'__e_join -- on join event');
								$ircd->notice($to,$from,'__e_part -- on part event');
								$ircd->notice($to,$from,'__e_kick -- on kick event');
								$ircd->notice($to,$from,'__e_mode -- on mode event');
								$ircd->notice($to,$from,'__e_topic -- on topic change event');
								$ircd->notice($to,$from,'__pm__<command> -- command issued by /msg');
								$ircd->notice($to,$from,'<script> is the PHP script for the command.');
								$ircd->notice($to,$from,'--- End of help ---');
							} elseif (strtolower($d[2]) == 'del') {
								$ircd->notice($to,$from,'--- Bot Help - Command Del ---');
								$ircd->notice($to,$from,'Removes a script from your bot.');
								$ircd->notice($to,$from,'Syntax: /msg '.$to.' Command Del <command>');
								$ircd->notice($to,$from,'<command> is the command that you wish to remove.');
								$ircd->notice($to,$from,'--- End of help ---');
							} elseif (strtolower($d[2]) == 'show') {
								$ircd->notice($to,$from,'--- Bot Help - Command Show ---');
								$ircd->notice($to,$from,'Gets a script from your bot.');
								$ircd->notice($to,$from,'Syntax: /msg '.$to.' Command Show <command>');
								$ircd->notice($to,$from,'<command> is the command that you wish to view.');
								$ircd->notice($to,$from,'--- End of help ---');
							} elseif (strtolower($d[2]) == 'list') {
								$ircd->notice($to,$from,'--- Bot Help - Command List ---');
								$ircd->notice($to,$from,'Lists all scripts on your bot.');
								$ircd->notice($to,$from,'Syntax: /msg '.$to.' Command List');
								$ircd->notice($to,$from,'--- End of help ---');
							} elseif (strtolower($d[2]) == 'template') {
								if (!isset($d[3])) {
									$ircd->notice($to,$from,'--- Bot Help - Command Template ---');
									$ircd->notice($to,$from,'Add      Add a script template to your bot.');
									$ircd->notice($to,$from,'Show                 Get a script template.');
									$ircd->notice($to,$from,'List            List all script templates.');
									$ircd->notice($to,$from,'For more information on these topics:');
									$ircd->notice($to,$from,'/msg '.$to.' Help Command Template Topic');
									$ircd->notice($to,$from,'--- End of help ---');
								} elseif (strtolower($d[3]) == 'add') {
									$ircd->notice($to,$from,'--- Bot Help - Command Template Add ---');
									$ircd->notice($to,$from,'Adds a script template to your bot.');
									$ircd->notice($to,$from,'Syntax: /msg '.$to.' Command Template Add <command>');
									$ircd->notice($to,$from,'<command> is the template command that you wish to add to your bot.');
									$ircd->notice($to,$from,'--- End of help ---');
								} elseif (strtolower($d[3]) == 'show') {
									$ircd->notice($to,$from,'--- Bot Help - Command Template Show ---');
									$ircd->notice($to,$from,'Gets a script template.');
									$ircd->notice($to,$from,'Syntax: /msg '.$to.' Command Template Show <command>');
									$ircd->notice($to,$from,'<command> is the template command that you wish to view.');
								} elseif (strtolower($d[3]) == 'list') {
									$ircd->notice($to,$from,'--- Bot Help - Command Template List ---');
									$ircd->notice($to,$from,'Lists all script templates.');
									$ircd->notice($to,$from,'Syntax: /msg '.$to.' Command Template List');
									$ircd->notice($to,$from,'--- End of help ---');
								}
							}
						} elseif (strtolower($d[1]) == 'access') {
							if (!isset($d[2])) {
								$ircd->notice($to,$from,'--- Bot Help - Access ---');
								$ircd->notice($to,$from,'Add                 Add a user to your bot.');
								$ircd->notice($to,$from,'Del            Remove a user from your bot.');
								$ircd->notice($to,$from,'List            List all users on your bot.');
								$ircd->notice($to,$from,'For more information on these topics:');
								$ircd->notice($to,$from,'/msg '.$to.' Help Access Topic');
								$ircd->notice($to,$from,'--- End of help ---');
							} elseif (strtolower($d[2]) == 'add') {
								$ircd->notice($to,$from,'--- Bot Help - Access Add ---');
								$ircd->notice($to,$from,'Adds a user to your bot.');
								$ircd->notice($to,$from,'Syntax: /msg '.$to.' Access Add <username> <level>');
								$ircd->notice($to,$from,'<username> is the user\'s PHPserv username that you wish to add.');
								$ircd->notice($to,$from,'<level> is the level that you wish to add them at.');
								$ircd->notice($to,$from,'This will be available in the script lang as $blvl.');
								$ircd->notice($to,$from,'--- End of help ---');
							} elseif (strtolower($d[2]) == 'del') {
								$ircd->notice($to,$from,'--- Bot Help - Access Del ---');
								$ircd->notice($to,$from,'Removes a user from your bot.');
								$ircd->notice($to,$from,'Syntax: /msg '.$to.' Access Del <username>');
								$ircd->notice($to,$from,'<username> is the user\'s PHPserv username that you wish to remove.');
								$ircd->notice($to,$from,'--- End of help ---');
							} elseif (strtolower($d[2]) == 'list') {
								$ircd->notice($to,$from,'--- Bot Help - Access List ---');
								$ircd->notice($to,$from,'Lists all users on your bot.');
								$ircd->notice($to,$from,'Syntax: /msg '.$to.' Access List');
								$ircd->notice($to,$from,'--- End of help ---');
							}
						} elseif (strtolower($d[1]) == 'coowner') {
							if (!isset($d[2])) {
								$ircd->notice($to,$from,'--- Bot Help - Coowner ---');
								$ircd->notice($to,$from,'Add              Add a co-owner to your bot.');
								$ircd->notice($to,$from,'Del         Remove a co-owner from your bot.');
								$ircd->notice($to,$from,'List         List all co-owners on your bot.');
								$ircd->notice($to,$from,'For more information on these topics:');
								$ircd->notice($to,$from,'/msg '.$to.' Help Coowner Topic');
								$ircd->notice($to,$from,'--- End of help ---');
							} elseif (strtolower($d[2]) == 'add') {
								$ircd->notice($to,$from,'--- Bot Help - Coowner Add ---');
								$ircd->notice($to,$from,'Adds a coowner to your bot.');
								$ircd->notice($to,$from,'Syntax: /msg '.$to.' Coowner Add <username>');
								$ircd->notice($to,$from,'<username> is the user\'s PHPserv username that you wish to add.');
								$ircd->notice($to,$from,'This will be available in the script lang as $coowner.');
								$ircd->notice($to,$from,'--- End of help ---');
							} elseif (strtolower($d[2]) == 'del') {
								$ircd->notice($to,$from,'--- Bot Help - Coowner Del ---');
								$ircd->notice($to,$from,'Removes a coowner from your bot.');
								$ircd->notice($to,$from,'Syntax: /msg '.$to.' Coowner Del <username>');
								$ircd->notice($to,$from,'<username> is the user\'s phpserv username that you wish to remove.');
								$ircd->notice($to,$from,'--- End of help ---');
							} elseif (strtolower($d[2]) == 'list') {
								$ircd->notice($to,$from,'--- Bot Help - Coowner List ---');
								$ircd->notice($to,$from,'Lists all users on your bot.');
								$ircd->notice($to,$from,'Syntax: /msg '.$to.' Coowner List');
								$ircd->notice($to,$from,'--- End of help ---');
							}
						} elseif (strtolower($d[1]) == 'set') {
							if (!isset($d[2])) {
								$ircd->notice($to,$from,'--- Bot Help - Set ---');
								$ircd->notice($to,$from,'Trigger         Set the command trigger for your bot.');
								$ircd->notice($to,$from,'For more information on these topics:');
								$ircd->notice($to,$from,'/msg '.$to.' Help Set Topic');
								$ircd->notice($to,$from,'--- End of help ---');
							} elseif (strtolower($d[2]) == 'trigger') {
								$ircd->notice($to,$from,'--- Bot Help - Set Trigger ---');
								$ircd->notice($to,$from,'Sets the in-channel command trigger for your bot.');
								$ircd->notice($to,$from,'Syntax: /msg '.$to.' Set Trigger <trigger>');
								$ircd->notice($to,$from,'<trigger> is the trigger you wish to set for the bot.');
								$ircd->notice($to,$from,'The trigger may be one of the following: . , - + ! ` ~ * ; \'');
								$ircd->notice($to,$from,'--- End of help ---');
							}
						}
					}
				}
				$cmd = '__pm__'.strtolower($d[0]);
				$bot = $this->bots[$unick];
				$botid = $mysql->get($mysql->sql('SELECT `id` FROM `botserv_bots` WHERE `nick` = '.$mysql->escape($bot['nick'])));
				$botid = $botid['id'];
				if ($x = $mysql->get($mysql->sql('SELECT * FROM `botserv_cmds` WHERE `botid` = '.$mysql->escape($botid).' AND `cmd` = '.$mysql->escape($cmd)))) {
					$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
					$uid = $nickd['loggedin'];
					$user = $mysql->get($mysql->sql('SELECT * FROM `access` WHERE `id` = '.$mysql->escape($uid)));
					$level = $user['level'];
					$user = $user['user'];
					$blvl = $mysql->get($mysql->sql('SELECT * FROM `botserv_acc` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)));
					$blvl = $blvl['level'];
					if ($mysql->get($mysql->sql('SELECT * FROM `botserv_cos` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)))) { $coowner = 1; } else { $coowner = 0; }
					if ($bot['owner'] == $uid) { $owner = 1; } else { $owner = 0; }
					$vars = array
					(
						'user'	=> $user,
						'uid'	=> $uid,
						'nickd'	=> $nickd,
						'level'	=> $level,
						'owner'	=> $owner,
						'coowner'=>$coowner,
						'blvl'	=> $blvl,
						'trig'	=> $bot['trig']
					);
					$this->sboteval($from,$cmd,implode(' ',array_splice($d,1)),$bot['channel'],$bot['nick'],$x['data'],$vars);
				}
			}

			if (substr($to,0,1) == '#') {
				if ((substr($d[0],0,1) == '.') or (substr($d[0],0,1) == '-') or (substr($d[0],0,1) == '+') or (substr($d[0],0,1) == '!') or (substr($d[0],0,1) == '`') or (substr($d[0],0,1) == '~') or (substr($d[0],0,1) == '*') or (substr($d[0],0,1) == ';') or (substr($d[0],0,1) == '\'')) {
					$cmd = substr($d[0],1);
					foreach ($this->bots as $bot) {
						if ((strtolower($bot['channel']) == strtolower($to)) and ($bot['trig'] == substr($d[0],0,1))) {
							$botid = $mysql->get($mysql->sql('SELECT `id` FROM `botserv_bots` WHERE `nick` = '.$mysql->escape($bot['nick'])));
							$botid = $botid['id'];
							if ($x = $mysql->get($mysql->sql('SELECT * FROM `botserv_cmds` WHERE `botid` = '.$mysql->escape($botid).' AND `cmd` = '.$mysql->escape($cmd)))) {
								$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
								$uid = $nickd['loggedin'];
								$user = $mysql->get($mysql->sql('SELECT * FROM `access` WHERE `id` = '.$mysql->escape($uid)));
								$level = $user['level'];
								$user = $user['user'];
								$blvl = $mysql->get($mysql->sql('SELECT * FROM `botserv_acc` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)));
								$blvl = $blvl['level'];
								if ($mysql->get($mysql->sql('SELECT * FROM `botserv_cos` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)))) { $coowner = 1; } else { $coowner = 0; }
								if ($bot['owner'] == $uid) { $owner = 1; } else { $owner = 0; }
								$vars = array
								(
									'user' 	=> $user,
									'uid'	=> $uid,
									'nickd'	=> $nickd,
									'level'	=> $level,
									'owner'	=> $owner,
									'coowner'=>$coowner,
									'blvl'	=> $blvl,
									'trig'	=> $bot['trig']
								);
								$this->sboteval($from,$cmd,implode(' ',array_splice($d,1)),$to,$bot['nick'],$x['data'],$vars);
							}
						}
					}
				} else {
					$cmd = '__ch__'.$d[0];
					foreach ($this->bots as $bot) {
						if (strtolower($bot['channel']) == strtolower($to)) {
							$botid = $mysql->get($mysql->sql('SELECT `id` FROM `botserv_bots` WHERE `nick` = '.$mysql->escape($bot['nick'])));
							$botid = $botid['id'];
							if ($x = $mysql->get($mysql->sql('SELECT * FROM `botserv_cmds` WHERE `botid` = '.$mysql->escape($botid).' AND `cmd` = '.$mysql->escape($cmd)))) {
								$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
								$uid = $nickd['loggedin'];
								$user = $mysql->get($mysql->sql('SELECT * FROM `access` WHERE `id` = '.$mysql->escape($uid)));
								$level = $user['level'];
								$user = $user['user'];
								$blvl = $mysql->get($mysql->sql('SELECT * FROM `botserv_acc` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)));
								$blvl = $blvl['level'];
								if ($mysql->get($mysql->sql('SELECT * FROM `botserv_cos` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)))) { $coowner = 1; } else { $coowner = 0; }
								if ($bot['owner'] == $uid) { $owner = 1; } else { $owner = 0; }
								$vars = array
								(
									'user'	=> $user,
									'uid'	=> $uid,
									'nickd'	=> $nickd,
									'level'	=> $level,
									'owner'	=> $owner,
									'coowner'=>$coowner,
									'blvl'	=> $blvl,
									'trig'	=> $bot['trig']
								);
								$this->sboteval($from,$cmd,implode(' ',array_splice($d,1)),$to,$bot['nick'],$x['data'],$vars);
							} elseif ($x = $mysql->get($mysql->sql('SELECT * FROM `botserv_cmds` WHERE `botid` = '.$mysql->escape($botid).' AND `cmd` = '.$mysql->escape('__ch__default')))) {
								$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
								$uid = $nickd['loggedin'];
								$user = $mysql->get($mysql->sql('SELECT * FROM `access` WHERE `id` = '.$mysql->escape($uid)));
								$level = $user['level'];
								$user = $user['user'];
								$blvl = $mysql->get($mysql->sql('SELECT * FROM `botserv_acc` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)));
								$blvl = $blvl['level'];
								if ($mysql->get($mysql->sql('SELECT * FROM `botserv_cos` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)))) { $coowner = 1; } else { $coowner = 0; }
								if ($bot['owner'] == $uid) { $owner = 1; } else { $owner = 0; }
								$vars = array
								(
									'user'	=> $user,
									'uid'	=> $uid,
									'nickd'	=> $nickd,
									'level'	=> $level,
									'owner'	=> $owner,
									'coowner'=>$coowner,
									'blvl'	=> $blvl,
									'trig'	=> $bot['trig']
								);
								$this->sboteval($from,$cmd,implode(' ',$d),$to,$bot['nick'],$x['data'],$vars);
							}
						}
					}
				}
			}
		}

		function event_ctcp ($from,$to,$type,$msg) {
			global $mysql;
			$ircd = &ircd();
			$cmd = '__e_ctcp_'.$type;
			foreach ($this->bots as $bot) {
				if ((strtolower($bot['channel']) == strtolower($to)) or (strtolower($bot['nick']) == strtolower($to))) {
					$botid = $mysql->get($mysql->sql('SELECT `id` FROM `botserv_bots` WHERE `nick` = '.$mysql->escape($bot['nick'])));
					$botid = $botid['id'];
					if ($x = $mysql->get($mysql->sql('SELECT * FROM `botserv_cmds` WHERE `botid` = '.$mysql->escape($botid).' AND `cmd` = '.$mysql->escape($cmd)))) {
						$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
						$uid = $nickd['loggedin'];
						$user = $mysql->get($mysql->sql('SELECT * FROM `access` WHERE `id` = '.$mysql->escape($uid)));
						$level = $user['level'];
						$user = $user['user'];
						$blvl = $mysql->get($mysql->sql('SELECT * FROM `botserv_acc` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)));
						$blvl = $blvl['level'];
						if ($mysql->get($mysql->sql('SELECT * FROM `botserv_cos` WHERE `uid` = '.$mysql->escape($uid).' AND `botid` = '.$mysql->escape($botid)))) { $coowner = 1; } else { $coowner = 0; }
						if ($bot['owner'] == $uid) { $owner = 1; } else { $owner = 0; }
						$vars = array
						(
							'user'  => $user,
							'uid'   => $uid,
							'nickd' => $nickd,
							'level' => $level,
							'owner' => $owner,
							'coowner'=>$coowner,
							'blvl'  => $blvl,
							'type'	=> $type,
							'trig'  => $bot['trig']
						);
						$this->sboteval($from,$cmd,$msg,$to,$bot['nick'],$x['data'],$vars);
					}
				}
			}
		}

		function delbot ($nick) {
			global $mysql;
			$unick = strtolower($nick);
			$this->quitbot($unick);
			$mysql->sql('DELETE FROM `botserv_bots` WHERE `nick` = '.$mysql->escape($nick));
			unset($this->bots[$unick]);
		}

		function quitbot ($nick) {
			$ircd = &ircd();
			$ircd->quit($nick,'Bot Quitted');
		}

		function addbot ($nick,$ident,$host,$owner,$channel) {
			global $mysql;
			$unick = strtolower($nick);
			if (!$this->bots[$unick]) {
				if ($ownerid = $mysql->get($mysql->sql('SELECT `id` FROM `access` WHERE `user` = '.$mysql->escape($owner)))) {
					$ownerid = $ownerid['id'];
					$this->bots[$unick] = array
					(
						'nick' 		=> $nick,
						'ident' 	=> $ident,
						'host'		=> $host,
						'owner'		=> $ownerid,
						'channel'	=> $channel,
						'trig'		=> '.'
					);
					$tmysqlins = array
					(
						'id'		=> 'NULL',
						'nick'		=> $nick,
						'ident'		=> $ident,
						'host'		=> $host,
						'ownerid'	=> $ownerid,
						'channel'	=> $channel,
						'trig'		=> '.'
					);
					$mysql->insert('botserv_bots',$tmysqlins);
					$botid = $mysql->get($mysql->sql('SELECT `id` FROM `botserv_bots` WHERE `nick` = '.$mysql->escape($nick)));
					$this->bots[$unick]['botid'] = $botid['id'];
					return $this->initbot($unick);
				} else { return false; }
			} else { return false; }
		}

		function startbots () {
			global $mysql;
			$x = $mysql->sql('SELECT * FROM `botserv_bots`');
			while ($y = $mysql->get($x)) {
				$unick = strtolower($y['nick']);
				$this->bots[$unick]['botid'] = $y['id'];
				$this->bots[$unick]['nick'] = $y['nick'];
				$this->bots[$unick]['ident'] = $y['ident'];
				$this->bots[$unick]['host'] = $y['host'];
				$this->bots[$unick]['owner'] = $y['ownerid'];
				$this->bots[$unick]['channel'] = $y['channel'];
				$this->bots[$unick]['trig'] = $y['trig'];
				$this->initbot($unick);
			}
		}

		function initbot ($nick) {
			global $mysql;
			$ircd = &ircd();
			$bot = $this->bots[$nick];
			$ircd->addnick($mysql->getsetting('server'),$bot['nick'],$bot['ident'],$bot['host'],'PHP Service Bot');
			$ircd->join($bot['nick'],$bot['channel']);
			$ircd->mode($bot['nick'],$bot['channel'],'+oa '.$bot['nick'].' '.$bot['nick']);
			$ircd->join($bot['nick'],'#services');
			$ircd->join($bot['nick'],'#bots');
			$ircd->mode($bot['nick'],'#services','+v '.$bot['nick']);
//			$ircd->mode($bot['nick'],'#bots','+v '.$bot['nick']); // Please don't
			$ircd->mode($bot['nick'],$bot['nick'],'+S');
			return $this->sandbot($nick);
		}

		function sandbot ($nick) {
			if(!is_dir('/home/phpserv/phpserv/modules/services/botcontroller/'.$nick.'/'))
				mkdir('/home/phpserv/phpserv/modules/services/botcontroller/'.$nick.'/');
			$options = array(
				'safe_mode'=>false,
				'open_basedir'=>'/home/phpserv/phpserv/modules/services/botcontroller/'.$nick.'/',
				'allow_url_fopen'=>true,
				'disable_functions'=>'exec,shell_exec,passthru,system,posix_kill,dl,' .
						'pcntl_fork,pcntl_signal,pcntl_exec,pcntl_wait,pcntl_waitpid,' .
						'pcntl_setpriority,pcntl_getpriority,pcntl_wexitstatus,' .
						'pcntl_wifexited,pcntl_wifsignaled,pcntl_wifstopped,pcntl_wstopsig,' .
						'pcntl_wtermsig,posix_access,posix_ctermid,posix_get_last_error,' .
						'posix_getcwd,posix_getegid,posix_geteuid,posix_getgid,posix_getgrgid,' .
						'posix_getgrnam,posix_getgroups,posix_getlogin,posix_getpgid,' .
						'posix_getpgrp,posix_getpid,posix_getppid,posix_getpwnam,posix_getpwuid,' .
						'posix_getrlimit,posix_getsid,posix_getuid,posix_isatty,posix_mkfifo,' .
						'posix_mknod,posix_setegid,posix_seteuid,posix_setgid,posix_setpgid,' .
						'posix_setsid,posix_setuid,posix_strerror,posix_times,posix_ttyname,' .
						'posix_uname,mail,error_log',
				'disable_classes'=>'');
			$this->bots[$nick]['sandbox'] = new Runkit_Sandbox($options);
			$this->bots[$nick]['sandbox']['output_handler'] = array($this,'sbotout');
			$this->bots[$nick]['sandbox']->ini_set('display_errors','On');
			$this->bots[$nick]['sandbox']->ini_set('log_errors','On');
			$this->bots[$nick]['sandbox']->ini_set('log_errors_max_len','0');
			$this->bots[$nick]['sandbox']->ini_set('html_errors','Off');
			$this->bots[$nick]['sandbox']->ini_set('error_log','/home/phpserv/phpserv/modules/services/botcontroller/'.$nick.'/error.log');
			$functions  = 'declare(ticks = 1);';
			$functions .= 'function say ($text) { global $__return__; $__return__["say"][] = $text; }';
			$functions .= 'function act ($text) { global $__return__; $__return__["say"][] = chr(1)."ACTION ".$text.chr(1); }';
			$functions .= 'function kick ($nick,$reason) { global $__return__; $__return__["kick"][] = $nick." ".$reason; }';
			$functions .= 'function wkb ($nick,$reason) { global $__return__; $__return__["wkb"][] = $nick." ".$reason; }';
			$functions .= 'function mode ($mode) { global $__return__; $__return__["mode"][] = $mode; }';
			$functions .= 'function topic ($text) { global $__return__; $__return__["topic"][] = $text; }';
			$functions .= 'function invite ($nick) { global $__return__; $__return__["invite"][] = $nick; }';
			$functions .= 'function notice ($text) { global $__return__; $__return__["notice"][] = $text; }';
			$functions .= 'function ctcpreply ($text) { global $__return__; $__return__["ctcpreply"][] = $text; }';
			$functions .= 'function call ($func) { global $__return__; $__return__["call"] = $func; }';
			$functions .= 'function get ($key, $default = "") { $cfg = unserialize(file_get_contents("cfg.ser")); if(isset($cfg[$key])) return $cfg[$key]; return $default; }';
			$functions .= 'function set ($key, $value) { $cfg = unserialize(file_get_contents("cfg.ser")); $cfg[$key] = $value; file_put_contents("cfg.ser",serialize($cfg)); }';
			//$functions .= 'function __trap_alrm ($s) { die("timeout"); }';
			$functions .= 'function __trap_tick () { static $start = null; if($start == null) $start = time(); if (time() - $start > 5) die("Timeout."); }';
			//$functions .= 'pcntl_signal(SIGALRM, "__trap_alrm");';
			//format_time (gnarfel)
			$functions .= 'function format_time($seconds) {$secs = intval($seconds % 60); $mins =';
			$functions .= 'intval($seconds / 60 % 60); $hours = intval($seconds / 3600 % 24); $da';
			$functions .= 'ys = intval($seconds / 86400); if ($days > 0) { $uptimeString .= $days';
			$functions .= '; $uptimeString .= (($days == 1) ? " day" : " days") . ", ";} if ($hou';
			$functions .= 'rs > 0) {$uptimeString .= $hours; $uptimeString .= ((hours == 1) ? " h';
			$functions .= 'our" : " hours") . ", ";} if ($mins > 0) {$uptimeString .= $mins; $upt';
			$functions .= 'imeString .= (($mins == 1) ? " minute" : " minutes");} if ($secs > 0) ';
			$functions .= '{$uptimeString .= ", " . $secs; $uptimeString .= (($secs == 1) ? " sec';
			$functions .= 'ond" : " seconds");} return $uptimeString;}';
			$functions .= 'define(\'DEBUG\', false); class phpStack { var $index; var $locArray; function phpStack() { $this->locArray = array(); $this->index = -1; } function peek() { if($this->index > -1) return $this->locArray[$this->index]; else return false; } function poke($data) { $this->locArray[++$this->index] = $data; } function push($data) { $this->poke($data); } function pop() { if($this->index > -1) { $this->index--; return $this->locArray[$this->index+1]; } else return false; } function clear() { $this->index = -1; $this->locArray = array(); } function getStack() { if($this->index > -1) { $tmpArray = array(); for($i=0;$i<$this->index;$i++) $tmpArray[$i] = $this->locArray[$i]; return $tmpArray; } else return false; } } $eqSEP = array(\'open\' => array(\'(\', \'[\'), \'close\' => array(\')\', \']\')); $eqSGL = array(\'!\'); $eqST = array(\'^\'); $eqST1 = array(\'/\', \'*\', \'%\'); $eqST2 = array(\'+\', \'-\'); $eqFNC = array(\'sin\', \'cos\', \'tan\', \'csc\', \'sec\', \'cot\'); class eqEOS { var $postFix; var $inFix; function eqEOS($inFix = "") { if($inFix) $this->inFix = $inFix; else $this->inFix = ""; $this->postFix = array(); } function in2post($inFix = "") { global $eqSEP, $eqSGL, $eqST1, $eqST2, $eqFNC, $eqST; $infix = ($inFix != "") ? $inFix : $this->inFix; $pf = array(); $ops = new phpStack(); $vars = new phpStack(); $lChar = ""; preg_replace("/\\s/", "", $infix); $pfIndex = 0; $lChar = \'\'; for($i=0;$i<strlen($infix);$i++) { $chr = substr($infix, $i, 1); if(eregi(\'[0-9.]\', $chr)) { if((!eregi(\'[0-9.]\', $lChar) && ($lChar != "")) && ($pf[$pfIndex]!="-")) $pfIndex++; $pf[$pfIndex] .= $chr; } else if(in_array($chr, $eqSEP[\'open\'])) { if(eregi(\'[0-9.]\', $lChar)) $ops->push(\'*\'); $ops->push($chr); } else if(in_array($chr, $eqSEP[\'close\'])) { $key = array_search($chr, $eqSEP[\'close\']); while($ops->peek() != $eqSEP[\'open\'][$key] && $ops->peek() != false) { $nchr = $ops->pop(); if($nchr) $pf[++$pfIndex] = $nchr; else return "Error while searching for \'". $eqSEP[\'open\'][$key] ."\'"; } $ops->pop(); } else if(in_array($chr, $eqST)) { $ops->push($chr); $pfIndex++; } else if(in_array($chr, $eqST1)) { while(in_array($ops->peek(), $eqST1) || in_array($ops->peek(), $eqST)) $pf[++$pfIndex] = $ops->pop(); $ops->push($chr); $pfIndex++; } else if(in_array($chr, $eqST2)) { if(((in_array($lChar, $eqST1) || in_array($lChar, $eqST2) || in_array($lChar, $eqST)) || $lChar=="") && $chr=="-") { $pfIndex++; $pf[$pfIndex] .= $chr; } else { while(in_array($ops->peek(), $eqST1) || in_array($ops->peek(), $eqST2) || in_array($ops->peek(), $eqST)) $pf[++$pfIndex] = $ops->pop(); $ops->push($chr); $pfIndex++; } } $lChar = $chr; } while(($tmp = $ops->pop()) != false) $pf[++$pfIndex] = $tmp; $i = 0; foreach($pf as $tmp) $pfTemp[$i++] = $tmp; $pf = $pfTemp; $this->postFix = $pf; return $pf; } function solvePF($pfArray = "") { global $eqSEP, $eqSGL, $eqST1, $eqST2, $eqFNC, $eqST; $pf = (!is_array($pfArray)) ? $this->postFix : $pfArray; $temp = array(); $tot = 0; $hold = 0; for($i=0;$i<count($pf); $i++) { if(!in_array($pf[$i], $eqST1) && !in_array($pf[$i], $eqST2) && !in_array($pf[$i], $eqST)) { $temp[$hold++] = $pf[$i]; } else { $opr = $pf[$i]; if($opr=="+") $temp[$hold-2] = $temp[$hold-2] + $temp[$hold-1]; else if($opr=="-") $temp[$hold-2] = $temp[$hold-2] - $temp[$hold-1]; else if($opr=="*") $temp[$hold-2] = $temp[$hold-2] * $temp[$hold-1]; else if($opr=="/" && $temp[$hold-1] != 0) $temp[$hold-2] = $temp[$hold-2] / $temp[$hold-1]; else if($opr=="^") $temp[$hold-2] = pow($temp[$hold-2], $temp[$hold-1]); else if($opr=="%" && $temp[$hold-2] > -1) $temp[$hold-2] = bcmod($temp[$hold-2], $temp[$hold-1]); $hold = $hold-1; } } return $temp[$hold-1]; } function solveIF($infix, $vArray = "") { global $eqSEP, $eqSGL, $eqST1, $eqST2, $eqFNC, $eqST; $if = ($infix != "") ? $infix : $this->inFix; if($infix=="") die; $ops = new phpStack(); $vars = new phpStack(); preg_replace("/\\s/", "", $infix); if(DEBUG) $hand=fopen("eq.txt","a"); while((preg_match("/(.){0,1}\\&([a-zA-Z]+)(.){0,1}/", $infix, $match)) != 0) { if(DEBUG) fwrite($hand, "{$match[1]} || {$match[3]}\\n"); if((!in_array($match[1], $eqST1) && !in_array($match[1], $eqST2) && !in_array($match[1], $eqST) && !in_array($match[1], $eqSEP[\'open\']) && !in_array($match[1], $eqSEP[\'close\']) && ($match[1] != "")) || is_numeric($match[1])) $front = "*"; else $front = ""; if(!in_array($match[3], $eqST1) && !in_array($match[3], $eqST2) && !in_array($match[3], $eqST) && !in_array($match[3], $eqSEP[\'open\']) && !in_array($match[3], $eqSEP[\'close\']) && ($match[3] != "")) $back = "*"; else $back = ""; if(!isset($vArray[$match[2]]) && (!is_array($vArray != "") && !is_numeric($vArray))) return "Mal-formed equation : variable \'{$match[2]}\' not found"; else if(!isset($vArray[$match[2]]) && (!is_array($vArray != "") && is_numeric($vArray))) $infix = str_replace($match[0], $match[1] . $front. $vArray. $back . $match[3], $infix); else if(isset($vArray[$match[2]])) $infix = str_replace($match[0], $match[1] . $front. $vArray[$match[2]]. $back . $match[3], $infix); } if(DEBUG) fwrite($hand, "$infix\\n"); while((preg_match("/(". implode("|", $eqFNC) . ")\\(([^\\)\\(]*(\\([^\\)]*\\)[^\\(\\)]*)*[^\\)\\(]*)\\)/", $infix, $match)) != 0) { $func = $this->solveIF($match[2]); $func = ($func); switch($match[1]) { case "cos": $ans = cos($func); break; case "sin": $ans = sin($func); break; case "tan": $ans = tan($func); break; case "sec": if(($tmp = cos($func)) != 0) $ans = 1/$tmp; break; case "csc": if(($tmp = sin($func)) != 0) $ans = 1/$tmp; break; case "cot": if(($tmp = tan($func)) != 0) $ans = 1/$tmp; break; default: break; } $infix = str_replace($match[0], $ans, $infix); } if(DEBUG) fclose($hand); return $this->solvePF($this->in2post($infix)); } }';
			$functions .= 'class EvalMath { var $suppress_errors = false; var $last_error = null; var $v = array(\'e\'=>2.71,\'pi\'=>3.14); var $f = array(); var $vb = array(\'e\', \'pi\'); var $fb = array( \'sin\',\'sinh\',\'arcsin\',\'asin\',\'arcsinh\',\'asinh\', \'cos\',\'cosh\',\'arccos\',\'acos\',\'arccosh\',\'acosh\', \'tan\',\'tanh\',\'arctan\',\'atan\',\'arctanh\',\'atanh\', \'sqrt\',\'abs\',\'ln\',\'log\'); function EvalMath() { $this->v[\'pi\'] = pi(); $this->v[\'e\'] = exp(1); } function e($expr) { return $this->evaluate($expr); } function evaluate($expr) { $this->last_error = null; $expr = trim($expr); if (substr($expr, -1, 1) == \';\') $expr = substr($expr, 0, strlen($expr)-1); if (preg_match(\'/^\\s*([a-z]\\w*)\\s*=\\s*(.+)$/\', $expr, $matches)) { if (in_array($matches[1], $this->vb)) { return $this->trigger("cannot assign to constant \'$matches[1]\'"); } if (($tmp = $this->pfx($this->nfx($matches[2]))) === false) return false; $this->v[$matches[1]] = $tmp; return $this->v[$matches[1]]; } elseif (preg_match(\'/^\\s*([a-z]\\w*)\\s*\\(\\s*([a-z]\\w*(?:\\s*,\\s*[a-z]\\w*)*)\\s*\\)\\s*=\\s*(.+)$/\', $expr, $matches)) { $fnn = $matches[1]; if (in_array($matches[1], $this->fb)) { return $this->trigger("cannot redefine built-in function \'$matches[1]()\'"); } $args = explode(",", preg_replace("/\\s+/", "", $matches[2])); if (($stack = $this->nfx($matches[3])) === false) return false; for ($i = 0; $i<count($stack); $i++) { $token = $stack[$i]; if (preg_match(\'/^[a-z]\\w*$/\', $token) and !in_array($token, $args)) { if (array_key_exists($token, $this->v)) { $stack[$i] = $this->v[$token]; } else { return $this->trigger("undefined variable \'$token\' in function definition"); } } } $this->f[$fnn] = array(\'args\'=>$args, \'func\'=>$stack); return true; } else { return $this->pfx($this->nfx($expr)); } } function vars() { $output = $this->v; unset($output[\'pi\']); unset($output[\'e\']); return $output; } function funcs() { $output = array(); foreach ($this->f as $fnn=>$dat) $output[] = $fnn . \'(\' . implode(\',\', $dat[\'args\']) . \')\'; return $output; } function nfx($expr) { $index = 0; $stack = new EvalMathStack; $output = array(); $expr = trim(strtolower($expr)); $ops = array(\'+\', \'-\', \'*\', \'/\', \'^\', \'_\'); $ops_r = array(\'+\'=>0,\'-\'=>0,\'*\'=>0,\'/\'=>0,\'^\'=>1); $ops_p = array(\'+\'=>0,\'-\'=>0,\'*\'=>1,\'/\'=>1,\'_\'=>1,\'^\'=>2); $expecting_op = false; if (preg_match("/[^\\w\\s+*^\\/()\\.,-]/", $expr, $matches)) { return $this->trigger("illegal character \'{$matches[0]}\'"); } while(1) { $op = substr($expr, $index, 1); $ex = preg_match(\'/^([a-z]\\w*\\(?|\\d+(?:\\.\\d*)?|\\.\\d+|\\()/\', substr($expr, $index), $match); if ($op == \'-\' and !$expecting_op) { $stack->push(\'_\'); $index++; } elseif ($op == \'_\') { return $this->trigger("illegal character \'_\'"); } elseif ((in_array($op, $ops) or $ex) and $expecting_op) { if ($ex) { $op = \'*\'; $index--; } while($stack->count > 0 and ($o2 = $stack->last()) and in_array($o2, $ops) and ($ops_r[$op] ? $ops_p[$op] < $ops_p[$o2] : $ops_p[$op] <= $ops_p[$o2])) { $output[] = $stack->pop(); } $stack->push($op); $index++; $expecting_op = false; } elseif ($op == \')\' and $expecting_op) { while (($o2 = $stack->pop()) != \'(\') { if (is_null($o2)) return $this->trigger("unexpected \')\'"); else $output[] = $o2; } if (preg_match("/^([a-z]\\w*)\\($/", $stack->last(2), $matches)) { $fnn = $matches[1]; $arg_count = $stack->pop(); $output[] = $stack->pop(); if (in_array($fnn, $this->fb)) { if($arg_count > 1) return $this->trigger("too many arguments ($arg_count given, 1 expected)"); } elseif (array_key_exists($fnn, $this->f)) { if ($arg_count != count($this->f[$fnn][\'args\'])) return $this->trigger("wrong number of arguments ($arg_count given, " . count($this->f[$fnn][\'args\']) . " expected)"); } else { return $this->trigger("internal error"); } } $index++; } elseif ($op == \',\' and $expecting_op) { while (($o2 = $stack->pop()) != \'(\') { if (is_null($o2)) return $this->trigger("unexpected \',\'"); else $output[] = $o2; } if (!preg_match("/^([a-z]\\w*)\\($/", $stack->last(2), $matches)) return $this->trigger("unexpected \',\'"); $stack->push($stack->pop()+1); $stack->push(\'(\'); $index++; $expecting_op = false; } elseif ($op == \'(\' and !$expecting_op) { $stack->push(\'(\'); $index++; $allow_neg = true; } elseif ($ex and !$expecting_op) { $expecting_op = true; $val = $match[1]; if (preg_match("/^([a-z]\\w*)\\($/", $val, $matches)) { if (in_array($matches[1], $this->fb) or array_key_exists($matches[1], $this->f)) { $stack->push($val); $stack->push(1); $stack->push(\'(\'); $expecting_op = false; } else { $val = $matches[1]; $output[] = $val; } } else { $output[] = $val; } $index += strlen($val); } elseif ($op == \')\') { return $this->trigger("unexpected \')\'"); } elseif (in_array($op, $ops) and !$expecting_op) { return $this->trigger("unexpected operator \'$op\'"); } else { return $this->trigger("an unexpected error occured"); } if ($index == strlen($expr)) { if (in_array($op, $ops)) { return $this->trigger("operator \'$op\' lacks operand"); } else { break; } } while (substr($expr, $index, 1) == \' \') { $index++; } } while (!is_null($op = $stack->pop())) { if ($op == \'(\') return $this->trigger("expecting \')\'"); $output[] = $op; } return $output; } function pfx($tokens, $vars = array()) { if ($tokens == false) return false; $stack = new EvalMathStack; foreach ($tokens as $token) { if (in_array($token, array(\'+\', \'-\', \'*\', \'/\', \'^\'))) { if (is_null($op2 = $stack->pop())) return $this->trigger("internal error"); if (is_null($op1 = $stack->pop())) return $this->trigger("internal error"); switch ($token) { case \'+\': $stack->push($op1+$op2); break; case \'-\': $stack->push($op1-$op2); break; case \'*\': $stack->push($op1*$op2); break; case \'/\': if ($op2 == 0) return $this->trigger("division by zero"); $stack->push($op1/$op2); break; case \'^\': $stack->push(pow($op1, $op2)); break; } } elseif ($token == "_") { $stack->push(-1*$stack->pop()); } elseif (preg_match("/^([a-z]\\w*)\\($/", $token, $matches)) { $fnn = $matches[1]; if (in_array($fnn, $this->fb)) { if (is_null($op1 = $stack->pop())) return $this->trigger("internal error"); $fnn = preg_replace("/^arc/", "a", $fnn); if ($fnn == \'ln\') $fnn = \'log\'; eval(\'$stack->push(\' . $fnn . \'($op1));\'); } elseif (array_key_exists($fnn, $this->f)) { $args = array(); for ($i = count($this->f[$fnn][\'args\'])-1; $i >= 0; $i--) { if (is_null($args[$this->f[$fnn][\'args\'][$i]] = $stack->pop())) return $this->trigger("internal error"); } $stack->push($this->pfx($this->f[$fnn][\'func\'], $args)); } } else { if (is_numeric($token)) { $stack->push($token); } elseif (array_key_exists($token, $this->v)) { $stack->push($this->v[$token]); } elseif (array_key_exists($token, $vars)) { $stack->push($vars[$token]); } else { return $this->trigger("undefined variable \'$token\'"); } } } if ($stack->count != 1) return $this->trigger("internal error"); return $stack->pop(); } function trigger($msg) { $this->last_error = $msg; if (!$this->suppress_errors) trigger_error($msg, E_USER_WARNING); return false; } } class EvalMathStack { var $stack = array(); var $count = 0; function push($val) { $this->stack[$this->count] = $val; $this->count++; } function pop() { if ($this->count > 0) { $this->count--; return $this->stack[$this->count]; } return null; } function last($n=1) { return $this->stack[$this->count-$n]; } }';
			$this->bots[$nick]['sandbox']->eval($functions);
			$this->bots[$nick]['sandbox']->eval('chdir(\'/home/phpserv/phpserv/modules/services/botcontroller/'.addslashes($nick).'/\');');
		}

		function sboteval ($nick,$cmd,$message,$chan,$bot,$script,$othervars) {
			global $mysql;
			$ircd = &ircd();
			$ubot = strtolower($bot);
			try {
				if (!$this->bots[$ubot]['sandbox']['active']) {
					unset($this->bots[$ubot]['sandbox']);
					$this->sandbot($ubot);
				}
				$this->curbot = $bot;
				$this->curbotctr = 0;
				$this->bots[$ubot]['sandbox']->nick = $nick;
				$this->bots[$ubot]['sandbox']->command = $cmd;
				$this->bots[$ubot]['sandbox']->message = $message;
				$this->bots[$ubot]['sandbox']->channel = $chan;
				$this->bots[$ubot]['sandbox']->me = $bot;
				foreach ($othervars as $y => $x) {
					$this->bots[$ubot]['sandbox']->$y = $x;
				}
				//$this->bots[$ubot]['sandbox']->pcntl_alarm(5);
				//$this->bots[$ubot]['sandbox']->set_time_limit(6);
				$this->bots[$ubot]['sandbox']->eval('register_tick_function("__trap_tick");');
				$this->bots[$ubot]['sandbox']->eval($script);
				//$this->bots[$ubot]['sandbox']->set_time_limit(0);
				//$this->bots[$ubot]['sandbox']->pcntl_alarm(0);
				$data = $this->bots[$ubot]['sandbox']->__return__;
			}
			catch (Exception $e) {
				logit('Caught exception: '.$e->getMessage());
				$bt = getTrace();
				logit('Backtrace:');
				foreach ($bt as $y) {
					logit($y);
				}
			}
			unset($this->bots[$ubot]['sandbox']->__return__);
			$this->curbot = '';
			$repeat = 1;
			$called = 0;
			
			while ($repeat == 1) {
				$repeat = 0;
				if (isset($data['say'])) {
					$x = 0;
					foreach ($data['say'] as $y) {
						$y = str_replace("\n",'',$y);
						if ($x <= 10) { $ircd->msg($bot,$chan,$y); }
						$x++;
					}
				}
	
				if (isset($data['notice'])) {
					$x = 0;
					foreach ($data['notice'] as $y) {
						$y = str_replace("\n",'',$y);
						if ($x <= 10) { $ircd->notice($bot,$nick,$y); }
						$x++;
					}
				}

				if (isset($data['ctcpreply'])) {
					$x = 0;
					foreach ($data['ctcpreply'] as $y) {
						$y = str_replace("\n",'',$y);
						if ($x <= 3) { $ircd->notice($bot,$nick,"\001".$y."\001"); }
						$x++;
					}
				}


				if (isset($data['kick'])) {
					$x = 0;
					foreach ($data['kick'] as $y) {
						$y = str_replace("\n",'',$y);
						if ($x <= 10) { $y1 = explode(" ",$y); $ircd->kick($bot,$chan,$y1[0],implode(" ",array_splice($y1,1))); }
						$x++;
					}
				}
				
				if (isset($data['wkb'])) {
					$x = 0;
					foreach ($data['wkb'] as $y) {
						$y = str_replace("\n",'',$y);
						if ($x <= 10) { $y1 = explode(" ",$y); event('warnkickban',$chan,$y1[0],implode(" ",array_splice($y1,1))); }
						$x++;
					}
				}

				if (isset($data['mode'])) {
					$x = 0;
					foreach ($data['mode'] as $y) {
						$y = str_replace("\n",'',$y);
						if ($x <= 10) { $ircd->mode($bot,$chan,$y); }
						$x++;
					}
				}

				if (isset($data['topic'])) {
					$x = 0;
					foreach ($data['topic'] as $y) {
						$y = str_replace("\n",'',$y);
						if ($x <= 2) { $ircd->topic($bot,$chan,$y); }
						$x++;
					}
				}

				if (isset($data['invite'])) {
					$x = 0;
					foreach ($data['invite'] as $y) {
						$y = str_replace("\n",'',$y);
						if ($x <= 3) { $ircd->invite($bot,$chan,$y); }
						$x++;
					}
				}

				if (isset($data['call'])) {
					if ($called < 3) {
						if ($x = $mysql->get($mysql->sql('SELECT * FROM `botserv_cmds` WHERE `botid` = '.$mysql->escape($this->bots[$ubot]['botid']).' AND `cmd` = '.$mysql->escape($data['call'])))) {
							$this->curbot = $bot;
							//$this->bots[$ubot]['sandbox']->eval('pcntl_alarm(5);');
							//$this->bots[$ubot]['sandbox']->eval('set_time_limit(6);');
							$this->bots[$ubot]['sandbox']->eval($x['data']);
							//$this->bots[$ubot]['sandbox']->eval('set_time_limit(0);');
							//$this->bots[$ubot]['sandbox']->eval('pcntl_alarm(0);');
							$data = $this->bots[$ubot]['sandbox']->__return__;
							unset($this->bots[$ubot]['sandbox']->__return__);
							$this->curbot = '';
							$repeat = 1;
						}
					}
					$called++;
				}
			}
		}

		function sbotout ($d) {
			if (!isset($this->curbot) || $this->curbot == '')
				return;
			$ircd = &ircd();
			$x = explode("\n",$d);
			$nick = $this->curbot;
			$unick = strtolower($nick);
			foreach ($x as $y) {
				if ($this->curbotctr >= 5) {
					//unset($this->bots[$unick]['sandbox']);
					break;
				}
				$ircd->notice($nick,$this->bots[$unick]['sandbox']->nick,$y);
				$this->curbotctr += 1;
			}
		}

		function event_eos () {
			global $mysql;
//			global $modules;
			$ircd = &ircd();

			$ircd->addnick($mysql->getsetting('server'),'BotServ','Services','Services.ClueNet.org','Bot Service');
			$ircd->join('BotServ','#services');
			$ircd->join('BotServ','#bots');
			$ircd->mode('BotServ','#services','+oa BotServ BotServ');
//			$ircd->mode('BotController','#bots','+oa BotController BotController');
			$this->startbots();
		}
	}

//	class modinit {
                function registerm () {
//                        global $modules;
                        $class = new botcontroller;
                        register($class, __FILE__, 'BotServ Module', 'botcontroller');
		}
//	}
?>
