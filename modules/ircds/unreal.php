<?PHP
	class unreal {
		private static $connected;
		function event_raw_in ($data) {
			global $mysql;
			$data = str_replace("\n", "", str_replace("\r", "", $data));
			$d_a = explode(' ', $data);
			if (@strtolower($d_a[0]) == "ping") {
				$this->raw('PONG '.$d_a[1]);

			} elseif (@strtolower($d_a[0]) == "nick") {
//                                event('signon', $d_a[1], $d_a[2], $d_a[3], $d_a[4], $d_a[5], $d_a[6], $d_a[7], substr(implode(array_slice($d_a, 8), " "), 1));
//				NICK gnarfel 1 1135469324 iseedp2 cpe-24-58-228-168.twcny.res.rr.com powerplace.ath.cx 0 +iwx * :Anthony F
				$a = preg_split('//', base64_decode($d_a[10]), -1, PREG_SPLIT_NO_EMPTY);
				foreach ($a as $y => $x) {
					$a[$y] = ord($x);
				}
				$msg = implode('.',$a);
				event('signon', $d_a[1], $d_a[4], $d_a[5], substr(implode(array_slice($d_a, 11), " "), 1), $msg, $d_a[6], $d_a[7]);

			} elseif (@strtolower($d_a[1]) == "nick") {
				event('nick', substr($d_a[0],1), $d_a[2]);

			} elseif (@strtolower($d_a[1]) == "svsnick") {
				event('nick', $d_a[2], $d_a[3]);

//			} elseif (@strtolower($d_a[1]) == "server") {
//				event('server', 
			} elseif (@strtolower($d_a[1]) == "quit") {
				event('quit', substr($d_a[0],1), substr(implode(' ', array_slice($d_a,2)),1));

			} elseif (@strtolower($d_a[1]) == "join") {
				$c = explode(',',$d_a[2]);
				foreach ($c as $y) {
					event('join', substr($d_a[0],1), $y);
				}

			} elseif (@strtolower($d_a[1]) == "part") {
				event('part', substr($d_a[0],1), $d_a[2], substr(implode(' ', array_slice($d_a,3)),1));

			} elseif (@strtolower($d_a[1]) == "kill") {
				event('kill', substr($d_a[0],1), $d_a[2], substr(implode(' ', array_slice($d_a,3)),1));

			} elseif (@strtolower($d_a[1]) == "svskill") {
				event('svskill', substr($d_a[0],1), $d_a[2], substr(implode(' ', array_slice($d_a,3)),1));

			} elseif (@strtolower($d_a[1]) == "mode") {
				$modeline = implode(' ', array_slice($d_a,3));
				if ($modeline{0} == ':') $modeline = substr($modeline,1);
				$from = substr($d_a[0],1);
				$to = $d_a[2];
				$modes = $d_a[3];
				if ($modes{0} == ':') $modes = substr($modes,1);

				event('mode', $from, $to, $modeline);

				if ($to{0} == '#') event('chanmode', $from, $to, $modeline);
				else event('usermode', $from, $to, $modeline);

				$i = 4;
				$t = '+';
				for ($j = 0; $j < strlen($modes); $j++) {
					if ($to{0} == '#') { // Channels
						switch ($modes{$j}) {
							case '+': $t = '+'; break;
							case '-': $t = '-'; break;
							case 'q': // Owner
							case 'a': // Chanadmin
							case 'o': // Op
							case 'h': // Halfop
							case 'v': // Voice
							case 'b': // Ban
							case 'e': // Ban exception
							case 'I': // Invite exception
							case 'k': // Keyed
							case 'f': // Anti-flood
							case 'L': // Limit redirect
							case 'l': // Limit
							case 'j': // Join throttle
								event('chanmode_'.$modes{$j}, $from, $to, $t, $d_a[$i]);
								$i++;
								break;
							default:
								event('chanmode_'.$modes{$j}, $from, $to, $t);
								break;
						}
					} else { // Users
						switch ($modes{$j}) {
							case '+': $t = '+'; break;
							case '-': $t = '-'; break;
							default:
								event('usermode_'.$modes{$j}, $from, $to, $t);
								break;
						}
					}
				}
				unset($from, $to, $modes, $modeline, $i, $j, $t);


			} elseif (@strtolower($d_a[1]) == "invite") {
				//:Cobi INVITE Katelin :#fun
				event('invite', substr($d_a[0],1), $d_a[2], substr($d_a[3],1));

			} elseif (@strtolower($d_a[1]) == "privmsg") {
				if (($d_a[3]{1} == chr(1)) and (substr(implode(' ', array_slice($d_a,3)),-1,1) == chr(1))) {
					$reply = substr(substr(implode(' ', array_slice($d_a,3)),1),1,-1);
					$reply = explode(' ',$reply,2);
					$type = $reply[0];
					if (!isset($reply[1])) { $reply = ''; }
					else { $reply = $reply[1]; }
					event('ctcp', substr($d_a[0],1), $d_a[2], $type, $reply);
				} else {
					event('msg', substr($d_a[0],1), $d_a[2], substr(implode(' ', array_slice($d_a,3)),1));
				}

			} elseif (@strtolower($d_a[1]) == "notice") {
				if (($d_a[3]{1} == chr(1)) and (substr(implode(' ', array_slice($d_a,3)),-1,1) == chr(1))) {
					$reply = substr(substr(implode(' ', array_slice($d_a,3)),1),1,-1);
					$reply = explode(' ',$reply,2);
					$type = $reply[0];
					$reply = $reply[1];
					if (!isset($reply)) { $reply = ''; }
					event('ctcpreply', substr($d_a[0],1), $d_a[2], $type, $reply);
				} else {
					event('notice', substr($d_a[0],1), $d_a[2], substr(implode(' ', array_slice($d_a,3)),1));
				}

			} elseif (@strtolower($d_a[1]) == "eos") {
				global $connected;
				if (!$connected) { 
					$connected = true;
					global $aml3;
					$aml3 = 1;
					$this->smo('o', "\002(\002Burst\002)\002 [".$mysql->getsetting('server')."] End of Incomming NetBurst.");
					event('eos', substr($d_a[0],1));
				}
			} elseif (@strtolower($d_a[1]) == "kick") {
				//:source KICK #channel user :reason
				// Emit event: kick, $src $pwntUser $channel $reason
				event('kick', substr($d_a[0],1), $d_a[3], $d_a[2], substr(implode(' ', array_slice($d_a,4)),1));
			} elseif (@strtolower($d_a[1]) == "topic") {
				//:source TOPIC #channel nick timestamp :topic
				// Emit event: topic, $nick $chan $newtopic
				event('topic',substr($d_a[0],1), $d_a[2], substr(implode(' ', array_slice($d_a,5)),1));
			}
		}

		function event_connected () {
			global $mysql;
			$this->raw('PASS '.$mysql->getsetting('pass'));
			$this->raw('PROTOCTL NICKv2 NICKIP');
			$this->raw('SERVER '.$mysql->getsetting('server').' '.$mysql->getsetting('numeric').' :'.$mysql->getsetting('desc'));
		}

		function raw ($data) {
			global $sock;
			$sock->write(str_replace("\r",'',$data)); /* We should never be sending \r. */
		}


		function addserv ($name,$desc) {
			global $mysql;
			$this->raw(':'.$mysql->getsetting('server').' SERVER '.$name.' 2 :'.$desc);
		}

		function smo ($mode,$message) {
			global $mysql;
			$this->raw(':'.$mysql->getsetting('server').' SMO '.$mode.' :'.$message);
		}

		function addserv2serv ($new,$old,$desc) {
			$this->raw(':'.$old.' SERVER '.$new.' 3 :'.$desc);
		}

		function ctcp ($src,$dest,$ctcp,$message = NULL) {
			if ($message != NULL) {
				$this->msg($src,$dest,"\001".strtoupper($ctcp).' '.$message."\001");
			} else {
				$this->msg($src,$dest,"\001".strtoupper($ctcp)."\001");
			}
		}

		function ctcpreply ($src,$dest,$ctcp,$reply = NULL) {
			if ($reply != NULL) {
				$this->notice($src,$dest,"\001".strtoupper($ctcp).' '.$reply."\001");
			} else {
				$this->notice($src,$dest,"\001".strtoupper($ctcp)."\001");
			}
		}

		function addnick ($server,$nick,$ident,$host,$name) {
			$this->svskill($nick,'Nick collision by services');
			$this->raw('NICK '.$nick.' 1 '.time().' '.$ident.' '.$host.' '.$server.' 0 :'.$name);
		}

		function join ($nick,$chan) {
			$this->raw(':'.$nick.' JOIN '.$chan);
		}

		function part ($nick,$chan,$reason = NULL) {
			if ($reason != NULL) {
				$this->raw(':'.$nick.' PART '.$chan.' :'.$reason);
			} else {
				$this->raw(':'.$nick.' PART '.$chan);
			}
		}

		function mode ($nick,$chan,$mode) {
			$this->raw(':'.$nick.' MODE '.$chan.' '.$mode);
			$this->event_raw_in(':'.$nick.' MODE '.$chan.' '.$mode);
		}

		function kick ($nick,$chan,$who,$reason) {
			$this->raw(':'.$nick.' KICK '.$chan.' '.$who.' :'.$reason);
			event('kick', $nick, $who, $chan, $reason);
		}

		function invite ($nick,$chan,$who) {
			$this->raw(':'.$nick.' INVITE '.$who.' '.$chan);
		}

		function topic ($nick,$chan,$topic) {
			$this->raw(':'.$nick.' TOPIC '.$chan.' :'.$topic);
		}

		function svsmode ($nick,$who,$mode) {
			$this->raw(':'.$nick.' SVSMODE '.$who.' '.$mode);
		}
		function chghost ($from,$nick,$host) {
			// $from = the nick to source the change from (ie HostServ)
			// $nick = the nick recieving the change
			// $host = the new hostname
			$this->raw(':'.$from.' CHGHOST .'.$nick.' '.$host);
		}

		function eos ($server = NULL) {
			if ($server != NULL) {
				$this->raw(':'.$server.' EOS');
			} else {
				$this->raw('EOS');
			}
		}

		function squit ($server,$reason) {
			$this->raw('SQUIT '.$server.' :'.$reason);
		}

		function quit ($nick,$reason) {
			$this->raw(':'.$nick.' QUIT :'.$reason);
			event('quit',$nick,$reason);
		}

		function msg ($src,$dest,$message) {
			$this->raw(':'.$src.' PRIVMSG '.$dest.' :'.$message);
		}

		function servmsg ($dest,$message) {
			$this->raw('PRIVMSG '.$dest.' :'.$message);
		}

		function notice ($src,$dest,$message) {
			$this->raw(':'.$src.' NOTICE '.$dest.' :'.$message);
		}

		function servnotice ($dest,$message) {
			$this->raw('NOTICE '.$dest.' :'.$message);
		}

		function svsnick ($old,$new) {
			global $mysql;
			$this->raw(':'.$mysql->getsetting('server').' SVSNICK '.$old.' '.$new.' '.time());
			event('nick',$old,$new);
		}

		function kill ($nick,$reason) {
			global $mysql;
			$this->raw('KILL '.$nick.' :'.$reason);
			event('kill',$mysql->getsetting('server'),$nick,$reason);
		}

		function shun ($from,$to,$time,$reason) {
			$this->nicetkl('s',$to,$time,$reason,$from);
		}

		function gline ($from,$to,$time,$reason) {
			$this->nicetkl('G',$to,$time,$reason,$from);
		}
		
		function gzline ($from,$to,$time,$reason) {
			$this->nicetkl('Z',$to,$time,$reason,$from);
		}

		function sajoin ($from,$who,$to) {
			$this->raw(':'.$from.' SAJOIN '.$who.' '.$to);
			event('join',$who,$to);
		}

		function svskill ($nick,$reason) {
			global $mysql;
			$this->raw('SVSKILL '.$nick.' :'.$reason);
			event('svskill',$mysql->getsetting('server'),$nick,$reason);
		}
		
		function swhois ($nick,$swhois='') {
			$this->raw('SWHOIS '.$nick.' :'.$swhois);
		}

		function nicetkl ($type,$mask,$duration,$reason,$source = null) {
			global $mysql;

			if ($mask{0} == '+') {
				$mode = '+';
				$mask = substr($mask,1);
			} else if ($mask{0} == '-') {
				$mode = '-';
				$mask = substr($mask,1);
			} else {
				$mode = '+';
			}

			if (strpos($mask,'!') !== false) {
				logit('[ircd] [tkl] [error] Cannot have "!" in masks.');
				return 0;
			}
			if ($mask{0} == ':') {
				logit('[ircd] [tkl] [error] Mask cannot start with a ":".');
				return 0;
			}
			if (strpos($mask,' ') !== false) {
				logit('[ircd] [tkl] [error] FAIL! FAIL! FAIL!  Masks can not have spaces in them ...');
				return 0;
			}

			if (strpos($mask,'@') !== false) {
				if (($mask{0} == '@') or (substr($mask,-1) == '@')) {
					logit('[ircd] [tkl] [error] No user@host specified.');
					return 0;
				}

				$usermask = explode('@',$mask,2);
				$hostmask = $usermask[1];
				$usermask = $usermask[0];

				if ($hostmask{0} == ':') {
					logit('[ircd] [tkl] [error] For (weird) technical reasons you cannot start the host with a ":", sorry.');
					return 0;
				}

				if ((($type == 'z') or ($type == 'Z')) and ($mode == '+')) {
					if ($usermask != '*') {
						logit('[ircd] [tkl] [error] (g)zlines must be placed at *@ipmask, not user@ipmask. This is '
							. 'because (g)zlines are processed BEFORE dns and ident lookups are done. '
							. 'If you want to use usermasks, use a KLINE/GLINE instead.');
						return -1;
					}
					if (preg_match('/[A-Za-z]/',$hostmask)) {
						logit('[ircd] [tkl] [error] (g)zlines must be placed at *@\037IPMASK\037, not *@HOSTMASK '
							. '(so for example *@192.168.* is ok, but *@*.aol.com is not). '
							. 'This is because (g)zlines are processed BEFORE dns and ident lookups are done. '
							. 'If you want to use hostmasks instead of ipmasks, use a KLINE/GLINE instead.');
						return -1;
					}
				}
			} else {
				$nickdata = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($mask)));
				if (is_array($nickdata)) {
					$usermask = '*';
					if (($type == 'z') or ($type == 'Z')) {
						$hostmask = $nickdata['ip'];
						if (!$hostmask) {
							logit('[ircd] [tkl] [error] Could not get IP address for user "'.$mask.'".');
							return 0;
						}
					} else {
						$hostmask = $nickdata['host'];
						if (!$hostmask) {
							logit('[ircd] [tkl] [error] Could not get host address for user "'.$mask.'".');
							return 0;
						}
					}
				} else {
					logit('[ircd] [tkl] [error] No such nick "'.$mask.'".');
					return 0;
				}
			}

			$secs = 0;

			if ($mode == '+') {
				$secs = $this->atime($duration);
				if ($secs < 0) {
					logit('[ircd] [tkl] [error] The time you specified is out of range!');
					return 0;
				}
			}

			if ($secs != 0) {
				$secs += time();
			}

			$this->tkl($mysql->getsetting('server'),$mode,$type,$usermask,$hostmask,($source == null)?'PHPServ!PHPServ@PHPServ':$source,$secs,time(),$reason);
		}

		function tkl ($server,$mode,$type,$ident,$host,$source,$expiry,$set,$reason) {
			$this->raw(':'.$server.' TKL '.$mode.' '.$type.' '.$ident.' '.$host.' '.$source.' '.$expiry.' '.$set.' :'.$reason);
		}

		function svso ($nick,$mode) {
			$this->raw('SVSO '.$nick.' '.$mode);
		}

		function atime ($time) {
			if (is_numeric($time)) return $time;

			$ret = 0;
			$tmp = '';
			for ($i = 0; $i < strlen($time); $i++) {
				if (is_numeric($time{$i})) {
					$tmp.= $time{$i};
				} else {
					switch ($time{$i}) {
						case 'd': $tmp *= 86400; break;
						case 'h': $tmp *= 3600; break;
						case 'm': $tmp *= 60; break;
					}
					$ret += $tmp;
					$tmp = 0;
				}
			}
			$ret += $tmp;
			return $ret;
		}

		function isValidNick($nick) { return preg_match('#^[a-zA-Z\\\\[\]{}][a-zA-Z0-9\x2d\x5b-\x5e\x60\x7b\7d]*$#',$nick); }
		
	}


	function registerm () {
		$class = new unreal;
		register($class, __FILE__, 'UnrealIRCd Server Module', 'ircd');
	}
?>
