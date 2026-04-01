alter table zt_copyflow_business add `isCancel` varchar(255) NOT NULL DEFAULT 'N';

ALTER TABLE `zt_project` ADD COLUMN `chteam` VARCHAR(100) NULL;

ALTER TABLE `zt_story` MODIFY COLUMN status ENUM('', 'changing', 'active', 'draft', 'closed', 'reviewing', 'launched', 'developing', 'PRDReviewing', 'PRDReviewed', 'confirming', 'devInProgress', 'beOnline', 'ancelled') DEFAULT '';
