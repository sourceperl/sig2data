# public
sensor.py         python lib to access sensor web platform
db_struct.sql     DB schema for SQLITE3
sensor_poll.py    poll sensor:
                    8 last messages for every object -> messages SQL table
up2xively.py      upload sensor value to Xively platform
populate.py       decode sigfox frame from messages and store it to data tables   
make_plot.py      request some data from sig_index to build a solar prod graph report

# private
db_data.sql       DB INSERT for SQLite (Object list...)
private.py        private Python const
