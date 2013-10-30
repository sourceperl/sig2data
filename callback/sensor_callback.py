#!/usr/bin/python2.7

# display what is wrong...
import cgitb
cgitb.enable()

import cgi
import json
import sqlite3

f = open('/tmp/mylog.txt', 'w')
f.write("start !!!\n")

# check POST/GET data
data = cgi.FieldStorage()
if (data.getvalue('callback') != None):
  cbk = json.loads(cgi.escape(data.getvalue('callback')))
else:
  exit(1)

f.write("level 1\n")
#f.write(cbk)

try:
  conn = sqlite3.connect('/tmp/mybase.db')
except:
  print("DB connect error")
  f.write("error 2\n")
  exit(2)

f.write("level 2\n")

conn.row_factory = sqlite3.Row
c = conn.cursor()

try:
  msg_object_id = '0';
  msg_when = cbk['msg']['when']
  msg_type = cbk['msg']['type']
  msg_station = int(cbk['msg']['station'], 16)
  msg_lvl = round(float(cbk['msg']['lvl']))
  #msg_pld = cbk['msg']['payload']   
  msg_pld = "00"
# next message if json var missing
except KeyError:
  exit(3)

f.write("level 3\n")


# insert into database
try:
  sql_command = ("INSERT INTO messages (`message_id`,`object_id`, " +
                 "`rx_timestamp`, `type`, `payload`, " + 
                 "`station_id`, `station_lvl`) " + 
                 "VALUES (NULL, '"+str(msg_object_id)+"','"+str(msg_when)+"', '" +
                 str(msg_type)+"', '"+str(msg_pld)+
                 "', '"+str(msg_station)+"', '"+str(msg_lvl)+"');")
except:
  f.write("I/O error({0}): {1}\n") #.format(e.errno, e.strerror))
  exit(8)

f.write("level 3 bis\n")

try:
  c.execute(sql_command)
  conn.commit()
except sqlite3.IntegrityError:
  print("duplicate line, skip")
  f.write("error 4\n")
  f.close()
  exit(4)

f.write("level 4\n")
f.close()
exit(42)
