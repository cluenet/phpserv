<?PHP
	class cluenet {
		private $settings;
		private $state;

		function construct() {
			$this->loadconfig();
			$this->loadstate();
			$this->event_eos();
		}

		function destruct() {
			$this->saveconfig();
			$this->savestate();
			$ircd = &ircd();
			$ircd->quit('ClueNet', 'Module Unloaded.');
		}

		function loadconfig() { $this->settings = unserialize(file_get_contents('Council.set')); }

		function saveconfig() { file_put_contents('Council.set', serialize($this->settings)); }
		
		function loadstate() { $this->state = unserialize(file_get_contents('Council.state')); }

		function savestate() { file_put_contents('Council.state', serialize($this->state)); }

		function adduser($user) {
			global $mysql;
			$ircd = &ircd();
			$uid = $mysql->get($mysql->sql('SELECT `id` FROM `access` WHERE `user` = '.$mysql->escape($user)));
			if (isset($uid['id'])) {
				$uid = $uid['id'];
				$this->settings['users'][$uid] = 1;
				$this->saveconfig();
				$result = $mysql->sql('SELECT `nick` FROM `users` WHERE `loggedin` = '.$mysql->escape($uid));
				while ($x = $mysql->get($result)) {
					$ircd->sajoin('ClueNet',$x['nick'],'#cluecouncil');
					$ircd->msg('ClueNet',$x['nick'],'You have been added to the ClueNet Council.  All ClueCouncil members MUST be in #cluecouncil.  Use /msg ClueNet joinme to get in.  All ClueCouncil members also must be logged into PHPServ.  /msg ClueNet help for help.');
				}
			}
		}

		function addbot($bot,$owner,$featureset) {
			global $mysql;
			$ircd = &ircd();
			$uid = $mysql->get($mysql->sql('SELECT `id` FROM `access` WHERE `user` = '.$mysql->escape($bot)));
			if (isset($uid['id'])) {
				$uid = $uid['id'];
				$this->settings['bots'][$uid] = array('owner' => $owner,'features' => $featureset);
				$this->saveconfig();
				$result = $mysql->sql('SELECT `nick` FROM `users` WHERE `loggedin` = '.$mysql->escape($uid));
				$ircd->msg('ClueNet',$owner,'Your bot, '.$bot.', has been added to the approved bots list for the official channels.  It must remain logged into PHPServ while it is on the channel.');
			}
		}

		function deluser($user) {
			global $mysql;
			$ircd = &ircd();
			$uid = $mysql->get($mysql->sql('SELECT `id` FROM `access` WHERE `user` = '.$mysql->escape($user)));
			if (isset($uid['id'])) {
				$uid = $uid['id'];
				unset($this->settings['users'][$uid]);
				$this->saveconfig();
				$result = $mysql->sql('SELECT `nick` FROM `users` WHERE `loggedin` = '.$mysql->escape($uid));
				while ($x = $mysql->get($result)) {
					$ircd->kick('ClueNet','#cluecouncil',$x['nick'],'You are no longer on the ClueCouncil.');
					$ircd->msg('ClueNet',$x['nick'],'You have been removed from the ClueCouncil.');
				}
			}
		}

		function delbot($bot) {
			global $mysql;
			$ircd = &ircd();
			$uid = $mysql->get($mysql->sql('SELECT `id` FROM `access` WHERE `user` = '.$mysql->escape($bot)));
			if (isset($uid['id'])) {
				$uid = $uid['id'];
				$owner = $this->settings['bots'][$uid]['owner'];
				unset($this->settings['bots'][$uid]);
				$result = $mysql->sql('SELECT `nick` FROM `users` WHERE `loggedin` = '.$mysql->escape($uid));
				while ($x = $mysql->get($result)) {
					$ircd->mode('ClueNet','#clueirc','+b '.$x['nick'].'!*@*');
					$ircd->kick('ClueNet','#clueirc',$x['nick'],'You are no longer an approved bot.');
				}
				$ircd->msg('ClueNet',$owner,'Your bot, '.$bot.', has been removed from the approved bots list for the official channels.');
			}
		}

		function vote ($percent, $count, $endtime, $endcount, $andor, $functions, $who, $whoid, $action) {
//			$ret = count($this->state['votes']);
			end($this->state['votes']);
			$ret = key($this->state['votes']) + 1;
			$this->state['votes'][$ret]['perc'] = $percent;
			$this->state['votes'][$ret]['count'] = $count;
			$this->state['votes'][$ret]['endtime'] = $endtime;
			$this->state['votes'][$ret]['endcount'] = $endcount;
			$this->state['votes'][$ret]['andor'] = $andor;
			$this->state['votes'][$ret]['functions'] = $functions;
			$this->state['votes'][$ret]['who'] = $who;
			$this->state['votes'][$ret]['whoid'] = $whoid;
			$this->state['votes'][$ret]['action'] = $action;
			$this->state['votes'][$ret]['yes'] = array();
			$this->state['votes'][$ret]['no'] = array();
			$this->savestate();
			return $ret;
		}

		function voteaction ($id) {
			global $mysql;
			$ircd = &ircd();
			foreach ($this->state['votes'][$id]['functions'] as $f) {
				if (is_array($f['function']) and (count($f['function']) > 1)) {
					if ($f['function'][0] == '$this') $f['function'][0] = $this;
					else if ($f['function'][0] == '$ircd') $f['function'][0] = $ircd;
					else if ($f['function'][0] == '$mysql') $f['function'][0] = $mysql;
				}
				call_user_func_array($f['function'],$f['params']);
			}
		}
		
		function votecheck ($id) {
			$ending = false;
			$votesyes = count($this->state['votes'][$id]['yes']);
			$votesno = count($this->state['votes'][$id]['no']);
			$votestotal = $votesyes + $votesno;
			if ($votestotal)
				$percent = $votesyes / $votestotal * 100;
			else
				$percent = 0;
			$metrics = $votesyes.'/'.$votesno.'/'.$votestotal.'/'.$percent.'% support/oppose/total/percent';
			if ( $this->state['votes'][$id]['andor'] == 'and') {
				if ( ($this->state['votes'][$id]['endtime'] <= time()) and ($votestotal >= $this->state['votes'][$id]['endcount'])) {
					$ending = true;
				}
			} else {
				if ( ($this->state['votes'][$id]['endtime'] <= time()) or ($votestotal >= $this->state['votes'][$id]['endcount'])) {
					$ending = true;
				}
			}
			if ($ending == true) {
				if ( ($percent >= $this->state['votes'][$id]['perc']) and ($votesyes >= $this->state['votes'][$id]['count'])) {
					$this->voteaction($id);
					$this->councilchat('Vote id "'.$id.'"'.(isset($this->state['votes'][$id]['action']) ? ' ('.$this->state['votes'][$id]['action'].')' : '').(isset($this->state['votes'][$id]['reason']) ? ' ('.$this->state['votes'][$id]['reason'].')' : '').' has ended and has been enacted.  ('.$metrics.')');
				} else {
					$this->councilchat('Vote id "'.$id.'"'.(isset($this->state['votes'][$id]['action']) ? ' ('.$this->state['votes'][$id]['action'].')' : '').(isset($this->state['votes'][$id]['reason']) ? ' ('.$this->state['votes'][$id]['reason'].')' : '').' has ended and has failed.  ('.$metrics.')');
				}
				unset($this->state['votes'][$id]);
				$this->savestate();
			}
		}

		function votecheckall () {
			foreach ($this->state['votes'] as $id => $arr) {
				$this->votecheck($id);
			}
		}
		
		function isvalidvote($id) {
			if (isset($this->state['votes'][$id])) return true;
			return false;
		}
		
		function voteyes($id,$user) {
			if (isset($this->state['votes'][$id]['yes'][$user])) return true;
			if (isset($this->state['votes'][$id]['no'][$user])) {
				unset($this->state['votes'][$id]['no'][$user]);
				$this->state['votes'][$id]['yes'][$user] = 1;
				$this->savestate();
				return true;
			}
			$this->state['votes'][$id]['yes'][$user] = 1;
			$this->savestate();
			return true;
		}

		function voteno($id,$user) {
			if (isset($this->state['votes'][$id]['no'][$user])) return true;
			if (isset($this->state['votes'][$id]['yes'][$user])) {
				unset($this->state['votes'][$id]['yes'][$user]);
				$this->state['votes'][$id]['no'][$user] = 1;
				$this->savestate();
				return true;
			}
			$this->state['votes'][$id]['no'][$user] = 1;
			$this->savestate();
			return true;
		}

		function votenull($id,$user) {
			if (isset($this->state['votes'][$id]['yes'][$user])) {
				unset($this->state['votes'][$id]['yes'][$user]);
			}
			if (isset($this->state['votes'][$id]['no'][$user])) {
				unset($this->state['votes'][$id]['no'][$user]);
			}
			$this->savestate();
		}

		function duration ($s) {
			$m = floor($s/60);
			$s %= 60;
			
			$h = floor($m/60);
			$m %= 60;

			$d = floor($h/24);
			$h %= 24;

			$w = floor($d/7);
			$d %= 7;

			if ($w > 0) $ret[] = $w.' week'.($w > 1 ? 's' : '');
			if ($d > 0) $ret[] = $d.' day'.($d > 1 ? 's' : '');
			if ($h > 0) $ret[] = $h.' hour'.($h > 1 ? 's' : '');
			if ($m > 0) $ret[] = $m.' minute'.($m > 1 ? 's' : '');
			if ($s > 0) $ret[] = $s.' second'.($s > 1 ? 's' : '');
			return implode(', ',$ret);
		}

		function timer ($name,$iterations,$interval,$function,$args = array()) {
			$this->state['timers'][$name] = array
				(
				'iterations' => $iterations,
				'interval' => $interval,
				'last' => $this->state['ts'],
				'count' => 0,
				'function' => $function,
				'args' => $args
				);
			$this->savestate();
		}

		function dotimers () {
			global $mysql;
			$ircd = &ircd();
			$this->state['ts']++;
			if (!isset($this->state['timers']) or !is_array($this->state['timers'])) return;
			foreach ($this->state['timers'] as $name => &$data) {
				 if (($data['last'] + $data['interval']) == $this->state['ts']) {
					$data['last'] = $this->state['ts'];
					$data['count']++;
					if (($data['iterations'] != 0) and ($data['count'] == $data['iterations'])) {
						unset($this->state['timers'][$name]);
					}
					$tmp = $data['function'];
					if (is_array($tmp) and (count($tmp) > 1)) { 
						if ($tmp[0] == '$this') $tmp[0] = $this;
						if ($tmp[0] == '$ircd') $tmp[0] = $ircd;
						if ($tmp[0] == '$mysql') $tmp[0] = $mysql; 
					}
					call_user_func_array($tmp,$data['args']);
				}
			}
			$this->savestate();
		}

		function event_timer () {
			$this->votecheckall();
			$this->dotimers();
		}
		
		function councilchat ($msg) {
			global $mysql;
			$ircd = &ircd();
			$query = 'SELECT `nick` FROM `users` WHERE `loggedin` = -2';
			foreach ($this->settings['users'] as $uid => $x) $query .= ' OR `loggedin` = '.$mysql->escape($uid);
			$result = $mysql->sql($query);
			while ($x = $mysql->get($result)) {
				$ircd->notice('ClueNet',$x['nick'],'*** CouncilChat *** '.$msg);
			}
			$ircd->msg('ClueNet','#cluecouncil','CouncilChat: '.$msg);
		}

		function event_usermode_B ($from,$to,$type) {
			if (strtolower($from) == strtolower($to)) {
				if ($type == '-') {
					unset($this->state['bots'][$from]);
					$this->savestate();
				} else if ($type == '+') {
					$this->state['bots'][$from] = 1;
					$this->savestate();
				}
				$this->checkbotvio();
			}
		}
		
		function wkbvote($time,$function,$what) {
			$voteid = $this->vote(
				60,
				3,
				time() + $time,
				7,
				'or',
				$function,
				'WKB',
				-1,
				$what
			);
			$this->voteyes($voteid,-1); //WKB
			$this->voteyes($voteid,-2); //Katelin
			$this->voteyes($voteid,1);  //Cobi
			$this->councilchat('WKB (-1) has requested a vote for "'.$what.'".  The vote id is "'.$voteid.'".  The required percent is 60%, the required supports to pass is "3", the end time is "'.date('F jS, Y, H:i:s (T)',time() + $time).'" ('.$this->duration(time() + $time - time()).' from now), the end count is "7".');
		}
		
		function event_warnkickban($chan,$nick,$reason) {
			global $mysql;
			$ircd = &ircd();
			
			if (!isset($this->state['warnings']))
				$this->state['warnings'] = array();
			foreach ($this->state['warnings'] as &$data)
				if ((time() - $data['time'] > 86400) and ($data['level'] > -1)) {
					$data['level'] -= floor((time() - $data['time']) / 86400);
					$data['time'] += 86400*(floor((time() - $data['time']) / 86400));
					if ($data['level'] < -1)
						$data['level'] = -1;
				}
			if (!isset($this->state['warnings'][$nick]))
				$this->state['warnings'][$nick] = array('time' => time(), 'level' => -1);
			$this->state['warnings'][$nick]['level']++;
			$this->state['warnings'][$nick]['time'] = time();
			
			if ($this->state['warnings'][$nick]['level'] == 0)
				$this->wkbvote(30,array (array (
					'function' => array ('$ircd','msg'),
					'params' => array('ClueNet',$chan,$nick.': '.$reason)
				)),'Warn '.$nick.' on '.$chan.' about '.$reason);
			else if ($this->state['warnings'][$nick]['level'] == 1)
				$this->wkbvote(60,array (array (
					'function' => array ('$ircd','kick'),
					'params' => array ('ClueNet',$chan,$nick,$reason)
				)),'Kick '.$nick.' on '.$chan.' for '.$reason);
			else if ($this->state['warnings'][$nick]['level'] == 9)
				$this->wkbvote(120,array (array (
					'function' => array ('$ircd','kill'),
					'params' => array ($nick,'This is your final warning: '.$reason)
				)),'Kill '.$nick.' for "'.'This is your final warning: '.$reason.'"');
			else if ($this->state['warnings'][$nick]['level'] > 9)
				$this->wkbvote(150,array (array (
					'function' => array ('$ircd','gzline'),
					'params' => array ('ClueNet',$nick,'2629743','So be it: '.$reason)
				)),'GZ:Line '.$nick.' for "'.'So be it: '.$reason.'"');
			else if ($this->state['warnings'][$nick]['level'] > 1) {
				$data1 = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
				$this->wkbvote(90,array (array (
					'function' => array ('$ircd','kick'),
					'params' => array ('ClueNet',$chan,$nick,$reason)
				)),'Kick '.$nick.' on '.$chan.' for '.$reason);
				$this->wkbvote(90,
					array (
						array (
							'function' => array ('$ircd','mode'),
							'params' => array ('ClueNet',$chan,'+b '.$nick.'!*@*')
						),
						array (
							'function' => array ('$this','timer'),
							'params' => array (md5('ban'.$chan.$nick.'!*@*'.'7200'),1,7200,
								array ( '$ircd', 'mode' ),
								array ( 'ClueNet', $chan, '-b '.$nick.'!*@*' )
							)
						)
					),
					'Ban '.$nick.'!*@* on '.$chan.' for 7200 seconds.'
				);
				$this->wkbvote(90,
					array (
						array (
							'function' => array ('$ircd','mode'),
							'params' => array ('ClueNet',$chan,'+b ~r:'.str_replace(' ','_',$data1['realname']))
						),
						array (
							'function' => array ('$this','timer'),
							'params' => array (md5('ban'.$chan.'~r:'.str_replace(' ','_',$data1['realname']).'7200'),1,7200,
								array ( '$ircd', 'mode' ),
								array ( 'ClueNet', $chan, '-b ~r:'.str_replace(' ','_',$data1['realname']) )
							)
						)
					),
					'Ban ~r:'.str_replace(' ','_',$data1['realname']).' on '.$chan.' for 7200 seconds.'
				);
			}
			
			$this->savestate();
		}
		
		function handlewkbmsg ($from,$to,$msg) {
			global $mysql;
			$ircd = &ircd();
			if (strtolower($msg) == 'level') {
				if (!isset($this->state['warnings']))
					$this->state['warnings'] = array();
				foreach ($this->state['warnings'] as &$data)
					if ((time() - $data['time'] > 86400) and ($data['level'] > -1)) {
						$data['level'] -= floor((time() - $data['time']) / 86400);
						$data['time'] += 86400*(floor((time() - $data['time']) / 86400));
						if ($data['level'] < -1)
							$data['level'] = -1;
					}
					if (isset($this->state['warnings'][$from]))
						$ircd->notice('ClueNet',$from,'You are level '.$this->state['warnings'][$from]['level'].' since '.(time() - $this->state['warnings'][$from]['time']).' seconds ago.');
					else
						$ircd->notice('ClueNet',$from,'You don\'t have a record with me.  (Good thing)');
			} else if ($mysql->getaccess($from) > 900) {
				$split = explode(' ',$msg,4);
				switch (strtolower($split[0])) {
					case 'wkb':
						event('warnkickban',$split[1],$split[2],$split[3]);
						$ircd->notice('ClueNet',$from,'Done.');
						break;
					case 'set':
						$this->state['warnings'][$split[1]] = array('time' => $split[2],'level' => $split[3]);
						$ircd->notice('ClueNet',$from,'Done.');
						break;
					case 'get':
						if (isset($split[1]) and ($split[1]))
							$x = var_export($this->state['warnings'][$split[1]],true);
						else
							$x = var_export($this->state['warnings'],true);
						$x = explode("\n", $x);
						foreach ($x as $y)
							$ircd->notice('ClueNet',$from,$y);
						break;
					case 'save':
						$this->savestate();
						$ircd->notice('ClueNet',$from,'Done.');
						break;
					case 'load':
						$this->loadstate();
						$ircd->notice('ClueNet',$from,'Done.');
						break;
					default:
						$ircd->notice('ClueNet',$from,'Unknown command.');
						break;
				}
			} else
				$ircd->notice('ClueNet',$from,'Insufficient access or unknown command.');
		}

		function event_msg ($from,$to,$message) {
			global $mysql;
			$ircd = &ircd();
			
			if (strtolower($to) != 'cluenet') return;

			$m = explode(' ',$message);

			include 'modules/services/council.cmds.php';

			$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
			$uid = $nickd['loggedin'];

			if (strtolower($m[0]) == 'commands') {
				foreach ($commands as $name => $cmd) {
					$ircd->notice('ClueNet',$from,$name.' - '.$cmd['description']);
				}
			} else if (isset($commands[strtolower($m[0])])) {
				if (isset($this->settings['users'][$uid])) {
					$cmd = $commands[strtolower($m[0])];
					$voteid = $this->vote($cmd['percent'], $cmd['count'], $cmd['endtime'], $cmd['endcount'], $cmd['andor'], $cmd['functions'], $from, $uid, $cmd['action']);
					$this->voteyes($voteid,$uid);
					$ircd->notice('ClueNet',$from,'Your vote id is "'.$voteid.'".');
					$this->councilchat($from.' ('.$uid.') has requested a vote for "'.$cmd['action'].'".  The vote id is "'.$voteid.'".  The required percent is '.$cmd['percent'].'%, the required supports to pass is "'.$cmd['count'].'", the end time is "'.date('F jS, Y, H:i:s (T)',$cmd['endtime']).'" ('.$this->duration($cmd['endtime'] - time()).' from now), the end count is "'.$cmd['endcount'].'".');
				} else {
					$ircd->notice('ClueNet',$from,'Insufficient access.');
				}
			} else if (strtolower($m[0]) == 'wkb') {
				$temp = explode(' ',$message,2);
				$this->handlewkbmsg($from,$to,$temp[1]);
			} else if (strtolower($m[0]) == 'vote') {
				if (isset($this->settings['users'][$uid])) {
					if ($this->isvalidvote($m[1])) {
						if ($m[2] == 'yes') {
							$this->voteyes($m[1],$uid);
							$ircd->notice('ClueNet',$from,'Vote recorded.');
						} else if ($m[2] == 'no') {
							$this->voteno($m[1],$uid);
							$ircd->notice('ClueNet',$from,'Vote recorded.');
						} else if ($m[2] == 'null') {
							$this->votenull($m[1],$uid);
							$ircd->notice('ClueNet',$from,'Vote removed.');
						} else {
							$ircd->notice('ClueNet',$from,'"'.$m[3].'" not understood, please use "yes", "no", or "null".');
						}
					} else {
						$ircd->notice('ClueNet',$from,'"'.$m[1].'" is not a valid vote id.');
					}
				} else {
					$ircd->notice('ClueNet',$from,'Insufficient access.');
				}
			} else if (strtolower($m[0]) == 'votes') {
				if (isset($this->settings['users'][$uid])) {
					if (count($this->state['votes']) > 0) {
						$ircd->notice('ClueNet',$from,'Votes:');
						foreach ($this->state['votes'] as $id => $v) {
							$ircd->notice('ClueNet',$from,'Vote ID #'.$id.': '.$v['action'].' Req. percent: '.$v['perc'].'% Req. supports: '.$v['count'].' End time: '.date('F jS, Y, H:i:s (T)',$v['endtime']).' ('.$this->duration($v['endtime'] - time()).' from now) '.$v['andor'].' End count: '.$v['endcount'].'.  Currently '.(count($v['yes']) + count($v['no'])).' votes.'.(isset($v['who'])?'  Proposed by '.$v['who'].'.':''));
							if (isset($v['reason']) and ($v['reason'] != '')) $ircd->notice('ClueNet',$from,'Vote ID #'.$id.': Reason: '.$v['reason']);
						}
					} else {
						$ircd->notice('ClueNet',$from,'There are currently no votes ongoing.');
					}
				} else {
					$ircd->notice('ClueNet',$from,'Insufficient access.');
				}
			} else if (strtolower($m[0]) == 'newvotes') {
				if (isset($this->settings['users'][$uid])) {
					$count = 0; foreach ($this->state['votes'] as $id => $v) if (!isset($v['yes'][$uid]) and !isset($v['no'][$uid])) $count++;
					if ($count > 0) {
						$ircd->notice('ClueNet',$from,'Votes you have not voted on:');
						foreach ($this->state['votes'] as $id => $v) {
							if (!isset($v['yes'][$uid]) and !isset($v['no'][$uid])) {
								$ircd->notice('ClueNet',$from,'Vote ID #'.$id.': '.$v['action'].' Req. percent: '.$v['perc'].'% Req. supports: '.$v['count'].' End time: '.date('F jS, Y, H:i:s (T)',$v['endtime']).' ('.$this->duration($v['endtime'] - time()).' from now) '.$v['andor'].' End count: '.$v['endcount'].'.  Currently '.(count($v['yes']) + count($v['no'])).' votes.'.(isset($v['who'])?'  Proposed by '.$v['who'].'.':''));
								if (isset($v['reason']) and ($v['reason'] != '')) $ircd->notice('ClueNet',$from,'Vote ID #'.$id.': Reason: '.$v['reason']);
							}
						}
					} else {
						$ircd->notice('ClueNet',$from,'You have voted on all current votes.');
					}
				} else {
					$ircd->notice('ClueNet',$from,'Insufficient access.');
				}
			} else if (strtolower($m[0]) == 'reason') {
				if (isset($this->settings['users'][$uid])) {
					if ($this->isvalidvote($m[1])) {
						if (isset($this->state['votes'][$m[1]]['whoid']) and ($this->state['votes'][$m[1]]['whoid'] == $uid)) {
							$t = explode(' ',$message,3);
							$this->state['votes'][$m[1]]['reason'] = $t[2];
							$this->savestate();
							$ircd->notice('ClueNet',$from,'Success.');
							$this->councilchat($from.' ('.$uid.') set or changed the reason for vote id "'.$m[1].'" to "'.$t[2].'"');
						} else {
							$ircd->notice('ClueNet',$from,'Only the proposer may add a reason.');
						}
					} else {
						$ircd->notice('ClueNet',$from,'Invalid Vote ID.');
					}
				} else {
					$ircd->notice('ClueNet',$from,'Insufficient access.');
				}
			} else if (strtolower($m[0]) == 'members') {
				if (isset($this->settings['users'][$uid])) {
					foreach ($this->settings['users'] as $cmuid => $null) {
						$username = $mysql->get($mysql->sql('SELECT `user` FROM `access` WHERE `id` = '.$mysql->escape($cmuid)));
						$username = $username['user'];
						$nicks = array();
						$res = $mysql->sql('SELECT `nick` FROM `users` WHERE `loggedin` = '.$mysql->escape($cmuid));
						while ($x = $mysql->get($res)) $nicks[] = $x['nick'];
						$nicks = implode(' ',$nicks);
						if ($nicks == '') $nicks = 'Not logged in.';
						$ircd->notice('ClueNet',$from,$username.' ('.$cmuid.') - Logged in from: '.$nicks);
					}
				} else {
					$ircd->notice('ClueNet',$from,'Insufficient access.');
				}
			} else if (strtolower($m[0]) == 'approvedbots') {
				if (isset($this->settings['users'][$uid])) {
					foreach ($this->settings['bots'] as $cmuid => $info) {
						$username = $mysql->get($mysql->sql('SELECT `user` FROM `access` WHERE `id` = '.$mysql->escape($cmuid)));
						$username = $username['user'];
						$nicks = array();
						$res = $mysql->sql('SELECT `nick` FROM `users` WHERE `loggedin` = '.$mysql->escape($cmuid));
						while ($x = $mysql->get($res)) $nicks[] = $x['nick'];
						$nicks = implode(' ',$nicks);
						if ($nicks == '') $nicks = 'Not logged in.';
						$ircd->notice('ClueNet',$from,$username.' ('.$cmuid.') - Owner: '.$info['owner'].' - Features: '.$info['features'].' - Logged in from: '.$nicks);
					}
				} else {
					$ircd->notice('ClueNet',$from,'Insufficient access.');
				}
			} else if (strtolower($m[0]) == 'extend') {
				if (isset($this->settings['users'][$uid])) {
					if ($this->isvalidvote($m[1])) {
						if ($m[2] > 0) {
							if ($m[2] <= 7*86400) {
								$m[2] = floor($m[2]);
								if ((isset($this->state['votes'][$m[1]]['extended'])) and ($this->state['votes'][$m[1]]['extended'] + $m[2] >= 7*86400)) {
									$ircd->notice('ClueNet',$from,'Can not extend that far.  Maximum allowed to extend is:  '.(7*86400 - $this->state['votes'][$m[1]]['extended']).'.');
								} else {
									$this->state['votes'][$m[1]]['extended'] += $m[2];
									$this->state['votes'][$m[1]]['endtime'] += $m[2];
									$this->savestate();
									$this->councilchat($from.' ('.$uid.') extended vote id "'.$m[1].'" by '.$m[2].' seconds.');
								}
							} else {
								$ircd->notice('ClueNet',$from,'Extension time must be less than 1 week.');
							}
						} else {
							$ircd->notice('ClueNet',$from,'Extension time must be greater than 0.');
						}
					} else {
						$ircd->notice('ClueNet',$from,'Vote id "'.$m[1].'" is not valid.');
					}
				} else {
					$ircd->notice('ClueNet',$from,'Insufficient access.');
				}				
			} else if (strtolower($m[0]) == 'help') {
				if (isset($this->settings['users'][$uid])) {
					$ircd->notice('ClueNet',$from,'ClueNet Council Bot Help:');
					$ircd->notice('ClueNet',$from,'COMMANDS                        - Shows all the votes that can be proposed.');
					$ircd->notice('ClueNet',$from,'VOTE         <Id> <yes|no|null> - Votes on a vote, yes, no, or removes your vote.');
					$ircd->notice('ClueNet',$from,'REASON       <Id> <Reason>      - Sets a reason for a proposed vote.');
					$ircd->notice('ClueNet',$from,'EXTEND       <Id> <Seconds>     - Extends a vote by a number of seconds.');
					$ircd->notice('ClueNet',$from,'VOTES                           - Shows all currently running votes.');
					$ircd->notice('ClueNet',$from,'NEWVOTES                        - Shows all currently running votes you have not voted on.');
					$ircd->notice('ClueNet',$from,'APPROVEDBOTS                    - List all approved bots.');
					$ircd->notice('ClueNet',$from,'MEMBERS                         - List all of the current users sitting on the ClueCouncil.');
					$ircd->notice('ClueNet',$from,'JOINME                          - Makes you join the council channel.');
					$ircd->notice('ClueNet',$from,'JOINSERVICES                    - Makes you join the services channel.');
					$ircd->notice('ClueNet',$from,'IAMABOT                         - Identifies you as a bot.');
					$ircd->notice('ClueNet',$from,'Any command found in COMMANDS may be sent with the proper parameters.');
//					$ircd->notice('ClueNet',$from,'The commands listed in COMMANDS are case sensitive.');
				} else {
					$ircd->notice('ClueNet',$from,'This is the ClueNet Council bot.  You are not on the council or are not identified.');
				}
			} else if (strtolower($m[0]) == 'joinme') {
				if (isset($this->settings['users'][$uid])) {
					$ircd->invite('ClueNet','#cluecouncil',$from);
					$ircd->sajoin('ClueNet',$from,'#cluecouncil');
				} else {
					$ircd->notice('ClueNet',$from,'Insufficient access.');
				}
			} else if (strtolower($m[0]) == 'joinservices') {
				if (isset($this->settings['users'][$uid])) {
					$ircd->sajoin('ClueNet',$from,'#services');
				} else {
					$ircd->notice('ClueNet',$from,'Insufficient access.');
				}
			} else if (strtolower($m[0]) == 'iamabot') {
				$this->state['bots'][$from] = 1;
				$this->savestate();
				$ircd->notice('ClueNet',$from,'Noted.  Thanks.');
				$this->checkbotvio();
			}

/*

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


			event_signon		($from,$user,$host,$real,$ip,$server)
			event_nick		($from,$to)
			event_quit		($nick,$message)
			event_join		($nick,$channel)
			event_part		($nick,$channel,$reason)
			event_kill		($from,$nick,$message)
			event_ctcp		($from,$to,$type,$msg)
			event_msg		($from,$to,$message)
			event_ctcpreply		($from,$to,$ctcp,$message = NULL)
			event_notice		($from,$to,$message)
			event_eos		()
			event_kick		($from,$nick,$channel,$reason)


			$mysql->getaccess($nick)
			$mysql->getsetting('server')

*/
			

		}

		function event_identify ($nick) {
			global $mysql;
			$ircd = &ircd();

			$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$uid = $nickd['loggedin'];

			if (isset($this->settings['users'][$uid])) {
				$ircd->sajoin('ClueNet',$nick,'#cluecouncil');
			}
		}

		function event_join ($nick,$channel) {
			global $mysql;
			$ircd = &ircd();

			if ($nick == 'ClueNet') return;
			if ($nick == 'DaVinci') return;
			if (strtolower($channel) == '#cluecouncil') {
				$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
				$uid = $nickd['loggedin'];
				if (!isset($this->settings['users'][$uid])) {
					$ircd->kick('ClueNet',$channel,$nick,'You are not on the ClueCouncil or are not logged in!');
				}
			} elseif (strtolower($channel) == '#clueirc') {
				$this->checkbotvio();
			}
		}

		function event_eos () {
			global $mysql;
			$ircd = &ircd();

			$ircd->addnick($mysql->getsetting('server'),'ClueNet','ClueNet','ClueNet.Org','The ClueNet bot.');
			$ircd->mode('ClueNet','ClueNet','+SBoOHaANq');
			$ircd->svso('ClueNet','+rDRhgwlcLkKbBnGAaNztZvqdX');
			$ircd->join('ClueNet','#clueirc');
			$ircd->join('ClueNet','#cluecouncil');
			$ircd->mode('ClueNet','#cluecouncil','+iOAo ClueNet');
			$ircd->topic('ClueNet','#cluecouncil','/msg ClueNet help for help. "CTAs are appointed by decision of the Co-Founders and have control over ClueNet as a whole. The council is self-elected by popular vote, and control, by popular vote, IRC, and can be overridden or dissolved by the CTAs. --Cobi."');
			$this->councilchat('All ClueCouncil members MUST be in #cluecouncil.  Use /msg ClueNet joinme to get in.  All ClueCouncil members also must be logged into PHPServ.  /msg ClueNet help for help.');
			if (!isset($this->settings['nspass'])) {
				$this->settings['nspass'] = md5(rand(1,1000000000000000000000));
				$this->timer('nsregister',1,120,array('$ircd','msg'),array('ClueNet','NickServ','REGISTER '.$this->settings['nspass'].' Cobi@cluenet.org'));
				$this->timer('nsidentify',1,125,array('$ircd','msg'),array('ClueNet','NickServ','IDENTIFY '.$this->settings['nspass']));
				$this->timer('ossuperon',1,130,array('$ircd','msg'),array('ClueNet','OperServ','SET SUPERADMIN ON'));
				$this->saveconfig();
			} else {
				$ircd->msg('ClueNet','NickServ','IDENTIFY '.$this->settings['nspass']);
				$ircd->msg('ClueNet','OperServ','SET SUPERADMIN ON');
			}

			$res = $mysql->sql('SELECT `nick`,`loggedin` FROM `user_chan`,`channels`,`users` WHERE `users`.`userid` = `user_chan`.`userid` AND `channels`.`chanid` = `user_chan`.`chanid` AND `channels`.`name` = '.$mysql->escape('#cluecouncil'));
			while ($x = $mysql->get($res)) {
				if (($x['nick'] != 'ClueNet') and ($x['nick'] != 'DaVinci')) {
					if (!isset($this->settings['users'][$x['loggedin']])) {
						$ircd->kick('ClueNet','#cluecouncil',$x['nick'],'You are not on the ClueCouncil or are not logged in!');
					}
				}
			}
			$this->checkbotvio();
		}

		function checkbotvio() {
			global $mysql;
			$ircd = &ircd();
			$res = $mysql->sql('SELECT `nick`,`loggedin` FROM `user_chan`,`channels`,`users` WHERE `users`.`userid` = `user_chan`.`userid` AND `channels`.`chanid` = `user_chan`.`chanid` AND `channels`.`name` = '.$mysql->escape('#clueirc'));
			while ($x = $mysql->get($res)) {
				if (($x['nick'] != 'ClueNet') and ($x['nick'] != 'CobiBot')) {
					if (isset($this->state['bots'][$x['nick']]) or fnmatch('*bot',strtolower($x['nick']))) {
						if (!isset($this->settings['bots'][$x['loggedin']])) {
							$ircd->kick('ClueNet','#clueirc',$x['nick'],'You have been identified as a bot, but are not on the approved bot list or are not logged in!');
						}
					}
				}
			}
		}
	}

	function registerm () {
		$class = new cluenet;
		register($class, __FILE__, 'ClueNet Module', 'cluenet');
	}
?>
