ALTER TABLE `zt_testsuite` ADD `acl` VARCHAR(30) NOT NULL DEFAULT 'open';
ALTER TABLE `zt_testsuite` ADD `whitelist` text;

CREATE TABLE `zt_childhistory` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `action` mediumint unsigned NOT NULL DEFAULT '0',
    `old` longtext,
    `new` longtext,
    PRIMARY KEY (`id`) USING BTREE,
    KEY `action` (`action`) USING BTREE
);
