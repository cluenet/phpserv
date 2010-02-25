<?PHP
	class Channel {
		protected $id; // channel ID
		protected $name; // channel name
		protected $modes; // simple modes
		protected $topic; // topic stuff
		
		public static function newFromId( $id ) {
			return self::newFrom( 'id', $id );
		}
		
		public static function newFromName( $name ) {
			return self::newFrom( 'name', $name );
		}
		
		protected static function newFrom( $field, $value ) {
			$chanData = MySQL::get( MySQL::sql( 'SELECT * FROM `channels` WHERE ' . $field . ' = ' . MySQL::escape( $value ) ) );
			
			if( $chanData ) {
				return new self(
					$chanData[ 'chanid' ],
					$chanData[ 'name' ],
					$chanData[ 'topic' ],
					$chanData[ 'modes' ],
				);
			}
			
			return null;
		}
		
		protected function __construct( $id, $name, $modes, $topic ) {
			$this->id = $id;
			$this->name = $name;
			$this->modes = $modes;
			$this->topic = $topic;
		}
		
		protected function __set( $name, $value ) {
			switch( $name ) {
				case 'id':
				case 'name':
					throw new Exception( 'Cannot set ' . $name . ' property' );
					break;
				case 'modes':
				case 'topic':
					$this->update( $name, $value );
					break;
				default:
					throw new Exception( 'Unknown property.' );
			}
			
		}
		
		protected function __get( $name ) {
			switch( $name ) {
				case 'id':
					return $this->id;
					break;
				case 'name':
					return $this->name;
					break;
				case 'modes':
					return $this->modes;
					break;
				case 'topic':
					return $this->topic;
					break;
				default:
					return null;
					break;
			}
		}

		function getUsers() {
			return ChannelUsers::newFromChannel( $this );
		}
					
		protected function update( $name, $value ) {
			MySQL::sql( 'UPDATE `access` SET `' . $name . '` = ' . MySQL::escape( $value ) . ' WHERE `id` = ' . MySQL::escape( $this->id ) );
			$this->$name = $value;
		}
?>
