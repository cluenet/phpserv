<?PHP
	class evalserv {
		function construct() {
			$this->event_eos();
		}

		function destruct() {
			$ircd = &ircd();
			$ircd->quit('evalserv', 'Unloaded.');
		}

		function event_msg ($from,$to,$message) {
			if (strtolower($to) == 'evalserv') {
				$d = explode(' ', $message);

				global $mysql;
//				global $modules;
				$ircd = &ircd();

				if ($mysql->getaccess($from) > 998) {

					if (strtolower($d[0]) == 'eval') {
						if (runkit_lint(implode(' ',array_slice($d, 1)))) {
/*							$options = array(
								'safe_mode'=>false,
								'open_basedir'=>'',
								'allow_url_fopen'=>'true',
								'disable_functions'=>'',
								'disable_classes'=>''
								);
							$sandbox = new Runkit_Sandbox($options);
							$sandbox['parent_access'] = true;
							$sandbox['parent_call'] = true;
							$sandbox['parent_read'] = true;
							$sandbox['parent_write'] = true;
							$sandbox['parent_echo'] = true;
							$sandbox['parent_scope'] = 0;
							$sandbox->eval('$PARENT = new Runkit_Sandbox_Parent;');
							$v = get_defined_vars();
							foreach ($v as $x => $y) {
								$sandbox->eval('$'.$x.' = $PARENT->'.$x.';');
//								$sandbox->$x = $$x;
							}
							unset($v);
							unset($x);
							unset($y);
							$sandbox->eval(implode(' ',array_slice($d, 1)));
							unset($sandbox); */

							logit('[EvalServ] *** '.$from.' is going to evaluate the following expression ***');
							logit('[EvalServ] *** '.implode(' ',array_slice($d, 1)).' ***');

							try {
								@eval(implode(' ',array_slice($d, 1)));
							}
							catch (Exception $e) {
								$ircd->notice($to,$from,'Error: '.$e->getMessage());
								$EXIT = FALSE;
							}
						}
						else { $ircd->notice($to,$from,'Syntax Error'); }
					}
				}
			}
		}

		function event_eos () {
//			global $settings;
//			global $modules;
			global $mysql;
			echo 'Caught EOS (os)\n';
			$ircd = &ircd();

			$ircd->addnick($mysql->getsetting('server'),'EvalServ','Services','Services.OpticPhase.Org','Bot Service');
			$ircd->join('EvalServ','#services');
		}
	}

	function registerm () {
		$class = new evalserv;
		register($class, __FILE__, 'OperServ Module', 'evalserv');
	}
?>
