<?php
	function logmessage($message) {
		shell_exec("sudo echo '" . $message . "' | sudo tee --append /var/log/raspberrywap.log");
	}
	
	function mask2cidr($mask){
	  $long = ip2long($mask);
	  $base = ip2long('255.255.255.255');
	  return 32-log(($long ^ $base)+1,2);
	
	  /* xor-ing will give you the inverse mask,
		  log base 2 of that +1 will return the number
		  of bits that are off in the mask and subtracting
		  from 32 gets you the cidr notation */
			
	}

	function cidr2broadcast($network, $cidr) {
	  $broadcast = long2ip(ip2long($network) + pow(2, (32 - $cidr)) - 2);
	  return $broadcast;
	}

	function test_input($data) {
	  $data = trim($data);
	  $data = stripslashes($data);
	  $data = htmlspecialchars($data);
	  return $data;
	}
	

	function hostapd_addbridge($action) {
	  $hostapdconfig = parse_ini_file("/etc/hostapd/hostapd.conf");
	  switch($action) {
		  case "enable":
			  if(!walk($hostapdconfig, 'bridge'))
				  $hostapdconfig['bridge'] = "br0";
				  write_hostapd_conf($hostapdconfig,"/etc/hostapd/hostapd.conf"); 
		  break;
		  case "disable":
			  if(walk($hostapdconfig, 'bridge'))
				  unset($hostapdconfig['bridge']);
				  write_hostapd_conf($hostapdconfig,"/etc/hostapd/hostapd.conf"); 
		  break;
	  }
	}

  
	function write_php_ini($array, $file)
	{
		$res = array();
		foreach($array as $key => $val)
		{
			if(is_array($val))
			{
				$res[] = "[$key]";
				foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
			}
			else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
		}
		safefilerewrite($file, implode("\r\n", $res));
	}

	function write_hostapd_conf($array, $file)
	{
		$res = array();
		foreach($array as $key => $val)
		{
			if(is_array($val))
			{
				$res[] = "[$key]";
				foreach($val as $skey => $sval) $res[] = "$skey=$sval";
			}
			else $res[] = "$key=$val";
		}
		safefilerewrite($file, implode("\n", $res));
	}
	
	
	
	
	function safefilerewrite($fileName, $dataToSave)
	{    if ($fp = fopen($fileName, 'w'))
		{
			$startTime = microtime();
			do
			{            $canWrite = flock($fp, LOCK_EX);
			   // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
			   if(!$canWrite) usleep(round(rand(0, 100)*1000));
			} while ((!$canWrite)and((microtime()-$startTime) < 1000));
	
			//file was locked so now we can store information
			if ($canWrite)
			{            fwrite($fp, $dataToSave);
				flock($fp, LOCK_UN);
			}
			fclose($fp);
		}
	}


	function walk($array, $key)
	{
	  if( !is_array( $array)) 
	  {
		  return false;
	  }
	  foreach ($array as $k => $v)
	  {
		  if($k == $key)
		  {
			  return True;
		  }
	  }
	return false;
	}


	function update_interfaces_file($select)
	{
	//read our config file
	  $configurationsettings = parse_ini_file("/home/pi/Raspberry-Wifi-Router/www/routersettings.ini");
	//declare arrays which will hold our configuration settings
	  $interfaces = array();
	  $dhcpcd = array();
	  $hostapdservice = array();
	  $resolvconfhead = array();
	  
	
	//populate our interfaces array with some original file contents
	  array_push($interfaces,"# interfaces(5) file used by ifup(8) and ifdown(8)\n\n");
	  array_push($interfaces,"# Please note that this file is written to be used with dhcpcd\n");
	  array_push($interfaces,"# For static IP, consult /etc/dhcpcd.conf and 'man dhcpcd.conf'\n\n");
	  array_push($interfaces,"# Include files from /etc/network/interfaces.d:\n");
	  array_push($interfaces,"source-directory /etc/network/interfaces.d\n\n");
	
	//populate our dhcpcd array with the original file contents
	  array_push($dhcpcd,"# A sample configuration for dhcpcd.\n");
	  array_push($dhcpcd,"# See dhcpcd.conf(5) for details.\n\n");
	  array_push($dhcpcd,"# Allow users of this group to interact with dhcpcd via the control socket.\n");
	  array_push($dhcpcd,"#controlgroup wheel\n\n");
	  array_push($dhcpcd,"# Inform the DHCP server of our hostname for DDNS.\n");
	  array_push($dhcpcd,"hostname\n\n");
	  array_push($dhcpcd,"# Use the hardware address of the interface for the Client ID.\n");
	  array_push($dhcpcd,"clientid\n");
	  array_push($dhcpcd,"# or\n");
	  array_push($dhcpcd,"# Use the same DUID + IAID as set in DHCPv6 for DHCPv4 ClientID as per RFC4361.\n");
	  array_push($dhcpcd,"#duid\n\n");
	  array_push($dhcpcd,"# Persist interface configuration when dhcpcd exits.\n");
	  array_push($dhcpcd,"persistent\n\n");
	  array_push($dhcpcd,"# Rapid commit support.\n");
	  array_push($dhcpcd,"# Safe to enable by default because it requires the equivalent option set\n");
	  array_push($dhcpcd,"# on the server to actually work.\n");
	  array_push($dhcpcd,"option rapid_commit\n\n");
	  array_push($dhcpcd,"# A list of options to request from the DHCP server.\n");
	  array_push($dhcpcd,"option domain_name_servers, domain_name, domain_search, host_name\n");
	  array_push($dhcpcd,"option classless_static_routes\n");
	  array_push($dhcpcd,"# Most distributions have NTP support.\n");
	  array_push($dhcpcd,"option ntp_servers\n");
	  array_push($dhcpcd,"# Respect the network MTU.\n");
	  array_push($dhcpcd,"# Some interface drivers reset when changing the MTU so disabled by default.\n");
	  array_push($dhcpcd,"#option interface_mtu\n\n");
	  array_push($dhcpcd,"# A ServerID is required by RFC2131.\n");
	  array_push($dhcpcd,"require dhcp_server_identifier\n\n");
	  array_push($dhcpcd,"# Generate Stable Private IPv6 Addresses instead of hardware based ones\n");
	  array_push($dhcpcd,"slaac private\n\n");
	  array_push($dhcpcd,"# A hook script is provided to lookup the hostname if not set by the DHCP\n");
	  array_push($dhcpcd,"# server, but it should not be run by default.\n");
	  array_push($dhcpcd,"nohook lookup-hostname\n\n");
	  
	//populate our dhcpcd array with the original file contents
	  array_push($hostapdservice,"[Unit]\n");
	  array_push($hostapdservice,"Description=hostapd service\n");
	  array_push($hostapdservice,"Wants=network.target\n");
	  array_push($hostapdservice,"Before=network.target\n");
	  array_push($hostapdservice,"BindsTo=sys-subsystem-net-devices-wlan0.device\n");
	  array_push($hostapdservice,"After=sys-subsystem-net-devices-wlan0.device\n\n");
	  array_push($hostapdservice,"[Service]\n");
	  array_push($hostapdservice,"Type=oneshot\n");
	  array_push($hostapdservice,"RemainAfterExit=yes\n");
	  array_push($hostapdservice,"ExecStart=/sbin/ip link set dev wlan0 up\n");
	  array_push($hostapdservice,"ExecStart=/usr/local/bin/hostapd -B /etc/hostapd/hostapd.conf\n");
	  array_push($hostapdservice,"ExecStop=/sbin/ip addr flush dev wlan0\n");
	  array_push($hostapdservice,"ExecStop=/sbin/ip link set dev wlan0 down\n\n");
	  array_push($hostapdservice,"[Install]\n");
	  array_push($hostapdservice,"WantedBy=multi-user.target\n");

	  switch ($select) {
		case "Router":
		  //operationmode Router - prepare interfaces file contents
		  
		  //push the settings for the loopback adapter up the array
			array_push($interfaces,"auto lo eth0 wlan0\n");
			array_push($interfaces,"iface lo inet loopback\n\n");

		  //push the settings for the eth0 adapter up the array
			array_push($interfaces,"iface eth0 inet manual\n");
			array_push($interfaces,"iface wlan0 inet manual\n");
			array_push($interfaces,"wpa-conf /etc/wpa_supplicant/wpa_supplicant.conf\n");
			  
		  
		  //if a mac address has been entered for the lan, configure it at boot time	
			
			$strdata = file_get_contents ("/boot/cmdline.txt");
			$arrdata = explode (" ",$strdata);
			
			foreach($arrdata as $key => $value) {
			  if (strpos($value, 'smsc95xx.macaddr=') !== FALSE) {
				unset($arrdata[$key]);
			  }
			}
			
			if(!empty($configurationsettings['lanmac'])) {
			  array_push($arrdata,"smsc95xx.macaddr=" . $configurationsettings['lanmac']);
			}
			else {
			  array_push($arrdata,"smsc95xx.macaddr=20:11:22:33:44:55");
			}
			$arrdata = str_replace("\n","",$arrdata);
			
			logmessage("Unmounting boot partition.");
			shell_exec("sudo umount /dev/mmcblk0p1 2>&1 | sudo tee --append /var/log/raspberrywap.log");
			logmessage("Mounting boot partition read-write.");
			shell_exec("sudo mount -o rw,relatime,fmask=0000,dmask=0022,codepage=437,iocharset=ascii,shortname=mixed,errors=remount-ro /dev/mmcblk0p1 /boot 2>&1 | sudo tee --append /var/log/raspberrywap.log");
			logmessage("Saving mac address changes to /boot/cmdline.txt");
			file_put_contents("/boot/cmdline.txt",implode(" ",$arrdata));
			logmessage("Unmounting boot partition.");
			shell_exec("sudo umount /dev/mmcblk0p1 2>&1 | sudo tee --append /var/log/raspberrywap.log");
			logmessage("Mounting boot partition read-only.");
			shell_exec("sudo mount -o ro,relatime,fmask=0022,dmask=0022,codepage=437,iocharset=ascii,shortname=mixed,errors=remount-ro /dev/mmcblk0p1 /boot 2>&1 | sudo tee --append /var/log/raspberrywap.log");
		  
		  //if there is a mtu value entered, save it to /etc/rc.local for reboot activation
			$strdata = file_get_contents ("/etc/rc.local");
			$arrdata = explode ("\n",$strdata);
			
			foreach($arrdata as $value) {
			  if (strpos($value, 'sudo ip link set dev') !== FALSE) {
				unset($arrdata[$value]);
			  }
			}
			
			if(!empty($configurationsettings['lanmtu'])) {
				array_splice($arrdata,13,0,"sudo ip link set dev eth0 mtu " . $configurationsettings['lanmtu']);
			}			
		  
		  //set our fixed ip address for the wifi network
			array_splice($hostapdservice,10,0,"ExecStart=/sbin/ip addr add " . $configurationsettings['wifiip'] . "/" . mask2cidr($configurationsettings['wifimask']) . " broadcast " . cidr2broadcast($configurationsettings['wifiip'], mask2cidr($configurationsettings['wifimask'])) . " dev wlan0\n");
		  
		  //set dhcp clientid if given
		  if(!empty($configurationsettings['dhcpclientid'])) {
			  array_push($dhcpcd,"clientid " . $configurationsettings['dhcpclientid'] . "\n\n");
		  }
		  
		  //set eth0 as only allowed interface for addressing by dhcpcd
			array_push($dhcpcd,"allowinterfaces eth0 wlan0\n\n");
			
		  //set static dns entries if in dhcp mode and dns override enabled
			if (strcmp($configurationsettings['lantype'],"dhcp") == 0 && strcmp($configurationsettings['dhcpdnsoverride'],"enabled") == 0) {
				if(!empty($configurationsettings['dns1']) || !empty($configurationsettings['dns2'])) {
					if(!empty($configurationsettings['dns1']) && empty($configurationsettings['dns2'])) {
					  array_push($resolvconfhead,"nameserver " . $configurationsettings['dns1'] . "\n");
					}
					else if(empty($configurationsettings['dns1']) && !empty($configurationsettings['dns2'])) {
					  array_push($resolvconfhead,"nameserver " . $configurationsettings['dns2'] . "\n");
					}
					else if(!empty($configurationsettings['dns1']) && !empty($configurationsettings['dns2'])) {
					  array_push($resolvconfhead,"nameserver " . $configurationsettings['dns1'] . "\n" . "nameserver " . $configurationsettings['dns2'] . "\n");
					}
				}
			}

		  //if static details are entered configure them for dhcpcd
			if (strcmp($configurationsettings['lantype'],"static") == 0) {
				array_push($dhcpcd,"interface eth0\n");
				array_push($dhcpcd,"static ip_address=" . $configurationsettings['lanip'] . "/" . mask2cidr($configurationsettings['lanmask']) . "\n");
				if(!empty($configurationsettings['langw'])) {
				  array_push($dhcpcd,"static routers=" . $configurationsettings['langw'] . "\n");
				}
				if(!empty($configurationsettings['dns1']) || !empty($configurationsettings['dns2'])) {
					array_push($dhcpcd,"static domain_name_servers=");
					if(!empty($configurationsettings['dns1'])) {
					  array_push($dhcpcd,$configurationsettings['dns1'] . " ");
					}
					if(!empty($configurationsettings['dns2'])) {
					  array_push($dhcpcd,$configurationsettings['dns2']);
					}
				}
			}
			
		  //write configuration files back to disk
			logmessage("Writing changes to /etc/network/interfaces");
			file_put_contents("/etc/network/interfaces",implode($interfaces));
			logmessage("Writing changes to /etc/dhcpcd.conf");
			file_put_contents("/etc/dhcpcd.conf",implode($dhcpcd));
			logmessage("Writing changes to /etc/resolv.conf.head");
			file_put_contents("/etc/resolv.conf.head",implode($resolvconfhead));
			logmessage("Writing changes to /etc/systemd/system/hostapd.service");
			file_put_contents("/etc/systemd/system/hostapd.service",implode($hostapdservice));
			shell_exec("sudo systemctl daemon-reload");
		break;
	
		  case "Access Point":
			//operationmode access point - prepare interfaces file contents	

			//push the settings for the loopback adapter up the array
			  array_push($interfaces,"auto lo\n");
			  array_push($interfaces,"iface lo inet loopback\n\n");

			//push the settings for the eth0 adapter up the array
			  array_push($interfaces,"iface eth0 inet manual\n");
			  
			//push the settings for the br0 adapter up the array
			  array_push($interfaces,"auto br0\n");
			  array_push($interfaces,"iface br0 inet manual\n");
			  array_push($interfaces,"bridge_ports wlan0 eth0\n");
			  array_push($interfaces,"bridge_stp off\n");

			//if a mac address has been entered for the lan, configure it at boot time	
			  if(!empty($configurationsettings['lanmac'])) {
				array_push($interfaces,"post-up ip link set br0 address " . $configurationsettings['lanmac'] . "\n");
			  }

			//if no mac address has been entered for the interface br0, configure our default mac address
			  else {
				array_push($interfaces,"post-up ip link set br0 address 20:11:22:33:44:55" . "\n");
			  //change eth0 mac address in our boot config, since both br0 and eth0 cannot be the same
				$strdata = file_get_contents ("/boot/cmdline.txt");
				$arrdata = explode (" ",$strdata);
				foreach($arrdata as $key => $value) {
				  if (strpos($value, 'smsc95xx.macaddr=') !== FALSE) {
					unset($arrdata[$key]);
				  }
				}
				array_push($arrdata,"smsc95xx.macaddr=20:11:22:33:44:56");
				$arrdata = str_replace("\n","",$arrdata);
			  }

			  logmessage("Unmounting boot partition.");
			  shell_exec("sudo umount /dev/mmcblk0p1 2>&1 | sudo tee --append /var/log/raspberrywap.log");
			  logmessage("Mounting boot partition read-write.");
			  shell_exec("sudo mount -o rw,relatime,fmask=0000,dmask=0022,codepage=437,iocharset=ascii,shortname=mixed,errors=remount-ro /dev/mmcblk0p1 /boot 2>&1 | sudo tee --append /var/log/raspberrywap.log");
			  logmessage("Saving mac address changes to /boot/cmdline.txt");
			  file_put_contents("/boot/cmdline.txt",implode(" ",$arrdata));
			  logmessage("Unmounting boot partition.");
			  shell_exec("sudo umount /dev/mmcblk0p1 2>&1 | sudo tee --append /var/log/raspberrywap.log");
			  logmessage("Mounting boot partition read-only.");
			  shell_exec("sudo mount -o ro,relatime,fmask=0022,dmask=0022,codepage=437,iocharset=ascii,shortname=mixed,errors=remount-ro /dev/mmcblk0p1 /boot 2>&1 | sudo tee --append /var/log/raspberrywap.log");

			//if there is a mtu value entered, save it to /etc/rc.local for reboot activation
			  $strdata = file_get_contents ("/etc/rc.local");
			  $arrdata = explode ("\n",$strdata);
			  
			  foreach($arrdata as $value) {
				if (strpos($value, 'sudo ip link set dev') !== FALSE) {
				  unset($arrdata[$value]);
				}
			  }
			  
			  if(!empty($configurationsettings['lanmtu'])) {
				  array_splice($arrdata,13,0,"sudo ip link set dev br0 mtu " . $configurationsettings['lanmtu']);
			  }			

			//set dhcp clientid if given
			if(!empty($configurationsettings['dhcpclientid'])) {
				array_push($dhcpcd,"clientid " . $configurationsettings['dhcpclientid'] . "\n\n");
			}
		
			//set br0 as only allowed interface for addressing by dhcpcd
			  array_push($dhcpcd,"allowinterfaces br0\n\n");

		  //set static dns entries if in dhcp mode and dns override enabled
			if (strcmp($configurationsettings['lantype'],"dhcp") == 0 && strcmp($configurationsettings['dhcpdnsoverride'],"enabled") == 0) {
				array_push($dhcpcd,"interface br0\n");
				
				if(!empty($configurationsettings['dns1']) || !empty($configurationsettings['dns2'])) {
					if(!empty($configurationsettings['dns1']) && empty($configurationsettings['dns2'])) {
					  array_push($resolvconfhead,"nameserver " . $configurationsettings['dns1'] . "\n");
					}
					else if(empty($configurationsettings['dns1']) && !empty($configurationsettings['dns2'])) {
					  array_push($resolvconfhead,"nameserver " . $configurationsettings['dns2'] . "\n");
					}
					else if(!empty($configurationsettings['dns1']) && !empty($configurationsettings['dns2'])) {
					  array_push($resolvconfhead,"nameserver " . $configurationsettings['dns1'] . "\n" . "nameserver " . $configurationsettings['dns2'] . "\n");
					}
				}
			}

			  
			//if static details entered, reconfigure dhcpcd
			  if (strcmp($configurationsettings['lantype'],"static") == 0) {
				  array_push($dhcpcd,"interface br0\n");
				  array_push($dhcpcd,"static ip_address=" . $configurationsettings['lanip'] . "/" . mask2cidr($configurationsettings['lanmask']) . "\n");
				  if(!empty($configurationsettings['langw'])) {
					array_push($dhcpcd,"static routers=" . $configurationsettings['langw'] . "\n");
				  }
				  if(!empty($configurationsettings['dns1']) || !empty($configurationsettings['dns2'])) {
					  array_push($dhcpcd,"static domain_name_servers=" . $configurationsettings['langw'] . "\n");
					  if(!empty($configurationsettings['dns1'])) {
						array_push($dhcpcd,$configurationsettings['dns1'] . " ");
					  }
					  if(!empty($configurationsettings['dns2'])) {
						array_push($dhcpcd,$configurationsettings['dns2']);
					  }
				  }
			  }

			//write all configuration files to disk
			  logmessage("Writing changes to /etc/network/interfaces");
			  file_put_contents("/etc/network/interfaces",implode($interfaces));
			  logmessage("Writing changes to /etc/dhcpcd.conf");
			  file_put_contents("/etc/dhcpcd.conf",implode($dhcpcd));
			  logmessage("Writing changes to /etc/resolv.conf.head");
			  file_put_contents("/etc/resolv.conf.head",implode($resolvconfhead));
			  logmessage("Writing changes to /etc/systemd/system/hostapd.service");
			  file_put_contents("/etc/systemd/system/hostapd.service",implode($hostapdservice));
			  shell_exec("sudo systemctl daemon-reload");
		  break;
	  }
	} // end function

?> 


