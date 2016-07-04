ALTER TABLE `likes` ADD `user_id` INT(11)  UNSIGNED  NULL  AFTER `id`;
ALTER TABLE `likes` ADD `session_key` VARCHAR(250)  NULL  DEFAULT NULL  AFTER `user_id`;

