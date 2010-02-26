<?PHP
	class ChannelUsers {
		protected $userList; // Array of users and their mode
		protected $chanObj; // The channel object

		public static function newFromChannel( $chanObj ) {
			$chanData = MySQL::sql( 'SELECT * FROM `user_chan` WHERE `id` = ' . MySQL::escape( $chanObj->id ) );

			// There could be a channel object with no users. For example, InspIRCd's m_permchannels module. --SnoFox
			if( $chanData ) {
				while( $row = MySQL::get( $chanData ) ) {
					$userList[] = array( 'user' => User::newFromId( $row[ 'userid' ] ), 'modes' => $row[ 'modes' ] );
				}
				return new self( $userList, $chanObj );
			}
			return null;
		}
		
		protected function __construct( $userList, $chanObj ) {
			$this->userList = $userList;
			$this->chanObj = $chanObj;
		}
		
		protected function __get() {
			return $this->userList;
		}

		public function add( $userObj, $modes = '' ) {
			MySQL::insert( 'user_chan', array( 'chanid' => $this->chanObj->id, 'userid' => $userObj[ 'id' ], 'modes' => $modes );
			$userList[] = array( 'user' => $userObj, 'modes' => $modes );
		}

		public function remove( $userObj ) {
			MySQL::sql( 'DELETE FROM `user_chan` WHERE `chanid` = ' . MySQL::escape( $this->chanObj->id ) .
				' AND `userid` = ' . MySQL::escape( $userObj[ 'id' ] ) );
			foreach( $user as $this->userList ) {
				if ( $user[ 'id' ] == $userObj[ 'id' ] ) {
					// XXX: Delete stuff here
					break;
				}
			}	
		}

		public function update( $userid, $modes ) {
			MySQL::sql( 'UPDATE `user_chan` SET `modes` = ' . $modes . ' WHERE `chanid` = ' . MySQL::escape( $this->ChanObj->id ) .
				' AND `userid` = ' . MySQL::escape( $userid ) );
			foreach( $user as $this->userList ) {
				if ( $user[ 'id' ] == $userObj[ 'id' ] ) {
					// XXX: Update stuff here
					break;
				}
			}	
		}
	}
?>
