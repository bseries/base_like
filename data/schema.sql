CREATE TABLE `likes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `model` varchar(50) NOT NULL DEFAULT '',
  `foreign_key` int(11) unsigned NOT NULL,
  `count` int(11) unsigned NOT NULL DEFAULT '0',
  `is_published` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `model` (`model`,`foreign_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;