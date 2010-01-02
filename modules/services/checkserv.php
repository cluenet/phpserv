<?PHP
	class checkserv {
		protected static $nicks;

		function construct() {
//			$this->event_eos();
		}

		function destruct() {
			$ircd = &ircd();
			foreach ($this->nicks as $nick => $data) {
				$ircd->quit($nick,'Module Unloaded.');
			}
			unset($this->nicks);
		}

		function event_ctcpreply ($from,$to,$ctcp,$message = NULL) {
			$ircd = &ircd();
			if (strtolower($ctcp) == 'LAG') {
				if ($message == NULL) {
					if (isset($this->nicks[$to])) {
						$ircd->kill($to,$from,'Bottler.');
						$ircd->quit($to,'Accomplished my goal.');
						unset($this->nicks[$to]);
					}
				}
			}
		}

		function event_signon ($from,$user,$host,$real,$ip,$server) {
			$ircd = &ircd();
			global $mysql;

			$nlen = rand(7,10);

			$r = rand(1,2);

			$nick = '';

			for ($i = 1; $i <= $nlen; $i++) {
				if ($r == 1) { $nick .= chr(rand(1,26) + 64); } elseif ($r == 2) { $nick .= chr(rand(1,26) + 96); } else { $nick .= rand(0,9); }
				$r = rand(1,3);
			}

//			$nick = 'test';

			$this->nicks[$nick]['timer'] = 300;
			$this->nicks[$nick]['nick'] = $from;

			$nnserver = $mysql->getsetting('server');
			$nnident = substr($nick,-8,8);
			$nnhost = strtoupper(dechex(rand(1,255))).strtoupper(dechex(rand(1,255))).strtoupper(dechex(rand(1,255))).strtoupper(dechex(rand(1,255))).'.ipt.aol.com';
			$nnreal = md5($from.$user.$host.$real.$ip.$server);

			logit($nnserver.','.$nick.','.$nnident.','.$nnhost.','.$nnreal);

			$this->nicks[$nick]['not'] = 1;
			$this->nicks[$nick]['nnserver'] = $nnserver;
			$this->nicks[$nick]['nnnick'] = $nick;
			$this->nicks[$nick]['nnident'] = $nnident;
			$this->nicks[$nick]['nnhost'] = $nnhost;
			$this->nicks[$nick]['nnreal'] = $nnreal;
/*
			$ircd->join($nick,'#svslog');

			$ircd->msg($nick,'#svslog','Scanning '.$from);

			$ircd->ctcp($nick,$from,'lag');*/
		}

		function event_timer () {
			$ircd = &ircd();
			if (isset($this->nicks)) {
				foreach ($this->nicks as $y => $x) {
					logit('new junk pending ...');
					if (isset($this->nicks[$y]['not'])) {
						logit('part 1...');
/*						$ircd->addnick($this->nicks[$y]['nnserver'],$this->nicks[$y]['nnnick'],$this->nicks[$y]['nnident'],$this->nicks[$y]['nnhost'],$this->nicks[$y]['nnreal']);
						logit('part 2...');
						$ircd->join($y,'#svslog');
						logit('part 3...');
						$ircd->msg($y,'#svslog','Scanning '.$this->nicks[$y]['nick']);
						logit('part 4...');
						$ircd->ctcp($y,$this->nicks[$y]['nick'],'lag');
						logit('part 5...');
						unset($this->nicks[$y]['not']);*/
						$ircd->raw('! #svslog :...');
					} else {
						$this->nicks[$y]['timer']--;
						if ($this->nicks[$y]['timer'] == 270) {
							$ircd->ctcp($y,$this->nicks[$y]['nick'],'lag');
						} elseif ($this->nicks[$y]['timer'] == 0) {
							$ircd->quit($y,'No Longer Needed');
							unset($this->nicks[$y]);
						}
					}
				}
			}
		}
	}

//	class modinit {
                function registerm () {
//                        global $modules;
                        $class = new checkserv;
                        register($class, __FILE__, 'CheckServ Module', 'checkserv');
		}
//	}
?>
