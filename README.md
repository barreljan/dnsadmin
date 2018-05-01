# DNS Admin
DNSmasq admin page for simple local domains.

![alt text](https://github.com/barreljan/dnsadmin/raw/master/dns_admin.png "Screenie")
!

Manage your local domain entries with a simple but effective GUI. Add A/AAAA or different records.
For a small home enviroment with some dev systems, .local TLD's etc it is super lightweight and
efficient. It does what it has to do.


### Future fixes:
* Entry value validations
* Adding domains
* Deleting domains
* MX records (not sure why)
* ?
 
The system works as following. There is a web and mysql server available for the pages and the domain 
information. The database should be readable and writeable by the DNSmasq server(s).
In a cron you can add the 'parser.php' file for lets say, every two minutes to check if there are changes.
If so, the DNSmasq files will be parsed to be up2date and the DNSmasq process restarted. After that the
'parser' toggle's a field that it has done it's update.


The system is based on 2 dnsmasq servers. On both servers the settings need to be correct, so on server 1
it's roll will be the 'server1' and so on for nr2.
