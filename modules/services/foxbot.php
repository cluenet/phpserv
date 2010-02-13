<?PHP

	class foxbot {
	
	private $set;
	private	$config;
	private $connected;
	
	function construct() {
		$this->config = array (
			'nick' => 'Unix',
			'user' => 'Unix',
			'host' => 'SnoFox.net',
			'gecos' => 'SnoFox\'s friend',
			'chan' => array (
				'main' => '#FoxDen',
				'secure' => '#FoxSecure',
			)
		);
		
		$this->set = unserialize(file_get_contents('foxbot.db'));
		
		$this->doBotStart();
	}
	
	function destruct() {
		$ircd = &ircd();
		$this->saveset();
		
		$ircd->quit($this->config['nick'],'Ciao!');
	}
	
	function doBotStart() {
		$ircd = &ircd();
		global $mysql;
		$config = $this->config;
	
		$ircd->addnick($mysql->getsetting('server'),$config['nick'],$config['user'],$config['host'],$config['gecos']);
		$this->connected = true;
		$ircd->mode($config['nick'],$config['nick'],'+oSpB');
		// Join the main, public channel
		if (!isset($this->set['chan'][strtolower($config['chan']['main'])])) {
			$this->doJoin(strtolower($config['chan']['main']));
		} else {
			$ircd->join($config['nick'],$config['chan']['main']);
		}
		$ircd->mode($config['nick'],$config['chan']['main'],'+h '.$config['nick']);
		// Join the private logging channel
		if (!isset($this->set['chan'][strtolower($config['chan']['secure'])])) {
			$this->doJoin(strtolower($config['chan']['secure']));
		} else {
			$ircd->join($config['nick'],$config['chan']['secure']);
		}
		$ircd->mode($config['nick'],$config['chan']['secure'],'+siIao *!*@SnoFox.net '.str_repeat($config['nick'].' ',2));
		
		$chans = array_keys($this->set['chan']);
		foreach ($chans as $chan) {
			if (strtolower($chan) == strtolower($config['chan']['main']) || strtolower($chan) == strtolower($config['chan']['secure'])) {
			// We already joined this channel
				continue;
			}
			$ircd->join($config['nick'],$chan);
			$ircd->msg($config['nick'],$config['chan']['secure'],"\002".'IRC'."\002".': '.$config['nick'].' has rejoined '.$chan);
		}
	}
	
	function saveset() {
		file_put_contents('foxbot.db',serialize($this->set));
	}
	
	function chkAccess($nick,$level) {
		global $mysql;
		$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
		$uid = $nickd['loggedin'];
		$user = $mysql->get($mysql->sql('SELECT * FROM `access` WHERE `id` = '.$mysql->escape($uid)));
		if (($user['level'] > $level) || ($user['user'] == 'SnoFox')) {
			return 1;
		} else {
			return 0;
		}
	}
	
	function doJoin($chan) {
		$ircd = &ircd();
		$this->set['chan'][strtolower($chan)] = 1;
		$ircd->msg($this->config['nick'],$this->config['chan']['secure'],"\002IRC\002: ".$this->config['nick'].' has joined '.$chan);
		$ircd->join($this->config['nick'],$chan);
		$this->saveset();
	}

	function doPart($chan,$reason) {
		$ircd = &ircd();
		$ircd->msg($this->config['nick'],$this->config['chan']['secure'],"\002IRC\002: ".$this->config['nick'].' has left '.$chan);
		$ircd->part($this->config['nick'],$chan,$reason);
		unset($this->set['chan'][strtolower($chan)]);
		$this->saveset();
	}
	
	function event_msg ($from,$to,$message) {
		if ($to[0] == '#') {
			if (isset($this->set['chan'][strtolower($to)])) {
				return $this->thisIsAChanMsg($from,$to,$message);
			}
		}
		if (strtolower($to) == strtolower($this->config['nick'])) {
			$ircd = &ircd();
			$config = $this->config;
			$to = explode('@', $to, 2);
			$to = $to[0];
			$d = explode(' ', $message);
		
			$ircd->msg($config['nick'],$config['chan']['secure'],"\00303\002Message\002\003: <".$from.'/PM> '.$message);
		} 
	}
	
	function thisIsAChanMsg($nick,$chan,$message) {
		$message = explode(' ',$message,2);
		$cmd = $message[0];
		$message = (isset($message[1]) ? $message[1] : '');
		$config = $this->config;
		$ircd = &ircd();
		
		switch ($cmd) {
			case '!fjoin':
				if ($this->chkAccess($nick,'599')) {
					if ($message[0] != '') {
						$this->doJoin(strtolower($message));
					} else {
						$ircd->notice($config['nick'],$nick,'Join where?');
					}
				} else { 
					$ircd->notice($config['nick'],$nick,'Insufficient access.');
				}
				break;
			case '!part':
				$this->doPart($chan,'Requested by '.$nick);
				break;
			case '!fpart':
				$message = explode(' ',$message,2);
				$where = $message[0];
				if (isset($message[1]))
					$why = $message[1];
				if ($this->chkAccess($nick,'599'))
					$this->doPart($where,(isset($why) ? $why : 'Requested by '.$nick));
				break;
			case 'default':
				return;
		}
	}
	
	function event_logger($string) {
		$string = explode(' ',$string,2);
		$src = $string[0];
		$msg = $string[1];
		if ($src == '[HostServ]') {
			$config = $this->config;
			$ircd = &ircd();
			
			$ircd->msg($config['nick'],$config['chan']['secure'],"\00307\002HostServ\002\003: ".$msg);
		}
	}
	
	function event_identify($from,$uid) {
		$ircd = &ircd();
		global $mysql;
		$config = $this->config;
		
		$user = $mysql->get($mysql->sql('SELECT `user` FROM `access` WHERE `id` = '.$mysql->escape($uid)));
		
		$ircd->msg($config['nick'],$config['chan']['secure'],"\00302\002Identify\002\003: ".$from.' identified to PHPserv using account '.$user['user']."\015". ' (UID '.$uid.')');
	}
	
	function event_logout($from,$user) {
		$ircd = &ircd();
		$config = $this->config;
		
		$ircd->msg($config['nick'],$config['chan']['secure'],"\00302\002Logout\002\003: ".$from.' logged out of account '.$user['user']);
	}
	
	function event_signon($nick,$user,$host,$real,$ip,$server,$stamp=0) {
		// scrawl57!~scrawl@data.searchirc.org * SearchIRC Crawler
		// tunix29!~atte@echo940.server4you.de * netsplit.de
		if (!preg_match('/^(scrawl|tunix)\d+!~?(scrawl|atte)@(data|echo\d+)\.(searchirc\.org|server4you\.de)/i',$nick.'!'.$user.'@'.$host)) {
			$ircd = &ircd();
			$config = $this->config;

			$ircd->msg($config['nick'],$config['chan']['secure'],"\002Connect\002: Client connecting on ".$server.': '.$nick.'!'.$user.'@'.$host.' ['.$ip.'] ('.$real."\015)");
		}
	}
	
	function event_quit($nick,$reason) {
		$config = $this->config;
		if (strtolower($nick) == strtolower($config['nick'])) {
			// We're dead. Lets not cause problems.
			return;
		}
		
		$ircd = &ircd();
		
		$ircd->msg($config['nick'],$config['chan']['secure'],"\002Disconnect\002: ".$nick.' has left the network ('.$reason."\015)");
	}
	
	function event_usermode($from,$to,$modes) {
		$modes = explode(' ',$modes,2);
		$param = (isset($modes[1]) ? $modes[1] : '');
		$modes = $modes[0];
		
		if (strpos($modes,'o')) {
			$ircd = &ircd();
			$config = $this->config;

			if ($modes[0] == '+') {
				$up = '';
			} elseif ($modes[0] == '-') {
				$up = 'De';
			} else {
				return $ircd->msg($config['nick'],$config['chan']['secure'],$to.' messed with umode o, but WTF JUST HAPPEND!?');
			}
		
			if ($up == '') {
				$ircd->msg($config['nick'],$config['chan']['secure'],"\00304\002Oper\002\003: ".	$to.' has leveled up'.($from == $to ? '!' : ', thanks to '.$from));
			} elseif ($from != $to) {
				$ircd->msg($config['nick'],$config['chan']['secure'],"\00304\002Deoper\002\003: ".$to.' was powered down by '.$from);
			} else {
				$ircd->msg($config['nick'],$config['chan']['secure'],"\00304\002Deoper\002\003: ".$to.' has powered down');
			}
		}
	}
			
	function event_nick($old,$new) {
		$ircd = &ircd();
		$config = $this->config;
		
		$ircd->msg($config['nick'],$config['chan']['secure'],"\002Nick\002: ".$old.' changed their nickname to '.$new);
	}
	function event_ctcp ($from,$to,$type,$msg) {
		$config = $this->config;
		if (strtolower($to) == strtolower($config['nick']) && strtoupper($type) != 'ACTION') {
			$ircd = &ircd();
			$ircd->msg($config['nick'],$config['chan']['secure'],"\00303\002CTCP\002\003: Got CTCP from ".$from.': '.$type.' '.$msg);
		}
	}
	
	function event_ctcpreply ($from,$to,$ctcp,$message = NULL) {
		$config = $this->config;
		if (strtolower($to) == strtolower($config['nick'])) {
			$ircd = &ircd();
			$ircd->msg($config['nick'],$config['chan']['secure'],"\00303\002CTCP\002\003: Got CTCP reply from ".$from.': '.$ctcp.' '.$message);
		}
	}
		
	function event_notice ($from,$to,$message) {
		$config = $this->config;
		if (strtolower($to) == strtolower($config['nick'])) {
			$ircd = &ircd();
		
			$ircd->msg($config['nick'],$config['chan']['secure'],"\00303\002Message\002\003: <".$from.'/Notice> '.$message);
		}
	}
		
	function event_kick ($src,$pwntUser,$chan,$reason) {
		if (strtolower($pwntUser) == strtolower($this->config['nick'])) {
			$ircd = &ircd();
			$config = $this->config;
			$ircd->msg($config['nick'],$config['chan']['secure'],"\002IRC\002: ".$src.' kicked '.$config['nick'].' from '.$chan."\015".' ('.$reason."\015)");
			$ircd->msg($config['nick'],$chan,'All you had to do was ask! :(');
			unset($this->set['chan'][strtolower($chan)]);
			$this->saveset();
		}
	}
		
	function event_join ($nick,$channel) {
		$config = $this->config;
		
		if ($channel == $config['chan']['secure']) {
			if ($this->chkAccess($nick,599) === 0) {
				$ircd = &ircd();
				global $mysql;
				$nickd = $mysql->get($mysql->sql('SELECT loggedin, host FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
				$uid = $nickd['loggedin'];
				$user = $mysql->get($mysql->sql('SELECT `level` FROM `access` WHERE `id` = '.$mysql->escape($uid)));
				$level = $user['level'];
				
				$ircd->svsmode($config['nick'],$config['chan']['secure'],'-e');
				$ircd->mode($config['nick'],$config['chan']['secure'],'+bb '.$nick.' '.$nickd['host']);
				$ircd->kick($config['nick'],$config['chan']['secure'],$nick,'You are not authorized to join '.$config['chan']['secure'].'. Required access: >599. Your access: '.($level == '' ? 'Nonexistant' : $level).'. Ciao!');
			}
		}
	}
	
	function event_channel_create ($channel,$nick) {
		$ircd = &ircd();
		$config = $this->config;
		
		$ircd->msg($config['nick'],$config['chan']['secure'],"\00312\002Chan Create\002\003: ".$channel."\015".' created by '.$nick);
	}
		
	
	function event_channel_destroyed ($channel,$nick,$why) {
		$ircd = &ircd();
		$config = $this->config;
		
		switch ($why) {
			case 'sapart':
			case 'part':
				$msg = 'due to '.$nick.' parting.';
				break;
			case 'svskill':
			case 'kill':
				$msg = 'due to '.$nick.' being killed.';
				break;
			case 'kick':
				$msg = 'due to '.$nick.' being kicked.';
				break;
			case 'quit':
				$msg = 'due to '.$nick.' quitting.';
				break;
			case 'default':
				$msg = 'for unknown reasons.';
			}
		$ircd->msg($config['nick'],$config['chan']['secure'],"\00312\002Chan Destroy\002\003: ".$channel."\015".' destroyed '.$msg);
		
		if (isset($this->set['chan'][strtolower($channel)])) {
			$ircd->part($config['nick'],$channel);
			unset($this->set['chan'][strtolower($channel)]);
		} else {
			return;
		}
		$this->saveset();
	}
	
	function event_invite($nick,$to,$chan) {
		$config = $this->config;
		if (strtolower($to) != strtolower($config['nick'])) {
			return;
		}
		
		$ircd = &ircd();
		
		$this->doJoin($chan);
		$ircd->msg($config['nick'],$chan,'Hey '.$chan.', sup? My name is '.$config['nick'].', and I\'m a robot! '.$nick.' invited me to join, so here I am! If you want me to leave, type !part. Ciao!');
	}
	
	function event_kill($src,$dest,$reason) {
		if ($this->connected === false) {
			// We got a kill, but we're not connected. Let's not respawn.
			return;
		}
		if (strtolower($dest) == strtolower($this->config['nick'])) {
			// We died! D:
			$this->connected = false;
			$this->doBotStart();
		}
	}
}
	function registerm () {
		$class = new foxbot;
		register($class, __FILE__, 'FoxBot Module', 'foxbot');
	}
?>
