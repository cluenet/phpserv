<?PHP
class dagger {

function construct() {
  $this->event_eos();
}

function destruct() {
  $ircd = &ircd();
  $ircd->quit('Dagger', 'Module Unloaded.');
}

function event_msg ($from,$to,$message) {
  global $mysql;
  $ircd = &ircd();
  if ((strtolower($to) == '#dagger') or (strtolower($to) == '#fun')) {
/*

	$ircd->addserv		($name,$desc)
	$ircd->smo		($mode,$message)
	$ircd->addserv2serv	($new,$old,$desc)
	$ircd->ctcp		($src,$dest,$ctcp,$message = NULL)
	$ircd->ctcpreply	($src,$dest,$ctcp,$reply = NULL)
	$ircd->addnick		($server,$nick,$ident,$host,$name)
	$ircd->join		($nick,$chan)
	$ircd->part		($nick,$chan,$reason = NULL)
	$ircd->mode		($nick,$chan,$mode)
	$ircd->kick		($nick,$chan,$who,$reason)
	$ircd->invite		($nick,$chan,$who)
	$ircd->topic		($nick,$chan,$topic)
	$ircd->quit		($nick,$reason)
	$ircd->msg		($src,$dest,$message)
	$ircd->servmsg		($dest,$message)
	$ircd->notice		($src,$dest,$message)
	$ircd->servnotice	($dest,$message)
	$ircd->svsnick		($old,$new)
	$ircd->kill		($nick,$reason)
	$ircd->svskill		($nick,$reason)


	event_signon		($from,$user,$host,$real,$ip,$server)
	event_nick		($from,$to)
	event_quit		($nick,$message)
	event_join		($nick,$channel)
	event_part		($nick,$channel,$reason)
	event_kill		($from,$nick,$message)
	event_ctcp		($from,$to,$type,$msg)
	event_msg		($from,$to,$message)
	event_ctcpreply		($from,$to,$ctcp,$message = NULL)
	event_notice		($from,$to,$message)
	event_eos		()
	event_kick		($from,$nick,$channel,$reason)


	$mysql->getaccess($nick)
	$mysql->getsetting('server')

*/

if ($message == '.fortune') {
  $x = shell_exec('/usr/games/fortune');
  $x = explode("\n",str_replace("\r",'',$x));
  foreach ($x as $y) {
    $ircd->msg('Dagger',$to,$y);
  }
  unset($x);
  unset($y);
}
//

}
} 

function event_eos () {
global $mysql;
$ircd = &ircd();

$ircd->addnick($mysql->getsetting('server'),'Dagger','knife','dagger.cluenet.org','Dagger');
$ircd->join('Dagger','#Dagger,#fun');
}
}

function registerm () {
$class = new dagger;
register($class, __FILE__, 'DaggerBot Module', 'dagger');
}
?>
