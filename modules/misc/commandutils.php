<?PHP
	class commandutils {
		private $commands;
		
		function construct() {
				event('commandutils_load');
		}
		
		function registercommand($module, $section, $command, $help = '', $pm = false, $nick = null) {
			if(!isset($this->commands[ get_class($module) ]))
				$this->commands[ get_class($module) ] = array();
				
			if(!isset($this->commands[ get_class($module) ][ $section ]))
				$this->commands[ get_class($module) ][ $section ] = array();
				
			$this->commands[ get_class($module) ][ $section ][ strtolower($command) ] = array(
				'command' => $command,
				'section' => $section,
				'module' => $module,
				'help' => $help,
				'pm' => $pm,
				'nick' => $nick
			);
			
			logit('[commandutils] Registered command: '.$command.' from '.get_class($module).' ('.$help.').');
		}
		
		function sendhelp($module, $section, $from, $to) {
			$ircd = &ircd();
			
			$size = 0;
			
			foreach($this->commands[ get_class($module) ][ $section ] as $key => $value) {
				$params = explode(' - ',$value['help'],2);
				if(count($params) == 1)
					$params = '';
				else
					$params = $params[0];					
				$size = max(array(strlen($value['command'].' '.$params),$size));
			}
				
			$ircd->notice($from,$to,'---- Help ----');
			foreach($this->commands[ get_class($module) ][ $section ] as $key => $value) {
				$params = explode(' - ',$value['help'],2);
				if(count($params) == 1)
					$params = '';
				else {
					$value['help'] = $params[1];
					$params = $params[0];
				}
				$line  = str_pad($value['command'].' '.$params,$size);
				$line .= ' - ' . $value['help'];
				if(160-strlen($from)-17-$size > 30) {
					$part = substr($line,0,160-strlen($from)-14);
					$line = substr($line,160-strlen($from)-14); 
					$ircd->notice($from,$to,$part);
					while( strlen($line) > 0 ) {
						$part = str_pad('',$size).'   '.substr($line,0,160-strlen($from)-17-$size);
						$line = substr($line,160-strlen($from)-17-$size);
						$ircd->notice($from,$to,$part);
					}
				} else
					$ircd->notice($from,$to,$line);
			}
			$ircd->notice($from,$to,'---- End Help ----');
		}
		
		function parsecommand($module, $section, $from, $to, $data, $extra = array()) {
			$ircd = &ircd();
			
			$parsed = explode(' ',$data,2);
			
			$rest = $parsed[1];
			$command = strtolower($parsed[0]);
			
			if( $command == 'help' ) {
				$this->sendhelp($module, $section, $to, $from);
				return 1;
			}
			
			if(!$this->command($module, $section, $from, $to, $command, explode(' ',$rest), $extra)) {
				$ircd->notice($to,$from,'Error:  No such command.  Send the HELP command for help.');
				return 0;
			}
			return 1;
		}
		
		function command($module, $section, $from, $to, $command, $rest, $extra) {
			if(!isset($this->commands[ get_class($module) ][ $section ][ strtolower($command) ]))
				return 0;
			
			$command = $this->commands[ get_class($module) ][ $section ][ strtolower($command) ];
			$funct = 'command_'.$section.'_'.$command['command'];	
			if(!method_exists($command['module'], $funct))
				return 0;
			
			call_user_func_array(array($module, $funct), array($from,$to,$rest,$extra));
			return 1;
		}
		
		function event_msg($from, $to, $message) {
			if($to[0] == '#')
				return;
			
			$parse = explode(' ',$message,2);
			$parsecmd = $parse[0]; 
			
			foreach($this->commands as $module => $sectiondata)
				foreach($sectiondata as $section => $commanddata)
					foreach($commanddata as $command => $data)
						if(
							$data['pm']
							and strtolower($data['command']) == strtolower($parsecmd)
							and strtolower($data['nick']) == strtolower($to)
						)
							$this->parsecommand($module,$section,$from,$to,$message);
		}
		
		function event_module_unload($module) {
			if(isset($this->commands[ get_class( getmod($module) ) ] ))
				unset($this->commands[ get_class( getmod($module) ) ] );
		}
	}
	
	function registerm() {
		$class = new commandutils;
		register($class, __FILE__, 'Command Utility Module', 'commandutils');
	}
?>
