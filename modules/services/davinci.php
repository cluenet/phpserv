<?PHP
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

	class davinci {
		private $config;
		private $locked = 0;
		private $lastsave = 0;
		private $users;
		private $lineaverage;

		
		function getpts ($source) {
			return $this->users[$source]['points'];
		}
		
		function mysort ($a,$b) {
			if (!isset($a))
				$a = 0;
			if (!isset($b))
				$b = 0;
			if ($a == $b)
				return 0;
			return ($a < $b) ? -1 : 1;
		}
		
		function loguser ($source,$reason) {
			$ircd = &ircd();
			if (isset($this->users[$source]) and isset($this->users[$source]['vlog']) and ($this->users[$source]['vlog'] == true))
				$ircd->notice('DaVinci',str_replace('*deauth','',$source),$reason);
			if(!isset($this->users))
				$this->users = array();
			if(!isset($this->users[$source]))
				$this->users[$source] = array();
			if(!isset($this->users[$source]['log']))
				$this->users[$source]['log'] = array();
			if(!isset($this->users[$source]['log'][$reason]))
				$this->users[$source]['log'][$reason] = 0;
			$this->users[$source]['log'][$reason]++;
		}
		
		function getstats ($source) {
			$ret = array();
			$tmp = '';
			foreach ($this->users[$source]['log'] as $reason => $count) {
				if (strlen( $tmp .  $reason.': '.$count.'.  ' ) > 400) {
					$ret[] = $tmp;
					$tmp = '';
				}
				$tmp .= $reason.': '.$count.'.  ';
			}
			$ret[] = $tmp;
			return $ret;
		}
		
		function gettop ($bottom = false) {
			foreach ($this->users as $nick => $data)
				$tmp[$nick] = (isset($data['points']) ? $data['points'] : 0);
			
			uasort($tmp,array($this,'mysort'));
			
			if ($bottom == false)
				$tmp = array_reverse($tmp,true);
			
			$i = 0;
			foreach ($tmp as $nick => $pts) {
				$i++;
				if ($i > 7) {
					$tmp2[substr($nick,0,round(strlen($nick)/2)).'[...]'] = substr($pts,0,round(strlen($pts)/2)).'[...]';
					break;
				}
				$tmp2[$nick] = $pts;
			}
			if ($bottom == true)
				$tmp2 = array_reverse($tmp2,true);
			
			return $tmp2;
		}
		
		function isadmin ($source) {
			if (isset($this->users[$source]['admin']))
				return $this->users[$source]['admin'];
			return false;
		}
		
		function nickassoc ($source) {
			if (isset($this->users[$source]['linked']))
				return $this->users[$source]['linked'];
			return -1;
		}
		
		function setignore ($target,$status = true) {
			$this->users[$target]['ignore'] = $status;
			$this->users[$target]['points'] = 0;
			
			unset($this->users[$target]['log']);
			
			if ($status == true)
				$this->loguser($target,'Ignored =0');
			else
				$this->loguser($target,'Unignored =0');
		}
		
		function chgpts ($source,$delta) {
			$ircd = &ircd();

			if (isset($this->users[$source]['ignore']) and $this->users[$source]['ignore'] == true)
				return;
			
			if (isset($this->users[$source]) and isset($this->users[$source]['verbose']) and ($this->users[$source]['verbose'] == true)) {
				if ($delta > 0)
					$what = 'gained';
				else
					$what = 'lost';
				
				if ((!isset($this->users[$source]['vdedo']) or $this->users[$source]['vdedo'] == false) or ($delta < 0))
					$ircd->notice('DaVinci',str_replace('*deauth','',$source),'You have '.$what.' '.abs($delta).' points.');
			}
			
			if ((!isset($this->users[$source]['creation'])) or ($this->users[$source]['creation'] == 0))
				$this->users[$source]['creation'] = time();
			
			$origpts = $this->users[$source]['points'];
			$this->users[$source]['points'] += $delta;
			$newpts = $this->users[$source]['points'];
			
			$done = false;
			
			if (($newpts <= -50) and ($delta < 0)) {
				for ($i = $newpts; $i <= $origpts; $i++) {
					if (($i % 100) == 0)
						foreach ($this->config['chans'] as $chan)
							$ircd->mode('DaVinci',$chan,' +bb ~q:'.str_replace('*deauth','',$source).'!*@* ~n:'.str_replace('*deauth','',$source).'!*@*');
					
					if ((($i % 25) == 0) and ($done == false)) {
						foreach ($this->config['chans'] as $chan) {
							if ($newpts > -150)
								$ircd->msg('DaVinci',$chan,str_replace('*deauth','',$source).': Here we encourage people to speak correctly to enhance intellectual exchange.  Please take the time to read: http://wiki.cluenet.org/Clueful_Chatting');
							elseif ($newpts > -1500)
								$ircd->msg('DaVinci',$chan,str_replace('*deauth','',$source).': Your style of chatting demonstrates: low levels of intelligence, laziness, and general non-cluefulness.  Please read this article about text-based chatting: http://wiki.cluenet.org/Clueful_Chatting');
							else
								$ircd->msg('DaVinci',$chan,str_replace('*deauth','',$source).': Your style of chatting seems quite unreadable. Please speak in English and please try to follow the rules in this article: http://wiki.cluenet.org/Clueful_Chatting');
							$done = true;
						}
					}
				}
			}
			
			if ($this->users[$source]['points'] <= -2000)
				foreach ($this->config['chans'] as $chan) {
					$ircd->kick('DaVinci',$chan,str_replace('*deauth','',$source),str_replace('*deauth','',$source).' is not clueful!');
					$this->loguser($source,'User Kicked =0');
					$this->users[$source]['points'] = 0;
				}

			$this->save_db();
		}

		function get_db () {
			$ret = unserialize(file_get_contents('cb_users.db'));
			return $ret;
		}
		
		function save_db () {
			file_put_contents('cb_users.db',serialize($this->users));
			if (!isset($this->lastsave))
				$this->lastsave = 0;
			if ($this->lastsave > time() - 300)
				return;
			if ($this->locked)
				return;
			file_put_contents('cb_users.db.'.time(),serialize($this->users));
			$this->lastsave = time();
		}
		
		function getlineaverage () {
			return file_get_contents('cb_la.dat');
		}
		
		function savelineaverage () {
			file_put_contents('cb_la.dat',$this->lineaverage);
		}
		
		function construct() {
			$this->config = unserialize(file_get_contents('DaVinci.set'));
			if(!is_array($this->config)) {
				$this->config = array(
					'chans' => array( '#clueirc' ),
					'trigger' => '.'
				);
				file_put_contents('DaVinci.set',serialize($this->config));
			}
			$this->lineaverage = $this->getlineaverage();
			$this->users = $this->get_db();
			$this->event_eos();
		}

		function destruct() {
			$ircd = &ircd();
			$ircd->quit('DaVinci', 'Module Unloaded.');
			$this->save_db();
			$this->savelineaverage();
			file_put_contents('DaVinci.set',serialize($this->config));
		}

		function event_ctcp ($from,$to,$type,$message) {
			$ircd = &ircd();

			if (strtolower($to) == 'davinci')
				switch (strtolower($type)) {
					case 'version':
						$ircd->ctcpreply($to,$from,strtoupper($type),'ClueBot ("DaVinci") v2.0');
						break;
				}
		}

		function event_invite ($from,$to,$chan) {
			$ircd = &ircd();
			
			if ($to == 'DaVinci') {
				$ircd->join('DaVinci',$chan);
				$ircd->mode('DaVinci',$chan,'+ao DaVinci DaVinci');
				$this->config['chans'][strtolower($chan)] = $chan;
			}
		}

		function event_kick ($from,$nick,$channel,$reason) {
			$ircd = &ircd();
			
			if ($nick == 'DaVinci') {
				$ircd->msg('DaVinci',$channel,':(');
				unset($this->config['chans'][strtolower($channel)]);
			}
		}

		function event_msg ($from,$to,$message) {
			global $mysql;
			$ircd = &ircd();
			
			$source = strtolower($from);

			if (substr($to,0,1) == '#') { // Message to a channel
				if (in_array($to,$this->config['chans'])) {
					$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
					$uid = $nickd['loggedin'];
					$sourcet = $source;
					if (!(($this->nickassoc($source) == -1) or ($this->nickassoc($source) == $uid)))
						$source .= '*deauth';
					if (substr($message,0,1) == $this->config['trigger']) {
						$tmp0 = explode(' ',$message);
						$cmd = strtolower(substr($tmp0[0],1));
						$bottom = false;
						$ignore = true;
						$spanish = false;

						switch ($cmd) {
							case 'verbose':
								if ($this->users[$source]['verbose'] == true) {
									$this->users[$source]['verbose'] = false;
									$this->users[$source]['vdedo'] = false;
									$ircd->notice('DaVinci',$sourcet,'No longer noticing you changes in points.');
								} else {
									$this->users[$source]['verbose'] = true;
									$ircd->notice('DaVinci',$sourcet,'Now noticing you changes in points.');
								}
								break;
							case 'vdeductions':
								if ($this->users[$source]['verbose'] == true) {
									if ($this->users[$source]['vdedo'] == true) {
										$this->users[$source]['vdedo'] = false;
										$ircd->notice('DaVinci',$sourcet,'Now noticing you all changes in points.');
									} else {
										$this->users[$source]['vdedo'] = true;
										$ircd->notice('DaVinci',$sourcet,'Now noticing you only negative changes in points.');
									}
								} else
									$ircd->notice('DaVinci',$source,'Verbose must be on before this option is available.');
								break;
							case 'vlog':
								if ($this->users[$source]['vlog'] == true) {
									$this->users[$source]['vlog'] = false;
									$ircd->notice('DaVinci',$sourcet,'No longer noticing you log entries relating to you.');
								} else {
									$this->users[$source]['vlog'] = true;
									$ircd->notice('DaVinci',$sourcet,'Now noticing you log entries relating to you.');
								}
								break;
							case 'puntos':
								$spanish = true;
							case 'points':
								if ($tmp0[1]) {
									$who = $tmp0[1];
								} else {
									$who = $source;
								}
								$ircd->notice('DaVinci',$sourcet,(($spanish) ? 'El ' : '').$who.(($spanish) ? ' tiene ' : ' has ').$this->getpts(strtolower($who)).(($spanish) ? ' puntos.' : ' points.'));
								unset($spanish);
								break;
							case 'bottom':
							case 'lamers':
								$bottom = true;
							case 'top':
								$top = $this->gettop($bottom);
								foreach ($top as $nick => $pts)
									$ircd->notice('DaVinci',$sourcet,$nick.' has '.$pts.' points.');
								break;
							case 'stats':
								if ($tmp0[1]) {
									$who = $tmp0[1];
								} else {
									$who = $source;
								}
								$ircd->notice('DaVinci',$sourcet,$who.'\'s stats:');
								foreach($this->getstats(strtolower($who)) as $statsline)
									$ircd->notice('DaVinci',$sourcet,$statsline);
								break;
							case 'unignore':
								$ignore = false;
							case 'ignore':
								if ($this->isadmin(strtolower($source))) {
									$this->setignore(strtolower($tmp0[1]),$ignore);
									if ($ignore == false)
										$ignore = ' not';
									else
										$ignore = '';
									$ircd->notice('DaVinci',$sourcet,$tmp0[1].' is now'.$ignore.' being ignored.');
								} else
									$ircd->notice('DaVinci',$sourcet,'You must be an administrator to use this feature!');
								break;
							case 'lock':
								if ($this->isadmin(strtolower($source))) {
									if ($this->locked == true) {
										$this->locked = false;
										$ircd->notice('DaVinci',$sourcet,'The database is now in read-write mode.');
									} else {
										$this->locked = true;
										$ircd->notice('DaVinci',$sourcet,'The database is now in read-only mode.');
									}
								} else
									$ircd->notice('DaVinci',$sourcet,'Only administrators may lock the database.');
								break;
							case 'reload':
								if ($this->isadmin(strtolower($source))) {
									$this->users = $this->get_db();
									$ircd->notice('DaVinci',$sourcet,'Internal database reloaded according to the MySQL database.');
								} else {
									$ircd->notice('DaVinci',$sourcet,'Only administrators may reload the database.');
								}
								break;
							case 'chgpts':
								if ($this->isadmin(strtolower($source))) {
									$this->loguser(strtolower($tmp0[1]),'Administratively changed');
									$this->chgpts(strtolower($tmp0[1]),$tmp0[2]);
									$ircd->notice('DaVinci',$sourcet,'Points changed.');
								} else {
									$ircd->notice('DaVinci',$source,'Only administrators may manipulate users\' points.');
								}
								break;
							case 'reset':
								if ($this->isadmin(strtolower($source))) {
									unset($this->users[strtolower($tmp0[1])]);
									$ircd->notice('DaVinci',$sourcet,'User reset.');
								} else {
									unset($this->users[strtolower($source)]);
									$ircd->kick('DaVinci',$to,$sourcet,'User reset.');
								}
								break;
							case 'merge':
								if ($this->isadmin(strtolower($source))) {
									$u1 = strtolower($tmp0[1]);
									$u2 = strtolower($tmp0[2]);
									if (!isset($this->users[$u1]) or !isset($this->users[$u2])) {
										$ircd->notice('DaVinci',$sourcet,'Error, both users do not exist.  Please provide two users, the second will be merged into the first, and then the second will be reset.');
									} else {
										foreach($this->users[$u2]['log'] as $key => $value)
											for($i = 0; $i < $value; $i++)
												$this->loguser($u1,$key);
										$this->chgpts($u1,$this->getpts($u2));
										unset($this->users[$u2]);
										$this->loguser($u1,'Merged with ' . $u2);
										$ircd->notice('DaVinci',$sourcet,'Users merged.');
									}
								} else {
									 $ircd->notice('DaVinci',$source,'Only administrators may manipulate users\' points.');
								}
								break;
							case 'lineaverage':
								$ircd->notice('DaVinci',$sourcet,'The line average is: '.$this->lineaverage);
								break;
							case 'whoami':
							case 'quiénsoyyo':
							case 'quienessonyo':
								$tmp0[1] = $source;
							case 'quienes':
								if (substr($cmd,0,7) == 'quienes')
									$spanish = true;
							case 'whois':
								$who = $tmp0[1];
								$pts = $this->getpts(strtolower($who));
								if ($pts < -1500)
									$rating = ($spanish?'Más cojo':'Lamer');
								elseif ($pts < -1000)
									$rating = 'No'.($spanish?'':'t').' clueful';
								elseif ($pts < -500)
									$rating = ($spanish?'Necesita muchos de trabajo':'Needs a lot of work');
								elseif ($pts < -10)
									$rating = ($spanish?'Necesita el trabajo':'Needs work');
								elseif ($pts < 25)
									$rating = 'Neutral';
								elseif ($pts < 50)
									$rating = 'Clueful';
								elseif ($pts < 500)
									$rating = ($spanish?'Muy':'Very').' Clueful';
								elseif ($pts < 1000)
									$rating = ($spanish?'Extremadamente':'Extremely').' Clueful';
								elseif ($pts < 5000)
									$rating = ($spanish?'Clueful estupendo':'Super Clueful');
								elseif ($pts < 50000)
									$rating = ($spanish?'Élite de clueful':'Clueful Elite');
								elseif ($pts < 100000)
									$rating = ($spanish?'La realeza clueful':'Clueful Royalty');
								elseif ($pts >= 100000)
									$rating = ($spanish?'El dios clueful':'Clueful God');
								
								$ircd->notice('DaVinci',$sourcet,($spanish?'El ':'').$who.($spanish?' lleva a cabo a fila de: ':' holds the rank of: ').$rating.'.');
								$ircd->notice('DaVinci',$sourcet,($spanish?'El ':'').$who.($spanish?' tiene ':' has ').$pts.($spanish?' puntos.':' points.'));
								foreach($this->getstats(strtolower($who)) as $statsline)
									$ircd->notice('DaVinci',$sourcet,($spanish?'Expediente de pista Del '.$who.': ':$who.'\'s track record: ').$statsline);
									
								if ($this->isadmin(strtolower($who)))
									$ircd->notice('DaVinci',$sourcet,($spanish?'El ':'').$who.($spanish?' es administrador.':' is an administrator.'));
								if (isset($this->users[strtolower($who)]['ignore']) and $this->users[strtolower($who)]['ignore'])
									$ircd->notice('DaVinci',$sourcet,($spanish?'Se no hace caso '.$who.'.':$who.' is ignored.'));
								
								unset($spanish);
								break;
						}
					} else {
						$tmppts = 0;
						$smilies = '((>|\})?(:|;|8)(-|\')?(\)|[Dd]|[Pp]|\(|[Oo]|[Xx]|\\|\/)';
						$smilies.= '|(\)|[Dd]|[Pp]|\(|[Oo]|[Xx]|\\|\/)(-|\')?(:|;|8)(>|\})?)';
						if ((!preg_match('/^'.$smilies.'$/i',$message))
							and (!preg_match('/^(uh+|um+|uhm+|er+|ok|ah+|er+m+)(\.+)?$/i',$message))
							and (!preg_match('/^[^A-Za-z].*$/',$message))
							and (!preg_match('/^s(.).+\1.+\1i?g?$/',$message))
							and (!preg_match('/(brb|bbl|lol|rofl|heh|wt[hf]|hah|lmao|bbiab|grr|hmm|hrm|https?:|grep|\||vtun|ifconfig|\$|mm|gtg|wb)/i',$message))
						) {
							if (preg_match('/^([^ ]+(:|,| -) .|[^a-z]).*(\?|\.(`|\'|")?|!|:|'.$smilies.')( '.$smilies.')?$/',$message)) {
								$this->loguser($source,'Clueful sentence +2');
								$tmppts+=2;
							} elseif (preg_match('/^([^ ]+(:|,| -) .|[^a-z]).*$/',$message)) {
								$this->loguser($source,'Normal sentence +1');
								$tmppts++;
							} else {
								$this->loguser($source,'Abnormal sentence -1');
								$tmppts--;
							}
							if (preg_match('/[^\x0a\x0d\x20-\x7e\x99\x9c\xa9\xb8]/',$message)) {
								$this->loguser($source,'Use of non-printable ascii characters -5');
								$tmppts -= 5;
							}
							if (preg_match('/(^| )i( |$)/',$message)) {
								$this->loguser($source,'Lower-case personal pronoun -5');
								$tmppts -= 5;
							}
							if (preg_match('/^[^a-z]{8,}$/',$message)) {
								$this->loguser($source,'All caps -20');
								$tmppts -= 20;
							}
							if (preg_match('/\<censored\>/',$message)) {
								$this->loguser($source,'Use of profanity -20');
								$tmppts -= 20;
							}
							if (preg_match('/(^| )lawl( |$)/',$message)) {
								$this->loguser($source,'Use of non-clueful variation of "lol" -20');
								$tmppts -= 20;
							}
							if (preg_match('/(^| )rawr( |$)/',$message)) {
								$this->loguser($source,'Use of non-clueful expression -20');
								$tmppts -= 20;
							}
							if (preg_match('/^[^aeiouy]{5,}$/i',$message)) {
								$this->loguser($source,'No vowels -30');
								$tmppts -= 30;
							}
							if (preg_match('/(^| )[rRuU]( |$)/',$message)) {
								$this->loguser($source,'Use of r, R, u, or U -40');
								$tmppts -= 40;
							}
							$tmppts = ceil(((strlen($message) < $this->lineaverage) ? 1 : (strlen($message)/$this->lineaverage)) * $tmppts);
							$this->lineaverage = $this->lineaverage + ((strlen($message) - $this->lineaverage) / 5000);
							$this->savelineaverage();
							$this->chgpts($source,$tmppts);
						}
					}
				}
			} else if (strtolower($to) == 'davinci') {
				$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
				$uid = $nickd['loggedin'];
				
				if ($uid != -1) {
					if (strtolower($message) == 'link') {
						if ($this->nickassoc($source) == -1) {
							$this->users[$source]['linked'] = $uid;
							$ircd->notice('DaVinci',$source,'Your DaVinci-tracked nick is now linked to your PHPServ account.');
						} else
							$ircd->notice('DaVinci',$source,'Your DaVinci-tracked nick was already linked to a PHPServ account.');
					} else if (strtolower($message) == 'unlink') {
						if ($this->nickassoc($source) == $uid) {
							$this->users[$source]['linked'] = -1;
							$ircd->notice('DaVinci',$source,'Your DaVinci-tracked nick is no longer linked to your PHPServ account.');
						} else
							$ircd->notice('DaVinci',$source,'Your DaVinci-tracked nick was not linked to this PHPServ account.');
					}
				} else
					$ircd->notice('DaVinci',$source,'You are not logged into PHPServ.');
			}
		}

		function event_eos () {
			global $mysql;
			$ircd = &ircd();

			$ircd->addnick($mysql->getsetting('server'),'DaVinci','ClueBot','DaVinci.ClueNet.Org','The Clueful Bot.');
			$ircd->mode('DaVinci','DaVinci','+S');
			foreach ($this->config['chans'] as $chan) {
				$ircd->join('DaVinci',$chan);
				$ircd->mode('DaVinci',$chan,'ao DaVinci DaVinci');
			}
		}
	}

	function registerm () {
		$class = new davinci;
		register($class, __FILE__, 'DaVinci Module', 'davinci');
	}
?>
