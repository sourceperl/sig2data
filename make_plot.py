#!/usr/bin/python2.7

import sys
import numpy as np
import matplotlib.pyplot as plt
import sqlite3

SENSOR_DB = 'sensor.db'

# open database
try:
  conn = sqlite3.connect(SENSOR_DB)
except:
  sys.stderr.write("DB connect error")
  exit(1)

# access to row by name
conn.row_factory = sqlite3.Row
#create db cursor
c = conn.cursor()

## process RAW message
sql = ("SELECT " +
      "  date(rx_timestamp, 'unixepoch') as date, " +
      "  object_id, var_id, " +
      "  MAX(index_p) - MIN(index_p) AS prod, " +
      "  MAX(index_n) - MIN(index_n) AS cons " +
      "FROM " +
      "  sig_index " +
      "WHERE " +
      "  datetime(rx_timestamp, 'unixepoch') LIKE \"2013-10-%\" AND " +
      "  object_id = 3 "+
      "GROUP BY " +
      "  date, var_id")
c.execute(sql)
day_records = c.fetchall()

batt_stk   = []
batt_dstk  = []
batt_day   = []

for day_record in day_records:
  batt_stk.append(day_record['prod'])
  batt_dstk.append(day_record['cons']) 
  batt_day.append(day_record['date'].split('-')[2]) 

fig, ax = plt.subplots()

fig.patch.set_facecolor('blue')
fig.patch.set_alpha(0.4)

index = np.arange(len(batt_stk))
bar_width = 0.35

error_config = {'ecolor': '0.3'}

rects1 = plt.bar(index, batt_stk, bar_width,
                 alpha=0.7,
                 color='orange',
                 error_kw=error_config,
                 label='stocking')

rects2 = plt.bar(index + bar_width, batt_dstk, bar_width,
                 alpha=0.7,
                 color='green',
                 error_kw=error_config,
                 label='destocking')

plt.xlabel('days')
plt.ylabel('electric charge (in uah)')
plt.title('Lestrem battery reporting')
plt.xticks(index + bar_width, batt_day)
plt.legend()

plt.tight_layout()
plt.savefig("graph.png",dpi=72)
