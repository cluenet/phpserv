<?PHP
	class Database {
	
		protected static $conn;
	
		private static $db_host;
		private static $db_port;
		private static $db_user;
		private static $db_pass;
		private static $db_db;
	
		public static function settings( $db_host, $db_port, $db_user, $db_pass, $db_db ) {
			self::$db_host = $db_host;
			self::$db_port = $db_port;
			self::$db_user = $db_user;
			self::$db_pass = $db_pass;
			self::$db_db = $db_db;
		}
	
		public static function connect() {
			
			echo 'connecting to ' . self::$db_host . ' ...';
			
			self::$conn = mysql_connect( self::$db_host . ':' . self::$db_port, self::$db_user, self::$db_pass, true );
			
			if( !self::$conn ) {
				logit( 'Not connected : ' . mysql_error() );
				return false;
			}
			
			if( !mysql_select_db( self::$db_db, self::$conn ) ) {
				logit( 'Can\'t use ' . self::$db_db . ' : ' . mysql_error() );
				return false;
			}
			
			return true;
		}
	
		public static function escape( $data ) {
			
			if( get_magic_quotes_gpc() )
				$data = stripslashes( $data );
			
			if( !is_numeric( $data ) )
				$data = "'" . mysql_real_escape_string( $data, self::$conn ) . "'";
			
			return $data;
		}
	
		public static function sql( $sql, $tryagain = true ) {
			
			mysql_close( self::$conn );
			
			while( !self::connect() )
				usleep( 100 );
			
			$ret = mysql_query( $sql );
			
			if( mysql_error() or !$ret )
				if( mysql_error() == 'Lost connection to MySQL server during query' and $tryagain ) {
					while( !self::connect() )
						usleep( 100 );
					return self::sql( $sql, false );
				} else
					logit( "MySQL Error: " . mysql_error() . "\nSQL: " . $sql );
			
			return $ret;
		}
	
		public static function insert( $tbl, $data, $onDuplicate = false ) {
			
			$sql = 'INSERT INTO `' . $tbl . '`';
			
			foreach( $data as $name => $val ) {
				
				$names[] = '`' . $name . '`';
				
				if( $val == 'NULL' )
					$values[] = 'NULL';
				
				else if( substr( $val, 0, 9 ) == 'PASSWORD(' )
					$values[] = 'PASSWORD(' . self::escape( substr( $val, 9, -1 ) ) . ')';
				
				else
					$values[] = self::escape( $val );
				
			}
			
			$names = implode( ',', $names );
			$values = implode( ',', $values );
			
			$sql .= ' (' . $names . ') VALUES (' . $values . ')';
			
			if( $onDuplicate ) {
				
				$sql .= ' ON DUPLICATE KEY UPDATE ';
				$update = array();
				
				foreach( $data as $name => $value )
					$update[] = '`' . $name . '`=VALUES(`' . $name . '`)';
				
				$sql .= implode( ', ', $update );
			}
			
			return self::sql( $sql );
		}
		
		public static function getIdFromNick( $nick ) {
			$accessId = self::get( self::sql( 'SELECT `loggedin` FROM `users` WHERE `nick` = ' . self::escape( $nick ) ) );
			return $accessId[ 'loggedin' ];
		}
	
		public static function loginAccess( $who, $user, $pass ) {
			$access = self::get( self::sql( 'SELECT * FROM `access` WHERE `user` = ' . self::escape( $user ) . ' AND `pass` = PASSWORD(' . self::escape( $pass ) . ')' ) );
			if( is_array( $access ) ) {
				self::sql( 'UPDATE `users` SET `loggedin` = ' . self::escape( $access[ 'id' ] ) . ' WHERE `nick` = ' . self::escape( $who ) );
				event( 'identify', $who, $access[ 'id' ] );
				return true;
			} else {
				return false;
			}
		}
	
		public static function logoutAccess( $who ) {
			$user = self::get( self::sql( 'SELECT * FROM `users` WHERE `nick` = ' . self::escape( $who ) ) );
			self::sql( 'UPDATE `users` SET `loggedin` = -1 WHERE `nick` = ' . self::escape( $who ) );
			event( 'logout', $who, $user );
			return true;
		}
	
		public static function get( $resource ) {
			return mysql_fetch_array( $resource );
		}
	
		public static function init() {
			self::sql( 'DELETE FROM `users`' );
			self::sql( 'DELETE FROM `user_chan`' );
			self::sql( 'DELETE FROM `channels`' );
			self::sql( 'DELETE FROM `servers`' );
		}
	
		public static function getSetting( $name, $section = 'core' ) {
			$tmp = self::sql( 'select `value` from `settings` where `name` = ' . self::escape( $name ) . ' and `section` = ' . self::escape( $section ) );
			$val = self::get( $tmp );
			$val = $val[ 'value' ];
			return $val;
		}
	
		public static function setSetting( $name, $value, $section = 'core' ) {
			
			if( self::issetting( $name, $section ) )
				self::sql( 'update `settings` set `value` = ' . self::escape( $value ) . ' where `name` = ' . self::escape( $name ) . ' and `section` = ' . self::escape( $section ) );
			
			else
				self::addsetting( $name, $value, $section );
			
		}
	
		public static function addSetting( $name, $value, $section = 'core' ) {
			self::sql( 'insert into `settings` (`name`,`value`,`section`) values (' . self::escape( $name ) . ',' . self::escape( $value ) . ',' . self::escape( $section ) . ')' );
		}
	
		public static function delSetting( $name, $section = 'core' ) {
			self::sql( 'delete from `settings` where `name` = ' . self::escape( $name ) . ' and `section` = ' . self::escape( $section ) );
		}
	
		public static function isSetting( $name, $section = 'core' ) {
			if( self::get( self::sql( 'select `id` from `settings` where `name` = ' . self::escape( $name ) . ' and `section` = ' . self::escape( $section ) ) ) === false )
				return false;
			
			return true;
		}
	

	}
?>