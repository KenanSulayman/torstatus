<?php 

// Copyright (c) 2006-2007, Joseph B. Kowalski
// See LICENSE for licensing information 

// Start new session
session_start();

// Include configuration settings
include("config.php");

// Declare and initialize variables
$LastUpdate = null;
$LastUpdateElapsed = null;
$ActiveNetworkStatusTable = null;
$ActiveDescriptorTable = null;

$HeaderRowString = "";

$Name = null;
$CountryCode = null;
$IP = null;
$Hostname = null;
$ORPort = null;
$DirPort = null;
$Fingerprint = null;
$Platform = null;
$LastDescriptorPublished = null;
$OnionKey = null;
$SigningKey = null;
$Contact = null;
$DescriptorSignature = null;

$RouterCount = 0;
$DescriptorCount = 0;
$CurrentResultSet = 0;
$RowCounter = 0;

$Self = $_SERVER['PHP_SELF'];
$Host = $_SERVER['HTTP_HOST'];
$forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'];
$xff = array_map( 'trim', explode( ',',$forwardedFor ) );
$xff = array_reverse( $xff );
if ($UsingSquid == 1)
{
	$ServerIP = $RealServerIP;
	$RemoteIP = $xff[0];
}
else
{
	$ServerIP = $_SERVER['SERVER_ADDR'];
	$RemoteIP = $_SERVER['REMOTE_ADDR'];
}
$ServerPort = $_SERVER['SERVER_PORT'];
$RemoteIPDBCount = null;
$PositiveMatch_IP = 0;
$PositiveMatch_ExitPolicy = null;
$TorNodeName = null;
$TorNodeFP = null;
$TorNodeExitPolicy = null;

$Count = 0;

$ColumnList_ACTIVE = null;
$ColumnList_INACTIVE = null;
$SR = null;
$SO = null;
$FAuthority = null;
$FBadDirectory = null;
$FBadExit = null;
$FExit = null;
$FFast = null;
$FGuard = null;
$FHibernating = null;
$FNamed = null;
$FStable = null;
$FRunning = null;
$FValid = null;
$FV2Dir = null;
$CSField = null;
$CSMod = null;
$CSInput = null;

// Function Declarations
function IsIPInSubnet($IP,$Subnet)
{
	// Credit for the parts of the code in this function:
	// This code used in this function was found on the PHP.net website's 'IP2Long' function page.
	// It was posted by 'Ian B' on '24-Dec-2006 04:22'.

	/* always return true if subnet is wildcard */
	if ($Subnet == '*')
	{
		return 1;
	}

	/* always return true if ip is an exact match as is */
	if ($Subnet == $IP)
	{
		return 1;
	}

	/* always return false if only an ip was provided, and it's not an exact match */
	if (strpos($Subnet, '/') === FALSE)
	{
		return 0;
	}

       /* get the base and the bits from the subnet */
       list($base, $bits) = explode('/', $Subnet);

       /* now split it up into it's classes */
       list($a, $b, $c, $d) = explode('.', $base);

       /* now do some bit shifting/switching to convert to ints */
       $i = ($a << 24) + ($b << 16) + ($c << 8) + $d;
       $mask = $bits == 0 ? 0 : (~0 << (32 - $bits));

       /* here's our lowest int */
       $low = $i & $mask;

       /* here's our highest int */
       $high = $i | (~$mask & 0xFFFFFFFF);

       /* now split the ip we're checking against up into classes */
       list($a, $b, $c, $d) = explode('.', $IP);

       /* now convert the ip we're checking against to an int */
       $check = ($a << 24) + ($b << 16) + ($c << 8) + $d;

       /* if the ip is within the range, including highest/lowest values, then it's within the subnet range */
       if ($check >= $low && $check <= $high)
	{
		return 1;
	}
       else
	{
		return 0;
	}
}

function GenerateHeaderRow()
{
	global 	
			$HeaderRowString, 
			$Self,
			$ColumnList_ACTIVE, 
			$SR,
			$SO,
			$FAuthority,
			$FBadDirectory,
			$FBadExit,
			$FExit,
			$FFast,
			$FGuard,
			$FHibernating,
			$FNamed,
			$FStable,
			$FRunning,
			$FValid,
			$FV2Dir;

	$HeaderRowString .= "<tr>\n";

	$ccso = "&nbsp;<a href='$Self?SR=CountryCode&amp;SO=Asc'>&#9662;</a>&nbsp;&nbsp;";
	if ($SR == 'CountryCode' && $SO == 'Asc') { $ccso = "&nbsp;<a href='$Self?SR=CountryCode&amp;SO=Desc'>&#9652;</a>&nbsp;&nbsp;"; }

	if($SR == 'Name'){$HeaderRowString .= "<td class='HRS'>$ccso";} else{$HeaderRowString .= "<td class='HRN'>$ccso";} 
	if ($SR == 'Name' && $SO == 'Asc'){$HeaderRowString .= "<a href='$Self?SR=Name&amp;SO=Desc'>&#9662;";}
	else if ($SR == 'Name' && $SO == 'Desc'){$HeaderRowString .= "<a href='$Self?SR=Name&amp;SO=Asc'>&#9652;";}
	else $HeaderRowString .= "<a href='$Self?SR=Name&amp;SO=Asc'>&#9662;";
	$HeaderRowString .= "&nbsp;Router Name</a></td>\n";

	foreach($ColumnList_ACTIVE as $value)
	{
		switch ($value)
		{
			case "Fingerprint":
   			if($SR == 'Fingerprint'){$HeaderRowString .= "<td class='HRS'>";} else{$HeaderRowString .= "<td class='HRN'>";} 
			if ($SR == 'Fingerprint' && $SO == 'Asc'){$HeaderRowString .= "<a href='$Self?SR=Fingerprint&amp;SO=Desc'>";}
			else if ($SR == 'Fingerprint' && $SO == 'Desc'){$HeaderRowString .= "<a href='$Self?SR=Fingerprint&amp;SO=Asc'>";}
			else $HeaderRowString .= "<a href='$Self?SR=Fingerprint&amp;SO=Asc'>";
			$HeaderRowString .= "Fingerprint</a></td>\n";
   			break;

			case "Bandwidth":
			if($SR == 'Bandwidth'){$HeaderRowString .= "<td class='HRS'>";} else{$HeaderRowString .= "<td class='HRN'>";} 
			if ($SR == 'Bandwidth' && $SO == 'Asc'){$HeaderRowString .= "<a href='$Self?SR=Bandwidth&amp;SO=Desc'>&#9652;";}
			else if ($SR == 'Bandwidth' && $SO == 'Desc'){$HeaderRowString .= "<a href='$Self?SR=Bandwidth&amp;SO=Asc'>&#9662;";}
			else $HeaderRowString .= "<a href='$Self?SR=Bandwidth&amp;SO=Desc'>&#9652;";
			$HeaderRowString .= "&nbsp;Bandwidth <span class='TRSM'>(KB/s)</span></a></td>\n";
			break;

			case "Uptime":
			if($SR == 'Uptime'){$HeaderRowString .= "<td class='HRS'>";} else{$HeaderRowString .= "<td class='HRN'>";} 
			if ($SR == 'Uptime' && $SO == 'Asc'){$HeaderRowString .= "<a href='$Self?SR=Uptime&amp;SO=Desc'>&#9652;";}
			else if ($SR == 'Uptime' && $SO == 'Desc'){$HeaderRowString .= "<a href='$Self?SR=Uptime&amp;SO=Asc'>&#9662;";}
			else $HeaderRowString .= "<a href='$Self?SR=Uptime&amp;SO=Desc'>&#9652;";
			$HeaderRowString .= " Uptime</a></td>\n";
			break;

			case "LastDescriptorPublished":
			if($SR == 'LastDescriptorPublished'){$HeaderRowString .= "<td class='HRS'>";} else{$HeaderRowString .= "<td class='HRN'>";} 
			if ($SR == 'LastDescriptorPublished' && $SO == 'Asc'){$HeaderRowString .= "<a href='$Self?SR=LastDescriptorPublished&amp;SO=Desc'>";}
			else if ($SR == 'LastDescriptorPublished' && $SO == 'Desc'){$HeaderRowString .= "<a href='$Self?SR=LastDescriptorPublished&amp;SO=Asc'>";}
			else $HeaderRowString .= "<a href='$Self?SR=LastDescriptorPublished&amp;SO=Asc'>";
			$HeaderRowString .= "Last Descriptor<br/><span class='TRSM'>(GMT)</span></a></td>\n";
			break;

			case "Hostname":
			if($SR == 'Hostname'){$HeaderRowString .= "<td class='HRS'>";} else{$HeaderRowString .= "<td class='HRN'>";} 
			if ($SR == 'Hostname' && $SO == 'Asc'){$HeaderRowString .= "<a href='$Self?SR=Hostname&amp;SO=Desc'>&#9652;";}
			else if ($SR == 'Hostname' && $SO == 'Desc'){$HeaderRowString .= "<a href='$Self?SR=Hostname&amp;SO=Asc'>&#9662;";}
			else $HeaderRowString .= "<a href='$Self?SR=Hostname&amp;SO=Asc'>&#9662;";
			$HeaderRowString .= " Hostname</a></td>\n";
			break;

			case "ORPort":
			if($SR == 'ORPort'){$HeaderRowString .= "<td class='HRS'>";} else{$HeaderRowString .= "<td class='HRN'>";} 
			if ($SR == 'ORPort' && $SO == 'Asc'){$HeaderRowString .= "<a href='$Self?SR=ORPort&amp;SO=Desc'>&#9652;";}
			else if ($SR == 'ORPort' && $SO == 'Desc'){$HeaderRowString .= "<a href='$Self?SR=ORPort&amp;SO=Asc'>&#9662;";}
			else $HeaderRowString .= "<a href='$Self?SR=ORPort&amp;SO=Asc'>&#9662;";
			$HeaderRowString .= " ORPort</a></td>\n";
			break;

			case "DirPort":
			if($SR == 'DirPort'){$HeaderRowString .= "<td class='HRS'>";} else{$HeaderRowString .= "<td class='HRN'>";} 
			if ($SR == 'DirPort' && $SO == 'Asc'){$HeaderRowString .= "<a href='$Self?SR=DirPort&amp;SO=Desc'>&#9652;";}
			else if ($SR == 'DirPort' && $SO == 'Desc'){$HeaderRowString .= "<a href='$Self?SR=DirPort&amp;SO=Asc'>&#9662;";}
			else $HeaderRowString .= "<a href='$Self?SR=DirPort&amp;SO=Asc'>&#9662;";
			$HeaderRowString .= " DirPort</a></td>\n";
			break;

			case "Contact":
			if($SR == 'Contact'){$HeaderRowString .= "<td class='HRS'>";} else{$HeaderRowString .= "<td class='HRN'>";} 
			if ($SR == 'Contact' && $SO == 'Asc'){$HeaderRowString .= "<a href='$Self?SR=Contact&amp;SO=Desc'>";}
			else if ($SR == 'Contact' && $SO == 'Desc'){$HeaderRowString .= "<a href='$Self?SR=Contact&amp;SO=Asc'>";}
			else $HeaderRowString .= "<a href='$Self?SR=Contact&amp;SO=Asc'>";
			$HeaderRowString .= "Contact</a></td>\n";
			break;

			case "BadDir":
			if(($FBadDirectory == '0') && ($SR == 'FBadDirectory'))
			{
				$HeaderRowString .= "<td class='HRFNOS'>";
			} 
			else if(($FBadDirectory == '0') && ($SR != 'FBadDirectory'))
			{
				$HeaderRowString .= "<td class='HRFNO'>";
			}
			else if(($FBadDirectory == '1') && ($SR == 'FBadDirectory'))
			{
				$HeaderRowString .= "<td class='HRFYESS'>";
			}
			else if(($FBadDirectory == '1') && ($SR != 'FBadDirectory'))
			{
				$HeaderRowString .= "<td class='HRFYES'>";
			}
			else if(($FBadDirectory == 'OFF') && ($SR == 'FBadDirectory'))
			{
				$HeaderRowString .= "<td class='HRS'>";
			}
			else
			{
				$HeaderRowString .= "<td class='HRN'>";
			}
			if ($SR == 'FBadDirectory' && $SO == 'Asc'){$HeaderRowString .= "<a href='$Self?SR=FBadDirectory&amp;SO=Desc'>";}
			else if ($SR == 'FBadDirectory' && $SO == 'Desc'){$HeaderRowString .= "<a href='$Self?SR=FBadDirectory&amp;SO=Asc'>";}
			else $HeaderRowString .= "<a href='$Self?SR=FBadDirectory&amp;SO=Asc'>";
			$HeaderRowString .= "Bad Dir</a></td>\n";
			break;

			case "BadExit":
			if(($FBadExit == '0') && ($SR == 'FBadExit'))
			{
				$HeaderRowString .= "<td class='HRFNOS'>";
			} 
			else if(($FBadExit == '0') && ($SR != 'FBadExit'))
			{
				$HeaderRowString .= "<td class='HRFNO'>";
			}
			else if(($FBadExit == '1') && ($SR == 'FBadExit'))
			{
				$HeaderRowString .= "<td class='HRFYESS'>";
			}
			else if(($FBadExit == '1') && ($SR != 'FBadExit'))
			{
				$HeaderRowString .= "<td class='HRFYES'>";
			}
			else if(($FBadExit == 'OFF') && ($SR == 'FBadExit'))
			{
				$HeaderRowString .= "<td class='HRS'>";
			}
			else
			{
				$HeaderRowString .= "<td class='HRN'>";
			}
			if ($SR == 'FBadExit' && $SO == 'Asc'){$HeaderRowString .= "<a href='$Self?SR=FBadExit&amp;SO=Desc'>";}
			else if ($SR == 'FBadExit' && $SO == 'Desc'){$HeaderRowString .= "<a href='$Self?SR=FBadExit&amp;SO=Asc'>";}
			else $HeaderRowString .= "<a href='$Self?SR=FBadExit&amp;SO=Asc'>";
			$HeaderRowString .= "Bad Exit</a></td>\n";
			break;

		}
	}
	
	$HeaderRowString .= "</tr>\n";
}

function DisplayRouterRow()
{
	global $CurrentResultSet, $record, $ColumnList_ACTIVE;
	if ($record['BadExit'])
	{
		echo "<tr class='B'>";
	}
	else
	{
	if ($record['Running'] == 0 && $record['Hibernating'] == 0)
	{
		echo "<tr class='d'>";
	}
	else if ($record['Running'] == 0 && $record['Hibernating'] == 1)
	{
		echo "<tr class='R'>";
	}
	else
	{
		echo "<tr class='r'>";
	}
	}

	if ($record['Named'] == 1)
	{
		echo "<td class='TRR'>";
	}
	else
	{
		echo "<td class='TRr'>";
	}
	$countrycode = strtolower($record['CountryCode']);
	if ($countrycode == "") { $countrycode = "na"; }
	echo "<img src='img/flags/".$countrycode.".gif' class='flag' width='18px' alt='".$record['CountryCode']."' title='".$record['CountryCode']."'/>&nbsp;<a href='router_detail.php?FP=" . $record['Fingerprint'] . "'>" . $record['Name'] . "</a></td>";
	foreach($ColumnList_ACTIVE as $value)
	{
		switch (TRUE) 
		{
			case
			($value == "Hostname"):
			echo "<td class='TDS'>";
			$innerTable = 0;
			if (isset($record['Authority']) || isset($record['Stable']) || isset($record['Platform']) || isset($record['Guard']) || isset($record['Fast']) || isset($record['Exit']) || isset($record['V2Dir']) || isset($record['Valid']))
			{
				$innerTable = 1;
				echo "<table class='iT'><tr><td class='iT'>";
			}
			echo $record[$value];
			if (isset($record['IP']))
			{
				if (defined("WHOISPath"))
				{
				echo " [<a class='who' href='".WHOISPath.$record['IP']."'>".$record['IP']."</a>]";
				}
				else
				{
				echo " [".$record['IP']."]";
				}
			}
			if ($record['Fast'] == 1)
			{
				echo "</td><td><img src='img/status/Fast.png' title='Fast Server' alt='Fast Server' />";
			}
			if ($record['Valid'] == 0)
			{
				echo "</td><td><img src='img/status/Disputed.png' title='Not Listed By All Directory Servers' alt='Disputed Server' />";
			}
			if ($record['Exit'] == 1)
			{
				echo "</td><td><img src='img/status/Exit.png' title='Exit Server' alt='Exit Server' />";
			}
			if ($record['V2Dir'] == 1)
			{
				echo "</td><td><img src='img/status/Dir.png' title='Directory Server' alt='Directory Server' />";
			}
			if ($record['Guard'] == 1)
			{
				echo "</td><td><img src='img/status/Guard.png' title='Guard Server' alt='Guard Server' />";
			}
			if ($record['Stable'] == 1)
			{
				echo "</td><td><img src='img/status/Stable.png' title='Stable Server' alt='Stable Server'/>";
			}
			if ($record['Authority'] == 1)
			{
				echo "</td><td><img src='img/status/Authority.png' title='Authority Server' alt='Authority Server'/>";
			}
			if (isset($record['Platform']))
			{
				$image = "NotAvailable";
				// Map the platform to something we know
				if (strpos($record['Platform'],'Linux',$record['Platform']) || strpos($record['Platform'],'linux',$record['Platform']))
				{
					$image = "Linux";
				}
				if (strpos($record['Platform'],'Windows XP'))
				{
					$image = "WindowsXP";
				}
				else if (strpos($record['Platform'],'Windows') && strpos($record['Platform'],'server'))
				{
					$image = "WindowsServer";
				}
				else if (strpos($record['Platform'],'Windows'))
				{
					$image = "WindowsOther";
				}
				if (strpos($record['Platform'],'Darwin'))
				{
					$image = "Darwin";
				}
				if (strpos($record['Platform'],'DragonFly'))
				{
					$image = "DragonFly";
				}
				if (strpos($record['Platform'],'FreeBSD'))
				{
					$image = "FreeBSD";
				}
				if (strpos($record['Platform'],'NetBSD'))
				{
					$image = "NetBSD";
				}
				if (strpos($record['Platform'],'IRIX'))
				{
					$image = "IRIX64";
				}
				if (strpos($record['Platform'],'Cygwin'))
				{
					$image = "Cygwin";
				}
				if (strpos($record['Platform'],'SunOS'))
				{
					$image = "SunOS";
				}
				if (strpos($record['Platform'],'OpenBSD'))
				{
					$image = "OpenBSD";
				}
				echo "</td><td><img src='img/os-icons/$image.png' title='".$record['Platform']."' alt='".$record['Platform']."' />";
			}
			if ($innerTable)
			{
				echo "</td></tr></table>";
			}
			echo "</td>";
			break;

			case
			($value == "Bandwidth"):
			// Determine the bandwidth colors
			$bandwidth = $record[$value];
			if ($record[$value] <= 1000)
			{
				$background = "bwr";
				$foreground = "1";
			}
			else if ($record[$value] > 1000 && $record[$value] <= 2000)
			{
				$background = "bwr1";
				$foreground = "2";
			}
			else if ($record[$value] > 2000 && $record[$value] <= 3000)
			{
				$background = "bwr2";
				$foreground = "3";
			}
			else if ($record[$value] > 3000 && $record[$value] <= 4000)
			{
				$background = "bwr3";
				$foreground = "4";
			}
			else if ($record[$value] > 4000 && $record[$value] <= 5000)
			{
				$background = "bwr4";
				$foreground = "5";
			}
			else if ($record[$value] > 5000 && $record[$value] <= 6000)
			{
				$background = "bwr5";
				$foreground = "6";
			}
			else if ($record[$value] > 6000)
			{
				$bandwidth = "1000";
				$background = "bwr5";
				$foreground = "6";
			}
			$bandwidthtop = 1000/85;
			if ($bandwidth % 1000 == 0 && $bandwidth != 0)
			{
				$bandwidth = 999;
			}
			$bandwidth = floor(($bandwidth % 1000)/$bandwidthtop);
			if ($bandwidth > 85) { $bandwidth = 85; }
			if ($bandwidth == 0) { $bandwidth = 1; }
			echo "<td class='TDb'><table cellspacing='0' cellpadding='0' class='bwb'><tr title='".$record[$value]." KBs'><td class='$background'><img src='img/bar/${foreground}.png' width='${bandwidth}px' height='15px' alt='".$record[$value]."' /></td><td>&nbsp;<small>&nbsp;".$record[$value]."</small></td></tr></table></td>";
			break;

  			case
			(
				$value == "Fingerprint" 		||  
				$value == "LastDescriptorPublished"	||
				$value == "Contact"
			):

			echo "<td class='TDS'>" . $record[$value] . "</td>";
			break;

  			case
			(
				$value == "BadDir" 			|| 
				$value == "BadExit"
			):

			echo "<td class='F" . $record[$value] . "'></td>";
			break;

  			case
			($value == "Uptime"):
			
			if ($record[$value] > -1 && $record[$value] < 5)
			{
				echo "<td class='TDc'>" . $record[$value] . " d</td>";
			}
			else if ($record[$value] >= 5)
			{
				echo "<td class='TDcb'>" . $record[$value] . " d</td>";
			}
			else
			{
				echo "<td class='TDc'>N/A</td>";
			}
			break;

  			case
			($value == "ORPort" || $value == "DirPort"):
			
			if ($record[$value] > 0 && $record[$value] != 80 && $record[$value] != 443)
			{
				echo "<td class='TDc'>" . $record[$value] . "</td>";
			}
			else if ($record[$value] == 80 || $record[$value] == 443)
			{
				echo "<td class='TDc'><b>" . $record[$value] . "</b></td>";
			}
			else
			{
				echo "<td class='TDc'>None</td>";
			}
			break;
		}
	}

	echo "</tr>\n";
}

// Get script start time
$TimeStart = microtime(true);

// Connect to database, select schema
$link = mysql_connect($SQL_Server, $SQL_User, $SQL_Pass) or die('Could not connect: ' . mysql_error());
mysql_select_db($SQL_Catalog) or die('Could not open specified database');

// Read SortRequest (SR) and SortOrder (SO) variables -- These come from POST, GET, or SESSION

// POST
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	if (isset($_POST["SR"]))
	{
		$SR = $_POST["SR"];
	}
	if (isset($_POST["SO"]))
	{
		$SO = $_POST["SO"];
	}
}

// GET
else if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET["SR"]) && isset($_GET["SO"]))
{
	if (isset($_GET["SR"]))
	{
		$SR = $_GET["SR"];
	}
	if (isset($_GET["SO"]))
	{
		$SO = $_GET["SO"];
	}
}

// SESSION
else
{
	if (isset($_SESSION["SR"]))
	{
		$SR = $_SESSION['SR'];
	}
	if (isset($_SESSION["SO"]))
	{
		$SO = $_SESSION['SO'];
	}
}

// VARIABLE SCRUB / DEFAULT VALUES HANDLING
if(
	$SR != "Name"				&&
	$SR != "Fingerprint"			&&
	$SR != "CountryCode"			&&
	$SR != "Bandwidth"			&&
	$SR != "Uptime"			&&
	$SR != "LastDescriptorPublished"	&&
	$SR != "IP"				&&
	$SR != "Hostname"			&&
	$SR != "ORPort"			&&
	$SR != "DirPort"			&&
	$SR != "Platform"			&&
	$SR != "Contact"			&&
	$SR != "FAuthority"			&&
	$SR != "FBadDirectory"		&&
	$SR != "FBadExit"			&&
	$SR != "FExit"			&&
	$SR != "FFast"			&&
	$SR != "FGuard"			&&
	$SR != "Hibernating"			&&
	$SR != "FNamed"			&&
	$SR != "FStable"			&&
	$SR != "FRunning"			&&
	$SR != "FValid"			&&
	$SR != "FV2Dir")
{
	$SR = "Name";
} 

if(
	$SO != "Asc"				&&
	$SO != "Desc")
{
	$SO = "Asc";
} 

// Read CustomSearch Field (CSField), CustomSearch Modifier (CSMod), CustomSearch Input (CSInput), and FLAGS variables -- These come from POST or SESSION

// POST
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	if (isset($_POST["FAuthority"]))
	{
		$FAuthority = $_POST["FAuthority"];
	}
	if (isset($_POST["FBadDirectory"]))
	{
		$FBadDirectory = $_POST["FBadDirectory"];
	}
	if (isset($_POST["FBadExit"]))
	{
		$FBadExit = $_POST["FBadExit"];
	}
	if (isset($_POST["FExit"]))
	{
		$FExit = $_POST["FExit"];
	}
	if (isset($_POST["FFast"]))
	{
		$FFast = $_POST["FFast"];
	}
	if (isset($_POST["FGuard"]))
	{
		$FGuard = $_POST["FGuard"];
	}
	if (isset($_POST["FHibernating"]))
	{
		$FHibernating = $_POST["FHibernating"];
	}
	if (isset($_POST["FNamed"]))
	{
		$FNamed = $_POST["FNamed"];
	}
	if (isset($_POST["FStable"]))
	{
		$FStable = $_POST["FStable"];
	}
	if (isset($_POST["FRunning"]))
	{
		$FRunning = $_POST["FRunning"];
	}
	if (isset($_POST["FValid"]))
	{
		$FValid = $_POST["FValid"];
	}
	if (isset($_POST["FV2Dir"]))
	{
		$FV2Dir = $_POST["FV2Dir"];
	}
	if (isset($_POST["CSField"]))
	{
		$CSField = $_POST["CSField"];
	}
	if (isset($_POST["CSMod"]))
	{
		$CSMod = $_POST["CSMod"];
	}
	if (isset($_POST["CSInput"]))
	{
		$CSInput = $_POST["CSInput"];
	}
}

// SESSION
else
{
	if (isset($_SESSION["FAuthority"]))
	{
		$FAuthority = $_SESSION["FAuthority"];
	}
	if (isset($_SESSION["FBadDirectory"]))
	{
		$FBadDirectory = $_SESSION["FBadDirectory"];
	}
	if (isset($_SESSION["FBadExit"]))
	{
		$FBadExit = $_SESSION["FBadExit"];
	}
	if (isset($_SESSION["FExit"]))
	{
		$FExit = $_SESSION["FExit"];
	}
	if (isset($_SESSION["FFast"]))
	{
		$FFast = $_SESSION["FFast"];
	}
	if (isset($_SESSION["FGuard"]))
	{
		$FGuard = $_SESSION["FGuard"];
	}
	if (isset($_SESSION["FHibernating"]))
	{
		$FHibernating = $_SESSION["FHibernating"];
	}
	if (isset($_SESSION["FNamed"]))
	{
		$FNamed = $_SESSION["FNamed"];
	}
	if (isset($_SESSION["FStable"]))
	{
		$FStable = $_SESSION["FStable"];
	}
	if (isset($_SESSION["FRunning"]))
	{
		$FRunning = $_SESSION["FRunning"];
	}
	if (isset($_SESSION["FValid"]))
	{
		$FValid = $_SESSION["FValid"];
	}
	if (isset($_SESSION["FV2Dir"]))
	{
		$FV2Dir = $_SESSION["FV2Dir"];
	}
	if (isset($_SESSION["CSField"]))
	{
		$CSField = $_SESSION["CSField"];
	}
	if (isset($_SESSION["CSMod"]))
	{
		$CSMod = $_SESSION["CSMod"];
	}
	if (isset($_SESSION["CSInput"]))
	{
		$CSInput = $_SESSION["CSInput"];
	}
}

// Read ColumnList_ACTIVE and ColumnList_INACTIVE variables -- These come from SESSION
	if (isset($_SESSION["ColumnList_ACTIVE"]))
	{
		$ColumnList_ACTIVE = $_SESSION["ColumnList_ACTIVE"];
	}
	if (isset($_SESSION["ColumnList_INACTIVE"]))
	{
		$ColumnList_INACTIVE = $_SESSION["ColumnList_INACTIVE"];
	}

// VARIABLE SCRUB / DEFAULT VALUES HANDLING
if (!(isset($_SESSION['ColumnSetVisited'])) && !(isset($_SESSION['IndexVisited'])))
{
	$ColumnList_ACTIVE = $ColumnList_ACTIVE_DEFAULT;
	$ColumnList_INACTIVE = $ColumnList_INACTIVE_DEFAULT;
}

if($FAuthority != '0' && $FAuthority != '1' && $FAuthority != 'OFF')
{
	$FAuthority = 'OFF';
}

if($FBadDirectory != '0' && $FBadDirectory != '1' && $FBadDirectory != 'OFF')
{
	$FBadDirectory = 'OFF';
}

if($FBadExit != '0' && $FBadExit != '1' && $FBadExit != 'OFF')
{
	$FBadExit = 'OFF';
}

if($FExit != '0' && $FExit != '1' && $FExit != 'OFF')
{
	$FExit = 'OFF';
}

if($FFast != '0' && $FFast != '1' && $FFast != 'OFF')
{
	$FFast = 'OFF';
}

if($FGuard != '0' && $FGuard != '1' && $FGuard != 'OFF')
{
	$FGuard = 'OFF';
}

if($FHibernating != '0' && $FHibernating != '1' && $FHibernating != 'OFF')
{
	$FHibernating = 'OFF';
}

if($FNamed != '0' && $FNamed != '1' && $FNamed != 'OFF')
{
	$FNamed = 'OFF';
}

if($FStable != '0' && $FStable != '1' && $FStable != 'OFF')
{
	$FStable = 'OFF';
}

if($FRunning != '0' && $FRunning != '1' && $FRunning != 'OFF')
{
	$FRunning = 'OFF';
}

if($FValid != '0' && $FValid != '1' && $FValid != 'OFF')
{
	$FValid = 'OFF';
}

if($FV2Dir != '0' && $FV2Dir != '1' && $FV2Dir != 'OFF')
{
	$FV2Dir = 'OFF';
}

if(
	$CSField != "Fingerprint"			&&
	$CSField != "Name"				&&
	$CSField != "CountryCode"			&&
	$CSField != "Bandwidth"			&&
	$CSField != "Uptime"				&&
	$CSField != "LastDescriptorPublished"	&&
	$CSField != "IP"				&&
	$CSField != "Hostname"			&&
	$CSField != "ORPort"				&&
	$CSField != "DirPort"			&&
	$CSField != "Platform"			&&
	$CSField != "Contact")
{
	$CSField = "Fingerprint";
} 

if(
	$CSMod != "Equals"		&&
	$CSMod != "Contains"		&&
	$CSMod != "LessThan"		&&
	$CSMod != "GreaterThan")
{
	$CSMod = "Equals";
}

if ($CSInput != null)
{
	if (strlen($CSInput) > 128)
	{
		$CSInput = substr($CSInput,0,128);
	}
}

// Register variables in SESSION
if (!isset($_SESSION['ColumnList_ACTIVE'])) 
{
	$_SESSION['ColumnList_ACTIVE'] = $ColumnList_ACTIVE;
} 
else
{
	unset($_SESSION['ColumnList_ACTIVE']);
	$_SESSION['ColumnList_ACTIVE'] = $ColumnList_ACTIVE;
}

if (!isset($_SESSION['ColumnList_INACTIVE'])) 
{
	$_SESSION['ColumnList_INACTIVE'] = $ColumnList_INACTIVE;
} 
else
{
	unset($_SESSION['ColumnList_INACTIVE']);
	$_SESSION['ColumnList_INACTIVE'] = $ColumnList_INACTIVE;
}

if (!isset($_SESSION['SR'])) 
{
	$_SESSION['SR'] = $SR;
} 
else
{
	unset($_SESSION['SR']);
	$_SESSION['SR'] = $SR;
}

if (!isset($_SESSION['SO'])) 
{
	$_SESSION['SO'] = $SO;
} 
else
{
	unset($_SESSION['SO']);
	$_SESSION['SO'] = $SO;
}

if (!isset($_SESSION['FAuthority'])) 
{
	$_SESSION['FAuthority'] = $FAuthority;
} 
else
{
	unset($_SESSION['FAuthority']);
	$_SESSION['FAuthority'] = $FAuthority;
}

if (!isset($_SESSION['FBadDirectory'])) 
{
	$_SESSION['FBadDirectory'] = $FBadDirectory;
} 
else
{
	unset($_SESSION['FBadDirectory']);
	$_SESSION['FBadDirectory'] = $FBadDirectory;
}

if (!isset($_SESSION['FBadExit'])) 
{
	$_SESSION['FBadExit'] = $FBadExit;
} 
else
{
	unset($_SESSION['FBadExit']);
	$_SESSION['FBadExit'] = $FBadExit;
}

if (!isset($_SESSION['FExit'])) 
{
	$_SESSION['FExit'] = $FExit;
} 
else
{
	unset($_SESSION['FExit']);
	$_SESSION['FExit'] = $FExit;
}

if (!isset($_SESSION['FFast'])) 
{
	$_SESSION['FFast'] = $FFast;
} 
else
{
	unset($_SESSION['FFast']);
	$_SESSION['FFast'] = $FFast;
}

if (!isset($_SESSION['FGuard'])) 
{
	$_SESSION['FGuard'] = $FGuard;
} 
else
{
	unset($_SESSION['FGuard']);
	$_SESSION['FGuard'] = $FGuard;
}

if (!isset($_SESSION['FHibernating'])) 
{
	$_SESSION['FHibernating'] = $FHibernating;
} 
else
{
	unset($_SESSION['FHibernating']);
	$_SESSION['FHibernating'] = $FHibernating;
}

if (!isset($_SESSION['FNamed'])) 
{
	$_SESSION['FNamed'] = $FNamed;
} 
else
{
	unset($_SESSION['FNamed']);
	$_SESSION['FNamed'] = $FNamed;
}

if (!isset($_SESSION['FStable'])) 
{
	$_SESSION['FStable'] = $FStable;
} 
else
{
	unset($_SESSION['FStable']);
	$_SESSION['FStable'] = $FStable;
}

if (!isset($_SESSION['FRunning'])) 
{
	$_SESSION['FRunning'] = $FRunning;
} 
else
{
	unset($_SESSION['FRunning']);
	$_SESSION['FRunning'] = $FRunning;
}

if (!isset($_SESSION['FValid'])) 
{
	$_SESSION['FValid'] = $FValid;
} 
else
{
	unset($_SESSION['FValid']);
	$_SESSION['FValid'] = $FValid;
}

if (!isset($_SESSION['FV2Dir'])) 
{
	$_SESSION['FV2Dir'] = $FV2Dir;
} 
else
{
	unset($_SESSION['FV2Dir']);
	$_SESSION['FV2Dir'] = $FV2Dir;
}

if (!isset($_SESSION['CSField'])) 
{
	$_SESSION['CSField'] = $CSField;
} 
else
{
	unset($_SESSION['CSField']);
	$_SESSION['CSField'] = $CSField;
}

if (!isset($_SESSION['CSMod'])) 
{
	$_SESSION['CSMod'] = $CSMod;
} 
else
{
	unset($_SESSION['CSMod']);
	$_SESSION['CSMod'] = $CSMod;
}

if (!isset($_SESSION['CSInput'])) 
{
	$_SESSION['CSInput'] = $CSInput;
} 
else
{
	unset($_SESSION['CSInput']);
	$_SESSION['CSInput'] = $CSInput;
}

// Get last update and active table information from database
$query = "select LastUpdate, LastUpdateElapsed, ActiveNetworkStatusTable, ActiveDescriptorTable from Status";
$result = mysql_query($query) or die('Query failed: ' . mysql_error());
$record = mysql_fetch_assoc($result);

$LastUpdate = $record['LastUpdate'];
$LastUpdateElapsed = $record['LastUpdateElapsed'];
$ActiveNetworkStatusTable = $record['ActiveNetworkStatusTable'];
$ActiveDescriptorTable = $record['ActiveDescriptorTable'];

// Get total number of routers from database
$query = "select count(*) as Count from $ActiveNetworkStatusTable";
$result = mysql_query($query);
if (!$result) 
{
	echo "It appears that the ".$SQL_Catalog." database has not yet been populated with information.  Please run tns_update.php or tns_agent.php from within your root TorStatus directory.  If you continue to run into problems, please submit a bug report at http://project.torstatus.kgprog.com/.";
	exit;
}
$record = mysql_fetch_assoc($result);

$RouterCount = $record['Count'];

// Get details on Network Status Source router from the database
$query = "select Name, IP, ORPort, DirPort, Fingerprint, Platform, LastDescriptorPublished, OnionKey, SigningKey, Contact, DescriptorSignature from NetworkStatusSource where ID = 1";
$result = mysql_query($query) or die('Query failed: ' . mysql_error());
$record = mysql_fetch_assoc($result);

$Name = $record['Name'];
$IP = $record['IP'];
$ORPort = $record['ORPort'];
$DirPort = $record['DirPort'];
$Fingerprint = $record['Fingerprint'];
$Platform = $record['Platform'];
$LastDescriptorPublished = $record['LastDescriptorPublished'];
$OnionKey = $record['OnionKey'];
$SigningKey = $record['SigningKey'];
$Contact = $record['Contact'];
$DescriptorSignature = $record['DescriptorSignature'];

$query = "select Hostname, CountryCode from $ActiveNetworkStatusTable where Fingerprint = '$Fingerprint'";
$result = mysql_query($query) or die('Query failed: ' . mysql_error());
$record = mysql_fetch_assoc($result);

$Hostname = $record['Hostname'];
$CountryCode = $record['CountryCode'];

// Determine if client IP exists in database as a Tor server
$query = "select count(*) as Count from $ActiveNetworkStatusTable where IP = '$RemoteIP'";
$result = mysql_query($query) or die('Query failed: ' . mysql_error());
$record = mysql_fetch_assoc($result);

$RemoteIPDBCount = $record['Count'];

if ($RemoteIPDBCount > 0)
{
	$PositiveMatch_IP = 1;	
}

// Get name, fingerprint, and exit policy of Tor node(s) if match was found, look for match in ExitPolicy
if ($PositiveMatch_IP == 1)
{
	$query = "select $ActiveNetworkStatusTable.Name, $ActiveNetworkStatusTable.Fingerprint, $ActiveDescriptorTable.ExitPolicySERDATA from $ActiveNetworkStatusTable inner join $ActiveDescriptorTable on $ActiveNetworkStatusTable.Fingerprint = $ActiveDescriptorTable.Fingerprint where $ActiveNetworkStatusTable.IP = '$RemoteIP'";
	$result = mysql_query($query) or die('Query failed: ' . mysql_error());

	while ($record = mysql_fetch_assoc($result))
	{ 
		$Count++;

		$TorNodeName[$Count] = $record['Name'];
		$TorNodeFP[$Count] = $record['Fingerprint'];
		$TorNodeExitPolicy = unserialize($record['ExitPolicySERDATA']);

		foreach($TorNodeExitPolicy as $ExitPolicyLine)
		{
			// Initialize variables
			$Condition = null;
			$NetworkLine = null;
			$Subnet = null;
			$PortLine = null;
			$Port = null;

			// Seperate parts of ExitPolicy line
			list($Condition,$NetworkLine) = explode(' ', rtrim($ExitPolicyLine));
			list($Subnet,$PortLine) = explode(':', $NetworkLine);
			$Port = explode(',', $PortLine);

			// Find out if IP client used to access this server is a match for the subnet specified on this ExitPolicy line
			if (IsIPInSubnet($ServerIP,$Subnet) == 1)
			{
				// Determine if port is also a match
				foreach($Port as $CurrentPortExpression)
				{
					// Handle condition where port is a '*' character (Port always matches)
					if ($CurrentPortExpression == '*')
					{
						if ($Condition == 'accept')
						{
							$PositiveMatch_ExitPolicy[$Count] = 1;
							break 2;
						}
						else if ($Condition == 'reject')
						{
							$PositiveMatch_ExitPolicy[$Count] = 0;
							break 2;
						}
					}

					// $CurrentPortExpression is a range of ports
					if(strpos($CurrentPortExpression, '-') !== FALSE)
					{
						list($LowerPort,$UpperPort) = explode('-', $CurrentPortExpression);
	
						if (($ServerPort >= $LowerPort && $ServerPort <= $UpperPort) && ($Condition == 'accept'))
						{
							$PositiveMatch_ExitPolicy[$Count] = 1;
							break 2;
						}
						else if (($ServerPort >= $LowerPort && $ServerPort <= $UpperPort) && ($Condition == 'reject'))
						{
							$PositiveMatch_ExitPolicy[$Count] = 0;
							break 2;
						}
						else
						{
							continue;
						}
					}
	
					// $CurrentPortExpression is a single port number
					else
					{
						if (($ServerPort == $CurrentPortExpression) && ($Condition == 'accept'))
						{
							$PositiveMatch_ExitPolicy[$Count] = 1;
							break 2;
						}
						else if (($ServerPort == $CurrentPortExpression) && ($Condition == 'reject'))
						{
							$PositiveMatch_ExitPolicy[$Count] = 0;
							break 2;
						}
						else
						{
							continue;
						}
					}
				}
			}
			else
			{
				continue;
			}
		}
	}
}

// Get descriptor count
$query = "select count(*) as Count from $ActiveDescriptorTable";
$result = mysql_query($query) or die('Query failed: ' . mysql_error());
$record = mysql_fetch_assoc($result);

$DescriptorCount = $record['Count'];

// Prepare and execute master router query
$query = "select $ActiveNetworkStatusTable.Name, $ActiveNetworkStatusTable.Fingerprint";

if (in_array("CountryCode", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveNetworkStatusTable.CountryCode";
}

if (in_array("Bandwidth", $ColumnList_ACTIVE))
{
	$query .= ", floor($ActiveDescriptorTable.BandwidthOBSERVED / 1024) as Bandwidth";
}

if (in_array("Uptime", $ColumnList_ACTIVE))
{
	$query .= ", floor(CAST(((UNIX_TIMESTAMP() - (UNIX_TIMESTAMP($ActiveDescriptorTable.LastDescriptorPublished) + $OffsetFromGMT)) + $ActiveDescriptorTable.Uptime) AS SIGNED) / 86400) as Uptime";
}

if (in_array("LastDescriptorPublished", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveDescriptorTable.LastDescriptorPublished";
}

if (in_array("Hostname", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveNetworkStatusTable.Hostname";
}

if (in_array("IP", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveNetworkStatusTable.IP";
}

if (in_array("ORPort", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveNetworkStatusTable.ORPort";
}

if (in_array("DirPort", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveNetworkStatusTable.DirPort";
}

if (in_array("Platform", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveDescriptorTable.Platform";
}

if (in_array("Contact", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveDescriptorTable.Contact";
}

if (in_array("Authority", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveNetworkStatusTable.FAuthority as Authority";
}

if (in_array("BadDir", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveNetworkStatusTable.FBadDirectory as BadDir";
}

if (in_array("BadExit", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveNetworkStatusTable.FBadExit as BadExit";
}

if (in_array("Exit", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveNetworkStatusTable.FExit as 'Exit'";
}

if (in_array("Fast", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveNetworkStatusTable.FFast as Fast";
}

if (in_array("Guard", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveNetworkStatusTable.FGuard as Guard";
}

if (in_array("Hibernating", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveDescriptorTable.Hibernating as 'Hibernating'";
}

if (in_array("Named", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveNetworkStatusTable.FNamed as Named";
}

if (in_array("Stable", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveNetworkStatusTable.FStable as Stable";
}

if (in_array("Running", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveNetworkStatusTable.FRunning as Running";
}

if (in_array("Valid", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveNetworkStatusTable.FValid as Valid";
}

if (in_array("V2Dir", $ColumnList_ACTIVE))
{
	$query .= ", $ActiveNetworkStatusTable.FV2Dir as V2Dir";
}

$query .= ", INET_ATON($ActiveNetworkStatusTable.IP) as NIP from $ActiveNetworkStatusTable inner join $ActiveDescriptorTable on $ActiveNetworkStatusTable.Fingerprint = $ActiveDescriptorTable.Fingerprint";

if ($FAuthority != 'OFF')
{
	if (strpos($query, "where") === false)
		{
			$query = $query . " where FAuthority = $FAuthority";
		}
	else
		{
			$query = $query . " and FAuthority = $FAuthority";
		}
}

if ($FBadDirectory != 'OFF')
{
	if (strpos($query, "where") === false)
		{
			$query = $query . " where FBadDirectory = $FBadDirectory";
		}
	else
		{
			$query = $query . " and FBadDirectory = $FBadDirectory";
		}
}

if ($FBadExit != 'OFF')
{
	if (strpos($query, "where") === false)
		{
			$query = $query . " where FBadExit = $FBadExit";
		}
	else
		{
			$query = $query . " and FBadExit = $FBadExit";
		}
}

if ($FExit != 'OFF')
{
	if (strpos($query, "where") === false)
		{
			$query = $query . " where FExit = $FExit";
		}
	else
		{
			$query = $query . " and FExit = $FExit";
		}
}

if ($FFast != 'OFF')
{
	if (strpos($query, "where") === false)
		{
			$query = $query . " where FFast = $FFast";
		}
	else
		{
			$query = $query . " and FFast = $FFast";
		}
}

if ($FGuard != 'OFF')
{
	if (strpos($query, "where") === false)
		{
			$query = $query . " where FGuard = $FGuard";
		}
	else
		{
			$query = $query . " and FGuard = $FGuard";
		}
}

if ($FHibernating != 'OFF')
{
	if (strpos($query, "where") === false)
		{
			$query = $query . " where Hibernating = $FHibernating";
		}
	else
		{
			$query = $query . " and Hibernating = $FHibernating";
		}
}

if ($FNamed != 'OFF')
{
	if (strpos($query, "where") === false)
		{
			$query = $query . " where FNamed = $FNamed";
		}
	else
		{
			$query = $query . " and FNamed = $FNamed";
		}
}

if ($FStable != 'OFF')
{
	if (strpos($query, "where") === false)
		{
			$query = $query . " where FStable = $FStable";
		}
	else
		{
			$query = $query . " and FStable = $FStable";
		}
}

if ($FRunning != 'OFF')
{
	if (strpos($query, "where") === false)
		{
			$query = $query . " where FRunning = $FRunning";
		}
	else
		{
			$query = $query . " and FRunning = $FRunning";
		}
}

if ($FValid != 'OFF')
{
	if (strpos($query, "where") === false)
		{
			$query = $query . " where FValid = $FValid";
		}
	else
		{
			$query = $query . " and FValid = $FValid";
		}
}

if ($FV2Dir != 'OFF')
{
	if (strpos($query, "where") === false)
		{
			$query = $query . " where FV2Dir = $FV2Dir";
		}
	else
		{
			$query = $query . " and FV2Dir = $FV2Dir";
		}
}

if ($CSInput != null)
{
	$CSInput_SAFE = null;
	$QueryPrepend = null;

	if (strpos($query, "where") === false)
	{
		$QueryPrepend = " where "; 
	}
	else
	{
		$QueryPrepend = " and ";
	}

	$query .= $QueryPrepend;

	$CSInput_SAFE = mysql_real_escape_string($CSInput);

	if ($CSField == 'Fingerprint')
	{
		if($CSMod == 'Equals')
		{
			$query .= "$ActiveNetworkStatusTable.Fingerprint = '$CSInput_SAFE'";
		}
		else if($CSMod == 'Contains')
		{
			$query .= "$ActiveNetworkStatusTable.Fingerprint like '%$CSInput_SAFE%'";
		}
		else if($CSMod == 'LessThan')
		{
			$query .= "$ActiveNetworkStatusTable.Fingerprint < '$CSInput_SAFE'";
		}
		else if($CSMod == 'GreaterThan')
		{
			$query .= "$ActiveNetworkStatusTable.Fingerprint > '$CSInput_SAFE'";
		}
	}
	else if ($CSField == 'Name')
	{
		if($CSMod == 'Equals')
		{
			$query .= "$ActiveNetworkStatusTable.Name = '$CSInput_SAFE'";
		}
		else if($CSMod == 'Contains')
		{
			$query .= "$ActiveNetworkStatusTable.Name like '%$CSInput_SAFE%'";
		}
		else if($CSMod == 'LessThan')
		{
			$query .= "$ActiveNetworkStatusTable.Name < '$CSInput_SAFE'";
		}
		else if($CSMod == 'GreaterThan')
		{
			$query .= "$ActiveNetworkStatusTable.Name > '$CSInput_SAFE'";
		}
	}
	else if ($CSField == 'CountryCode')
	{
		if($CSMod == 'Equals')
		{
			$query .= "$ActiveNetworkStatusTable.CountryCode = '$CSInput_SAFE'";
		}
		else if($CSMod == 'Contains')
		{
			$query .= "$ActiveNetworkStatusTable.CountryCode like '%$CSInput_SAFE%'";
		}
		else if($CSMod == 'LessThan')
		{
			$query .= "$ActiveNetworkStatusTable.CountryCode < '$CSInput_SAFE'";
		}
		else if($CSMod == 'GreaterThan')
		{
			$query .= "$ActiveNetworkStatusTable.CountryCode > '$CSInput_SAFE'";
		}
	}
	else if ($CSField == 'Bandwidth')
	{
		if(!(is_numeric($CSInput_SAFE))) 
		{
			$CSInput = 0;
			$CSInput_SAFE = 0;
		}

		if($CSMod == 'Equals')
		{
			$query .= "floor($ActiveDescriptorTable.BandwidthOBSERVED / 1024) = '$CSInput_SAFE'";
		}
		else if($CSMod == 'Contains')
		{
			$query .= "floor($ActiveDescriptorTable.BandwidthOBSERVED / 1024) like '%$CSInput_SAFE%'";
		}
		else if($CSMod == 'LessThan')
		{
			$query .= "floor($ActiveDescriptorTable.BandwidthOBSERVED / 1024) < '$CSInput_SAFE'";
		}
		else if($CSMod == 'GreaterThan')
		{
			$query .= "floor($ActiveDescriptorTable.BandwidthOBSERVED / 1024) > '$CSInput_SAFE'";
		}
	}
	else if ($CSField == 'Uptime')
	{
		if(!(is_numeric($CSInput_SAFE))) 
		{
			$CSInput = 0;
			$CSInput_SAFE = 0;
		}

		if($CSMod == 'Equals')
		{
			$query .= "floor($ActiveDescriptorTable.Uptime / 86400) = '$CSInput_SAFE'";
		}
		else if($CSMod == 'Contains')
		{
			$query .= "floor($ActiveDescriptorTable.Uptime / 86400) like '%$CSInput_SAFE%'";
		}
		else if($CSMod == 'LessThan')
		{
			$query .= "floor($ActiveDescriptorTable.Uptime / 86400) < '$CSInput_SAFE'";
		}
		else if($CSMod == 'GreaterThan')
		{
			$query .= "floor($ActiveDescriptorTable.Uptime / 86400) > '$CSInput_SAFE'";
		}
	}
	else if ($CSField == 'LastDescriptorPublished')
	{
		if($CSMod == 'Equals')
		{
			$query .= "$ActiveNetworkStatusTable.LastDescriptorPublished = '$CSInput_SAFE'";
		}
		else if($CSMod == 'Contains')
		{
			$query .= "$ActiveNetworkStatusTable.LastDescriptorPublished like '%$CSInput_SAFE%'";
		}
		else if($CSMod == 'LessThan')
		{
			$query .= "$ActiveNetworkStatusTable.LastDescriptorPublished < '$CSInput_SAFE'";
		}
		else if($CSMod == 'GreaterThan')
		{
			$query .= "$ActiveNetworkStatusTable.LastDescriptorPublished > '$CSInput_SAFE'";
		}
	}
	else if ($CSField == 'IP')
	{
		if($CSMod == 'Equals')
		{
			$query .= "$ActiveNetworkStatusTable.IP = '$CSInput_SAFE'";
		}
		else if($CSMod == 'Contains')
		{
			$query .= "$ActiveNetworkStatusTable.IP like '%$CSInput_SAFE%'";
		}
		else if($CSMod == 'LessThan')
		{
			$query .= "$ActiveNetworkStatusTable.IP < '$CSInput_SAFE'";
		}
		else if($CSMod == 'GreaterThan')
		{
			$query .= "$ActiveNetworkStatusTable.IP > '$CSInput_SAFE'";
		}
	}
	else if ($CSField == 'Hostname')
	{
		if($CSMod == 'Equals')
		{
			$query .= "$ActiveNetworkStatusTable.Hostname = '$CSInput_SAFE'";
		}
		else if($CSMod == 'Contains')
		{
			$query .= "$ActiveNetworkStatusTable.Hostname like '%$CSInput_SAFE%'";
		}
		else if($CSMod == 'LessThan')
		{
			$query .= "$ActiveNetworkStatusTable.Hostname < '$CSInput_SAFE'";
		}
		else if($CSMod == 'GreaterThan')
		{
			$query .= "$ActiveNetworkStatusTable.Hostname > '$CSInput_SAFE'";
		}
	}
	else if ($CSField == 'ORPort')
	{
		if(!(is_numeric($CSInput_SAFE))) 
		{
			$CSInput = 0;
			$CSInput_SAFE = 0;
		}

		if($CSMod == 'Equals')
		{
			$query .= "$ActiveNetworkStatusTable.ORPort = '$CSInput_SAFE'";
		}
		else if($CSMod == 'Contains')
		{
			$query .= "$ActiveNetworkStatusTable.ORPort like '%$CSInput_SAFE%'";
		}
		else if($CSMod == 'LessThan')
		{
			$query .= "$ActiveNetworkStatusTable.ORPort < '$CSInput_SAFE'";
		}
		else if($CSMod == 'GreaterThan')
		{
			$query .= "$ActiveNetworkStatusTable.ORPort > '$CSInput_SAFE'";
		}
	}
	else if ($CSField == 'DirPort')
	{
		if(!(is_numeric($CSInput_SAFE))) 
		{
			$CSInput = 0;
			$CSInput_SAFE = 0;
		}

		if($CSMod == 'Equals')
		{
			$query .= "$ActiveNetworkStatusTable.DirPort = '$CSInput_SAFE'";
		}
		else if($CSMod == 'Contains')
		{
			$query .= "$ActiveNetworkStatusTable.DirPort like '%$CSInput_SAFE%'";
		}
		else if($CSMod == 'LessThan')
		{
			$query .= "$ActiveNetworkStatusTable.DirPort < '$CSInput_SAFE'";
		}
		else if($CSMod == 'GreaterThan')
		{
			$query .= "$ActiveNetworkStatusTable.DirPort > '$CSInput_SAFE'";
		}
	}
	else if ($CSField == 'Platform')
	{
		if($CSMod == 'Equals')
		{
			$query .= "$ActiveDescriptorTable.Platform = '$CSInput_SAFE'";
		}
		else if($CSMod == 'Contains')
		{
			$query .= "$ActiveDescriptorTable.Platform like '%$CSInput_SAFE%'";
		}
		else if($CSMod == 'LessThan')
		{
			$query .= "$ActiveDescriptorTable.Platform < '$CSInput_SAFE'";
		}
		else if($CSMod == 'GreaterThan')
		{
			$query .= "$ActiveDescriptorTable.Platform > '$CSInput_SAFE'";
		}
	}
	else if ($CSField == 'Contact')
	{
		if($CSMod == 'Equals')
		{
			$query .= "$ActiveDescriptorTable.Contact = '$CSInput_SAFE'";
		}
		else if($CSMod == 'Contains')
		{
			$query .= "$ActiveDescriptorTable.Contact like '%$CSInput_SAFE%'";
		}
		else if($CSMod == 'LessThan')
		{
			$query .= "$ActiveDescriptorTable.Contact < '$CSInput_SAFE'";
		}
		else if($CSMod == 'GreaterThan')
		{
			$query .= "$ActiveDescriptorTable.Contact > '$CSInput_SAFE'";
		}
	}
}

if ($SR == 'Name')
{
	$query = $query . " order by " . $SR . " " . $SO;
}
else if ($SR == 'IP')
{
	$query = $query . " order by NIP " . $SO . ", Name Asc";
}
else
{
	$query = $query . " order by " . $SR . " " . $SO . ", Name Asc";
}

$result = mysql_query($query) or die('Query failed: ' . mysql_error());
?><!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<title>TorStatus - Tor Network Status</title>
	<link rel="stylesheet" type="text/css" href="css/main.css" />
</head>

<body class="BOD">
<table cellspacing="2" cellpadding="2" class="body">

<tr>
<td class="mirrors">Known mirrors: <b><?php echo $myMirrorName; ?></b> | <?php echo $mirrorList; ?></td>
</tr>

<tr>
<td class="PT"><br/><a href="index.php" class="plain">Tor Network Status</a><br/><br/></td>
</tr>

<tr>
<td class="links"><?php if($DNSEL_Domain != null){echo '<a class="plain" href="dnsel_server.php">DNSEL Server</a> |';} ?>
<a class="plain" href="tor_exit_query.php">Tor Exit Node Query</a> |
<a class='plain' href='#AppServer'>TorStatus Server Details</a> |
<a class='plain' href='#TorServer'>Opinion Source</a> |
<a class='plain' href='#CustomQuery'>Advanced Query Options</a> |
<a class='plain' href='#CustomDisplay'>Advanced Display Options</a> |
<a class='plain' href='#Stats'>Network Statistic Summary</a> |
<a class='plain' href='network_detail.php'>Network Statistic Graphs</a>
</td>
</tr>

<tr>
<td class="links">
<a class='plain' href='query_export.php/Tor_query_EXPORT.csv'>CSV List of Current Result Set</a> |
<a class='plain' href='ip_list_all.php/Tor_ip_list_ALL.csv'>CSV List of All Current Tor Server IP Addresses</a> |
<a class='plain' href='ip_list_exit.php/Tor_ip_list_EXIT.csv'>CSV List of All Current Tor Server Exit Node IP Addresses</a>
</td>
</tr>

<tr><td><br/></td></tr>

<?php
	echo "<tr>\n";
	echo "<td class='TRC'>";

	if(!(false === strpos($Hidden_Service_URL, $Host)))
	{
		echo "<font class='usingTor'>-You appear to be accessing this server through the Tor network as a hidden service-</span><br/><br/>";
	}
	else if ($PositiveMatch_IP == 1)
	{
		echo '<table class="notUsingTor"><tr><td class="imgTor"><img src="/img/usingtor.png" alt="You are using Tor" width="32px" height="32px" /></td><td class="notUsingTor">';
		echo "<span class='usingTor'>It appears that you are using the Tor network</span><br/>Your IP Address is: $RemoteIP";
		echo '</td></tr>';
		for($i=1 ; $i < ($Count + 1) ; $i++)
		{
			echo '<tr><td colspan="2" class="torServers">';
			echo "Server name: <a class='plain' href='router_detail.php?FP=$TorNodeFP[$i]'>$TorNodeName[$i]</a><br/>";
			if ($PositiveMatch_ExitPolicy[$i] == 1)
			{
				// POSSIBLE REVERT LATER: I do not see a need for this (kasimir)
				//echo "<span class='usingTor'>-This Tor server would allow exiting to this page-</span>";
			}
			else if ($PositiveMatch_ExitPolicy[$i] == 0)
			{
				echo "<span class='notUsingTor'>-This Tor server would NOT allow exiting to this page-</span>";
			}
			echo "</td></tr>";
		}
		echo "</table><br/><br/>";
	}
	else
	{
		echo '<table class="notUsingTor"><tr><td class="imgTor"><img src="/img/notor.png" alt="You are not using Tor" width="32px" height="32px" /></td><td class="notUsingTor">';
		echo "<span class='notUsingTor'>You do not appear to be using Tor</span><br/>Your IP Address is: $RemoteIP";
		echo "</td></tr></table><br/><br/>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if($Hidden_Service_URL != null)
	{
		echo "<tr>\n";
		echo "<td class='TRC'><b>";
		echo "<font color='#3344ee'>This site is available as a Tor Hidden Service at:</font><br/><a class='plain' href='$Hidden_Service_URL'>$Hidden_Service_URL</a><br/><br/>";
		echo "</b></td>\n";
		echo "</tr>\n";
	}
?>

<tr>
<td class='TRC'>
<table class='legend'>
<tr><th class='legend'>Legend:</th></tr>
<tr class='r'><td>Router is okay</td></tr>
<tr class='R'><td>Router is hibernating</td></tr>
<tr class='d'><td>Router is currently down</td></tr>
<tr class='B'><td>Router is a bad exit node</td></tr>
</table>
<br/><br/>
</td>
</tr>

<tr>
<td>

<table width='100%' cellspacing='0' cellpadding='0' border='0' align='center' class='displayTable'>

<?php

	// Generate header row
	GenerateHeaderRow();

	// Display header row
	echo $HeaderRowString;

	// Loop through and display all routers returned by query
	while ($record = mysql_fetch_assoc($result)) 
	{
	
		if ($RowCounter < $ColumnHeaderInterval)
		{
			// Display router row
			DisplayRouterRow();	
	
			$CurrentResultSet++;
			$RowCounter++;
		}
		else
		{
			// Display header row
			echo $HeaderRowString;
		
			// Display router row
			DisplayRouterRow();
		
			$CurrentResultSet++;
			$RowCounter = 1;
		}
	}
?>
</table>
</td>
</tr>

<tr>
<td><br/></td>
</tr>

</table>

<a name="Stats"></a>

<table width='40%' cellspacing='2' cellpadding='2' border='0' align='center'>
<tr>
<td class='TDBLACK'>
	
<table cellspacing='2' cellpadding='2' border='0' align='center' width='100%'>
<tr>
<td class='THN' colspan='3'>Aggregate Network Statistic Summary | <a href='network_detail.php'>Graphs / Details</a></td>
</tr>

<?php

	// Retrieve statistics from database
	$query = "select
		(select count(*) from $ActiveNetworkStatusTable) as 'Total',
		(select count(*) from $ActiveNetworkStatusTable where FAuthority = '1') as 'Authority',
		(select count(*) from $ActiveNetworkStatusTable where FBadDirectory = '1') as 'BadDirectory',
		(select count(*) from $ActiveNetworkStatusTable where FBadExit = '1') as 'BadExit',
		(select count(*) from $ActiveNetworkStatusTable where FExit = '1') as 'Exit',
		(select count(*) from $ActiveNetworkStatusTable where FFast = '1') as 'Fast',
		(select count(*) from $ActiveNetworkStatusTable where FGuard = '1') as 'Guard',
		(select count(*) from $ActiveDescriptorTable inner join $ActiveNetworkStatusTable on $ActiveNetworkStatusTable.Fingerprint = $ActiveDescriptorTable.Fingerprint where Hibernating = '1') as 'Hibernating',
		(select count(*) from $ActiveNetworkStatusTable where FNamed = '1') as 'Named',
		(select count(*) from $ActiveNetworkStatusTable where FStable = '1') as 'Stable',
		(select count(*) from $ActiveNetworkStatusTable where FRunning = '1') as 'Running',
		(select count(*) from $ActiveNetworkStatusTable where FValid = '1') as 'Valid',
		(select count(*) from $ActiveNetworkStatusTable where FV2Dir = '1') as 'V2Dir',
		(select count(*) from $ActiveNetworkStatusTable where DirPort > 0) as 'DirMirror'";
		
	$result = mysql_query($query) or die('Query failed: ' . mysql_error());
	$record = mysql_fetch_assoc($result);

	// Display total number of routers
	echo "<tr>\n";
	echo "<td class='TRAR'><b>Total Number of Routers:</b></td>\n";
	echo "<td class='TRS'>$RouterCount</td>\n";
	echo "<td class='TRS'>" . round((($RouterCount / $RouterCount) * 100),2) . "%</td>\n";	echo "</tr>\n";

	// Display number of routers in current result set
	echo "<tr>\n";
	echo "<td class='TRAR'><b>Routers in Current Query Result Set:</b></td>\n";
	echo "<td class='TRS'>$CurrentResultSet</td>\n";
	echo "<td class='TRS'>" . round((($CurrentResultSet / $RouterCount) * 100),2) . "%</td>\n";
	echo "</tr>\n";

	// Display total number of routers which are Authority servers
	echo "<tr>\n";
	echo "<td class='TRAR'><b>Total Number of 'Authority' Routers:</b></td>\n";
	echo "<td class='TRS'>" . $record['Authority'] . "</td>\n";
	echo "<td class='TRS'>" . round((($record['Authority'] / $RouterCount) * 100),2) . "%</td>\n";
	echo "</tr>\n";

	// Display total number of routers which are BadDirectory servers
	echo "<tr>\n";
	echo "<td class='TRAR'><b>Total Number of 'Bad Directory' Routers:</b></td>\n";
	echo "<td class='TRS'>" . $record['BadDirectory'] . "</td>\n";
	echo "<td class='TRS'>" . round((($record['BadDirectory'] / $RouterCount) * 100),2) . "%</td>\n";
	echo "</tr>\n";

	// Display total number of routers which are bad exits
	echo "<tr>\n";
	echo "<td class='TRAR'><b>Total Number of 'Bad Exit' Routers:</b></td>\n";
	echo "<td class='TRS'>" . $record['BadExit'] . "</td>\n";
	echo "<td class='TRS'>" . round((($record['BadExit'] / $RouterCount) * 100),2) . "%</td>\n";
	echo "</tr>\n";

	// Display total number of routers which are exits
	echo "<tr>\n";
	echo "<td class='TRAR'><b>Total Number of 'Exit' Routers:</b></td>\n";
	echo "<td class='TRS'>" . $record['Exit'] . "</td>\n";
	echo "<td class='TRS'>" . round((($record['Exit'] / $RouterCount) * 100),2) . "%</td>\n";
	echo "</tr>\n";

	// Display total number of routers which are fast
	echo "<tr>\n";
	echo "<td class='TRAR'><b>Total Number of 'Fast' Routers:</b></td>\n";
	echo "<td class='TRS'>" . $record['Fast'] . "</td>\n";
	echo "<td class='TRS'>" . round((($record['Fast'] / $RouterCount) * 100),2) . "%</td>\n";
	echo "</tr>\n";

	// Display total number of routers which are guards
	echo "<tr>\n";
	echo "<td class='TRAR'><b>Total Number of 'Guard' Routers:</b></td>\n";
	echo "<td class='TRS'>" . $record['Guard'] . "</td>\n";
	echo "<td class='TRS'>" . round((($record['Guard'] / $RouterCount) * 100),2) . "%</td>\n";
	echo "</tr>\n";

	// Display total number of routers hibernating
	echo "<tr>\n";
	echo "<td class='TRAR'><b>Total Number of 'Hibernating' Routers:</b></td>\n";
	echo "<td class='TRS'>" . $record['Hibernating'] . "</td>\n";
	echo "<td class='TRS'>" . round((($record['Hibernating'] / $RouterCount) * 100),2) . "%</td>\n";
	echo "</tr>\n";

	// Display total number of routers which are named
	echo "<tr>\n";
	echo "<td class='TRAR'><b>Total Number of 'Named' Routers:</b></td>\n";
	echo "<td class='TRS'>" . $record['Named'] . "</td>\n";
	echo "<td class='TRS'>" . round((($record['Named'] / $RouterCount) * 100),2) . "%</td>\n";
	echo "</tr>\n";

	// Display total number of routers which are stable
	echo "<tr>\n";
	echo "<td class='TRAR'><b>Total Number of 'Stable' Routers:</b></td>\n";
	echo "<td class='TRS'>" . $record['Stable'] . "</td>\n";
	echo "<td class='TRS'>" . round((($record['Stable'] / $RouterCount) * 100),2) . "%</td>\n";
	echo "</tr>\n";

	// Display total number of routers which are running
	echo "<tr>\n";
	echo "<td class='TRAR'><b>Total Number of 'Running' Routers:</b></td>\n";
	echo "<td class='TRS'>" . $record['Running'] . "</td>\n";
	echo "<td class='TRS'>" . round((($record['Running'] / $RouterCount) * 100),2) . "%</td>\n";
	echo "</tr>\n";

	// Display total number of routers which are valid
	echo "<tr>\n";
	echo "<td class='TRAR'><b>Total Number of 'Valid' Routers:</b></td>\n";
	echo "<td class='TRS'>" . $record['Valid'] . "</td>\n";
	echo "<td class='TRS'>" . round((($record['Valid'] / $RouterCount) * 100),2) . "%</td>\n";
	echo "</tr>\n";

	// Display total number of routers which are V2Dir ready
	echo "<tr>\n";
	echo "<td class='TRAR'><b>Total Number of 'V2Dir' Routers:</b></td>\n";
	echo "<td class='TRS'>" . $record['V2Dir'] . "</td>\n";
	echo "<td class='TRS'>" . round((($record['V2Dir'] / $RouterCount) * 100),2) . "%</td>\n";
	echo "</tr>\n";

	// Display total number of routers mirroring directory
	echo "<tr>\n";
	echo "<td class='TRAR'><b>Total Number of 'Directory Mirror' Routers:</b></td>\n";
	echo "<td class='TRS'>" . $record['DirMirror'] . "</td>\n";
	echo "<td class='TRS'>" . round((($record['DirMirror'] / $RouterCount) * 100),2) . "%</td>\n";
	echo "</tr>\n";
?>
</table>
</td>
</tr>
</table>

<br/>

<a name='TorServer'></a>

<table width='*' cellpadding='0' cellspacing='0' border='0' align='center'>
<tr>

<td>
<table width='*' cellspacing='2' cellpadding='2' border='0' align='center'>
<tr>
<td class='TDBLACK'>
<table cellspacing='2' cellpadding='6' border='0' align='center' width='100%'>
<tr>
<td class='THN'>Network Status Opinion Source</td>
</tr>
<tr>
<td class='TRSB'>
<?php
	
	echo "<br/>\n";
	echo "<b>Nickname:</b><br/><a class='plain' href='router_detail.php?FP=$Fingerprint'>" . $Name . "</a><br/>\n";
	echo "<b>Fingerprint:</b><br/>" . chunk_split(strtoupper($Fingerprint), 4, " ") . "<br/>\n";
	echo "<b>Country Code:</b><br/>"; if($CountryCode == null){echo "Unknown";}else{echo $CountryCode;} echo "<br/>\n";
	echo "<b>Contact:</b><br/>"; if($Contact == null){echo "None Given";} else{$Contact = htmlspecialchars($Contact, ENT_QUOTES); echo "$Contact";} echo "<br/>\n";
	echo "<b>Platform:</b><br/>" . $Platform . "<br/>\n";
	echo "<b>IP Address:</b><br/>" . $IP . "<br/>\n";
	echo "<b>Hostname:</b><br/>"; if ($IP == $Hostname){echo "Unavailable";} else{echo "$Hostname";} echo "<br/>\n";
	echo "<b>Onion Router Port:</b><br/>" . $ORPort . "<br/>\n";
	echo "<b>Directory Server Port:</b><br/>"; if($DirPort == 0){echo "None";} else {echo $DirPort;} echo "<br/>\n";
	echo "<b>Last Published Descriptor (GMT):</b><br/>" . $LastDescriptorPublished . "<br/><br/>\n";
	echo "<b>Onion Key:</b><pre>" . $OnionKey . "</pre>\n";
	echo "<b>Signing Key:</b><pre>" . $SigningKey . "</pre>\n";
	echo "<b>Descriptor Signature:</b><pre>" . $DescriptorSignature . "</pre>\n";
?>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>

<td>
<a name='CustomDisplay'></a>
<table width='100%' cellspacing='2' cellpadding='2' border='0' align='center'>
<tr>
<td class='TDBLACK'>
<table cellspacing='2' cellpadding='6' border='0' align='center' width='100%'>
<tr>
<td class='THN'>Custom / Advanced Display Options</td>
</tr>
<tr>
<td class='TRS'><br/><a class='plain' href='column_set.php'><b>Column Display Preferences</b></a><br/><br/></td>
</tr>
</table>
</td>
</tr>
</table>
<a name='CustomQuery'></a>
<table width='*' cellspacing='2' cellpadding='2' border='0' align='center'>
<tr>
<td class='TDBLACK'>
<table cellspacing='2' cellpadding='6' border='0' align='center' width='100%'>
<tr>
<td class='THN'>Custom / Advanced Query Options</td>
</tr>
<tr>
<td class='TRS'><br/>
<?php
	echo "<form action='$Self' method='post'>\n";
	echo "<b>Sort Router Listing By:</b><br/><span class='TRSM'>(Sorted-by column will be <i>italic</i>)<br/>(Column names can also be clicked to sort)</span><br/>\n";
	echo "<select name='SR' class='BOX'>\n";
	echo "<option value='Name'"; if ($SR == 'Name'){echo " selected='selected'";} echo ">Router Name</option>\n";
	echo "<option value='Fingerprint'"; if ($SR == 'Fingerprint'){echo " selected='selected'";} echo ">Fingerprint</option>\n";
	echo "<option value='CountryCode'"; if ($SR == 'CountryCode'){echo " selected='selected'";} echo ">Country Code</option>\n";
	echo "<option value='Bandwidth'"; if ($SR == 'Bandwidth'){echo " selected='selected'";} echo ">Bandwidth</option>\n";
	echo "<option value='Uptime'"; if ($SR == 'Uptime'){echo " selected='selected'";} echo ">Uptime</option>\n";
	echo "<option value='LastDescriptorPublished'"; if ($SR == 'LastDescriptorPublished'){echo " selected='selected'";} echo ">Last Descriptor Published</option>\n";
	echo "<option value='Hostname'"; if ($SR == 'Hostname'){echo " selected='selected'";} echo ">Hostname</option>\n";
	echo "<option value='IP'"; if ($SR == 'IP'){echo " selected='selected'";} echo ">IP Address</option>\n";
	echo "<option value='ORPort'"; if ($SR == 'ORPort'){echo " selected='selected'";} echo ">ORPort</option>\n";
	echo "<option value='DirPort'"; if ($SR == 'DirPort'){echo " selected='selected'";} echo ">DirPort</option>\n";
	echo "<option value='Platform'"; if ($SR == 'Platform'){echo " selected='selected'";} echo ">Platform</option>\n";
	echo "<option value='Contact'"; if ($SR == 'Contact'){echo " selected='selected'";} echo ">Contact</option>\n";
	echo "<option value='FAuthority'"; if ($SR == 'FAuthority'){echo " selected='selected'";} echo ">Authority</option>\n";
	echo "<option value='FBadDirectory'"; if ($SR == 'FBadDirectory'){echo " selected='selected'";} echo ">Bad Directory</option>\n";
	echo "<option value='FBadExit'"; if ($SR == 'FBadExit'){echo " selected='selected'";} echo ">Bad Exit</option>\n";
	echo "<option value='FExit'"; if ($SR == 'FExit'){echo " selected='selected'";} echo ">Exit</option>\n";
	echo "<option value='FFast'"; if ($SR == 'FFast'){echo " selected='selected'";} echo ">Fast</option>\n";
	echo "<option value='FGuard'"; if ($SR == 'FGuard'){echo " selected='selected'";} echo ">Guard</option>\n";
	echo "<option value='Hibernating'"; if ($SR == 'Hibernating'){echo " selected='selected'";} echo ">Hibernating</option>\n";
	echo "<option value='FNamed'"; if ($SR == 'FNamed'){echo " selected='selected'";} echo ">Named</option>\n";
	echo "<option value='FStable'"; if ($SR == 'FStable'){echo " selected='selected'";} echo ">Stable</option>\n";
	echo "<option value='FRunning'"; if ($SR == 'FRunning'){echo " selected='selected'";} echo ">Running</option>\n";
	echo "<option value='FValid'"; if ($SR == 'FValid'){echo " selected='selected'";} echo ">Valid</option>\n";
	echo "<option value='FV2Dir'"; if ($SR == 'FV2Dir'){echo " selected='selected'";} echo ">V2Dir</option>\n";
	echo "</select><br/><br/>\n";
	echo "<b>Sort Order:</b><br/><span class='TRSM'>(Column names can also be clicked to toggle)</span><br/>\n";
	echo "<select name='SO' class='BOX'>\n";
	echo "<option value='Asc'"; if ($SO == 'Asc'){echo " selected='selected'";} echo ">Ascending</option>\n";
	echo "<option value='Desc'"; if ($SO == 'Desc'){echo " selected='selected'";} echo ">Descending</option>\n";
	echo "</select><br/><br/>\n";
	echo "<b>Require Flags:</b><br/><span class='TRSM'>(Columns flagged YES will have <font color='#00dd00'>green</font> background)<br/>(Columns flagged NO will have <font color='#ff0000'>red</font> background)</span><br/>\n";
	echo "<table width='*' cellspacing='0' cellpadding='0' border='0' align='left'>\n";
	echo "<tr>\n";
	echo "<td class='TRS'>&nbsp;Authority:</td>\n";
	echo "<td class='TRS'>\n";
	echo "<input type='radio' name='FAuthority' value='OFF'"; if($FAuthority == 'OFF'){echo " checked='checked' />Off&nbsp;\n";}else{echo " />Off&nbsp;\n";}
	echo "<input type='radio' name='FAuthority' value='1'"; if($FAuthority == '1'){echo " checked='checked' />Yes&nbsp;\n";}else{echo " />Yes&nbsp;\n";}
	echo "<input type='radio' name='FAuthority' value='0'"; if($FAuthority == '0'){echo " checked='checked' />No\n";}else{echo " />No\n";}
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='TRS'>&nbsp;BadDirectory:</td>\n";
	echo "<td class='TRS'>\n";
	echo "<input type='radio' name='FBadDirectory' value='OFF'"; if($FBadDirectory == 'OFF'){echo " checked='checked' />Off&nbsp;\n";}else{echo " />Off&nbsp;\n";}
	echo "<input type='radio' name='FBadDirectory' value='1'"; if($FBadDirectory == '1'){echo " checked='checked' />Yes&nbsp;\n";}else{echo " />Yes&nbsp;\n";}
	echo "<input type='radio' name='FBadDirectory' value='0'"; if($FBadDirectory == '0'){echo " checked='checked' />No\n";}else{echo " />No\n";}
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='TRS'>&nbsp;BadExit:</td>\n";
	echo "<td class='TRS'>\n";
	echo "<input type='radio' name='FBadExit' value='OFF'"; if($FBadExit == 'OFF'){echo " checked='checked' />Off&nbsp;\n";}else{echo " />Off&nbsp;\n";}
	echo "<input type='radio' name='FBadExit' value='1'"; if($FBadExit == '1'){echo " checked='checked' />Yes&nbsp;\n";}else{echo " />Yes&nbsp;\n";}
	echo "<input type='radio' name='FBadExit' value='0'"; if($FBadExit == '0'){echo " checked='checked' />No\n";}else{echo " />No\n";}
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='TRS'>&nbsp;Exit:</td>\n";
	echo "<td class='TRS'>\n";
	echo "<input type='radio' name='FExit' value='OFF'"; if($FExit == 'OFF'){echo " checked='checked' />Off&nbsp;\n";}else{echo " />Off&nbsp;\n";}
	echo "<input type='radio' name='FExit' value='1'"; if($FExit == '1'){echo " checked='checked' />Yes&nbsp;\n";}else{echo " />Yes&nbsp;\n";}
	echo "<input type='radio' name='FExit' value='0'"; if($FExit == '0'){echo " checked='checked' />No\n";}else{echo " />No\n";}
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='TRS'>&nbsp;Fast:</td>\n";
	echo "<td class='TRS'>\n";
	echo "<input type='radio' name='FFast' value='OFF'"; if($FFast == 'OFF'){echo " checked='checked' />Off&nbsp;\n";}else{echo " />Off&nbsp;\n";}
	echo "<input type='radio' name='FFast' value='1'"; if($FFast == '1'){echo " checked='checked' />Yes&nbsp;\n";}else{echo " />Yes&nbsp;\n";}
	echo "<input type='radio' name='FFast' value='0'"; if($FFast == '0'){echo " checked='checked' />No\n";}else{echo " />No\n";}
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='TRS'>&nbsp;Guard:</td>\n";
	echo "<td class='TRS'>\n";
	echo "<input type='radio' name='FGuard' value='OFF'"; if($FGuard == 'OFF'){echo " checked='checked' />Off&nbsp;\n";}else{echo " />Off&nbsp;\n";}
	echo "<input type='radio' name='FGuard' value='1'"; if($FGuard == '1'){echo " checked='checked' />Yes&nbsp;\n";}else{echo " />Yes&nbsp;\n";}
	echo "<input type='radio' name='FGuard' value='0'"; if($FGuard == '0'){echo " checked='checked' />No\n";}else{echo " />No\n";}
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='TRS'>&nbsp;Hibernating:</td>\n";
	echo "<td class='TRS'>\n";
	echo "<input type='radio' name='FHibernating' value='OFF'"; if($FHibernating == 'OFF'){echo " checked='checked' />Off&nbsp;\n";}else{echo " />Off&nbsp;\n";}
	echo "<input type='radio' name='FHibernating' value='1'"; if($FHibernating == '1'){echo " checked='checked' />Yes&nbsp;\n";}else{echo " />Yes&nbsp;\n";}
	echo "<input type='radio' name='FHibernating' value='0'"; if($FHibernating == '0'){echo " checked='checked' />No\n";}else{echo " />No\n";}
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='TRS'>&nbsp;Named:</td>\n";
	echo "<td class='TRS'>\n";
	echo "<input type='radio' name='FNamed' value='OFF'"; if($FNamed == 'OFF'){echo " checked='checked' />Off&nbsp;\n";}else{echo " />Off&nbsp;\n";}
	echo "<input type='radio' name='FNamed' value='1'"; if($FNamed == '1'){echo " checked='checked' />Yes&nbsp;\n";}else{echo " />Yes&nbsp;\n";}
	echo "<input type='radio' name='FNamed' value='0'"; if($FNamed == '0'){echo " checked='checked' />No\n";}else{echo " />No\n";}
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='TRS'>&nbsp;Stable:</td>\n";
	echo "<td class='TRS'>\n";
	echo "<input type='radio' name='FStable' value='OFF'"; if($FStable == 'OFF'){echo " checked='checked' />Off&nbsp;\n";}else{echo " />Off&nbsp;\n";}
	echo "<input type='radio' name='FStable' value='1'"; if($FStable == '1'){echo " checked='checked' />Yes&nbsp;\n";}else{echo " />Yes&nbsp;\n";}
	echo "<input type='radio' name='FStable' value='0'"; if($FStable == '0'){echo " checked='checked' />No\n";}else{echo " />No\n";}
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='TRS'>&nbsp;Running:</td>\n";
	echo "<td class='TRS'>\n";
	echo "<input type='radio' name='FRunning' value='OFF'"; if($FRunning == 'OFF'){echo " checked='checked' />Off&nbsp;\n";}else{echo " />Off&nbsp;\n";}
	echo "<input type='radio' name='FRunning' value='1'"; if($FRunning == '1'){echo " checked='checked' />Yes&nbsp;\n";}else{echo " />Yes&nbsp;\n";}
	echo "<input type='radio' name='FRunning' value='0'"; if($FRunning == '0'){echo " checked='checked' />No\n";}else{echo " />No\n";}
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='TRS'>&nbsp;Valid:</td>\n";
	echo "<td class='TRS'>\n";
	echo "<input type='radio' name='FValid' value='OFF'"; if($FValid == 'OFF'){echo " checked='checked' />Off&nbsp;\n";}else{echo " />Off&nbsp;\n";}
	echo "<input type='radio' name='FValid' value='1'"; if($FValid == '1'){echo " checked='checked' />Yes&nbsp;\n";}else{echo " />Yes&nbsp;\n";}
	echo "<input type='radio' name='FValid' value='0'"; if($FValid == '0'){echo " checked='checked' />No\n";}else{echo " />No\n";}
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='TRS'>&nbsp;V2Dir:</td>\n";
	echo "<td class='TRS'>\n";
	echo "<input type='radio' name='FV2Dir' value='OFF'"; if($FV2Dir == 'OFF'){echo " checked='checked' />Off&nbsp;\n";}else{echo " />Off&nbsp;\n";}
	echo "<input type='radio' name='FV2Dir' value='1'"; if($FV2Dir == '1'){echo " checked='checked' />Yes&nbsp;\n";}else{echo " />Yes&nbsp;\n";}
	echo "<input type='radio' name='FV2Dir' value='0'"; if($FV2Dir == '0'){echo " checked='checked' />No\n";}else{echo " />No\n";}
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='TRS' colspan='4'><br/>\n";
	echo "<b>Advanced Search:</b><br/><span class='TRSM'>(Clear search box to disable)</span><br/>\n";
	echo "<select name='CSField' class='BOX'>\n";
	echo "<option value='Fingerprint'"; if ($CSField == 'Fingerprint'){echo " selected='selected'";} echo ">Fingerprint</option>\n";
	echo "<option value='Name'"; if ($CSField == 'Name'){echo " selected='selected'";} echo ">Router Name</option>\n";
	echo "<option value='CountryCode'"; if ($CSField == 'CountryCode'){echo " selected='selected'";} echo ">Country Code</option>\n";
	echo "<option value='Bandwidth'"; if ($CSField == 'Bandwidth'){echo " selected='selected'";} echo ">Bandwidth (KB/s)</option>\n";
	echo "<option value='Uptime'"; if ($CSField == 'Uptime'){echo " selected='selected'";} echo ">Uptime (Days)</option>\n";
	echo "<option value='LastDescriptorPublished'"; if ($CSField == 'LastDescriptorPublished'){echo " selected='selected'";} echo ">Last Descriptor Published</option>\n";
	echo "<option value='IP'"; if ($CSField == 'IP'){echo " selected='selected'";} echo ">IP Address</option>\n";
	echo "<option value='Hostname'"; if ($CSField == 'Hostname'){echo " selected='selected'";} echo ">Hostname</option>\n";
	echo "<option value='ORPort'"; if ($CSField == 'ORPort'){echo " selected='selected'";} echo ">Onion Router Port</option>\n";
	echo "<option value='DirPort'"; if ($CSField == 'DirPort'){echo " selected='selected'";} echo ">Directory Server Port</option>\n";
	echo "<option value='Platform'"; if ($CSField == 'Platform'){echo " selected='selected'";} echo ">Platform</option>\n";
	echo "<option value='Contact'"; if ($CSField == 'Contact'){echo " selected='selected'";} echo ">Contact</option>\n";
	echo "</select>\n";
	echo "<select name='CSMod' class='BOX'>\n";
	echo "<option value='Equals'"; if ($CSMod == 'Equals'){echo " selected='selected'";} echo ">Equals</option>\n";
	echo "<option value='Contains'"; if ($CSMod == 'Contains'){echo " selected='selected'";} echo ">Contains</option>\n";
	echo "<option value='LessThan'"; if ($CSMod == 'LessThan'){echo " selected='selected'";} echo ">Is Less Than</option>\n";
	echo "<option value='GreaterThan'"; if ($CSMod == 'GreaterThan'){echo " selected='selected'";} echo ">Is Greater Than</option>\n";
	echo "</select><br/>\n";
	echo "<input type='text' name='CSInput' class='BOX' maxlength='128' size='45' value='" . htmlspecialchars($CSInput, ENT_QUOTES) . "' /><br/><br/>\n";
	echo "&nbsp;&nbsp;<input type='submit' value='Apply Options' /><br/><br/>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "</form>\n";
?>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>

<br/>

<a name="AppServer"></a>

<table width='50%' cellspacing='2' cellpadding='2' border='0' align='center'>
<tr>
<td class='TDBLACK'>
	
<table cellspacing='2' cellpadding='2' border='0' align='center' width='100%'>
<tr>
<td class='THN' colspan='2'>Application Server Details</td>
</tr>

<?php

	echo "<tr>\n";
	echo "<td class='TRAR'><b>Cache Last Updated (Local Server Time):</b></td>\n";
	echo "<td class='TRS'>$LastUpdate $LocalTimeZone</td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td class='TRAR'><b>Last Update Cycle Processing Time (Seconds):</b></td>\n";
	echo "<td class='TRS'>$LastUpdateElapsed</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='TRAR'><b>Current Cache Expire Time (Seconds):</b></td>\n";
	echo "<td class='TRS'>$Cache_Expire_Time</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='TRAR'><b>Number of Routers In Cache:</b></td>\n";
	echo "<td class='TRS'>$RouterCount</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='TRAR'><b>Number of Descriptors In Cache:</b></td>\n";
	echo "<td class='TRS'>$DescriptorCount</td>\n";
	echo "</tr>\n";
	
	// Get script end time
	$TimeStop = microtime(true);

	echo "<tr>\n";
	echo "<td class='TRAR'><b>Approximate Page Generation Time (Seconds):</b></td>\n";
	echo "<td class='TRS'>" . (round(($TimeStop - $TimeStart),4)) . "</td>\n";
	echo "</tr>\n";

?>
</table>
</td>
</tr>
</table>

<br/>

<table width='70%' cellspacing='2' cellpadding='2' border='0' align='center'>
<tr>
<td class='TRC'><?php echo $footerText; ?></td>
</tr>
</table>
</body>
</html>

<?php

// Close connection
mysql_close($link);

// Register session variable to mark that this page has been loaded
if (!isset($_SESSION['IndexVisited'])) 
{
	$_SESSION['IndexVisited'] = 1;
} 

?>
