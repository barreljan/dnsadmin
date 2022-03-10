<?php
/*
 * Page by bartjan@is.nl, Dec 2017
 */

$cfg    = array();
$cfg['mysql_hostname']	= 'localhost';
$cfg['mysql_username']	= 'dnsadmin';
$cfg['mysql_password']	= '<sup3rsecrEt!>';
$cfg['mysql_database']	= 'dnsadmin';
$cfg['mail_to']	 	= 'johndoe@domain.tld';
$cfg['mail_from']	= 'noreply@domain.tld';
$cfg['debug']		= 0;
$cfg['dry_run']		= 0;

if($cfg['debug'] > 0) {
	ini_set('display_errors', '1');
	error_reporting(E_ALL);
}

/*************************************************************************************/

show_header();

$dbh    = connect_db();

if (isset($_POST) && sizeof($_POST) > 0){
	$submit_data=0;
	$recorddata = array();
	$recorddata['zone'] = '';
	$recorddata['hostname'] = '';
	$recorddata['value'] = '';
	$recorddata['type'] = '';
	$recorddata['pref'] = '';
	$recorddata['active'] = '';
	$recorddata['recordid'] = '';

	if(!empty($_POST['hostname'])) {
		$recorddata['hostname'] = htmlentities($_POST['hostname']);
		$submit_data++;
	}
	if(!empty($_POST['value'])) {
		$recorddata['value'] = htmlentities($_POST['value']);
		$submit_data++;
	}
	if(!empty($_POST['type'])) {
		$recorddata['type'] = htmlentities($_POST['type']);
		$submit_data++;
	}
	if(!empty($_POST['pref'])) {
		$recorddata['pref'] = htmlentities($_POST['pref']);
		$submit_data++;
	}
	if(!empty($_POST['active'])) {
		$recorddata['active'] = htmlentities($_POST['active']);
		$submit_data++;
	}
	if(!empty($_POST['zone'])) {
		$recorddata['zone'] = htmlentities($_POST['zone']);
		$submit_data++;
	}
	if(!empty($_POST['recordid'])) {
		$recorddata['recordid'] = htmlentities($_POST['recordid']);
		$submit_data++;
	}
	if($submit_data > 3) {
		process_input($recorddata);
	} else {
		//show_edit_form(null,null);
		print "Oops, input seems to be mostly empty!<br><br>\n";
		print "\t<a href=\"javascript:history.back()\">Go back...</a>\n";
	}
} elseif (isset($_GET['action']) && ($_GET['action'] == 'edit')) {
	$reqzone = $_GET['zone'];
	show_zones(get_available_zones());
	show_records(get_available_records($reqzone));
	if (isset($_GET['record'])) {
		show_edit_form(get_record($reqzone,$_GET['record']),"edit");
	} else {
		show_edit_form(null,null);
	}
} elseif (isset($_GET['action']) && ($_GET['action'] == 'delete')) {
	$reqzone = $_GET['zone'];
	delete_record($reqzone,$_GET['record']);
} else {
	show_zones(get_available_zones());
}

show_footer();
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

function show_header() {
	global $cfg;
	?>
<!DOCTYPE HTML>
<html>
 <head>
  <title>pc-mania DNS Admin</title>
   <link rel="stylesheet" type="text/css" media="all" href="./style.css"/>
 </head>
 <body>
  <table class="none" width="100%">
   <tr>
    <td width="60%"><b>DNS Admin - <?php echo date('Y-m-d H:i:s'); ?></b></td>
    <td align="right"><a href=".">home</a> | <a href=".">dance baby dance</a></td>
   </tr>
<?php if ($cfg['dry_run'] == 1) { echo "   <tr>\n    <td>!! Dry run enabled, no changes will be made on DNS servers !!</td>\n   </tr>\n"; } ?>
  </table>
  <br />
	<?php
}

function show_footer() {
	?>
 </body>
</html>
	<?php
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
	} else {
		print "Sorry... no zones found\n<br>\n";
	}
	$r_select->free_result();
	$q_select->close();

	return($zones);
}

function get_record($zone,$record) {
	global $dbh;

	$_zone = "`{$zone}`";
	$_record = "{$record}";
	$q_select = $dbh->prepare("SELECT id,zoneid,hostname,recordtype,recordvalue,pref,active FROM {$_zone} WHERE id = ?");
	$q_select->bind_param('s',$_record);
	$q_select->execute();
	$r_select = $q_select->get_result();

	$record       = array();
	if($r_select->num_rows > 0) {
		$record = $r_select->fetch_assoc();
	}
	$r_select->free_result();
	$q_select->close();

	return($record);
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

function show_records($records) {
	global $reqzone;

	$_php_self      = htmlentities($_SERVER['PHP_SELF']);
	if ((isset($_POST) && sizeof($_POST) > 0) && ($records == null)) {
		printf("<br/><br/>\n<a href=\"%s\">Go back...</a>",$_php_self);
		return;
	} elseif ($records == null) {
		print "<br>Sorry... no active records found. The zone seems to be empty. Add them below<br>\n";
		return;
	}
	print "\n<br><br>\n<table class=\"outline\">\n";
	print " <tr>\n";
	print "  <th><font size=\"1px\">Zone ID</font></th>\n";
	print "  <th><font size=\"1px\">Hostname</font></th>\n";
	print "  <th><font size=\"1px\">Record type</font></th>\n";
	print "  <th><font size=\"1px\">Preference</font></th>\n";
	print "  <th><font size=\"1px\">Record value</font></th>\n";
	print "  <th><font size=\"1px\">Active</font></th>\n";
	print "  <th style=\"text-align:center\"><font size=\"1px\">Action</font></th>\n";
	print " </tr>\n";
	$linetag = "0";
	foreach($records as $rc) {
		if ($linetag == "0") {
			$linetag = "1";
			$class = "grey";
		} else {
			$linetag = "0";
			$class = "";
		}
		print " <tr class=\"{$class}\">\n";
		printf("  <td>%s</td>\n",$rc['zoneid']);
		printf("  <td>%s</td>\n",$rc['hostname']);
		printf("  <td>%s</td>\n",strtoupper($rc['recordtype']));
		printf("  <td>%s</td>\n",$rc['pref']);
		printf("  <td>%s</td>\n",$rc['recordvalue']);
		if ($rc['active'] == "1") {
			$act = "yes";
		} else {
			$act = "no";
		}
		printf("  <td>%s</td>\n",$act);
		printf("  <td><a href=\"%s?action=edit&zone=%s&record=%s\">Edit</a> | <a href=\"%s?action=delete&zone=%s&record=%s\">Delete</a></td>\n",$_php_self,$reqzone,$rc['id'],$_php_self,$reqzone,$rc['id']);
		print " </tr>\n";

	}

	print "</table>\n";
}

function show_zones($zones) {
	if ((isset($_POST) && sizeof($_POST) > 0) && ($zones == null)) {
		printf("<br/><br/>\n<a href=\"%s\">Go back...</a>",htmlentities($_SERVER['PHP_SELF']));
		return;
	} elseif ($zones == null) {
		print "<br/><br/>\nSay what? No zones found?";
		return;
	}
	print "\n<br><br>\n<table class=\"outline\">\n";
	print " <tr>\n";
	print "  <th><font size=\"1px\">Zone ID</font></th>\n";
	print "  <th><font size=\"1px\">Zone Name</font></th>\n";
	print "  <th><font size=\"1px\">Active</font></th>\n";
	print "  <th><font size=\"1px\">Action</font></th>\n";
	print " </tr>\n";
	$linetag = "0";
	foreach($zones as $zn) {
		if ($linetag == "0") {
			$linetag = "1";
			$class = "grey";
		} else {
			$linetag = "0";
			$class = "";
		}
		print " <tr class=\"{$class}\">\n";
		printf("  <td>%s</td>\n",$zn['id']);
		printf("  <td>%s</td>\n",$zn['zonename']);
		if ($zn['active'] == "1") {
			$act = "yes";
		} else {
			$act = "no";
		}
		printf("  <td>%s</td>\n",$act);
		printf("  <td><a href=\"%s?action=edit&zone=%s\">Edit</a></td>\n",htmlentities($_SERVER['PHP_SELF']),$zn['zonename']);
		print " </tr>\n";

	}
	print "</table>\n";
}

function show_edit_form($record,$action) {
	//$hostname,$type,$pref,$value,$active,$action
	$hstn	= $record['hostname'];
	$type	= $record['recordtype'];
	$pref	= $record['pref'];
	$val	= $record['recordvalue'];
	$act	= $record['active'];

	$_php_self      = htmlentities($_SERVER['PHP_SELF']);
	
	print "\n<br><br>\n";
	print "<form action=\"$_php_self\" method=\"POST\"><table class=\"outline\">\n";
	print " <tr>\n";
	print "  <th><font size=\"1px\">Hostname</font></th>\n";
	print "  <th><font size=\"1px\">Record type</font></th>\n";
	print "  <th><font size=\"1px\">Preference</font></th>\n";
	print "  <th><font size=\"1px\">Record value</font></th>\n";
	print "  <th><font size=\"1px\">Active</font></th>\n";
	print " </tr>\n";
	print " <tr>\n";
	if ($action == "edit") {
		print "  <td><input type=\"text\" name=\"hostname\" value=\"{$hstn}\" autofocus></td>\n";
		print "  <td><select name=\"type\">\t<option value=\"A\"";
		if ($type == "A" || $type == "a") { print " selected"; }
		print ">A</option>\n\t<option value=\"AAAA\"";
		if ($type == "AAAA" || $type == "aaaa") { print " selected"; }
		print ">AAAA</option>\n\t<option value=\"MX\"";
		if ($type == "MX" || $type == "mx") { print " selected"; }
		print ">MX</option>\n\t<option value=\"PTR\"";
		if ($type == "PTR" || $type == "ptr") { print " selected"; }
		print ">PTR</option>\n  </select></td>\n";
		print "  <td><input type=\"text\" name=\"pref\" value=\"{$pref}\"";
		if ($pref == "" || empty($pref)) { print " readonly"; }
		print "></td>\n";
		print "  <td><input type=\"text\" name=\"value\" value=\"{$val}\"></td>\n";
		print "  <td><select name=\"active\">\n\t<option value=\"1\"";
		if ($act == "1") { print " selected"; }
		print ">Yes</option>\n\t<option value=\"0\"";
		if ($act == "0") { print " selected"; }
		print ">No</option>\n  </select> <input type=\"hidden\" name=\"zone\" value=\"{$_GET['zone']}\"> <input type=\"hidden\" name=\"recordid\" value=\"{$_GET['record']}\"></td>\n";
		print " </tr>\n <tr>\n";
		print "  <td><input type=\"submit\" name=\"MOD\" value=\"Modify\"></td>\n  <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>\n </tr>\n";
	} else {
		print "  <td><input type=\"text\" name=\"hostname\"></td>\n";
		print "  <td><select name=\"type\">\n\t<option value=\"A\">A</option>\n\t<option value=\"AAAA\">AAAA</option>\n\t<option value=\"MX\">MX</option>\n\t<option value=\"PTR\">PTR</option>\n  </select></td>\n";
		print "  <td><input type=\"text\" name=\"pref\"></td>\n";
		print "  <td><input type=\"text\" name=\"value\"></td>\n";
		print "  <td><select name=\"active\"><option value=\"1\">Yes</option><option value=\"0\">No</option></select> <input type=\"hidden\" name=\"zone\" value=\"{$_GET['zone']}\"></td>\n";
		print " </tr>\n";
		print " <tr><td><input type=\"submit\" name=\"ADD\" value=\"Add\"></td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>\n";
	}
	print "</table></form>\n";

}

function process_input($recorddata) {
	global $dbh;

	$_zone		= $recorddata['zone'];
	$_hostname	= strtolower($recorddata['hostname']);
	$_value		= $recorddata['value'];
	$_type		= strtoupper($recorddata['type']);
	if ($recorddata['pref'] == '') {
		$_pref	= null;
	} else {
		$_pref 	= $recorddata['pref']; }
	$_active 	= $recorddata['active'];
	$_recordid 	= $recorddata['recordid'];

	// get zone id
	$q_select = $dbh->prepare("SELECT id FROM `_zones` WHERE zonename = ?");
	$q_select->bind_param('s',$_zone);
	$q_select->execute();
	$r_select = $q_select->get_result();

	$zone       = array();
	if($r_select->num_rows > 0) {
		$zone = $r_select->fetch_assoc();
	} else {
		print "Sorry... no zone-id found\n<br>\n";
	}
	$q_select->close();
	$r_select->free_result();
	$_zoneid	= $zone['id'];

	if (!empty($_POST['ADD'])) {
		$q_select = $dbh->prepare("INSERT INTO `{$_zone}` (zoneid,hostname,recordtype,recordvalue,pref,active) VALUES (?,?,?,?,?,?)");
		$q_select->bind_param('ssssss',$_zoneid, $_hostname, $_type, $_value, $_pref, $_active);
		$q_select->execute();
		$q_select->close();

		set_changed_param($_zone);

	} elseif (!empty($_POST['MOD'])) {
		$q_select = $dbh->prepare("UPDATE `{$_zone}` SET hostname = ?, recordtype = ?, recordvalue = ?, pref = ?, active = ?, datemodified = current_timestamp() WHERE id = ?");
		$q_select->bind_param('ssssis',$_hostname, $_type, $_value, $_pref, $_active, $_recordid);
		$q_select->execute();
		$q_select->close();

		set_changed_param($_zone);
	}
}

function delete_record($zone,$record) {
	global $dbh;

	$_zone		= "{$zone}";
	$_record	= "{$record}";

	$q_select = $dbh->prepare("UPDATE `{$_zone}` SET active = '0', datedeleted = current_timestamp() WHERE id = ?");
	$q_select->bind_param('i',$_record);
	$q_select->execute();
	$q_select->close();

	set_changed_param($_zone);
}

function set_changed_param($_zone) {
	global $cfg,$dbh;

	$_php_self	= htmlentities($_SERVER['PHP_SELF']);

	if ($cfg['dry_run'] == 0) {
		$_value = "1";
		$q_select = $dbh->prepare("UPDATE `_update` SET server1 = ?, server2 = ?, changedate = current_timestamp() WHERE id = 1");
		$q_select->bind_param('ii',$_value,$_value);
		$q_select->execute();
		$q_select->close();
	}
	header("Location:{$_php_self}?action=edit&zone={$_zone}");
}

?>
