########################################################################################
# Installation procedure for the Wireless Access Router
########################################################################################

# This procedure was designed on top of a foundation Raspbian Jessie lite image with build date 18-03-2016
# Download the latest Raspbian Jessie Lite image from https://downloads.raspberrypi.org/raspbian_lite_latest
# Unzip your downloaded image, and write it to SD card with win32 disk imager.
# Boot up your SD card and Log into the Jessie OS, with pi as username and as password.
# Start executing below commands in sequence.

########################################################################################
# Bootstrap - Preparing the Raspbian OS.
########################################################################################
# Regen our security keys, it's a best practice
sudo /bin/rm -v /etc/ssh/ssh_host_*
sudo ssh-keygen -t dsa -N "" -f /etc/ssh/ssh_host_dsa_key
sudo ssh-keygen -t rsa -N "" -f /etc/ssh/ssh_host_rsa_key
sudo ssh-keygen -t ecdsa -N "" -f /etc/ssh/ssh_host_ecdsa_key
sudo ssh-keygen -t ed25519 -N "" -f /etc/ssh/ssh_host_ed25519_key
sudo systemctl restart sshd.service
# Resize our root partition to maximum size
sudo raspi-config --expand-rootfs
sudo partprobe
sudo resize2fs /dev/mmcblk0p2
# update raspbian
sudo apt-get update && sudo apt-get -y upgrade

########################################################################################
# Update Firmware - Making sure that your Raspbian firmware is the latest version.
########################################################################################
sudo apt-get -y install r
-update
sudo r
-update
sudo reboot

########################################################################################
# Set-up git and clone our repository into place.
########################################################################################
# Install git and clone our repository
sudo apt-get -y install git-core
git clone https://github.com/ronnyvdbr/Raspberry-Wifi-Router.git /home/admin/Raspberry-Wifi-Router

########################################################################################
# Set-up nginx with php support and enable our Raspberry-Wifi-Router website.
########################################################################################
# Install nginx with php support.
sudo apt-get -y install nginx php5-fpm
# Disable the default nginx website.
sudo rm /etc/nginx/sites-enabled/default
# Copy our siteconf into place
sudo cp /home/admin/Raspberry-Wifi-Router/defconfig/RaspberryWifiRouter.Nginx.Siteconf /etc/nginx/sites-available/RaspberryWifiRouter.Nginx.Siteconf
# Lets enable our website
sudo ln -s /etc/nginx/sites-available/RaspberryWifiRouter.Nginx.Siteconf /etc/nginx/sites-enabled/RaspberryWifiRouter.Nginx.Siteconf
# Disable output buffering in php.
sudo sed -i 's/output_buffering = 4096/;output_buffering = 4096/g' /etc/php5/fpm/php.ini
# Set permissions for our router's config file
sudo chgrp www-data /home/admin/Raspberry-Wifi-Router/www/routersettings.ini
sudo chmod g+w /home/admin/Raspberry-Wifi-Router/www/routersettings.ini
# enable file uploads
sudo sed -i 's/;file_uploads = On/file_uploads = On/g' /etc/php5/fpm/php.ini

########################################################################################
# Set-up hostapd.
########################################################################################
# Install some required libraries for hostapd.
sudo apt-get install -y libnl-3-dev libnl-genl-3-dev libssl-dev
# Download and extract the hostapd source files.
wget -O /home/admin/hostapd-2.5.tar.gz http://w1.fi/releases/hostapd-2.5.tar.gz
tar -zxvf /home/admin/hostapd-2.5.tar.gz -C /home/admin
# Prepare for compiling hostapd, create .config and modify some variables.
cp /home/admin/hostapd-2.5/hostapd/defconfig /home/admin/hostapd-2.5/hostapd/.config
sed -i 's/#CONFIG_LIBNL32=y/CONFIG_LIBNL32=y/g' /home/admin/hostapd-2.5/hostapd/.config
sed -i 's/#CFLAGS += -I$<path to libnl include files>/CFLAGS += -I\/usr\/include\/libnl3/g' /home/admin/hostapd-2.5/hostapd/.config
sed -i 's/#LIBS += -L$<path to libnl library files>/LIBS += -L\/lib\/arm-linux-gnueabihf/g' /home/admin/hostapd-2.5/hostapd/.config
sed -i 's/#CONFIG_IEEE80211N=y/CONFIG_IEEE80211N=y/g' /home/admin/hostapd-2.5/hostapd/.config
# Create some links to fix some bugs while compiling
sudo ln -s /lib/arm-linux-gnueabihf/libnl-genl-3.so.200.5.2 /lib/arm-linux-gnueabihf/libnl-genl.so
sudo ln -s /lib/arm-linux-gnueabihf/libnl-3.so.200.5.2 /lib/arm-linux-gnueabihf/libnl.so
# Compile hostapd.
make -C /home/admin/hostapd-2.5/hostapd
# Ok, now install hostapd.
sudo make install -C /home/admin/hostapd-2.5/hostapd
# Create config folder and copy our default hostapd config file into place.
sudo mkdir /etc/hostapd
sudo cp /home/admin/Raspberry-Wifi-Router/defconfig/hostapd.conf /etc/hostapd/hostapd.conf
sudo chgrp www-data /etc/hostapd/hostapd.conf
sudo chmod g+w /etc/hostapd/hostapd.conf
# Set permissions on config file so our router can modify it.
sudo chgrp www-data /etc/hostapd/hostapd.conf
sudo chmod g+w /etc/hostapd/hostapd.conf
# Copy our own systemd service unit into place for starting hostapd during boot time and load it in systemd.
sudo cp /home/admin/Raspberry-Wifi-Router/defconfig/hostapd.service /etc/systemd/system/hostapd.service
sudo chgrp www-data /etc/systemd/system/hostapd.service
sudo chmod g+w /etc/systemd/system/hostapd.service

sudo systemctl daemon-reload
sudo systemctl enable hostapd.service

########################################################################################
# Set-up other network requirements
########################################################################################
sudo apt-get -y install iw bridge-utils dnsmasq iptables
# disable dnsmasq?
sudo sed -i 's/netdev:x:108:admin/netdev:x:108:admin,www-data/g' /etc/group
# Copy some config files into place
sudo cp /home/admin/Raspberry-Wifi-Router/defconfig/interfaces /etc/network/interfaces
sudo chgrp www-data /etc/network/interfaces
sudo chmod g+w /etc/network/interfaces

sudo cp /home/admin/Raspberry-Wifi-Router/defconfig/dhcpcd.conf /etc/dhcpcd.conf

sudo cp /home/admin/Raspberry-Wifi-Router/defconfig/wr_commands /etc/sudoers.d/wr_commands
sudo chmod 644 /etc/sudoers.d/wr_commands

sudo cp /home/admin/Raspberry-Wifi-Router/defconfig/ntp.conf /etc/ntp.conf
sudo chgrp www-data /etc/ntp.conf
sudo chmod g+w /etc/ntp.conf

sudo cp /home/admin/Raspberry-Wifi-Router/defconfig/dnsmasq.conf /etc/dnsmasq.conf
sudo chgrp www-data /etc/dnsmasq.conf
sudo chmod g+w /etc/dnsmasq.conf

# modify some shit in existing config files
sudo chgrp www-data /etc/dhcp/dhclient.conf
sudo chmod g+w /etc/dhcp/dhclient.conf

sudo chgrp www-data /etc/timezone
sudo chmod g+w /etc/timezone

sudo cp /home/admin/Raspberry-Wifi-Router/defconfig/routersettings.ini /home/admin/Raspberry-Wifi-Router/www/routersettings.ini

sudo mount -o remount rw /boot
sudo cp /home/admin/Raspberry-Wifi-Router/defconfig/cmdline.txt /boot/cmdline.txt

# disable ntp in default config
sudo systemctl stop ntp
sudo systemctl disable ntp

# fix a bug in which dnsmasq overwrites our resolv.conf file's dns servers
echo "DNSMASQ_EXCEPT=lo" | sudo tee -a /etc/default/dnsmasq

# set security rights on /etc/rc.local
sudo chgrp www-data /etc/rc.local
sudo chmod g+w /etc/rc.local

# create empty /etc/resolv.conf.head file for dns override
sudo touch /etc/resolv.conf.head
sudo chgrp www-data /etc/resolv.conf.head
sudo chmod g+w /etc/resolv.conf.head

# set permissions on temp folder for router
sudo chgrp -R www-data /home/admin/Raspberry-Wifi-Router/www/temp
sudo chmod -R 775 /home/admin/Raspberry-Wifi-Router/www/temp

########################################################################################
# Set-up mysql
########################################################################################
sudo apt-get -y install debhelper
echo 'mysql-server mysql-server/root_password password @casi123' | debconf-set-selections
echo 'mysql-server mysql-server/root_password_again password @casi123' | debconf-set-selections
sudo apt-get -y install mysql-server php5-mysql 

########################################################################################
# Set-up freeradius
########################################################################################
sudo apt-get -y install freeradius freeradius-mysql
echo 'create database radius;' | mysql --host=localhost --user=root --password=@casi123
sudo cat /etc/freeradius/sql/mysql/schema.sql | mysql --host=localhost --user=root --password=@casi123 radius
sudo cat /etc/freeradius/sql/mysql/admin.sql | mysql --host=localhost --user=root --password=@casi123 radius
echo "insert into radcheck (username, attribute, op, value) values ('user', 'Cleartext-Password', ':=', 'password');" | mysql --host=localhost --user=root --password=@casi123 radius
sudo sed -i 's/#[[:space:]]$INCLUDE sql.conf/$INCLUDE sql.conf/g' /etc/freeradius/radiusd.conf
sudo cp /home/admin/Raspberry-Wifi-Router/defconfig/sites-available-default /etc/freeradius/sites-available/default
sudo systemctl restart freeradius.service

########################################################################################
# Login Database - Creating a login database and storing our user passwords
########################################################################################
echo 'create database login;' | mysql --host=localhost --user=root --password=@casi123
echo " \
CREATE TABLE users ( \
  id int(11) NOT NULL auto_increment, \
  username varchar(64) NOT NULL default '', \
  password varchar(64) NOT NULL default '', \
  PRIMARY KEY  (id) \
) ;" | mysql --host=localhost --user=root --password=@casi123 --database login

echo " \
CREATE TABLE openvpnusers ( \
  id int(11) NOT NULL auto_increment, \
  openvpnservername varchar(64) NOT NULL default '', \
  username varchar(64) NOT NULL default '', \
  firstname varchar(64) NOT NULL default '', \
  lastname varchar(64) NOT NULL default '', \
  country varchar(2) NOT NULL default '', \
  province varchar(64) NOT NULL default '', \
  city varchar(64) NOT NULL default '', \
  organisation varchar(64) NOT NULL default '', \
  email varchar(64) NOT NULL default '', \
  packageurl varchar(64) NOT NULL default '', \
  PRIMARY KEY  (id) \
) ;" | mysql --host=localhost --user=root --password=@casi123 --database login

echo "INSERT INTO users (username,password) VALUES('admin','@casi123');" | \
mysql --host=localhost --user=root --password=@casi123 --database login

########################################################################################
# OpenVPN - Installing OpenVPN Requirements
########################################################################################
sudo apt-get -y install openvpn
sudo apt-get -y install zip
mkdir /home/admin/Raspberry-Wifi-Router/www/temp
mkdir /home/admin/Raspberry-Wifi-Router/www/temp/OpenVPN_ClientPackages
sudo systemctl disable openvpn.service


########################################################################################
# Reconfigure networking
########################################################################################
sudo iw wlan0 set 4addr on # for bridging the wlan interface
