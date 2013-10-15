/* table messages */
CREATE TABLE messages(message_id INTEGER PRIMARY KEY, object_id INTEGER, rx_timestamp INTEGER, type TEXT, payload TEXT, station_id INTEGER, station_lvl INTEGER, UNIQUE (object_id, rx_timestamp));

/* table objects */
CREATE TABLE objects(object_id INTEGER PRIMARY KEY, modem_id INTEGER UNIQUE, modem_key INTEGER, object_name TEXT);

/* table index */
CREATE TABLE sig_index(id INTEGER PRIMARY KEY, object_id INTEGER, var_id INTEGER, rx_timestamp INTEGER, index_p INTEGER, index_n INTEGER, UNIQUE (object_id, var_id, rx_timestamp));

/* table stat */
CREATE TABLE sig_stat(id INTEGER PRIMARY KEY, object_id INTEGER, var_id INTEGER, rx_timestamp INTEGER, var_min INTEGER, var_avg INTEGER, var_max INTEGER, var_inst INTEGER,  UNIQUE (object_id, var_id, rx_timestamp));

/* table vars */
CREATE TABLE vars(id INTEGER PRIMARY KEY, var_name TEXT UNIQUE, var INTEGER);
INSERT INTO vars(var_name, var) VALUES( 'id_last_msg_populate', 0);
