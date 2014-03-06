GRANT USAGE ON *.* TO 'bee_sigfox_cbk'@'localhost';

GRANT SELECT ON `beedb`.`sig_vars` TO 'bee_sigfox_cbk'@'localhost';

GRANT INSERT ON `beedb`.`sig_debug_log` TO 'bee_sigfox_cbk'@'localhost';

GRANT SELECT (rx_timestamp, message_id, object_id, payload), INSERT ON `beedb`.`sig_messages` TO 'bee_sigfox_cbk'@'localhost';

GRANT SELECT (modem_id, object_id) ON `beedb`.`sig_objects` TO 'bee_sigfox_cbk'@'localhost';
