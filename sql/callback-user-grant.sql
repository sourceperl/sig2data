GRANT USAGE ON *.* TO 'bee_sigfox_cbk'@'localhost';

GRANT SELECT ON `beedb`.`vars` TO 'bee_sigfox_cbk'@'localhost';

GRANT INSERT ON `beedb`.`debug_log` TO 'bee_sigfox_cbk'@'localhost';

GRANT INSERT ON `beedb`.`messages` TO 'bee_sigfox_cbk'@'localhost';

GRANT SELECT (modem_id, object_id) ON `beedb`.`objects` TO 'bee_sigfox_cbk'@'localhost';
