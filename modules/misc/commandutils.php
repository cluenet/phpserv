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
			foreach($this->commands[ get_class($module) ][ $section ] as $key => $value)
				$ircd->notice($from,$to,$value['command'].' - '.$value['help']);
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
		
		function event_unload($module) {
			if(isset($this->commands[ get_class( getmod($module) ) ] ))
				unset($this->commands[ get_class( getmod($module) ) ] );
		}
	}
	
	function registerm() {
		$class = new commandutils;
		register($class, __FILE__, 'Command Utility Module', 'commandutils');
	}
?>