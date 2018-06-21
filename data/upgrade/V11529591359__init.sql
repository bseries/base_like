CREATE TABLE `likes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `session_key` varchar(250) DEFAULT NULL,
  `model` varchar(50) NOT NULL DEFAULT '',
  `foreign_key` int(11) unsigned NOT NULL,
  `count_real` int(11) unsigned NOT NULL DEFAULT '0',
  `count_seed` int(11) unsigned NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
