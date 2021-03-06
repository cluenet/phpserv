<?PHP
	class nickserv {
		function construct () {
			$this->event_eos('a');
			if(ismod('commandutils'))
				$this->event_commandutils_load();
		}
		
		function event_module_loaded($file) {
			if(realpath($file) == realpath(__FILE__))
				if(!ismod('commandutils'))
					load('modules/misc/commandutils.php');
		}
		
		function event_commandutils_load() {
			if(!ismod('commandutils')) {
				logit('Huh?  We were told commandutils was ready, but it does not exist.');
				return;
			}
				
			$cu = getmod('commandutils');
			$cu->registercommand($this, 'anon', 'INFO', '[username] - Get info about a user.');
			$cu->registercommand($this, 'anon', 'REGISTER', 'Register information.');
			$cu->registercommand($this, 'anon', 'IDENTIFY', '<password>[:<username>] - Identifies you to services.');
			$cu->registercommand($this, 'anon', 'ID', 'Alias for IDENTIFY.');
			$cu->registercommand($this, 'anon', 'LOGOUT', 'Logs out of your account');
			
			$cu->registercommand($this, 'auth', 'IDENTIFY', '<password>[:<username>] - Identifies you to services.');
			$cu->registercommand($this, 'auth', 'ID', 'Alias for IDENTIFY.');
			
			$cu->registercommand($this, 'auth', 'REGISTER', 'Associates your current nick with your PHPServ account.');
			$cu->registercommand($this, 'auth', 'DROP', 'Dissociates your current nick with your PHPServ account.');
			$cu->registercommand($this, 'auth', 'GHOST', '<nick> - Kills <nick> if you are the owner of it.');
			$cu->registercommand($this, 'auth', 'INFO', '[username] - Get info about a user.');
			$cu->registercommand($this, 'auth', 'LOGOUT', 'Logs out of your account');
			
			$cu->registercommand($this, 'oper', 'OVERRIDE', 'Discards all privilege checks and does what you want.');
		}

		function destruct () {
			$ircd = &ircd();
			$ircd->quit('NickServ','La Gone!');
		}

		function command_anon_info($from, $to, $rest, $extra) {
			$ircd = &ircd();
			global $mysql;

			if($rest[0]) $user = $rest[0];
			else $user = $from;

			if (!$data = $mysql->get($mysql->sql('SELECT `userid` FROM `nickserv` WHERE `nick` = '.$mysql->escape($user))))
				$ircd->notice($to,$from,'The user '. $user.' does not exist.');
			else {
				$channels = array();
				$uid = $data['userid'];
				$access = $mysql->get($mysql->sql('SELECT id, level, user FROM `access` WHERE `id` = '.$mysql->escape($uid)));
				$host = $mysql->get($mysql->sql('SELECT host FROM `hostserv` WHERE `active` = 1 AND `uid` = '.$mysql->escape($uid)));
				$result = $mysql->sql('SELECT channel FROM `chanserv` WHERE `owner` = '.$mysql->escape($uid));
				while($row = $mysql->get($result)) $channels[] = $row[0];

				$channelc = count($channels);
				$channels = array_chunk($channels, 10);

				$ircd->notice($to,$from, '---- Info about '. $user.' ----');
				$ircd->notice($to,$from, 'Account name: '.$access['user'].' (User ID: '.$uid.')');
				$ircd->notice($to,$from, 'PHPserv access: '.$access['level']);
				if(!empty($host['host']))
					$ircd->notice($to,$from, 'vHost: '.$host['host']);
				$ircd->notice($to,$from, 'Owns '. $channelc.' channels');
				foreach($channels as $a)
					$ircd->notice($to,$from, 'Owned channels: '.implode(", ", $a));
				$ircd->notice($to,$from, '---- End Info ----');
			}
			
		}
		
		function command_auth_info($f,$t,$r,$e) {
			return $this->command_anon_info($f,$t,$r,$e);
		}
		
		function command_anon_register($from,$to,$rest,$extra) {
			$ircd = &ircd();
			$ircd->notice($to,$from,'You need to identify to your PHPServ account to do this.');
			$ircd->notice($to,$from,'To create a PHPServ account, please type "/MSG PHPServ HELP".');
		}
		
		function command_anon_id ($from,$to,$rest,$extra) {
			$this->command_anon_identify($from,$to,$rest,$extra);
		}
		
		function command_anon_identify ($from,$to,$rest,$extra) {
			$p = explode(':',$rest[0]);

			if (isset($p[1])) {
				event('msg',$from,'PHPServ','IDENTIFY '.$p[1].' '.$p[0]);
			} else {
				event('msg',$from,'PHPServ','IDENTIFY '.$from.' '.$p[0]);
			}
		}
		
		function command_anon_logout( $from, $to, $rest, $extra ) {
			event( 'msg', $from, 'PHPServ', 'LOGOUT' );
		}

		function command_auth_logout( $from, $to, $rest, $extra ) {
			event( 'msg', $from, 'PHPServ', 'LOGOUT' );
		}
						
		function command_auth_id( $from, $to, $rest, $extra) {
			$this->command_auth_identify( $from, $to, $rest, $extra);
		}
		
		function command_auth_identify( $from, $to, $rest, $extra) {
			$ircd = &ircd();
			
			$ircd->notice( $to, $from, 'You are already identified!' );
		}
		
		function command_auth_register ( $from, $to, $rest, $extra ) {
			global $mysql;
			$ircd = &ircd();
			$data = $mysql->get( $mysql->sql( 'SELECT * FROM `nickserv` WHERE `nick` = ' . $mysql->escape( $from ) ) );
			if ( $data )
				$ircd->notice( $to, $from, '"'.$from.'" is already registered!' );
			else {
				$data = array
				(
					'id'		=> 'NULL',
					'userid'	=> $extra['uid'],
					'nick'		=> $from
				);
				$mysql->insert( 'nickserv', $data );
				$ircd->notice( $to, $from, '"' . $from . '" is now linked to your PHPServ account.' );
			}
		}
		
		function command_oper_override( $from, $to, $rest, $extra ) {
			global $mysql;
			$ircd = &ircd();
			// XXX: We need a better way to do override. Perhaps a value in $extra that ignores permission checks? - SnoFox
			if( strtolower( $rest[0] ) == 'drop' ) {
			// XXX: My crappy MySQL work here needs work. :p  - SnoFox
				$targetData = $mysql->get( $mysql->sql( 'SELECT * FROM `nickserv` WHERE `nick` = ' . $mysql->escape( $rest[1] ) ) );
				if( $targetData === false ) {
					$ircd->notice( $to, $from, 'The nick "' . $rest[1] . '" is not registered.' );
				} else {
					$targetId = $targetData['userid'];
					$targetAccount = $mysql->get( $mysql->sql( 'SELECT `user` FROM `access` WHERE `id` = ' . $mysql->escape( $targetId ) ) );
					$targetAccount = $targetAccount['user'];
					logit( '[NickServ] ' . $from . '(' . $extra['nickd']['user'] . ') forced dropped nick ' . $rest[1] . ' from account '.$targetAccount );
					$mysql->sql( 'DELETE FROM `nickserv` WHERE `nick` = ' . $mysql->escape( $rest[1] ) );
					$ircd->notice( $to, $from, 'The nick "' . $rest[1] . '" has been forced dropped.' );
				}
			} else {
				$ircd->notice( $to , $from , 'Invalid override command "' . $rest[0] . '".' );
			}
		}
		
		function command_auth_ghost ( $from, $to, $rest, $extra ) {
			global $mysql;
			$ircd = &ircd();
			
			$data = $mysql->get( $mysql->sql( 'SELECT * FROM `nickserv` WHERE `nick` = '.$mysql->escape( $rest[0] ) ) );
			if ( $extra['uid'] == $data['userid'] ) {
				$ircd->svskill( $rest[0], 'Connection reset by Ghost. This user has been ghostified by ' . $from );
				$ircd->notice( $to, $from, 'Ghost busted. :D' );
			} else
				$ircd->notice( $to, $from, 'You don\'t own that nick!' );
		}
		
		function command_auth_drop ( $from, $to, $rest, $extra ) {
			global $mysql;
			$ircd = &ircd();			
			
			$data = $mysql->get( $mysql->sql( 'SELECT * FROM `nickserv` WHERE `nick` = ' . $mysql->escape( $from ) ) );
			if ($extra['uid'] == $data['userid']) {
				$accout = $mysql->get( $mysql->sql( 'SELECT `user` FROM `access` WHERE `id` = ' . $mysql->escape( $data['userid'] ) ) );
				logit('[NickServ] ' . $from . ' dropped their nick (from account ' . $account['user'] . ')' );
				$mysql->sql( 'DELETE FROM `nickserv` WHERE `nick` = '.$mysql->escape( $from ) );
				$ircd->notice( $to, $from, 'Nick "'.$from.'" dropped.' );
			} else
				$ircd->notice( $to, $from, 'You don\'t own that nick!' );
		}
		
		function event_msg ($from,$to,$message) {
			global $mysql;
			$ircd = &ircd();
			
			if ((strtolower($to) == 'nickserv') or (strtolower($to) == 'nickserv@'.strtolower($mysql->getsetting('server')))) {
				$to = explode('@', $to, 2);
				$to = $to[0];
				
				$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
				$uid = $nickd['loggedin'];
				
				if( $uid == -1 ) 
					$level = 'anon';
				
				// Run an SQL query to get the UID's level; elseif and else to set the oper/user level, test command.
				getmod('commandutils')->parsecommand($this, $level, $from, $to, $message, array('uid' => $uid,'nickd' => $nickd));
			}
		}

		function event_kill ($from,$to,$reason) {
			global $mysql;
			$ircd = &ircd();
			
			if (strtolower($to) == 'nickserv') {
				$this->event_eos();
//				$ircd->svskill($from,'Killing service bots isn\'t a smart idea.');
			}
		}

		function event_eos ($a) {
			global $mysql;
			$ircd = &ircd();

			$ircd->addnick($mysql->getsetting('server'),'NickServ','Services','Services.ClueNet.Org','Nick Service');
			$ircd->join('NickServ','#services');
		}

	}

	function registerm () {
		$class = new nickserv;
		register($class, __FILE__, 'NickServ Module', 'nickserv');
	}
?>
