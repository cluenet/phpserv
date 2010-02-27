<?PHP
	class ChannelUsers {
		protected $userList; // Array of users and their mode
		protected $chanObj; // The channel object

		public static function newFromChannel( $chanObj ) {
			$chanData = MySQL::sql( 'SELECT * FROM `user_chan` WHERE `id` = ' . MySQL::escape( $chanObj->id ) );
			$userList = Array();

			// There could be a channel object with no users. For example, InspIRCd's m_permchannels module. --SnoFox
			if( $chanData ) {
				while( $row = MySQL::get( $chanData ) )
					$userList[] = Array(
						'user' => User::newFromId( $row[ 'userid' ] ),
						'modes' => $row[ 'modes' ]
					);
				return new self( $userList, $chanObj );
			}
			return null;
		}
		
		protected function __construct( $userList, $chanObj ) {
			$this->userList = $userList;
			$this->chanObj = $chanObj;
		}
		
		protected function __get( $name ) {
			switch( $name ) {
				case 'asArray':
					return $this->userList;
				default:
					throw new Exception( 'No such property.' );
			}
		}

		public function add( $userObj, $modes = '' ) {
			MySQL::insert(
				'user_chan',
				Array(
					'chanid' => $this->chanObj->id,
					'userid' => $userObj[ 'id' ],
					'modes' => $modes
				)
			);
			$this->userList[] = Array( 'user' => $userObj, 'modes' => $modes );
		}

		public function remove( $userObj ) {
			// Note: If you iterate through this oddly without recreating the object, PHP errors will arise.
			// So please, just use foreach ... --SnoFox
			MySQL::sql(
				'DELETE FROM `user_chan` '
					. 'WHERE `chanid` = ' . MySQL::escape( $this->chanObj->id )
					. ' AND `userid` = ' . MySQL::escape( $userObj[ 'id' ] )
			);
			
			foreach( $this->userList as $key => $data )
				if ( $data[ 'user' ]->id == $userObj->id ) {
					unset( $this->userList[ $key ] );
					break;
				}
		}

		public function update( $userid, $modes ) {
			MySQL::sql(
				'UPDATE `user_chan` '
					. 'SET `modes` = ' . $modes
					. ' WHERE `chanid` = ' . MySQL::escape( $this->ChanObj->id )
					. ' AND `userid` = ' . MySQL::escape( $userid )
			);
			foreach( $this->userList as $key => $data )
				if ( $data[ 'user' ]->id == $userObj->id ) {
					$this->userList[ $key ][ 'modes' ] = $modes;
					break;
				}
		}
	}
?>
