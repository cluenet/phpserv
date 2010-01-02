<?PHP
	class mysql {
		protected static $conn;

		function connect () {
			global $db_host;
			global $db_port;
			global $db_user;
			global $db_pass;
			global $db_db;
			$this->conn = mysql_connect($db_host.':'.$db_port, $db_user, $db_pass);
			if (!$this->conn) {
				die('Not connected : ' . mysql_error());
			}
			if (!mysql_select_db($db_db, $this->conn)) {
				die ('Can\'t use '.$db_db.' : ' . mysql_error());
			}
		}

		function escape ($data) {
			if (get_magic_quotes_gpc()) {
				$data = stripslashes($data);
			}
			if (!is_numeric($data)) {
				$data = "'".mysql_real_escape_string($data, $this->conn)."'";
			}
			return $data;
		}

		function sql ($sql) {
			$ret = mysql_query($sql);
			if (mysql_error()) {
				logit("MySQL Error: ".mysql_error()."\nSQL: ".$sql);
			}
			return $ret;
		}

		function insert ($tbl,$data) {
			$sql = 'INSERT INTO `'.$tbl.'`';
			foreach ($data as $name => $val) {
				$names[] = '`'.$name.'`';
				if ($val == 'NULL') {
					$values[] = 'NULL';
				} elseif (substr($val,0,9) == 'PASSWORD(') {
					$values[] = 'PASSWORD('.$this->escape(substr($val,9,-1)).')';
				} else {
					$values[] = $this->escape($val);
				}
			}
			$names = implode(',',$names);
			$values = implode(',',$values);
			$sql .= ' ('.$names.') VALUES ('.$values.')';
			return $this->sql($sql);
		}

		function getaccess ($nick) {
			$accessid = $this->get($this->sql('SELECT `loggedin` FROM `users` WHERE `nick` = '.$this->escape($nick)));
			$accessid = $accessid['loggedin'];
			if ($accessid != -1) {
				$level = $this->get($this->sql('SELECT `level` FROM `access` WHERE `id` = '.$accessid));
			} else {
				$level = 0;
			}
			return $level['level'];
		}

		function setaccess ($id,$level) {
			$this->sql('UPDATE `access` SET `level` = '.$this->escape($level).' WHERE `id` = '.$this->escape($id));
		}

		function addaccess ($user,$pass,$level) {
			$data = Array(
				'id'	=> 'NULL',
				'user'	=> $user,
				'pass'	=> 'PASSWORD('.$pass.')',
				'level'	=> $level
			);
			$this->insert('access',$data);
		}

		function loginaccess ($who,$user,$pass) {
			$access = $this->get($this->sql('SELECT * FROM `access` WHERE `user` = '.$this->escape($user).' AND `pass` = PASSWORD('.$this->escape($pass).')'));
			if ($access) {
				$this->sql('UPDATE `users` SET `loggedin` = '.$this->escape($access['id']).' WHERE `nick` = '.$this->escape($who));
				return true;
			} else {
				return false;
			}
		}

		function logoutaccess ($who) {
			$this->sql('UPDATE `users` SET `loggedin` = -1 WHERE `nick` = '.$this->escape($who));
			return true;
		}

		function get ($resource) {
			return mysql_fetch_array($resource);
		}

		function init () {
			$this->sql('DELETE FROM `users`');
			$this->sql('DELETE FROM `user_chan`');
			$this->sql('DELETE FROM `channels`');
			$this->sql('DELETE FROM `servers`');
		}

		function getsetting ($name) {
			$tmp = $this->sql('select `value` from `settings` where `name` = '.$this->escape($name));
			$val = $this->get($tmp);
			$val = $val['value'];
			return $val;
		}

		function setsetting ($name,$value) {
			$tmp = $this->sql('update `settings` set `value` = '.$this->escape($value).' where `name` = '.$this->escape($name));
		}

		function addsetting ($name,$value) {
			$tmp = $this->sql('insert into `settings` (`name`,`value`) values ('.$this->escape($name).','.$this->escape($value).')');
		}

		function delsetting ($name) {
			$tmp = $this->sql('delete from `settings` where `name` = '.$this->escape($name));
		}

	}
?>
