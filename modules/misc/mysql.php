<?PHP
	class mysqlserv {

		function check_empty () {
			global $mysql;
			$res = $mysql->sql('SELECT `chanid`,`name` FROM `channels` WHERE `chanid` NOT IN (SELECT `channels`.`chanid` FROM `channels`, `user_chan` WHERE `channels`.`chanid` = `user_chan`.`chanid` GROUP BY `channels`.`chanid` HAVING COUNT(*) > 0)');

			while ($x = $mysql->get($res)) {
				$mysql->sql('DELETE FROM `channels` WHERE `chanid` = \''.$mysql->escape($x['chanid']).'\'');
				event('channel_destroyed',$x['name']);
			}
		}

		function event_signon ($nick,$user,$host,$real,$ip,$server) {
			global $mysql;
			$servid = $mysql->get($mysql->sql('SELECT `servid` FROM `servers` WHERE `name` = '.$mysql->escape($server)));
			$servid = $servid['servid'];
			$data = Array (
				'userid'	=> 'NULL',
				'nick'		=> $nick,
				'user'		=> $user,
				'host'		=> $host,
				'realname'	=> $real,
				'ip'		=> $ip,
				'servid'	=> $servid
			);
			$mysql->insert('users', $data);	
		}

		function event_quit ($nick,$message) {
			global $mysql;
			$userid = $mysql->get($mysql->sql('SELECT `userid` FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$userid = $userid['userid'];
			$mysql->sql('DELETE FROM `user_chan` WHERE `userid` = \''.$userid.'\'');
			$mysql->sql('DELETE FROM `users` WHERE `userid` = \''.$userid.'\'');
			$this->check_empty();
		}

		function event_kill ($from,$nick,$message) {
			global $mysql;
			$userid = $mysql->get($mysql->sql('SELECT `userid` FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$userid = $userid['userid'];
			$mysql->sql('DELETE FROM `user_chan` WHERE `userid` = \''.$userid.'\'');
			$mysql->sql('DELETE FROM `users` WHERE `userid` = \''.$userid.'\'');
			$this->check_empty();
		}

		function event_svskill ($from,$nick,$message) {
			global $mysql;
			$userid = $mysql->get($mysql->sql('SELECT `userid` FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$userid = $userid['userid'];
			$mysql->sql('DELETE FROM `user_chan` WHERE `userid` = \''.$userid.'\'');
			$mysql->sql('DELETE FROM `users` WHERE `userid` = \''.$userid.'\'');
			$this->check_empty();
		}

		function event_join ($nick,$channel) {
			global $mysql;
			$userid = $mysql->get($mysql->sql('SELECT `userid` FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$userid = $userid['userid'];
			$chanid = $mysql->get($mysql->sql('SELECT `chanid` FROM `channels` WHERE `name` = '.$mysql->escape($channel)));
			$chanid = $chanid['chanid'];
			if (!$chanid) {
				$data = Array (
					'chanid'	=> 'NULL',
					'name'		=> $channel
				);
				$mysql->insert('channels',$data);
				$chanid = $mysql->get($mysql->sql('SELECT `chanid` FROM `channels` WHERE `name` = '.$mysql->escape($channel)));
				$chanid = $chanid['chanid'];
				$data = Array (
					'id'		=> 'NULL',
					'userid'	=> $userid,
					'chanid'	=> $chanid,
					'modes'		=> 'o'
				);
				event('channel_create',$channel);
			} else {
				$data = Array (
					'id'		=> 'NULL',
					'userid'	=> $userid,
					'chanid'	=> $chanid
				);
			}
			$mysql->insert('user_chan',$data);
		}

		function event_sajoin ($from,$nick,$channel) {
			global $mysql;
			$userid = $mysql->get($mysql->sql('SELECT `userid` FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$userid = $userid['userid'];
			$chanid = $mysql->get($mysql->sql('SELECT `chanid` FROM `channels` WHERE `name` = '.$mysql->escape($channel)));
			$chanid = $chanid['chanid'];
			if (!$chanid) {
				$data = Array (
					'chanid'	=> 'NULL',
					'name'		=> $channel
				);
				$mysql->insert('channels',$data);
				$chanid = $mysql->get($mysql->sql('SELECT `chanid` FROM `channels` WHERE `name` = '.$mysql->escape($channel)));
				$chanid = $chanid['chanid'];
				$data = Array (
					'id'		=> 'NULL',
					'userid'	=> $userid,
					'chanid'	=> $chanid,
					'modes'		=> 'o'
				);
			} else {
				$data = Array (
					'id'		=> 'NULL',
					'userid'	=> $userid,
					'chanid'	=> $chanid
				);
			}
			$mysql->insert('user_chan',$data);
		}

		function event_part ($nick,$channel,$reason) {
			global $mysql;
			$userid = $mysql->get($mysql->sql('SELECT `userid` FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$userid = $userid['userid'];
			$chanid = $mysql->get($mysql->sql('SELECT `chanid` FROM `channels` WHERE `name` = '.$mysql->escape($channel)));
			$chanid = $chanid['chanid'];
			$mysql->sql('DELETE FROM `user_chan` WHERE `chanid` = \''.$chanid.'\' AND `userid` = \''.$userid.'\'');
			$this->check_empty();
		}

		function event_sapart ($from,$nick,$channel,$reason) {
			global $mysql;
			$userid = $mysql->get($mysql->sql('SELECT `userid` FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$userid = $userid['userid'];
			$chanid = $mysql->get($mysql->sql('SELECT `chanid` FROM `channels` WHERE `name` = '.$mysql->escape($channel)));
			$chanid = $chanid['chanid'];
			$mysql->sql('DELETE FROM `user_chan` WHERE `chanid` = \''.$chanid.'\' AND `userid` = \''.$userid.'\'');
			$this->check_empty();
		}

		function event_kick ($from,$nick,$channel,$reason) {
			global $mysql;
			$userid = $mysql->get($mysql->sql('SELECT `userid` FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$userid = $userid['userid'];
			$chanid = $mysql->get($mysql->sql('SELECT `chanid` FROM `channels` WHERE `name` = '.$mysql->escape($channel)));
			$chanid = $chanid['chanid'];
			$mysql->sql('DELETE FROM `user_chan` WHERE `chanid` = \''.$chanid.'\' AND `userid` = \''.$userid.'\'');
			$this->check_empty();
		}

/*		function event_mode ($from,$to,$mode) {
			global $mysql;
			if ('#' == mid($to,0,1)) {
				//channel mode
				
				
			} else {
				//user mode
				
			}
		}*/

		function event_nick ($from,$to) {
			global $mysql;
			$mysql->sql('UPDATE `users` SET `nick` = '.$mysql->escape($to).' WHERE `nick` = '.$mysql->escape($from));
		}
	}

	function registerm () {
		$class = new mysqlserv;
		register($class, __FILE__, 'MySQL Module', 'MySQL');
	}
?>
