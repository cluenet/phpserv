<?PHP
	class User {
		protected $id;
		protected $nick;
		protected $user;
		protected $host;
		protected $realName;
		protected $modes;
		protected $snomask;
		protected $operFlags;
		protected $servicesWhois;
		protected $ip;
		protected $virtualHost;
		protected $server;
		protected $account;
		
		public static function newFromId( $id ) {
			return self::newFrom( 'id', $id );
		}
		
		public static function newFromNick( $nick ) {
			return self::newFrom( 'nick', $user );
		}
		
		protected static function newFrom( $field, $value ) {
			$userData = MySQL::get( MySQL::sql( 'SELECT * FROM `nicks` WHERE `' . $field . '` = ' . MySQL::escape( $value ) . ' LIMIT 1' ) );
			
			if( isset( $userData ) )
				return new self(
					$userData[ 'userid' ],
					$userData[ 'nick' ],
					$userData[ 'user' ],
					$userData[ 'host' ],
					$userData[ 'realname' ],
					$userData[ 'modes' ],
					$userData[ 'snomask' ],
					$userData[ 'oflags' ],
					$userData[ 'swhois' ],
					$userData[ 'ip' ],
					$userData[ 'vhost' ],
					Server::newFromId( $userData[ 'servid' ] ),
					Account::newFromId( $userData[ 'loggedin' ] )
				);
			
			return null;
		}
		
		protected function __construct( $id, $nick, $user, $host, $realName, $modes, $snomask, $operFlags, $servicesWhois, $ip, $virtualHost, $server, $account ) {
			$this->id = $id;
			$this->nick = $nick;
			$this->user = $user;
			$this->host = $host;
			$this->realName = $realName;
			$this->modes = $modes;
			$this->snomask = $snomask;
			$this->operFlags = $operFlags;
			$this->servicesWhois = $servicesWhois;
			$this->ip = $ip;
			$this->virtualHost = $virtualHost;
			$this->server = $server;
			$this->account = $account;
		}
		
		public function __set( $name, $value ) {
			
		}
		
		public function __get( $name ) {
			
		}
	}
?>