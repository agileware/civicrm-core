{* file to handle db changes in 5.37.3 during upgrade *}

-- https://lab.civicrm.org/dev/core/-/issues/2122

ALTER TABLE `civicrm_event` ADD COLUMN `event_tz` text NULL DEFAULT NULL COMMENT 'Event\'s native time zone',
MODIFY COLUMN `start_date` timestamp NULL DEFAULT NULL COMMENT 'Date and time that event starts.',
MODIFY COLUMN `end_date` timestamp NULL DEFAULT NULL COMMENT 'Date and time that event ends. May be NULL if no defined end date/time',
MODIFY COLUMN `registration_start_date` timestamp NULL DEFAULT NULL COMMENT 'Date and time that online registration starts.',
MODIFY COLUMN `registration_end_date` timestamp NULL DEFAULT NULL COMMENT 'Date and time that online registration ends.';
