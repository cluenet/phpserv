<?PHP
	class fishbot {
		private static $channels;
		private static $cresponses;
		private static $aresponses;

		function construct() {
			if (file_exists('fchan.ser')) {
				$this->channels = unserialize(file_get_contents('fchan.ser'));
			} else {
				$this->channels = array ( '#debug' => 1 );
			}
			$this->cresponses = array
				(
					'/hampster/i'			=> '%n: There is no \'p\' in hamster you retard.',
					'/vinegar.*aftershock/i'	=> 'Ah, a true connoisseur!',
					'/aftershock.*vinegar/i'	=> 'Ah, a true connoisseur!',
					'/^some people are being fangoriously devoured by a gelatinous monster$/i'
									=> 'Hillary\'s legs are being digested.',
					'/^ag$/i'			=> 'Ag, ag ag ag ag ag AG AG AG!',
					'/^fishbot owns$/i'		=> 'Aye, I do.',
					'/vinegar/i'			=> 'Nope, too sober for vinegar.  Try later.',
					'/martian/i'			=> 'Don\'t run! We are your friends!',
					'/^just then, he fell into the sea$/i'
									=> 'Ooops!',
					'/aftershock/i'			=> 'mmmm, Aftershock.',
					'/^why are you here\?$/i'	=> 'Same reason.  I love candy.',
					'/^spoon$/i'			=> 'There is no spoon.',
					'/^(bounce|wertle)$/i'		=> 'moo',
					'/^crack$/i'			=> 'Doh, there goes another bench!',
					'/^you can\'t just pick people at random!$/i'
									=> 'I can do anything I like, %n, I\'m eccentric!  Rrarrrrrgh!  Go!',
					'/^flibble$/i'			=> 'plob',
					'/(the fishbot has created splidge|fishbot created splidge)/i'
									=> 'omg no! Think I could show my face around here if I was responsible for THAT?',
					'/^now there\'s more than one of them\?$/i'
									=> 'A lot more.',
					'/^i want everything$/i'	=> 'Would that include a bullet from this gun?',
					'/we are getting aggravated/i'	=> 'Yes, we are.',
					'/^how old are you, fishbot\?$/i'
									=> chr(1).'ACTION is older than time itself!'.chr(1),
					'/^atlantis$/i'			=> 'Beware the underwater headquarters of the trout and their bass henchmen. From there they plan their attacks on other continents.',
					'/^oh god$/i'			=> 'fishbot will suffice.',
					'/^fishbot$/i'			=> 'Yes?',
					'/^what is the matrix\?$/i'	=> 'No-one can be told what the matrix is.  You have to see it for yourself.',
					'/^what do you need\?$/i'	=> 'Guns. Lots of guns.',
					'/^i know kungfu$/i'		=> 'Show me.',
					'/^cake$/i'			=> 'fish',
					'/^trout go m[o0][o0]$/i'	=> 'Aye, that\'s cos they\'re fish.',
					'/^kangaroo$/i'			=> 'The kangaroo is a four winged stinging insect.',
					'/^sea bass$/i'			=> 'Beware of the mutant sea bass and their laser cannons!',
					'/^trout$/i'			=> 'Trout are freshwater fish and have underwater weapons.',
					'/has returned from playing counterstrike/i'
									=> 'like we care fs :(',
					'/^where are we\?$/i'		=> 'Last time I looked, we were in %c.',
					'/^where do you want to go today\?$/i'
									=> 'anywhere but redmond :(.',
					'/^fish go m[o0][o0]$/i'	=> chr(1).'ACTION notes that %n is truly enlightened.'.chr(1),
					'/^(.*) go m[o0][o0]$/i'	=> '%n: only when they are impersonating fish.',
					'/^fish go ([a-z0-9 _]+)$/i'	=> '%n LIES! Fish don\'t go %1! fish go m00!',
					'/^you know who else (.*)$/i'	=> '%n: YA MUM!',
					'/^if there\'s one thing i know for sure, it\'s that fish don\'t m00\.$/i'
									=> '%n: HERETIC! UNBELIEVER!',
					'/^fishbot: muahahaha\. ph33r the dark side\. :\)$/i'
									=> '%n: You smell :P',
					'/^ammuu\?$/i'			=> '%n: fish go m00 oh yes they do!',
					'/^fish$/i'			=> '%n: fish go m00!',
					'/^snake$/i'			=> 'Ah snake a snake! Snake, a snake! Ooooh, it\'s a snake!',
					'/^carrots handbags cheese$/i'	=> 'toilets russians planets hamsters weddings poets stalin KUALA LUMPUR! pygmies budgies KUALA LUMPUR!',
					'/sledgehammer/i'		=> 'sledgehammers go quack!',
					'/^badger badger badger badger badger badger badger badger badger badger badger badger$/i'
									=> 'mushroom mushroom!',
					'/^moo\?$/i'			=> 'To moo, or not to moo, that is the question. Whether \'tis nobler in the mind to suffer the slings and arrows of outrageous fish...',
					'/^herring$/i'			=> 'herring(n): Useful device for chopping down tall trees. Also moos (see fish).',
					'/www\.outwar\.com/i'		=> 'would you please GO AWAY with that outwar rubbish!'
				);
			
			$this->aresponses = array
				(
					'/hampster/i'			=> '%n: There is no \'p\' in hamster you retard.',
					'/^feeds fishbot hundreds and thousands$/i'
									=> 'MEDI.. er.. FISHBOT',
					'/(vinegar.*aftershock|aftershock.*vinegar)/i'
									=> 'Ah, a true connoisseur!',
					'/vinegar/i'			=> 'Nope, too sober for vinegar.  Try later.',
					'/martians/i'			=> 'Don\'t run! We are your friends!',
					'/aftershock/i'			=> 'mmmm, Aftershock.',
					'/(the fishbot has created splidge|fishbot created splidge)/i'
									=> 'omg no! Think I could show my face around here if I was responsible for THAT?',
					'/we are getting aggravated/i'	=> 'Yes, we are.',
					'/^strokes fishbot$/i'		=> chr(1).'ACTION m00s loudly at %n.'.chr(1),
					'/^slaps (.*) around a bit with a large trout$/i'
									=> 'trouted!',
					'/has returned from playing counterstrike/i'
									=> 'like we care fs :(',
					'/^fish go m[o0][o0]$/i'	=> chr(1).'ACTION notes that %n is truly enlightened.'.chr(1),
					'/^(.*) go m[o0][o0]$/i'	=> '%n: only when they are impersonating fish.',
					'/^fish go ([a-z0-9 _]+)$/i'	=> '%n LIES! Fish don\'t go %1! fish go m00!',
					'/^you know who else (.*)$/i'	=> '%n: YA MUM!',
					'/^thinks happy thoughts about pretty (.*)$/i'
									=> chr(1).'ACTION has plenty of pretty %1. Would you like one %n?'.chr(1),
					'/^snaffles a (.*) off fishbot.$/i'
									=> ':('
				);

			$this->event_eos();
		}

		function destruct() {
			$ircd = &ircd();
			$ircd->quit('fishbot', 'Module Unloaded.');
		}

		function event_msg ($from,$to,$message) {
			if (isset($this->channels[strtolower($to)])) {
				$d = explode(' ', $message);

				$ircd = &ircd();
				
				foreach ($this->cresponses as $regex => $mtpl) {
					if (preg_match($regex,$message,$m)) {
						$ircd->msg('fishbot',$to,str_replace(array('%n','%c','%1'),array($from,$to,(isset($m[1]) ? $m[1] : '')),$mtpl));
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
							$ircd->msg('fishbot',$to,str_replace(array('%n','%c','%1'),array($from,$to,$m[1]),$mtpl));
							break;
						}
					}
				}
			}
		}

		function event_invite ($from,$to,$chan) {
			if (strtolower($to) == 'fishbot') {
				$this->channels[strtolower($chan)] = 1;
				$ircd = &ircd();
				$ircd->join('fishbot',$chan);
				$ircd->msg('fishbot',$chan,chr(1).'ACTION m00s contentedly at '.$from.'.');
				file_put_contents('fchan.ser',serialize($this->channels));
			}
		}

		function event_kick ($from,$who,$to,$reason) {
			if (strtolower($who) == 'fishbot') {
				unset($this->channels[strtolower($to)]);
				file_put_contents('fchan.ser',serialize($this->channels));
			}
		}

		function event_eos () {
			global $mysql;
			$ircd = &ircd();

			$ircd->addnick($mysql->getsetting('server'),'fishbot','fish','go.moo.oh.yes.they.do','Teh f1shb0t <'.chr(176).')))))><');
			foreach ($this->channels as $y => $x) {
				$ircd->join('fishbot',$y);
			}
		}
	}

	function registerm () {
		$class = new fishbot;
		register($class, __FILE__, 'FishBot Module', 'fishbot');
	}
?>
