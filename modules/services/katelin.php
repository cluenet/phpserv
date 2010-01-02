<?PHP

	class katelin {
		private $set;
		private $state;

		function construct() {
			$this->set = unserialize(file_get_contents('Katelin.set'));
			$this->loadstate();
			$this->event_eos();
		}

		function destruct() {
			$ircd = &ircd();
			$ircd->quit('Katelin', 'Module Unloaded.');
			$ircd->quit('K', 'Module Unloaded.');
			$this->savestate();
		}

		function loadstate() {
			$this->state = unserialize(file_get_contents('Katelin.state'));
		}

		function savestate() {
			file_put_contents('Katelin.state',serialize($this->state));
		}

		function warnkickban($chan,$nick,$reason) {
			global $mysql;
			$ircd = &ircd();

			if (!isset($this->state['warnings']))
				$this->state['warnings'] = array();
			foreach ($this->state['warnings'] as &$data)
				if (($data['time'] < time() - 86400) and ($data['level'] > -1)) {
					$data['time'] = time();
					$data['level'] -= floor((time() - $data['time']) / 86400);
					if ($data['level'] < -1)
						$data['level'] = -1;
				}
			if (!isset($this->state['warnings'][$nick]))
				$this->state['warnings'][$nick] = array('time' => time(), 'level' => -1);
			$this->state['warnings'][$nick]['level']++;
			$this->state['warnings'][$nick]['time'] = time();

			if ($this->state['warnings'][$nick]['level'] == 0)
				$ircd->msg('Katelin',$chan,$nick.': '.$reason);
			else if ($this->state['warnings'][$nick]['level'] == 1)
				$ircd->kick('Katelin',$chan,$nick,$reason);
			else if ($this->state['warnings'][$nick]['level'] > 1) {
				$data = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
				$ircd->kick('Katelin',$chan,$nick,$reason);
				$ircd->mode('Katelin',$chan,'+bb '.$nick.'!*@* ~r:'.str_replace(' ','_',$data['realname']));
			}

			$this->savestate();
		}
		
		function bashsplit($input,$split = ' ') {
			$ret = array();
			$len = strlen($input);
			$buf = '';
			$dq = $sq = $bs = false; // double quote, single quote, backslash
			$d = 0; // brace depth
			for ($i = 0; $i < $len; $i++) {
				if (($sq and ($input{$i} != "'")) or ($dq and ($input{$i} != '"')) or $bs) {
					$buf .= $input{$i};
					if ($bs)
						$bs = false;
					continue;
				}
				if ($d > 0) {
					switch ($input{$i}) {
						case '{': $d++; break;
						case '}': $d--; break;
					}
					$buf .= $input{$i};
					continue;
				}
				switch ($input{$i}) {
					case '\\': $bs = true; break;
					case '"': $dq = !$dq; break;
					case "'": $sq = !$sq; break;
					case '{': $buf .= $input{$i}; $d++; break;
					case $split:
						$ret[] = $buf;
						$buf = '';
						break;
					default:
						$buf .= $input{$i};
				}
			}
			$ret[] = $buf;
			return $ret;
		}
		
		function findbraces($input) {
			$len = strlen($input);
			$buf = '';
			$dq = $sq = $bs = false; // double quote, single quote, backslash
			$d = 0; // brace depth
			$ret = array();
			$done = false;
			for ($i = 0; ($i < $len) and ($input{$i} != '{'); $i++)
				$buf .= $input{$i};
				$ret[] = $buf;
				$buf = '';
				$i++;
				for (; $i < $len; $i++) {
					if (($sq and ($input{$i} != "'")) or ($dq and ($input{$i} != '"')) or $bs) {
						$buf .= $input{$i};
						if ($bs)
							$bs = false;
						continue;
					}
					if ($d > 0) {
						switch ($input{$i}) {
							case '{': $d++; break;
							case '}': $d--; break;
						}
						$buf .= $input{$i};
						continue;
					}
					switch ($input{$i}) {
						case '\\': $bs = true; break;
						case '"': $dq = !$dq; break;
						case "'": $sq = !$sq; break;
						case '{': $buf .= '{'; $d++; break;
						case '}': break;
						default: $buf .= $input{$i};
						}
						if ($input{$i} == '}')
							break;
				}
				$ret[] = $buf;
				$buf = '';
				$i++;
				for (; $i < $len; $i++)
					$buf .= $input{$i};
				$ret[] = $buf;
				return $ret;
		}
		
		function bashexpand($input) {
			static $level = -1;
			$modified = false;
			$ret = array();
			$level++;
			
			if (!is_array($input))
				return $this->bashexpand($this->bashsplit($input));
			
			foreach ($input as $token) {
				$data = $this->findbraces($token);
				$split = $this->bashsplit($data[1],',');
				foreach ($split as $part)
					$ret[] = $data[0].$part.$data[2];
				if (!((strlen($data[1]) == 0) and (strlen($data[2]) == 0)))
					$modified = true;
			}
			
			if ($modified)
				return $this->bashexpand($ret);
			return $ret;
		}
		
		function formatbashoutput($input) {
			$x = $this->bashexpand($input);
			$d = array();
			foreach ($x as $y)
				$d[] .= var_export($y,true);
			$d = 'char **parsed_line = (char **){'.implode(', ',$d).'};'."\n";
			return $d;
		}

		function event_ctcp ($from,$to,$type,$message) {
			$ircd = &ircd();

			if ((strtolower($to) == 'katelin') or (strtolower($to) == 'k')) {
				switch (strtolower($type)) {
					case 'version':
						$ircd->ctcpreply($to,$from,strtoupper($type),'Katelin, version 2.0.0a1c1.');
						break;
				}
			}
		}

		function event_invite ($from,$to,$chan) {
			$ircd = &ircd();
			
			if ($to == 'Katelin') {
				$ircd->join('Katelin',$chan);
				if (!in_array($to,$this->set['chans'])) {
					$this->set['chans'][] = $chan;
				}
			}
		}

		function event_msg ($from,$to,$message) {
			global $mysql;
			$ircd = &ircd();

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
			if (substr($to,0,1) == '#') { // Message to a channel
				if (in_array($to,$this->set['chans'])) { // This is one of our channels
					if (substr($message,0,1) == $this->set['trigger']) {
						$cmd = explode(' ',substr($message,1));
						$rest = explode(' ',$message,2);
						if ($mysql->getaccess($from) > 900) {
							switch (strtolower($cmd[0])) {
								case 'global':
									$ircd->notice('K','$*','[Global] '.$rest[1]);
									break;
								case 'kill':
									$ircd->kill($cmd[1],'Remote kill from K (SIGKILL recieved.)');
									break;
								case 'eval':
									if ($mysql->getaccess($from) > 999)
										if (runkit_lint($rest[1]))
											eval($rest[1]);
										else
											$ircd->msg('Katelin',$to,'Error:  Syntax error.');
									else
										$ircd->msg('Katelin',$to,'Error:  Insufficient privileges.');
									break;
							}
						}
						switch (strtolower($cmd[0])) {
							case 'ibeam':
								$ircd->msg('Katelin',$to,chr(1).'ACTION gets out her massive, industrial-strength I-beam out from its place in the corner.'.chr(1));
								$ircd->msg('Katelin',$to,chr(1).'ACTION whacks '.((isset($cmd[1])) ? $cmd[1] : $from).' around a bit with her massive, industrial-strength I-beam.'.chr(1));
								$ircd->msg('Katelin',$to,chr(1).'ACTION puts her massive, industrial-strength I-beam back in its place in the corner.'.chr(1));
								break;
							case 'suicide':
								$ircd->kill($from,$from.' committed suicide! :(');
								break;
							case 'roulette':
								if ((rand(0,5) == 4) and ($from != 'Cobi')) {
									$ircd->svskill($from, '*bang*');
								} else {
									$ircd->msg('Katelin', $to, '*click*');
								}
								break;
							case 'bashexpand':
								$ircd->msg('Katelin',$to,$this->formatbashoutput($rest[1]));
								break;
							case 'bashexpandcondensed':
								$ircd->msg('Katelin',$to,implode(' ',$this->bashexpand($rest[1])));
								break;
							case 'dig':
								$host = $rest[1];

								$answer = dns_get_record($host,DNS_ALL,$authns,$addtl);
								
								$ircd->notice('Katelin',$from,'##########################################');
								$ircd->notice('Katelin',$from,'DNS Report for '.$host.':');
								$ircd->notice('Katelin',$from,'##########################################');
								
								foreach (array('Answer' => $answer,'Authoritative' => $authns,'Additional' => $addtl) as $section => $dns) {
									$ircd->notice('Katelin',$from,'==========================================');
									$ircd->notice('Katelin',$from,$section.' Section:');
									$ircd->notice('Katelin',$from,'==========================================');
									
									$records = array();
									

									foreach ($dns as $record) {
										$data = '';
										switch ($record['type']) {
											case 'A': $data = $record['ip']; break;
											case 'MX': $data = array($record['pri'],$record['target']); break;
											case 'CNAME':
											case 'NS':
											case 'PTR':
												$data = $record['target']; break;
											case 'TXT': $data = $record['txt']; break;
											case 'HINFO': $data = array($record['cpu'],$record['os']); break;
											case 'SOA': $data = array($record['mname'],$record['rname'],$record['serial'],$record['refresh'],$record['retry'],$record['expire'],$record['minimum-ttl']); break;
											case 'AAAA': $data = $record['ipv6']; break;
											case 'A6': $data = array($record['masklen'],$record['ipv6'],$record['chain']); break;
											case 'SRV': $data = array($record['pri'],$record['weight'],$record['target'],$record['port']); break;
											case 'NAPTR': $data = array($record['order'],$record['pref'],$record['flags'],$record['services'],$record['regex'],$record['replacement']); break;
										}
										if (!isset($records[$record['type']]))
											$records[$record['type']] = array();
										$records[$record['type']][] = array($record['host'],$record['ttl'],$data);
									}

									foreach ($records as $type => $recs) {
										$ircd->notice('Katelin',$from,'------------------------------------------');
										$ircd->notice('Katelin',$from,$type.' records:');
										$ircd->notice('Katelin',$from,'------------------------------------------');
										foreach ($recs as $recarr) {
											$host = $recarr[0];
											$rec = $recarr[2];
											$ttl = $recarr[1];
											switch ($type) {
												case 'A':
												case 'CNAME':
												case 'NS':
												case 'PTR':
												case 'TXT':
												case 'AAAA':
													$ircd->notice('Katelin',$from,$host.' -> '.$rec.' (TTL: '.$ttl.')');
													break;
												case 'MX':
													$ircd->notice('Katelin',$from,$host.' -> '.$rec[1].' (Priority: '.$rec[0].')'.' (TTL: '.$ttl.')');
													break;
												case 'HINFO':
													$ircd->notice('Katelin',$from,$host.' -> CPU: '.$rec[0].' - OS: '.$rec[1].' (TTL: '.$ttl.')');
													break;
												case 'SOA':
													$ircd->notice('Katelin',$from,$host.' -> '
														.'My name: '.$rec[0].' - '
														.'E-mail: '.$rec[1].' - '
														.'Serial: '.$rec[2].' - '
														.'Refresh: '.$rec[3].' - '
														.'Retry: '.$rec[4].' - '
														.'Expire: '.$rec[5].' - '
														.'Minimum TTL: '.$rec[6]
														.' (TTL: '.$ttl.')'
														);
													break;
												case 'A6':
													$ircd->notice('Katelin',$from,$host.' -> '
														.'Mask Length: '.$rec[0].' - '
														.'IPv6: '.$rec[1].' - '
														.'Chain: '.$rec[2]
														.' (TTL: '.$ttl.')'
														);
													break;
												case 'SRV':
													$ircd->notice('Katelin',$from,$host.' -> '.$rec[2].':'.$rec[3].' (Priority: '.$rec[0].') (Weight: '.$rec[1].')'.' (TTL: '.$ttl.')');
													break;
												case 'NAPTR':
													$ircd->notice('Katelin',$from,$host.' -> '
														.'Order: '.$rec[0].' - '
														.'Preference: '.$rec[1].' - '
														.'Flags: '.$rec[2].' - '
														.'Services: '.$rec[3].' - '
														.'Regex: '.$rec[4].' - '
														.'Replacement: '.$rec[5]
														.' (TTL: '.$ttl.')'
														);
													break;
											}
										}
									}
								}
								break;
						}
					}
				}
				if (strtolower($to) == '#clueirc') {
					$m = trim(strtolower(str_replace('\\','',$message)));
					switch (strtolower($from)) {
						case 'lordlandon':
						case 'nathan':
						case 'tonyb':
						case 'dvyjones':
							if (
								fnmatch('*b00m*',$m)
								or preg_match('/b[0o]{2,}b/i',$m)
								or fnmatch('*b0rked*',$m)
								or fnmatch('*whatsdat*',$m)
								or fnmatch('*kewl*',$m)
								or fnmatch('*waddabout*',$m)
								or fnmatch('*hardcoah*',$m)
								or fnmatch('*mr0ning*',$m)
								or fnmatch('*h4x0r*',$m)
								or preg_match('/\bhai\b/i',$m)
								or preg_match('/.*w00t(?!\.chules\.net).*/i',$m)
							)
								event('warnkickban',$to,$from,'Please use clueful chatting to the best of your ability.  You should try to set an example, not be lame.');
						default:
							if (
								preg_match('/^.*(\!\!+|\?\?+|\!+\?+(\!+\?+)+).*$/',$m)
							)
								event('warnkickban',$to,$from,'Using multiple exclamation points or question marks in a row is not clueful.');
					}
				}
			}

		}

		function event_join ($nick,$channel) {
			global $mysql;
			$ircd = &ircd();

			if (strtolower($channel) == '#clueirc') {
				$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
				$uid = $nickd['loggedin'];
				$user = $mysql->get($mysql->sql('SELECT * FROM `access` WHERE `id` = '.$mysql->escape($uid)));
				$user = $user['user'];

				if ((strtolower($user) == 'methecooldude') and (strtolower($nick) != 'methecooldude')) {
					$ircd->svskill('methecooldude','Requested by '.$nick.'.');
					$ircd->svsnick($nick,'methecooldude');
				}
			}
		}

		function event_nick ($from,$to) {
			global $mysql;
			$ircd = &ircd();
			if ($mysql->getaccess($to) == 1000) {
				if (strtolower($from) == 'cobi') {
					$ircd->svsnick($to,'Cobi');
				}
			}
		}

		function event_eos () {
			global $mysql;
			$ircd = &ircd();

			$ircd->addnick($mysql->getsetting('server'),'Katelin','TheKBot','TheKBot.ClueNet.Org','The K bot.');
			$ircd->addnick($mysql->getsetting('server'),'K','TheKBot','TheKBot.ClueNet.Org','The K bot.');
			$ircd->mode('Katelin','Katelin','+S');
			foreach ($this->set['chans'] as $chan) {
				$ircd->join('Katelin',$chan);
			}
		}
	}

	function registerm () {
		$class = new katelin;
		register($class, __FILE__, 'Katelin Module', 'katelin');
	}
?>
