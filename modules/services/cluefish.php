<?PHP
	class cluefish {
		private static $channels;
		private static $cresponses;
		private static $aresponses;

		function construct() {
			if (file_exists('cfchan.ser')) {
				$this->channels = unserialize(file_get_contents('cfchan.ser'));
			} else {
				$this->channels = array ( '#debug' => 1 );
			}
			$this->cresponses = array
				(
					'/hampster/i'			=> '%n: There is no \'p\' in hamster you retard.',
					'/vinegar.*aftershock/i'	=> 'Ah, a true connoisseur!',
					'/aftershock.*vinegar/i'	=> 'Ah, a true connoisseur!',
					'/^some people are being fangoriously devoured by a gelatinous monster(\!|\.)$/i'
									=> 'Hillary\'s legs are being digested.',
					'/^ag\!$/i'			=> 'Ag, ag, ag!',
					'/^cluefish owns(\.|\!)$/i'	=> 'Aye, I do.',
					'/vinegar/i'			=> 'Nope, too sober for vinegar.  Try later.',
					'/martian/i'			=> 'Don\'t run! We are your friends!',
					'/^just then, he fell into the sea(\.|\!)$/i'
									=> 'Ooops!',
					'/aftershock/i'			=> 'mmmm, Aftershock.',
					'/^why are you here\?$/i'	=> 'Same reason.  I love candy.',
					'/^spoon(\?|\!|\.)$/i'		=> 'There is no spoon.',
					'/^(bounce|wertle)(\?|\!|\.)$/i'=> 'Moo.',
					'/^crack(\?|\!|\.)$/i'		=> 'Doh, there goes another bench!',
					'/^you can\'t just pick people at random!$/i'
									=> 'I can do anything I like, %n, I\'m eccentric!  Rrarrrrrgh!  Go!',
					'/^flibble(\?|\!|\.)$/i'	=> 'Plob.',
					'/(the cluefish has created splidge|cluefish created splidge)(\?|\!|\.)/i'
									=> 'Absolutely not! Think I could show my face around here if I was responsible for that?',
					'/^now there\'s more than one of them\?$/i'
									=> 'A lot more.',
					'/^i want everything(\?|\!|\.)$/i'
									=> 'Would that include a bullet from this gun?',
					'/we are getting aggravated(\?|\!|\.)/i'
									=> 'Yes, we are.',
					'/^how old are you, cluefish\?$/i'
									=> chr(1).'ACTION is older than time itself!'.chr(1),
					'/^atlantis(\?|\!|\.)$/i'	=> 'Beware the underwater headquarters of the trout and their bass henchmen. From there they plan their attacks on other continents.',
					'/^oh god(\?|\!|\.)$/i'		=> 'ClueFish will suffice.',
					'/^cluefish(\?|\!|\.)$/i'	=> 'Yes?',
					'/^what is the matrix\?$/i'	=> 'No-one can be told what the matrix is.  You have to see it for yourself.',
					'/^what do you need\?$/i'	=> 'Guns. Lots of guns.',
					'/^i know kungfu(\?|\!|\.)$/i'	=> 'Show me.',
					'/^cake(\?|\!|\.)$/i'		=> 'Fish.',
					'/^trout go moo(\?|\!|\.)$/i'	=> 'Aye, that\'s \'cause they\'re fish.',
					'/^kangaroo(\?|\!|\.)$/i'	=> 'The kangaroo is a four winged stinging insect.',
					'/^sea bass(\?|\!|\.)$/i'	=> 'Beware of the mutant sea bass and their laser cannons!',
					'/^trout(\?|\!|\.)$/i'		=> 'Trout are freshwater fish and have underwater weapons.',
					'/has returned from playing counterstrike(\?|\!|\.)/i'
									=> 'We care because ...?',
					'/^where are we\?$/i'		=> 'Last time I looked, we were in %c.',
					'/^where do you want to go today\?$/i'
									=> 'anywhere but redmond :(.',
					'/^fish go moo(\?|\!|\.)$/i'	=> chr(1).'ACTION notes that %n is truly enlightened.'.chr(1),
					'/^(.*) go moo(\?|\!|\.)$/i'	=> '%n: Only when they are impersonating fish.',
					'/^fish go ([a-z0-9 _]+)(\?|\!|\.)$/i'
									=> '%n: Lies! Fish don\'t go %1! Fish go moo!',
					'/^you know who else (.*)(\?|\!|\.)$/i'
									=> '%n: Your mom?',
					'/^if there\'s one thing i know for sure, it\'s that fish don\'t moo\.$/i'
									=> '%n: Heretic! Unbeliever!',
					'/^cluefish: muahahaha\. fear the dark side\. :\)$/i'
									=> '%n: You smell :P',
					'/^ammuu\?$/i'			=> '%n: fish go moo oh yes they do!',
					'/^fish(\?|\!|\.)$/i'		=> '%n: fish go moo!',
					'/^snake(\?|\!|\.)$/i'		=> 'Ah snake a snake! Snake, a snake! Ooooh, it\'s a snake!',
					'/^carrots handbags cheese(\?|\!|\.)$/i'
									=> 'Toilets, russians, planets, hamsters, weddings, poets, stalin, Kuala Lumpur! Pygmies, budgies, Kuala Lumpur!',
					'/sledgehammer(\?|\!|\.)/i'	=> 'Sledgehammers go quack!',
					'/^badger, badger, badger, badger, badger, badger, badger, badger, badger, badger, badger, badger(\?|\!|\.)$/i'
									=> 'Mushroom, mushroom!',
					'/^moo\?$/i'			=> 'To moo, or not to moo, that is the question. Whether \'tis nobler in the mind to suffer the slings and arrows of outrageous fish...',
					'/^herring(\?|\!|\.)$/i'	=> 'herring(n): Useful device for chopping down tall trees. Also moos (see fish).',
					'/www\.outwar\.com/i'		=> 'Would you please *go away* with that outwar rubbish?'
				);
			
			$this->aresponses = array
				(
					'/hampster/i'			=> '%n: There is no \'p\' in hamster you retard.',
					'/^feeds cluefish hundreds and thousands(\?|\!|\.)$/i'
									=> 'Medi... Er... ClueFish!',
					'/(vinegar.*aftershock|aftershock.*vinegar)/i'
									=> 'Ah, a true connoisseur!',
					'/vinegar/i'			=> 'Nope, too sober for vinegar.  Try later.',
					'/martians/i'			=> 'Don\'t run! We are your friends!',
					'/aftershock/i'			=> 'Mmmm, Aftershock.',
					'/(the cluefish has created splidge|cluefish created splidge)/i'
									=> 'Absolutely not! Think I could show my face around here if I was responsible for that?',
					'/we are getting aggravated/i'	=> 'Yes, we are.',
					'/^strokes cluefish(\?|\!|\.)$/i'=> chr(1).'ACTION moos loudly at %n.'.chr(1),
					'/^slaps (.*) around a bit with a large trout(\?|\!|\.)$/i'
									=> 'Trouted!',
					'/has returned from playing counterstrike/i'
									=> 'We care because ...?',
					'/^fish go moo(\?|\!|\.)$/i'	=> chr(1).'ACTION notes that %n is truly enlightened.'.chr(1),
					'/^(.*) go moo(\?|\!|\.)$/i'	=> '%n: Only when they are impersonating fish.',
					'/^fish go ([a-z0-9 _]+)(\?|\!|\.)$/i'
									=> '%n: Lies! Fish don\'t go %1! fish go moo!',
					'/^you know who else (.*)(\?|\!|\.)$/i'
									=> '%n: Your mom?',
					'/^thinks happy thoughts about pretty (.*)(\?|\!|\.)$/i'
									=> chr(1).'ACTION has plenty of pretty %1. Would you like one, %n?'.chr(1),
					'/^snaffles a (.*) off cluefish.$/i'
									=> ':('
				);

			$this->event_eos();
		}

		function destruct() {
			$ircd = &ircd();
			$ircd->quit('cluefish', 'Module Unloaded.');
		}

		function event_msg ($from,$to,$message) {
			if (isset($this->channels[strtolower($to)])) {
				$d = explode(' ', $message);

				$ircd = &ircd();
				
				foreach ($this->cresponses as $regex => $mtpl) {
					if (preg_match($regex,$message,$m)) {
						$ircd->msg('cluefish',$to,str_replace(array('%n','%c','%1'),array($from,$to,(isset($m[1]) ? $m[1] : '')),$mtpl));
						break;
					}
				}
			}
		}

		function event_ctcp ($from,$to,$type,$message) {
			if (isset($this->channels[strtolower($to)])) {
				if (strtolower($type) == 'action') {
					$d = explode(' ', $message);

					$ircd = &ircd();

					foreach ($this->aresponses as $regex => $mtpl) {
						if (preg_match($regex,$message,$m)) {
							$ircd->msg('ClueFish',$to,str_replace(array('%n','%c','%1'),array($from,$to,$m[1]),$mtpl));
							break;
						}
					}
				}
			}
		}

		function event_invite ($from,$to,$chan) {
			if (strtolower($to) == 'cluefish') {
				if (strtolower($chan) == '#clueirc') {
					$ircd = &ircd();
					$ircd->notice('cluefish',$from,'No, I do not want to join that channel. :(');
					return;
				}
				$this->channels[strtolower($chan)] = 1;
				$ircd = &ircd();
				$ircd->join('cluefish',$chan);
				$ircd->msg('cluefish',$chan,chr(1).'ACTION moos contentedly at '.$from.'.'.chr(1));
				file_put_contents('cfchan.ser',serialize($this->channels));
			}
		}

		function event_kick ($from,$who,$to,$reason) {
			if (strtolower($who) == 'cluefish') {
				unset($this->channels[strtolower($to)]);
				file_put_contents('cfchan.ser',serialize($this->channels));
			}
		}

		function event_eos () {
			global $mysql;
			$ircd = &ircd();

			$ircd->addnick($mysql->getsetting('server'),'ClueFish','fish','ClueFish.bots.cluenet.org','The ClueFish.');
			foreach ($this->channels as $y => $x) {
				$ircd->join('ClueFish',$y);
			}
		}
	}

	function registerm () {
		$class = new cluefish;
		register($class, __FILE__, 'ClueFish Module', 'cluefish');
	}
?>
