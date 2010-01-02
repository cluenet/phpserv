<?PHP
	class versionanalysis {
		private $nicks;
		private $timer;

		function construct() {
			$this->event_eos();
		}

		function destruct() {
			$ircd = &ircd();
			$ircd->quit('VersionAnalysis', 'Module Unloaded.');
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
				if ($to == '#botnetattack') { // This is one of our channels
					if (substr($message,0,1) == '!') {
						$cmd = explode(' ',substr($message,1));
						$rest = explode(' ',$message,2);
						if ($mysql->getaccess($from) > 900) {
							switch (strtolower($cmd[0])) {
								case 'doit':
									$ircd->msg('VersionAnalysis','#botnetattack','Grabbing list of nicks ...');
									$result = $mysql->sql('SELECT `nick` FROM `users`');
									while ($x = $mysql->get($result)) {
										$this->nicks[$x['nick']] = 1;
									}
									$ircd->msg('VersionAnalysis','#botnetattack','Done ... Ctcp versioning ...');
									$ircd->ctcp('VersionAnalysis','$*','VERSION',NULL);
									$this->timer = 10;
									break;
							}
						}
					}
				}
			}
		}

		function event_ctcpreply ($from,$to,$ctcp,$message = NULL) {
			$ircd = &ircd();
			if (($to == 'VersionAnalysis') and (strtoupper($ctcp) == 'VERSION')) {
//				if (fnmatch('*Khaled*',$message)) {
					$ircd->msg('VersionAnalysis','#botnetattack',$from./*' is using mIRC.  Responded'*/' responded'.' to CTCP VERSION with: "'.$message.'".');
					unset($this->nicks[$from]);
//				}
			}
		}

		function event_timer () {
			$ircd = &ircd();

			if (isset($this->timer) and ($this->timer > 0)) {
				$this->timer--;
				if ($this->timer == 0) {
					foreach ($this->nicks as $nick => $v) {
						$ircd->msg('VersionAnalysis','#botnetattack',$nick.' did not respond.');
					}
					unset ($this->nicks);
				}
			}
		}


		function event_eos () {
			global $mysql;
			$ircd = &ircd();

			$ircd->addnick($mysql->getsetting('server'),'VersionAnalysis','ClueNet','Administration.ClueNet.Org','Department of forensics.');
			$ircd->mode('VersionAnalysis','VersionAnalysis','+S');
			$ircd->join('VersionAnalysis','#botnetattack');
		}
	}

	function registerm () {
		$class = new versionanalysis;
		register($class, __FILE__, 'versionanalysis Module', 'versionanalysis');
	}
?>
