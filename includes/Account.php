<?PHP
	class Account {
		protected $id;
		protected $username;
		protected $level;
		
		public static function create( $id, $userName, $password, $level = 1 ) {
			$insertData = Array( 
				'id' => 'NULL',
				'user' => $userName,
				'pass' => 'PASSWORD(' . $password . ')',
				'level' => $level 
			);
			
			MySQL::insert( 'access', $insertData );
			
			return self::newFromUsername( $userName );
		}
		
		public static function newFromId( $id ) {
			return self::newFrom( 'id', $id );
		}
		
		public static function newFromUsername( $userName ) {
			return self::newFrom( 'username', $userName );
		}
		
		protected static function newFrom( $field, $value ) {
			$userData = MySQL::get( MySQL::sql( 'SELECT * FROM `access` WHERE `' . $field . '` = ' . MySQL::escape( $value ) . ' LIMIT 1' ) );
			
			if( isset( $userData ) )
				return new self( $userData[ 'id' ], $userData[ 'username' ], $userData[ 'level' ] );
			
			return null;
		}
		
		protected function __construct( $id, $username, $level ) {
			$this->id = $id;
			$this->username = $username;
			$this->level = $level;
		}
		
		public function __set( $name, $value ) {
			switch( $name ) {
				
				case 'id':
					throw new Exception( 'Can not set id property.' );
					break;
				
				case 'username':
				case 'level':
					$this->update( $name, $value );
					break;
					
				case 'password':
					MySQL::sql( 'UPDATE `access` SET `password` = PASSWORD(' . MySQL::escape( $value ) . ') WHERE `id` = ' . MySQL::escape( $this->id ) );
					break;
					
				default:
					throw new Exception( 'Unknown property.' );
			}
		}
		
		public function __get( $name ) {
			switch( $name ) {
				
				case 'id':
					return $this->id;
				
				case 'username':
					return $this->username;
				
				case 'level':
					return $this->level;

				case 'properties':
					return $this->getProperties();
					
				case 'nicks':
					return $this->getNicks();
				
			}
		}
		
		protected function update( $name, $value ) {
			MySQL::sql( 'UPDATE `access` SET `' . $name . '` = ' . MySQL::escape( $value ) . ' WHERE `id` = ' . MySQL::escape( $this->id ) );
			$this->$name = $value;
		}
		
		protected function getProperties() {
			return AccountProperties::newFromAccount( $this );
		}
		
		protected function getNicks() {
			return AccountNicks::newFromAccount( $this );
		}
	}
?>