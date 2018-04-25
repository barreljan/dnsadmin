<?php
/*
 * Parser by bartjan@is.nl, Dec 2017
 * Written for output to be used by dnsmasq
 *
 * Note: only forward dns entries
 * Reverse/PTR not possible yet...
 */

$cfg    = array();
$cfg['mysql_hostname']	= 'localhost';
$cfg['mysql_username']	= 'dnsadmin';
$cfg['mysql_password']	= '5G1Zkft52HDYyddbRpQl';
$cfg['mysql_database']	= 'dnsadmin';
$cfg['mail_to']	 	= 'bartjan@pc-mania.nl';
$cfg['mail_from']	= 'dnsadmin@pc-mania.nl';
$cfg['outputfile']	= 'forwardrecords.conf';
$cfg['debug']		= 1;

if($cfg['debug'] > 0) {
	ini_set('display_errors', '1');
	error_reporting(E_ALL);
}

/*************************************************************************************/

$dbh	= connect_db();
$_file	= $cfg['outputfile'];
$data	= "";

foreach (get_available_zones() as $zone) {
	$_zone	= $zone['zonename'];

	foreach (get_available_records($_zone) as $record) {
		$_hostname	= $record['hostname'];
		$_value		= $record['recordvalue'];

		$data .= sprintf("address=/%s.%s/%s\n",$_hostname,$_zone,$_value);
	}
	//print $data;
}
file_put_contents($_file, $data);

disconnect_db($dbh);


/*************************************************************************************/

function connect_db() {
	global $cfg;

	$dbh = new mysqli($cfg['mysql_hostname'], $cfg['mysql_username'], $cfg['mysql_password'], $cfg['mysql_database']);
	if($dbh->connect_errno > 0){
		printf("<font class=\"error\"><b>ERROR:</b> Unable to connect to database: %s\n<br>\n", $dbh->connect_error);
		show_footer();
		exit;
	}
	$dbh->set_charset("utf8");

	return($dbh);
}

function disconnect_db($dbh) {
	$dbh->close();
}

function get_available_zones() {
	global $dbh;

	$q_select = $dbh->prepare("SELECT id,zonename,active FROM `_zones` WHERE active = 1 AND `datedeleted` IS NULL");

	$q_select->execute();
	$r_select = $q_select->get_result();

	$zones       = array();
	if($r_select->num_rows > 0) {
		while($row_select = $r_select->fetch_assoc()) {
			$zones[$row_select['id']]    = $row_select;
		}
	}
	$r_select->free_result();
	$q_select->close();

	return($zones);
}

function get_available_records($zone) {
	global $dbh;

	$_zone = "`{$zone}`";
	$q_select = $dbh->prepare("SELECT id,zoneid,hostname,recordtype,recordvalue,pref,active FROM {$_zone} WHERE `datedeleted` IS NULL ORDER BY `hostname`");

	$q_select->execute();
	$r_select = $q_select->get_result();

	$records       = array();
	if($r_select->num_rows > 0) {
		while($row_select = $r_select->fetch_assoc()) {
			$records[$row_select['id']]    = $row_select;
		}
	}
	$r_select->free_result();
	$q_select->close();

	return($records);
}

?>
