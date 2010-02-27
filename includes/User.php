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
			switch( $name ) {
				case 'id':
				case 'ip':
				case 'server':
					throw new Exception( 'Cannot set ' . $name . 'property.' );
					break;
				case 'nick':
				case 'user':
				case 'host':
				case 'realName':
				case 'modes':
				case 'snomask':
				case 'operFlags':
				case 'servicesWhois':
				case 'virtualHost':
					$this->update( $name, $value );
					break;
				case 'account':
				// XXX: Update accounts with a new object? Or should we pull a new object ourself? --SnoFox
					MySQL::sql( 'UPDATE `access SET `loggedin` = ' . MySQL::escape( $value[ 'loggedin' ] ) );
					$this->account = $value
					break
				default:
					throw new Exception( 'Unknown property: ' . $name );
			}
		}
		
		public function __get( $name ) {
			switch( $name ) {
				case 'id':
				case 'nick':
				case 'user':
				case 'host':
				case 'realName':
				//case 'gecos': -- I think this should be used instead of real name (preference; less typing) --SnoFox
				case 'modes':
				case 'snomask':
				case 'operFlags':
				case 'servicesWhois':
				case 'ip':
				case 'vhost':
				case 'server':
				case 'account':
					return $this->$name;
				default:
					// XXX: Should we return null or throw an exception? --SnoFox
					return null;
			}
		}
		
		protected function update( $name, $value ) {
			MySQL::sql( 'UPDATE `access` SET `' . $name . '` = ' . MySQL::escape( $value ) . ' WHERE `id` = ' . MySQL::escape( $this->id ) );
			$this->$name = $value;
		}
	}
?>
