<?PHP

	declare(ticks = 1);
	echo "Starting...\n";
	/* here we go... background ourselves */
	echo "Here goes nothing...\n";
	$fork = pcntl_fork();
	if ($fork == -1) { die("Err... Uh-Oh"); }
	elseif ($fork) { echo "W00T!!! Forked!\n"; die(); }
	/* woot... backgrounded */
	$__error_logger__ = fopen('stdout.log','a');
	$eventlog = fopen('event.log','a');
	function errorlog ($d) {
		global $__error_logger__;
		fwrite($__error_logger__,$d);
	}
	ob_start('errorlog',1);
	ini_set('display_errors','Off');
	ini_set('log_errors','On');
	ini_set('log_errors_max_len','0');
	ini_set('html_errors','Off');
	ini_set('error_log','stderr.log');
	while (1) {

		$pid = pcntl_fork();
		if ($pid == -1) { die("ERROR!"); }
		elseif ($pid) { file_put_contents('phpserv.pid',$pid); pcntl_waitpid($pid,$stat); }
		else {

			declare(ticks = 1);

			ini_set('display_errors','Off');
			ini_set('log_errors','On');
			ini_set('log_errors_max_len','0');
			ini_set('html_errors','Off');
			ini_set('error_log','stderr.log');

			include 'signals.php';

//			pcntl_alarm(1);

			include 'errors.php';

			global $modinfo;
			global $modules;

			function logit ($str) {
				global $modules;
				if (isset($modules['log'])) {
					$modules['log']->log($str);
				} else {
					file_put_contents('init.log',$str."\n",FILE_APPEND);
		
				}
			}

			function load ($file) {
				if (!runkit_lint_file($file)) { 
					logit('Error loading module: '.$file.' Syntax Error.');
					return 0;
				} else {
					if(function_exists('registerm'))
						runkit_function_remove("registerm");
					event('module_load',$file);
					runkit_import($file, 18);
					logit('Imported module: '.$file.' Sent request for registration. Awaiting Module Registration.');
					registerm();
					if(function_exists('registerm'))
						runkit_function_remove("registerm");
					event('module_loaded',$file);
					return 1;
				}
			}

			function aml ($lvl) {
				/*
				$lvl:
					0 = manual
					1 = pre-connect
					2 = connect
					3 = eos
				*/
				global $mysql;
				$result = $mysql->sql('SELECT * FROM `modules` WHERE `when` ='.$mysql->escape($lvl));
				while ($x = $mysql->get($result)) {
					if ($x['verbose']) {
						logit('Loading module: '.$x['name']);
					}
					load('modules/'.$x['type'].'/'.$x['fname'].'.php');
					if ($x['verbose']) {
						logit('Loaded module: '.$x['name']);
					}
				}
				return 1;
			}

			function unload ($module) {
				global $modinfo;
				global $modules;
				if (!isset($modules[$module])) {
					logit('Error unloading module: '.$module.' Not Loaded.');
					return 0;
				} else {
					event('module_unload',$module);
					if (method_exists($modules[$module], "destruct")) {
						$modules[$module]->destruct();
					}
			
					$classname = get_class($modules[$module]);
					$arr = get_class_methods($classname);
					foreach ($arr as $y) {
						runkit_method_remove($classname,$y);
					}

					$file = $modinfo[$module]['file'];
					unset($modules[$module]);
					unset($modinfo[$module]);
					logit('Destroyed module: '.$module);
					event('module_destroy',$module,$file);
					return 1;
				}
			}

			function register ($class,$file,$desc,$name) {
				global $modules;
				global $modinfo;
				$modules[$name] = $class;
				$modinfo[$name]['file'] = $file;
				$modinfo[$name]['desc'] = $desc;
				logit('(Module Registration) Name: '.$name.' File: '.$file.' Description: '.$desc);
				if (method_exists($modules[$name], "construct")) { $modules[$name]->construct(); }
				event('module_register',$name);
				return 1;
			}

			function reload ($module) {
				global $modules;
				global $modinfo;
				$file = $modinfo[$module]['file'];
				event('module_reload_before',$module);
				if (unload($module) == 1) {
					if (load($file)) {
						event('module_reload_after',$module);
						return 1;
					} else {
						return 0;
					}
				} else {
					return 0;
				}
			}

			function getmod ($module) {
				global $modules;
				return $modules[$module];
			}
			
			function ismod ($module) {
				global $modules;
				return isset($modules[$module]);
			}

			function ircd () {
				global $modules;
				return $modules['ircd'];
			}

			function event () {
				global $modules;
				global $eventlog;
				$time = microtime(1);
				$x = $modules;
				$arr = func_get_args();
				$funct = 'event_'.$arr[0];
				fwrite($eventlog,'Event '.$funct." Start\n");
				if (func_num_args() > 1) {
					$arr = array_slice($arr, 1);
				} else {
					$arr = Array('null');
				}
//				logit('Event: '.$funct.' Fired.');
				if (count($x) > 0) {
					foreach ($x as $item => $data) {
						$time2 = microtime(1);
						fwrite($eventlog,"\tChecking ".$item." Module\n");
						if (method_exists($modules[$item], $funct)) {
							fwrite($eventlog,"\t\tRunning ".$item." Module\n");
							$blah = call_user_func_array(array(&$modules[$item], $funct), $arr);
							fwrite($eventlog,"\t\tDone Running ".$item." Module\n");
						}
						fwrite($eventlog,"\tDone Checking ".$item." Module - time: ".(microtime(1) - $time2)."\n");
					}
				}
				fwrite($eventlog,'Event '.$funct.' End - time: '.(microtime(1) - $time)."\n");
				if ((microtime(1) - $time) > 1) {
					echo 'Long event!!! ('.(microtime(1) - $time).') '.$funct.'(';var_export($arr);echo ');'."\n";
				}
				unset ($x);
				return 1;
			}		

			class socks {
				protected static $socket;

				function connect ($ip,$port,$bind) {
				global $modules;
                    $opts = array('socket' => array('bindto' => $bind.':0'));
                    $context = stream_context_create($opts);
                    $this->socket = stream_socket_client('tcp://'.$ip.':'.$port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
                    if (!$this->socket) {
                        logit('Error: '.$errno.': '.$errstr);
                        return 0;
                    } else {
                        return 1;
                    }
				}

				function write ($data) {
					global $modules;
					event('raw_out',$data);
					fwrite($this->socket, $data."\n");
				}

				function read () {
					$read = array($this->socket);
					$write = array();
					$except = array();
					if(@stream_select($read,$write,$except,NULL))
						foreach($read as $socket) {
							$tmp = fgets($socket);
							return $tmp;
						}
					else
						return false;
				}

				function timeout ($time) {
					//stream_set_timeout($this->socket, $time);
				}

				function eof () {
					return feof($this->socket);
				}

				function disconnect () {
					fclose($this->socket);
				}
			}


			$mysql = new mysql;

			include 'settings.php';

			//$mysql->connect();
			while( !$mysql->connect() ) usleep(100);

			$mysql->init();

			$sock = new socks;

			aml(1);

			logit('Connecting to '.$mysql->getsetting('ircip').':'.$mysql->getsetting('ircport').' from '.$mysql->getsetting('ircbind').' ...');
			if (!$sock->connect($mysql->getsetting('ircip'), $mysql->getsetting('ircport'), $mysql->getsetting('ircbind'))) {
				logit('Agg ... Couldnt connect to '.$mysql->getsetting('ircip').':'.$mysql->getsetting('ircport').' from '.$mysql->getsetting('ircbind').'!');
				die();
			}
			logit('Loading level 2 modules');
			aml(2);
			logit('Event_Connected');
			event('connected');
			logit('Connected.');

			// EXTMODS

			// /EXTMODS

			while (!$sock->eof()) {
				$sock->timeout(1);
				if(($data = $sock->read()) === false)
					continue;
				if(str_replace(array("\r","\n"),"",$data) == "")
					continue;
				$time = microtime(1);
				if ($data) {
					event('raw_in', $data);
//					logit('Raw_In: '. $data);
					unset($data);
				}
				if (isset($aml3)) {
					aml(3);
					unset($aml3);
				}
				$time = microtime(1) - $time;
				if ($time > 1)
					echo 'Long parse!!!'."\n";
			}

			logit('Uh-Oh.');
			die();
		}
	}
?>
