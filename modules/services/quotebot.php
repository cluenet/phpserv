<?PHP
	class quotebot {
		private static $quotes;
		private static $channels;
		private static $counter;

		function construct() {
			$this->quotes = fopen('quotes.txt','a+');
			if (file_exists('qchan.ser')) {
				$this->channels = unserialize(file_get_contents('qchan.ser'));
			} else {
				$this->channels = array ( '#clueirc' => 1 );
			}
			foreach ($this->channels as $c => $d) if (($c{0} != '#') or (fnmatch('*,*',$c))) unset($this->channels[$c]);
			file_put_contents('qchan.ser',serialize($this->channels));

			if (!file_exists('quotes.idx')) {
				fseek($this->quotes,0);
				$i = 0;
				while (!feof($this->quotes)) {
					$i++;
					$tmp[$i] = ftell($this->quotes);
					fgets($this->quotes,1024);
				}
				$tmp2 = fopen('quotes.idx','w');
				foreach ($tmp as $num => $seek) {
					fwrite($tmp2,$num.':'.$seek."\n");
				}
				fseek($this->quotes,0,SEEK_END);
				fclose($tmp2);
				unset($tmp,$tmp2,$i);
			}

			if (!file_exists('quotes.2dx')) {
				$tmp = fopen('quotes.idx','r');
				$tmp2 = fopen('quotes.2dx','w');
				fwrite($tmp2,"0:0\n");
				while (!feof($tmp)) {
					$t = ftell($tmp);
					$tmp3 = explode(':',fgets($tmp,1024));
					if (isset($tmp3[1])) {
						if ($tmp3[0] % 1000 == 0) {
							fwrite($tmp2,($tmp3[0]/1000).':'.$t."\n");
						}
					}
				}
				fclose($tmp);
				fclose($tmp2);
				unset($t,$tmp3,$tmp2,$tmp);
			}
			
			$tmp = fopen('quotes.2dx','r');
			while (!feof($tmp)) {
				$tmp2 = explode(':',fgets($tmp,1024));
				$this->index[$tmp2[0]] = $tmp2[1];
			}
			fclose($tmp);
			unset($tmp);	

			$tmp = fopen('quotes.idx','r');
			fseek($tmp,$tmp2[1]);
			$this->qcount = ($tmp2[0] * 1000);
			while (!feof($tmp)) {
				fgets($tmp,1024);
				$this->qcount++;
			}
			fclose($tmp);
			unset($tmp,$tmp2);
			
			$this->event_eos();
		}

		function destruct() {
			$ircd = &ircd();
			unset($this->quotes);
			$ircd->quit('QuoteBot', 'Module Unloaded.');
		}

		function getquoteposition($number) {
			$tmp = fopen('quotes.idx','r');
			fseek($tmp,$this->index[floor($number/1000)]);
			while (!feof($tmp)) {
				$x = explode(':',fgets($tmp,1024));
				if ($x[0] == $number) {
					fclose($tmp);
					return $x[1];
				}
			}
		}

		function event_invite ($from,$to,$chan) {
			if (strtolower($to) == 'quotebot') {
				$ircd = &ircd();
				if (isset($chan) and (!isset($this->channels[strtolower($chan)]))) {
					$this->channels[strtolower($chan)] = 1;
					$ircd->join('QuoteBot',$chan);
					file_put_contents('qchan.ser',serialize($this->channels));
				}
			}
		}

		function event_kick ($from,$who,$to,$reason) {
			if (strtolower($who) == 'quotebot') {
				$ircd = &ircd();
				if (isset($to) and isset($this->channels[strtolower($to)])) {
					unset($this->channels[strtolower($to)]);
					$ircd->msg('QuoteBot',$to,'Bye and thanks for the fish!');
					file_put_contents('qchan.ser',serialize($this->channels));
				}
			}
		}

		function event_msg ($from,$to,$message) {
			if (isset($this->channels[strtolower($to)])) {
				$d = explode(' ', $message);

				$ircd = &ircd();

				if (strtolower($d[0]) == '!quote') {
					if ((!isset($d[1])) or ($d[1] < 0)) {
						$x = rand(1,$this->qcount);
					} else {
						$x = $d[1];
					}
					fseek($this->quotes,$this->getquoteposition($x));
					$randomline = fgets($this->quotes,1024);
					fseek($this->quotes,0,SEEK_END);

					$ircd->msg('QuoteBot', $to, $x.': '.$randomline);
				} elseif (strtolower($d[0]) == '!quotecount') {
					$ircd->msg('QuoteBot', $to, $from.': I know of '.$this->qcount.' quotes.');
				} elseif (strtolower($d[0]) == '!search') {
					$pid = pcntl_fork();
					if ($pid == -1) {
						logit("[quotebot] ERROR forking!");
					} elseif ($pid) {
						/* Parent, do nothing */
					} else {
						$y = explode(' ',$message,2);
						$y = $y[1];
						if (isset($y) and (strlen($y) > 4)) {
							rewind($this->quotes);
							$c = 1;
							$sentcount = 0;
							while (!feof($this->quotes)) {
								$x = fgets($this->quotes,1024);
								if (stripos($x,$y) !== FALSE) {
									$ircd->notice('QuoteBot', $from, $c.': '.$x);
									$sentcount++;
									if ($sentcount >= 25) die();
								}
								$c++;
							}
						}
						die();
					}
				} else {
					$tmp = fopen('quotes.idx','a+');
					$this->qcount++;
					fseek($tmp,0,SEEK_END);
					fseek($this->quotes,0,SEEK_END);
					if (($this->qcount % 1000) == 0) {
						$tmp2 = fopen('quotes.2dx','a+');
						fwrite($tmp2,($this->qcount / 1000).':'.ftell($tmp)."\n");
						$this->index[$this->qcount / 1000] = ftell($tmp);
						fclose($tmp2);
						unset($tmp2);
					}
					fwrite($tmp,$this->qcount.':'.ftell($this->quotes)."\n");
					fclose($tmp);
					unset($tmp);
					fwrite($this->quotes,'<'.$from.'/'.$to.'> '.$message."\n");
				}
			}
		}

		function event_eos () {
			global $mysql;
			$ircd = &ircd();

			$ircd->addnick($mysql->getsetting('server'),'QuoteBot','Services','Services.ClueNet.Org','Quote Service');
			foreach ($this->channels as $y => $x) {
				$ircd->join('QuoteBot',$y);
			}
		}
	}

	function registerm () {
		$class = new quotebot;
		register($class, __FILE__, 'QuoteBot Module', 'quotebot');
	}
?>
