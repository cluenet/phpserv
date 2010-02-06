<?PHP

	class cobibot {

		private $set;
		private $bans;
		private $timer;
		private $lastcobispeak;
		private $deathbyfire;

		function construct() {
			$this->set = unserialize(file_get_contents('CobiBot.set'));
			$this->lastcobispeak = 0;
			$this->event_eos();
		}

		function saveset() {
			file_put_contents('CobiBot.set',serialize($this->set));
		}

		function destruct() {
			$ircd = &ircd();
			$ircd->quit('CobiBot', 'Module Unloaded.');
			$this->saveset();
		}

		function mask($user,$host) {
			$ret = '*!*';
			if ($user{0} == '~') $user = substr($user,1);
			$ret .= $user.'@';
			if (strpos($host,':') !== false) {
				$host = explode(':',$host);
				$ret.= $host[0].':'.$host[1].':'.$host[2].':'.$host[3].':*';
			} else if (long2ip(ip2long($host)) == $host) {
				$host = explode('.',$host);
				$ret.= $host[0].'.'.$host[1].'.'.$host[2].'.*';
			} else {
				$host = explode('.',$host,2);
				$ret.= '*.'.$host[1];
			}
			return $ret;
		}

		function alsoknownas($nick) {
			global $mysql;
			$othernicks = array();
			$nickdata = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$mask = $this->mask($nickdata['user'],$nickdata['host']);
			$res = $mysql->sql('SELECT `nick` FROM `cobibot_aka` WHERE `host` = '.$mysql->escape($mask).' AND LOWER(`nick`) != '.$mysql->escape(strtolower($nick)));
			while ($x = $mysql->get($res)) $othernicks[] = $x['nick'];
			$send = '';
			if (count($othernicks) > 2) {
				for ($i = 0; $i < (count($othernicks) - 1); $i++) $send.= $othernicks[$i].', ';
				$send.= 'and '.$othernicks[count($othernicks)-1];
			} elseif (count($othernicks) == 2) { 
				$send.= $othernicks[0].' and '.$othernicks[1];
			} elseif (count($othernicks) == 1) { 
				$send.= $othernicks[0];
			}
			return $send;
		}

/*
		IRC Framework commands:

		$ircd->addserv		($name,$desc)
		$ircd->smo		($mode,$message)
		$ircd->addserv2serv	($new,$old,$desc)
		$ircd->ctcp		($src,$dest,$ctcp,$message = NULL)
		$ircd->ctcpreply	($src,$dest,$ctcp,$reply = NULL)
		$ircd->addnick		($server,$nick,$ident,$host,$name)
		$ircd->join		($nick,$chan)
		$ircd->part		($nick,$chan,$reason = NULL)
		$ircd->mode		($nick,$chan,$mode)
		$ircd->kick		($nick,$chan,$who,$reason)
		$ircd->invite		($nick,$chan,$who)
		$ircd->topic		($nick,$chan,$topic)
		$ircd->quit		($nick,$reason)
		$ircd->msg		($src,$dest,$message)
		$ircd->servmsg		($dest,$message)
		$ircd->notice		($src,$dest,$message)
		$ircd->servnotice	($dest,$message)
		$ircd->svsnick		($old,$new)
		$ircd->kill		($nick,$reason)
		$ircd->svskill		($nick,$reason)

		$mysql->getaccess($nick)
		$mysql->getsetting('server')
*/

		function event_msg ($from,$to,$message) {
			global $mysql;
			$ircd = &ircd();
		
//			$ircd->msg('CobiBot','#CobiBot','Message: From '.$from.' to '.$to.': '.$message);
			if (strtolower($to) == 'cobibot') {
				if ($message{0} != '!') {
					$ircd->msg('CobiBot','#CobiBot',chr(2).'Private Message to me:'.chr(2).' ('.$from.') '.$message);
					$ircd->msg('CobiBot','#HelpOps',chr(2).'Private Message to me:'.chr(2).' ('.$from.') '.$message);
				}
			}
			if (strtolower($to) == '#cobibot') {
				if ($mysql->getaccess($from) >= 999) {
					if ($message{0} == '!') {
						$data = explode(' ',substr($message,1));
						switch (strtolower($data[0])) {
							case 'chanactivewatch':
								$this->set['join'][strtolower($data[1])] = 1;
								$ircd->join('CobiBot',$data[1]);
							case 'chanpassivewatch':
								$this->set['watch'][strtolower($data[1])] = 1;
								break;
							case 'chanactiveunwatch':
								unset($this->set['join'][strtolower($data[1])]);
								$ircd->part('CobiBot',$data[1]);
							case 'chanpassiveunwatch':
								unset($this->set['watch'][strtolower($data[1])]);
								break;
						}
						$this->saveset();
					}
				}
			}

			if ($message{0} == '!') {
				$data = explode(' ',substr($message,1),2);
				$headers = 'From: CobiBot@ClueNet.Org';
				switch (strtolower($data[0])) {
					case 'cobi':
						if ($mysql->getaccess($from) >= 999) {
							mail('9194268602@messaging.sprintpcs.com',$from.'@IRC',$data[1],$headers);
							$ircd->notice('CobiBot',$from,'SMS sent.');
						}
					case 'cobimail':
						mail('cobi@cluenet.org',$from.'@IRC',$data[1], $headers);
						$ircd->notice('CobiBot',$from,'Email sent.');
						break;
					case 'mj94':
						mail($mysql->getsetting('mj94.textaddress','cobibot'),$from.'@IRC',$data[1],$headers);
						$ircd->msg('CobiBot','#cobibot','Sent email ('.$from.'@IRC'.') to '.$mysql->getsetting('mj94.textaddress','cobibot').' containing: '.$data[1]);
						$ircd->notice('CobiBot',$from,'SMS sent.');
						break;
					case 'cobitask':
						//php ~/taskfreakadder/addtask.php Cobi 1 'Incoming from CobiBot' 8 - 1 'Testing' 'This is just another test.'
						$items = explode(':',$data[1],4);
						if (count($items) != 4) {
							$ircd->notice('CobiBot',$from,'Invalid syntax.  Please use !CobiTask priority:deadline:title:description, where');
							$ircd->notice('CobiBot',$from,'priority is a number between 1 and 9, with lower numbers being higher priority;');
							$ircd->notice('CobiBot',$from,'deadline is the number of days from now, 0 being today, and - meaning no deadline;');
							$ircd->notice('CobiBot',$from,'title is a *short* description of what to do; and');
							$ircd->notice('CobiBot',$from,'description is a longer description of what exactly to do.');
						} else {
							$ret = system('php /home/phpserv/taskfreakadder/addtask.php Cobi '.escapeshellarg($items[0]).' '.escapeshellarg('Incoming from CobiBot').' 8 '.escapeshellarg($items[1]).' 1 '.escapeshellarg($from.': '.$items[2]).' '.escapeshellarg($items[3]));
							if ($ret == '')
								$ircd->notice('CobiBot',$from,'Task added.');
							else
								$ircd->notice('CobiBot',$from,'Possible error: '.$ret);
						}
						break;
					case 'aka':
						if (isset($data[1])) $akanick = $data[1];
						else $akanick = $from;
						$send = $this->alsoknownas($akanick);
						if ($send != '') $ircd->msg('CobiBot',$to,$from.': '.$akanick.' has also been known as: '.$send.'.');
						else $ircd->msg('CobiBot',$to,$from.': '.$akanick.' has not been known as any other nick.');
						unset($send,$akanick);
						break;
					case 'deathbyfire':
					case 'listdbf':
						if( $mysql->getaccess( $from ) >= 999 ) {
							if( isset( $data[ 1 ] ) ) $time = $data[ 1 ];
							else $time = 60;
							foreach( $this->deathbyfire as $v )
								if( time() - $v[ 'time' ] < $time )
									if( strtolower( $data[ 0 ] ) == 'deathbyfire' )
										$ircd->gzline( 'CobiBot', $v[ 'nick' ], '84d', 'Die in a fire!' );
									else
										$ircd->notice( 'CobiBot', $from, $v[ 'nick' ] . ' - ' . ( time() - $v[ 'time' ] ) . ' ago.' );
						}
						unset( $time );
						break;
				}
			}

			if (isset($this->set['watch'][strtolower($to)])) {
				$ircd->msg('CobiBot','#CobiBot','Message: From '.$from.' to '.$to.': '.$message);
			}

			if ($from == 'Cobi') {
				$this->lastcobispeak = time();
			}

			if ($to{0} == '#') {
				if (fnmatch('*cobi*',strtolower($message))) {
					$ircd->msg('CobiBot','#CobiBot','Cobi: People are talking about you in '.$to.' - <'.$from.'> '.$message);
					if ((substr(strtolower($message),0,4) == 'cobi') or (substr(strtolower($message),-5,4) == 'cobi')) {
						if ((time() - $this->lastcobispeak) > 300) {
							$ircd->notice('CobiBot',$from,'Cobi may not be here. However if you have a question, now or in the future, please use !Cobi <your question> if it is semi-important.  Cobi will see it ASAP.  Thanks.');
						}
					}
				}
			}
		}

		function event_signon ($from,$user,$host,$real,$ip,$server) {
			global $mysql;
			$ircd = &ircd();

			$ircd->msg('CobiBot','#CobiBot','User connect: '.$from.'!'.$user.'@'.$host.':'.$real.' ('.$ip.') to '.$server);
			$mask = $this->mask($user,$host);
			$count = $mysql->get($mysql->sql('SELECT COUNT(*) AS `count` FROM `cobibot_aka` WHERE `host` = '.$mysql->escape($mask).' AND LOWER(`nick`) = '.$mysql->escape(strtolower($from))));
			if ($count['count'] == 0) $mysql->insert('cobibot_aka',array('host' => $mask, 'nick' => $from));
		}

		function event_nick ($from,$to) {
			global $mysql;
			$ircd = &ircd();

			$ircd->msg('CobiBot','#CobiBot','Nick change: '.$from.' -> '.$to);
			if (!($nickdata = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)))))
				$nickdata = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($to)));

			$mask = $this->mask($nickdata['user'],$nickdata['host']);
			$count = $mysql->get($mysql->sql('SELECT COUNT(*) AS `count` FROM `cobibot_aka` WHERE `host` = '.$mysql->escape($mask).' AND LOWER(`nick`) = '.$mysql->escape(strtolower($to))));
			if ($count['count'] == 0) $mysql->insert('cobibot_aka',array('host' => $mask, 'nick' => $to));
		}

		function event_quit ($nick,$message) {
			global $mysql;
			$ircd = &ircd();
			$ircd->msg('CobiBot','#CobiBot','User quit: '.$nick.' with reason: '.$message);
			if ($nick == 'Cobi') {
				if (fnmatch('*Local kill by*',$message)) {
					$m = explode(' ',$message);
					$ircd->kill($m[4],'Global kill by CobiBot (Die in a fire.)');
					$ircd->addnick($mysql->getsetting('server'),$m[4],'CobiBot','CobiBot.cluenet.org','Juped nick held by CobiBot.');
				}
			}
		}
		
		function event_join ($nick,$channel) {
			global $mysql;
			$ircd = &ircd();
			$ircd->msg('CobiBot','#CobiBot','User join: '.$nick.' -> '.$channel);
			if( strtolower( $channel ) == '#clueirc' ) {
				foreach( $this->deathbyfire as $k => $v )
					if( time() - $v[ 'time' ] > 3600 )
						unset( $this->deathbyfire[ $k ] );
				$this->deathbyfire[] = array(
					'time' => time(),
					'nick' => $nick
				);
			}
		}
		
		function event_part ($nick,$channel,$reason) {
			$ircd = &ircd();
			$ircd->msg('CobiBot','#CobiBot','User part: '.$nick.' <> '.$channel.' Reason: '.$reason);
		}
		
		function event_kill ($from,$nick,$message) {
			global $mysql;
			$ircd = &ircd();
			$ircd->msg('CobiBot','#CobiBot','User kill: '.$from.' killed '.$nick.' with reason: '.$message);
			if ((($nick == 'Cobi') or ($nick == 'CobiBot')) and (strpos($from,'.')===false)) {
				$ircd->kill($from,'Global kill by CobiBot (Die in a fire.)');
				$ircd->addnick($mysql->getsetting('server'),$from,'CobiBot','CobiBot.cluenet.org','Juped nick held by CobiBot.');
			}
			if ($nick == 'CobiBot') {
				sleep(3);
				$this->construct();
			}
		}
		
		function event_ctcp ($from,$to,$type,$msg) {
			$ircd = &ircd();
			if (strtoupper($type) != 'ACTION') {
				$ircd->msg('CobiBot','#CobiBot','CTCP: '.$from.' sent CTCP ('.$type.') to '.$to.' with message: '.$msg);
			}
			if (strtoupper($type) == 'ACTION') {
				if ($to{0} == '#') {
					if (fnmatch('*cobi*',strtolower($msg))) {
						$ircd->msg('CobiBot','#CobiBot','Cobi: People are talking about you in '.$to.' - * '.$from.' '.$msg);
						if ((substr(strtolower($msg),0,4) == 'cobi') or (substr(strtolower($msg),-5,4) == 'cobi')) {
							if ((time() - $this->lastcobispeak) > 300) {
								$ircd->notice('CobiBot',$from,'Cobi may not be here, however if you have a question, now or in the future, please use !Cobi <your question> if it is semi-important.  Cobi will see it ASAP.  Thanks.');
							}
						}
					}
				}
			}
		}
		
		function event_ctcpreply ($from,$to,$ctcp,$message = NULL) {
			$ircd = &ircd();
			$ircd->msg('CobiBot','#CobiBot','CTCP Reply: '.$from.' sent CTCPREPLY ('.$ctcp.') to '.$to.' with message: '.$message);
		}
		
		function event_notice ($from,$to,$message) {
			$ircd = &ircd();
			$ircd->msg('CobiBot','#CobiBot','NOTICE: '.$from.' noticed '.$to.': '.$message);
		}
		
		function event_kick ($from,$nick,$channel,$reason) {
			$ircd = &ircd();
			$ircd->msg('CobiBot','#CobiBot','User kick: '.$from.' kicked '.$nick.' from '.$channel.' with a reason: '.$reason);
			if (strtolower($nick) == 'cobibot') {
				unset($this->set['newchans'][strtolower($channel)]);
				unset($this->set['join'][strtolower($channel)]);
				$this->saveset();
				$ircd->msg('CobiBot',$channel,'Sorry if I was intruding.  Thanks for removing me.  :)');
			}
		}
		
		function event_mode ($from,$to,$mode) {
			$ircd = &ircd();
			$ircd->msg('CobiBot','#CobiBot','Mode: '.$from.' set mode '.$mode.' on '.$to);
		}
		
		function event_chanmode_b ($from,$to,$type,$mask) {
			if ($type == '+') {
				$this->bans[] = Array (
					'setter' => $from,
					'channel' => $to,
					'mask' => $mask,
					'set' => time()
				);
			} elseif ($type == '-') {
				foreach ($this->bans as $k => $v) {
					if ($v['mask'] == $mask) unset($this->bans[$k]);
				}
			}
		}
		
		function event_channel_create ($channel,$nick) {
			$ircd = &ircd();
			$ircd->msg('CobiBot','#CobiBot','Channel Created: '.$channel);
			$this->set['newchans'][strtolower($channel)] = 1;
			$this->saveset();
			$ircd->join('CobiBot',$channel);
			$ircd->msg('CobiBot',$channel,'Congratulations!  You have created a new channel.  If you want to register this channel with ClueNet\'s services so that it will be yours if you get disconnected, type /cs help register.  If you need help, don\'t hesitate to PM me.  Unless you object, I am going to hang out here for a while.  If I have intruded upon a private channel, please accept my apologies, and kick me to let me know it was private.  I won\'t mind.  :)');
		}
		
		function event_channel_destroyed ($channel,$nick,$why) {
			$ircd = &ircd();
			$ircd->msg('CobiBot','#CobiBot','Channel Destroyed: '.$channel);
			$ircd->part('CobiBot',$channel);
			unset($this->set['newchans'][strtolower($channel)]);
			unset($this->set['join'][strtolower($channel)]);
			foreach ($this->bans as $k => $v) {
				if (strtolower($v['channel']) == strtolower($channel)) unset($this->bans[$k]);
			}
			$this->saveset();
		}


		function event_eos () {
			global $mysql;
			$ircd = &ircd();

			$ircd->addnick($mysql->getsetting('server'),'CobiBot','Cobi','Cobi.cluenet.org','Cobi.');
//			$ircd->mode('CobiBot','CobiBot','');
			$ircd->join('CobiBot','#CobiBot');
//			if (!isset($this->set['nspass'])) {
//				$this->set['nspass'] = md5(rand(1,1000000000000000000000));
//				$ircd->msg('CobiBot','NickServ','REGISTER '.$this->set['nspass']);
//				$this->needreg = 1;
//				$this->saveset();
//			}
			$this->timer = 0;
//			$ircd->msg('CobiBot','NickServ','IDENTIFY '.$this->set['nspass']);

			foreach ($this->set['join'] as $chan => $junk) $ircd->join('CobiBot',$chan);
			foreach ($this->set['newchans'] as $chan => $junk) $ircd->join('CobiBot',$chan);

			$ircd->join('CobiBot','#ClueIRC');
			$ircd->join('CobiBot','#HelpOps');

			$ircd->svsmode('CobiBot','#CobiBot','-vhoaqIeb');
			$ircd->mode('CobiBot','#CobiBot','-kfLljpcQKVCu');
			$ircd->mode('CobiBot','#CobiBot','-zNSMTG+smntir');
			$ircd->mode('CobiBot','#CobiBot','+ROI Cobi!*@*');

			$ircd->msg('CobiBot','#CobiBot','End of sync.');
			$this->bans = array();
		}

		function event_timer() {
			$ircd = &ircd();
			$this->timer++;
			if (isset($this->needreg)) {
				if ($this->timer == 45) {
					$ircd->msg('CobiBot','NickServ','REGISTER '.$this->set['nspass']);
					$ircd->msg('CobiBot','NickServ','IDENTIFY '.$this->set['nspass']);
				}
			}
			if ($this->timer % 10 == 0) {
				foreach ($this->bans as $k => $v) {
					if (
						(strtolower($v['channel']) == '#clueirc')
						or (strtolower($v['channel']) == '#clueshells')
					) {
						switch (strtolower($v['setter'])) {
							case 'cobi': $expiry = 7*86400; break; // 1 Week
							case 'crispy': $expiry = 14*86400; break; // 2 Weeks
							case 'bash':
							case 'clue':
							case 'rembrandt':
							case 'chanserv':
							case 'davinci':
								$expiry = 2*3600; break; // 2 Hours
							case 'tonyb':
							case 'spyro_boy':
							case 'jared':
							case 'santium':
							case 'crazytales':
							case 'martinp23':
							case 'wimt':
							case 'monobi':
							case 'soxred93':
							case 'phase':
							case 'lamia':
							case 'methecooldude':
								$expiry = 2*86400; break; // 2 Days
							default:
								$expiry = 4*7*86400; break; // 4 Weeks
						}
						if ($v['set'] + $expiry < time()) {
							$ircd->mode('CobiBot',$v['channel'],'-b '.$v['mask']);
							$ircd->msg('CobiBot','#CobiBot','Ban '.$k.' ('.$v['mask'].') set by '.$v['setter'].' at '.$v['set'].' on '.$v['channel'].' has expired and been unset.');
							unset($this->bans[$k]);
						}
					}
				}
			}
		}
	}

	function registerm () {
		$class = new cobibot;
		register($class, __FILE__, 'CobiBot Module', 'cobibot');
	}
?>
