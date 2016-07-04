ALTER TABLE `likes` ADD `created` DATETIME  NOT NULL  AFTER `count_seed`;
ALTER TABLE `likes` ADD `modified` DATETIME  NOT NULL  AFTER `created`;

