<?PHP
	class ChannelUsers {
		protected $userList; // Array of users and their mode
		protected $chanObj; // The channel object

		protected static function newFromChannel( $chanObj ) {
			$chanData = MySQL::sql( 'SELECT * FROM `user_chan` WHERE `id` = ' . MySQL::escape( $chanObj->id ) );

			// There could be a channel object with no users. For example, InspIRCd's m_permchannels module. --SnoFox
			if( $chanData ) {
				while( $thisUser = MySQL::get( $chanData ) ) {
					$userList[] = array( User::newFromId( $thisUser[ 'userid' ] ), thisUser[ 'modes' ] );
					if (!isset( $chanId ) )
						$chanId = $thisUser[ 'chanid' ];
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

		// Not sure what these are supposed to be using to update `user_chan`, but a user ID was easiest
		public function add( $userid, $modes = '' ) {
			MySQL::insert( 'user_chan', array( 'userid' => $userid, 'modes' => $modes );
		}

		public function remove( $userid ) {
			MySQL::sql( 'DELETE FROM `user_chan` WHERE `chanid` = ' . MySQL::escape( $this->chanObj->id ) .
				' AND `userid` = ' . MySQL::escape( $userid ) );
		}

		public function update( $userid, $modes ) {
			MySQL::sql( 'UPDATE `user_chan` SET `modes` = ' . $modes . ' WHERE `chanid` = ' . MySQL::escape( $this->ChanObj->chanid ) .
				' AND `userid` = ' . MySQL::escape( $userid ) );
		}
	}
?>
