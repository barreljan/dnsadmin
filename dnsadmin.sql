CREATE TABLE `_update` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `server1` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `server2` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `changedate` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

CREATE TABLE `_zones` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `zonename` varchar(255) DEFAULT NULL,
  `dateadded` timestamp NOT NULL DEFAULT current_timestamp(),
  `datemodified` timestamp NULL DEFAULT NULL,
  `datedeleted` timestamp NULL DEFAULT NULL,
  `reverse` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `active` tinyint(1) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

INSERT INTO `_zones` VALUES (1,'example.com','2017-12-08 20:49:09',NULL,NULL,0,0);

CREATE TABLE `example.com` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `zoneid` int(2) NOT NULL,
  `hostname` varchar(255) DEFAULT '',
  `recordtype` varchar(4) DEFAULT 'a',
  `recordvalue` varchar(255) DEFAULT '',
  `pref` int(2) DEFAULT NULL,
  `dateadded` timestamp NOT NULL DEFAULT current_timestamp(),
  `datemodified` timestamp NULL DEFAULT NULL,
  `datedeleted` timestamp NULL DEFAULT NULL,
  `active` int(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

INSERT INTO `example.com` VALUES (1,1,'www','a','127.0.0.1',NULL,'2017-12-08 20:56:37',NULL,NULL,1)

CREATE TABLE `template_zone` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `zoneid` int(2) NOT NULL,
  `hostname` varchar(255) DEFAULT '',
  `recordtype` varchar(4) DEFAULT 'a',
  `recordvalue` varchar(255) DEFAULT '',
  `pref` int(2) DEFAULT NULL,
  `dateadded` timestamp NOT NULL DEFAULT current_timestamp(),
  `datemodified` timestamp NULL DEFAULT NULL,
  `datedeleted` timestamp NULL DEFAULT NULL,
  `active` int(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
