<?PHP
	class warnkickban {
		private $state;
		private $bans;
		private $timer;
		
		function construct() {
			$this->bans = array();
			$this->loadstate();
			$this->event_eos();
		}
		
		function destruct() {
			$ircd = &ircd();
			$ircd->quit('WKB', 'Module Unloaded.');
			$this->savestate();
		}
		
		function loadstate() {
			$this->state = unserialize(file_get_contents('wkb.state'));
		}
	
		function savestate() {
			file_put_contents('wkb.state',serialize($this->state));
		}

		function event_msg($from,$to,$msg) {
			global $mysql;
			$ircd = &ircd();
			if (strtolower($to) == 'wkb') {
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
						$ircd->notice('WKB',$from,'You are level '.$this->state['warnings'][$from]['level'].' since '.(time() - $this->state['warnings'][$from]['time']).' seconds ago.');
					else
						$ircd->notice('WKB',$from,'You don\'t have a record with me.  (Good thing)');
				} else if ($mysql->getaccess($from) > 900) {
					$split = explode(' ',$msg,4);
					switch (strtolower($split[0])) {
						case 'wkb':
							event('warnkickban',$split[1],$split[2],$split[3]);
							$ircd->notice('WKB',$from,'Done.');
							break;
						case 'set':
							$this->state['warnings'][$split[1]] = array('time' => $split[2],'level' => $split[3]);
							$ircd->notice('WKB',$from,'Done.');
							break;
						case 'get':
							if (isset($split[1]) and ($split[1]))
								$x = var_export($this->state['warnings'][$split[1]],true);
							else
								$x = var_export($this->state['warnings'],true);
							$x = explode("\n", $x);
							foreach ($x as $y)
								$ircd->notice('WKB',$from,$y);
							break;
						case 'save':
							$this->savestate();
							$ircd->notice('WKB',$from,'Done.');
							break;
						case 'load':
							$this->loadstate();
							$ircd->notice('WKB',$from,'Done.');
							break;
						default:
							$ircd->notice('WKB',$from,'Unknown command.');
							break;
					}
				} else
					$ircd->notice('WKB',$from,'Insufficient access or unknown command.');
			}
		}

		function event_warnkickban($chan,$nick,$reason) {
			global $mysql;
			$ircd = &ircd();
			
			if (strtolower($chan) != '#clueirc')
				$ircd->join('WKB',$chan);
			
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
				$ircd->msg('WKB',$chan,$nick.': '.$reason);
			else if ($this->state['warnings'][$nick]['level'] == 1)
				$ircd->kick('WKB',$chan,$nick,$reason);
			else if ($this->state['warnings'][$nick]['level'] > 1) {
				$data1 = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
				$ircd->kick('WKB',$chan,$nick,$reason);
				$ircd->mode('WKB',$chan,'+bb '.$nick.'!*@* ~r:'.str_replace(' ','_',$data1['realname']));
				$this->bans[] = Array (
					'channel' => $chan,
					'mask' => $nick.'!*@*',
					'set' => time()
				);
				$this->bans[] = Array (
					'channel' => $chan,
					'mask' => '~r:'.str_replace(' ','_',$data1['realname']),
					'set' => time()
				);
			}
			
			if (strtolower($chan) != '#clueirc')
				$ircd->part('WKB',$chan,'My business is done.');
			
			$this->savestate();
		}
		
		function event_chanmode_b ($from,$to,$type,$mask) {
			if ($type == '-')
				foreach ($this->bans as $k => $v)
					if ($v['mask'] == $mask)
						if (strtolower($v['channel']) == strtolower($to))
							unset($this->bans[$k]);
		}
		
		function event_timer() {
			$ircd = &ircd();
			$this->timer++;
			if ($this->timer % 10 == 0) {
				foreach ($this->bans as $k => $v) {
					if (
						(strtolower($v['channel']) == '#clueirc')
						or (strtolower($v['channel']) == '#clueshells')
					) {
						$expiry = 7200;
						if ($v['set'] + $expiry < time()) {
							$ircd->mode('WKB',$v['channel'],'-b '.$v['mask']);
							$ircd->msg('WKB',$v['channel'],'Ban '.$k.' ('.$v['mask'].') set at '.$v['set'].' has expired and been unset.');
							unset($this->bans[$k]);
						}
					}
				}
			}
		}
		
		function event_eos () {
			global $mysql;
			$ircd = &ircd();
			
			$ircd->addnick($mysql->getsetting('server'),'WKB','WKB','WKBBot.ClueNet.Org','Warn Kick Ban.');
			$ircd->mode('WKB','WKB','+S');
			$ircd->join('WKB','#ClueIRC');
		}
	}
	
	function registerm () {
		$class = new warnkickban;
		register($class, __FILE__, 'WKB Module', 'warnkickban');
	}
?>
