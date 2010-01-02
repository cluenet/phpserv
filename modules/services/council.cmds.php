<?PHP
	$c = count($m);
	$commands = array (
		'kick' => array (
			'description' => '<channel> <user> - Kick a user from a channel.',
			'action' => 'kick '.(($c > 2)?$m[2]:'').' from '.(($c > 1)?$m[1]:''),
			'percent' => 65,
			'count' => 1,
			'endtime' => time() + 120,
			'endcount' => 4,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'kick'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						(($c > 2)?$m[2]:''),
						'Requested by '.$from.' and approved by the ClueNet Council.'
					)
				)
			)
		),
		'kickreason' => array (
			'description' => '<channel> <user> <reason> - Kick a user from a channel with a reason.',
			'action' => 'kick '.(($c > 2)?$m[2]:'').' from '.(($c > 1)?$m[1]:'').' for '.(($c > 3)?implode(' ',array_slice($m,3)):''),
			'percent' => 75,
			'count' => 2,
			'endtime' => time() + 120,
			'endcount' => 4,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'kick'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						(($c > 2)?$m[2]:''),
						(($c > 3)?implode(' ',array_slice($m,3)):'')
					)
				)
			)
		),
		'kickban' => array (
			'description' => '<channel> <user> - Kickban a user from a channel.',
			'action' => 'kickban '.(($c > 2)?$m[2]:'').' from '.(($c > 1)?$m[1]:''),
			'percent' => 65,
			'count' => 1,
			'endtime' => time() + 120,
			'endcount' => 4,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'mode'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						'+b '.(($c > 2)?$m[2]:'').'!*@*'
					)
				),
				array (
					'function' => array (
						'$ircd',
						'kick'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						(($c > 2)?$m[2]:''),
						'Requested by '.$from.' and approved by the ClueNet Council.'
					)
				)
			)
		),
		'kickbanreason' => array (
			'description' => '<channel> <user> <reason> - Kickban a user from a channel with a reason.',
			'action' => 'kickban '.(($c > 2)?$m[2]:'').' from '.(($c > 1)?$m[1]:'').' for '.(($c > 3)?implode(' ',array_slice($m,3)):''),
			'percent' => 75,
			'count' => 2,
			'endtime' => time() + 120,
			'endcount' => 4,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'mode'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						'+b '.(($c > 2)?$m[2]:'').'!*@*'
					)
				),
				array (
					'function' => array (
						'$ircd',
						'kick'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						(($c > 2)?$m[2]:''),
						(($c > 3)?implode(' ',array_slice($m,3)):'')
					)
				)
			)
		),
		'adduser' => array (
			'description' => '<user> - Adds a user to the council.',
			'action' => 'add '.(($c > 1)?$m[1]:'').' to the ClueCouncil.',
			'percent' => 80,
			'count' => 2,
			'endtime' => time() + 2*86400,
			'endcount' => 20,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$this',
						'adduser'
					),
					'params' => array (
						(($c > 1)?$m[1]:'')
					)
				)
			)
		),
		'deluser' => array (
			'description' => '<user> - Deletes a user from the council.',
			'action' => 'remove '.(($c > 1)?$m[1]:'').' from the ClueCouncil.',
			'percent' => 90,
			'count' => 5,
			'endtime' => time() + 5*86400,
			'endcount' => 50,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$this',
						'deluser'
					),
					'params' => array (
						(($c > 1)?$m[1]:'')
					)
				)
			)
		),
		'addbot' => array (
			'description' => '<user> <owner> <features> - Approves a bot.',
			'action' => 'approve '.(($c > 2)?$m[2]:'').'\'s bot '.(($c > 1)?$m[1]:'').' ('.(($c > 3)?implode(' ',array_slice($m,3)):'').').',
			'percent' => 65,
			'count' => 2,
			'endtime' => time() + 3600,
			'endcount' => 10,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$this',
						'addbot'
					),
					'params' => array (
						(($c > 1)?$m[1]:''),
						(($c > 2)?$m[2]:''),
						(($c > 3)?implode(' ',array_slice($m,3)):'')
					)
				)
			)
		),
		'delbot' => array (
			'description' => '<user> - Unapproves a bot.',
			'action' => 'unapprove bot '.(($c > 1)?$m[1]:'').'.',
			'percent' => 65,
			'count' => 3,
			'endtime' => time() + 2*3600,
			'endcount' => 10,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$this',
						'delbot'
					),
					'params' => array (
						(($c > 1)?$m[1]:'')
					)
				)
			)
		),
		'helloworld' => array (
			'description' => 'Hello World!',
			'action' => 'Send Hello, World to #clueirc.',
			'percent' => 100,
			'count' => 1,
			'endtime' => time() + 120,
			'endcount' => 1,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'msg'
					),
					'params' => array (
						'ClueNet',
						'#ClueIRC',
						'Hello, World!'
					)
				)
			)
		),
		'ban' => array (
			'description' => '<channel> <banmask> - Sets a ban on a channel.',
			'action' => 'ban '.(($c > 2)?$m[2]:'').' on '.(($c > 1)?$m[1]:'').'.',
			'percent' => 65,
			'count' => 2,
			'endtime' => time() + 120,
			'endcount' => 5,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'mode'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						'+b '.(($c > 2)?$m[2]:'')
					)
				)
			)
		),
		'timeban' => array (
			'description' => '<channel> <banmask> <time in seconds> - Bans a user on a channel for a certain number of seconds.',
			'action' => 'ban '.(($c > 2)?$m[2]:'').' on '.(($c > 1)?$m[1]:'').' for '.(($c > 3)?$m[3]:'').' seconds.',
			'percent' => 65,
			'count' => 2,
			'endtime' => time() + 120,
			'endcount' => 5,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'mode'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						'+b '.(($c > 2)?$m[2]:'')
					)
				),
				array (
					'function' => array (
						'$this',
						'timer'
					),
					'params' => array (
						md5('ban'.(($c > 1)?$m[1]:'').(($c > 2)?$m[2]:'').(($c > 3)?$m[3]:'')),
						1,
						(($c > 3)?$m[3]:''),
						array ( '$ircd', 'mode' ),
						array ( 'ClueNet', (($c > 1)?$m[1]:''), '-b '.(($c > 2)?$m[2]:'') )
					)
				)
			)
		),
		'chanmute' => array (
			'description' => '<channel> <nick> - Mutes a user on a channel.',
			'action' => 'mute '.(($c > 2)?$m[2]:'').' on '.(($c > 1)?$m[1]:'').'.',
			'percent' => 65,
			'count' => 2,
			'endtime' => time() + 120,
			'endcount' => 5,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'mode'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						'+b ~q:'.(($c > 2)?$m[2]:'').'!*@*'
					)
				)
			)
		),
		'timechanmute' => array (
			'description' => '<channel> <nick> <time in seconds> - Mutes a user on a channel for a number of seconds.',
			'action' => 'mute '.(($c > 2)?$m[2]:'').' on '.(($c > 1)?$m[1]:'').' for '.(($c > 3)?$m[3]:'').' seconds',
			'percent' => 65,
			'count' => 2,
			'endtime' => time() + 120,
			'endcount' => 5,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'mode'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						'+b ~q:'.(($c > 2)?$m[2]:'').'!*@*'
					)
				),
				array (
					'function' => array (
						'$this',
						'timer'
					),
					'params' => array (
						md5('mute'.(($c > 1)?$m[1]:'').(($c > 2)?$m[2]:'').(($c > 3)?$m[3]:'')),
						1,
						(($c > 3)?$m[3]:''),
						array ( '$ircd', 'mode' ),
						array ( 'ClueNet', (($c > 1)?$m[1]:''), '-b ~q:'.(($c > 2)?$m[2]:'').'!*@*' )
					)
				)
			)
		),
		'shun' => array (
			'description' => '<nickname|user@host> <reason> - Shun someone for some reason.',
			'action' => 'shun '.(($c > 1)?$m[1]:'').' for "'.(($c > 2)?implode(' ',array_slice($m,2)):'').'".',
			'percent' => 80,
			'count' => 3,
			'endtime' => time() + 300,
			'endcount' => 8,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'shun'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						0,
						(($c > 2)?implode(' ',array_slice($m,2)):'')
					)
				)
			)
		),
		'timeshun' => array (
			'description' => '<nickname|user@host> <time> <reason> - Shun someone for some reason for a certain amount of time.',
			'action' => 'shun '.(($c > 1)?$m[1]:'').' for '.(($c > 2)?$m[2]:'').' because "'.(($c > 3)?implode(' ',array_slice($m,3)):'').'".',
			'percent' => 80,
			'count' => 3,
			'endtime' => time() + 300,
			'endcount' => 8,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'shun'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						(($c > 2)?$m[2]:''),
						(($c > 3)?implode(' ',array_slice($m,3)):'')
					)
				)
			)
		),
		'kill' => array (
			'description' => '<nickname> <reason> - Kill a user for a reason.',
			'action' => 'kill '.(($c > 1)?$m[1]:'').' for "'.(($c > 2)?implode(' ',array_slice($m,2)):'').'".',
			'percent' => 70,
			'count' => 2,
			'endtime' => time() + 120,
			'endcount' => 5,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'kill'
					),
					'params' => array (
						(($c > 1)?$m[1]:''),
						(($c > 2)?implode(' ',array_slice($m,2)):'')
					)
				)
			)
		),
		'gzline' => array (
			'description' => '<nick|user@ip> <time> <reason> - GZ:Line someone.',
			'action' => 'GZ:Line '.(($c > 1)?$m[1]:'').' for '.(($c > 2)?$m[2]:'').' because "'.(($c > 3)?implode(' ',array_slice($m,3)):'').'".',
			'percent' => 80,
			'count' => 3,
			'endtime' => time() + 600,
			'endcount' => 10,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'gzline'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						(($c > 2)?$m[2]:''),
						(($c > 3)?implode(' ',array_slice($m,3)):'')
					)
				)
			)
		),
		'gline' => array (
			'description' => '<nick|user@host> <time> <reason> - G:Line someone.',
			'action' => 'G:Line '.(($c > 1)?$m[1]:'').' for '.(($c > 2)?$m[2]:'').' because "'.(($c > 3)?implode(' ',array_slice($m,3)):'').'".',
			'percent' => 80,
			'count' => 3,
			'endtime' => time() + 600,
			'endcount' => 10,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'gline'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						(($c > 2)?$m[2]:''),
						(($c > 3)?implode(' ',array_slice($m,3)):'')
					)
				)
			)
		),
		'sajoin' => array (
			'description' => '<nick> <channel> - Force a nickname to join a channel.',
			'action' => 'force '.(($c > 1)?$m[1]:'').' to join '.(($c > 2)?$m[2]:'').'.',
			'percent' => 70,
			'count' => 2,
			'endtime' => time() + 120,
			'endcount' => 5,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'sajoin'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						(($c > 2)?$m[2]:'')
					)
				)
			)
		),
		'unshun' => array (
			'description' => '<user@host> - Unshun someone.',
			'action' => 'unshun '.(($c > 1)?$m[1]:'').'.',
			'percent' => 80,
			'count' => 3,
			'endtime' => time() + 300,
			'endcount' => 8,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'shun'
					),
					'params' => array (
						'ClueNet',
						'-'.(($c > 1)?$m[1]:''),
						0,
						'Removing'
					)
				)
			)
		),
		'ungzline' => array (
			'description' => '<user@ip> - Un-GZ:Line someone.',
			'action' => 'Un-GZ:Line '.(($c > 1)?$m[1]:'').'.',
			'percent' => 80,
			'count' => 3,
			'endtime' => time() + 600,
			'endcount' => 10,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'gzline'
					),
					'params' => array (
						'ClueNet',
						'-'.(($c > 1)?$m[1]:''),
						0,
						'Removing'
					)
				)
			)
		),
		'ungline' => array (
			'description' => '<user@host> - Un-G:Line someone.',
			'action' => 'Un-G:Line '.(($c > 1)?$m[1]:'').'.',
			'percent' => 80,
			'count' => 3,
			'endtime' => time() + 600,
			'endcount' => 10,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'gline'
					),
					'params' => array (
						'ClueNet',
						'-'.(($c > 1)?$m[1]:''),
						0,
						'Removing'
					)
				)
			)
		),
		'unban' => array (
			'description' => '<channel> <banmask> - Unset a ban on a channel.',
			'action' => 'unset ban '.(($c > 2)?$m[2]:'').' on '.(($c > 1)?$m[1]:'').'.',
			'percent' => 70,
			'count' => 2,
			'endtime' => time() + 120,
			'endcount' => 5,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'mode'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						'-b '.(($c > 2)?$m[2]:'')
					)
				)
			)
		),
		'un-chan-mute' => array (
			'description' => '<channel> <nick> - Unmute a nick on a channel.',
			'action' => 'unmute '.(($c > 2)?$m[2]:'').' on '.(($c > 1)?$m[1]:'').'.',
			'percent' => 65,
			'count' => 2,
			'endtime' => time() + 120,
			'endcount' => 5,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$this',
						'mode'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						'-b ~q:'.(($c > 2)?$m[2]:'').'!*@*'
					)
				)
			)
		),
		'topic' => array (
			'description' => '<channel> <topic> - Set the topic of a channel.',
			'action' => 'set topic of '.(($c > 1)?$m[1]:'').' to '.(($c > 2)?implode(' ',array_slice($m,2)):'').'.',
			'percent' => 60,
			'count' => 1,
			'endtime' => time() + 300,
			'endcount' => 3,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'topic'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						(($c > 2)?implode(' ',array_slice($m,2)):'')
					)
				)
			)
		),
		'chanmode' => array (
			'description' => '<channel> <modestring> - Set modes on a channel.',
			'action' => 'set mode '.(($c > 2)?implode(' ',array_slice($m,2)):'').' on '.(($c > 1)?$m[1]:'').'.',
			'percent' => 75,
			'count' => 3,
			'endtime' => time() + 600,
			'endcount' => 5,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'mode'
					),
					'params' => array (
						'ClueNet',
						(($c > 1)?$m[1]:''),
						(($c > 2)?implode(' ',array_slice($m,2)):'')
					)
				)
			)
		),
		'noactionvote' => array (
			'description' => '<percent> <req. supports> <end time in seconds> <and|or> <end count> <vote text> - Propose a vote with no automatic action.',
			'action' => (($c > 6)?implode(' ',array_slice($m,6)):'').'.',
			'percent' => (($c > 1)?$m[1]:''),
			'count' => (($c > 2)?$m[2]:''),
			'endtime' => time() + (($c > 3)?(($m[3] < 7*86400)?$m[3]:7*86400):0),
			'andor' => (($c > 5)?((($m[4] == 'and') and ($m[5] <= 5))?'and':'or'):'or'),
			'endcount' => (($c > 5)?$m[5]:''),
			'functions' => array ()
		),
		'tempoper' => array (
			'description' => 'Give you temporary oper - should only be used when absolutely necessary and should be accompanied with a REASON.',
			'action' => 'give '.$from.' temporary oper.',
			'percent' => 90,
			'count' => 4,
			'endtime' => time() + 300,
			'endcount' => 8,
			'andor' => 'or',
			'functions' => array (
				array (
					'function' => array (
						'$ircd',
						'svsmode'
					),
					'params' => array (
						'ClueNet',
						$from,
						'+oOaACN'
					)
				),
				array (
					'function' => array (
						'$ircd',
						'svso'
					),
					'params' => array (
						$from,
						'+rDRhgwlcLkKbBnGAaNztZvqdX'
					)
				),
				array (
					'function' => array (
						'$ircd',
						'msg'
					),
					'params' => array (
						'ClueNet',
						$from,
						'You now have oper for 10 minutes.'
					)
				),
				array (
					'function' => array (
						'$this',
						'councilchat'
					),
					'params' => array (
						$from.' now has oper for 10 minutes.'
					)
				),
				array (
					'function' => array (
						'$this',
						'timer'
					),
					'params' => array (
						md5('tempoper0'.$from),
						1,
						600,
						array ( '$ircd', 'svso' ),
						array ( $from, '-' )
					)
				),
				array (
					'function' => array (
						'$this',
						'timer'
					),
					'params' => array (
						md5('tempoper1'.$from),
						1,
						600,
						array ( '$ircd', 'svsmode' ),
						array ( 'ClueNet', $from, '-oOaACN' )
					)
				),
				array (
					'function' => array (
						'$this',
						'timer'
					),
					'params' => array (
						md5('tempoper2'.$from),
						1,
						600,
						array ( '$this', 'councilchat' ),
						array ( $from.'\'s tempoper has expired and been removed.' )
					)
				)
			)
		)
	);
?>
