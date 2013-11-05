#!/usr/bin/python

import sys
import pygal
import sqlite3

#SENSOR_DB = '/usr/local/share/sensor/sensor.db'
SENSOR_DB = 'sensor.db'

# open database
try:
  conn = sqlite3.connect(SENSOR_DB)
except:
  sys.stderr.write("DB connect error\n")
  exit(1)

# access to row by name
conn.row_factory = sqlite3.Row
#create db cursor
c = conn.cursor()

## process RAW message
sql = ("SELECT"+ 
       "  strftime('%d/%m/%Y %H:%M', rx_timestamp, 'unixepoch', 'localtime') AS datetime,"+ 
       "  var_avg "+
       "FROM sig_stat WHERE object_id = 4 "+
       "ORDER BY rx_timestamp DESC LIMIT 6;")
c.execute(sql)

records = c.fetchall()

avg_datetime = []
avg_current  = []

for record in records:
  avg_datetime.append(record['datetime'])
  avg_current.append(record['var_avg']) 

avg_datetime.reverse()
avg_current.reverse()

line_chart = pygal.Line()
line_chart.title   = 'Lestrem battery average current (+ -> fill, - -> empty) '+
                     'for last 3 hours'
line_chart.x_title = 'date and time'
line_chart.y_title = 'current (in ma)'
line_chart.x_labels = avg_datetime
line_chart.x_label_rotation = 45
line_chart.add('AVG Current', avg_current)
line_chart.render_to_file('/tmp/bar_chart.svg')
