#!/usr/bin/python3
import sys
import time
import datetime
import sqlite3

# some consts
MSG_T_STAT  = 0x02
MSG_T_INDEX = 0x03
# extract feed_id and api_key from environment variables
SENSOR_DB = 'sensor.db'
#SENSOR_DB = '/usr/local/share/sensor/sensor.db'

# some functions
def twos_comp(val, bits):
  """compute the 2's comp of int value val"""
  """ex: twos_comp(<int16_t>, 16)"""
  """    twos_comp(<int32_t>, 32)"""
  if( (val&(1<<(bits-1))) != 0 ):
    val = val - (1<<bits)
  return val

# open database
try:
  conn = sqlite3.connect(SENSOR_DB)
except:
  print("DB connect error", file=sys.stderr)
  exit(1)

# access to row by name
conn.row_factory = sqlite3.Row
#create db cursor
c = conn.cursor()

# retrive the 'id_last_msg_populate'
c.execute("select * from vars where var_name = 'id_last_msg_populate' limit 1;")
id_last_msg_populate = c.fetchone()['var']

# process RAW message
c.execute("select * from messages where type = 'raw' and message_id > '" +
          str(id_last_msg_populate) +"' order by message_id asc limit 1000;")
messages = c.fetchall()

for message in messages:
  message_id    = message['message_id'] 
  object_id     = message['object_id']
  msg_timestamp = round(message['rx_timestamp']/1000)
  msg_pld       = message['payload']
  msg_raw_type = int(msg_pld[0:2], 16)
  # process INDEX msg
  if msg_raw_type == MSG_T_INDEX: 
    msg_id_var   = int(msg_pld[2:4], 16)
    msg_index_p  = int(msg_pld[4:12], 16)
    msg_index_n  = int(msg_pld[12:20], 16)
    #date = datetime.datetime.fromtimestamp(msg_timestamp).strftime('%Y-%m-%d %H:%M:%S')
    # insert into database
    sql_command = ("INSERT INTO sig_index (`object_id`,`var_id`, " +
                   "`rx_timestamp`, `index_p`, `index_n`) " + 
                   "VALUES ('"+str(object_id)+"', '"+str(msg_id_var)+"', '" +
                   str(msg_timestamp)+"', '"+str(msg_index_p)+"', '" +
                   str(msg_index_n)+"');")
    print("%s" % (sql_command))    
    try:
      c.execute(sql_command)
      c.execute("UPDATE vars SET var='"+ str(message_id) + "' "+
                "WHERE var_name='id_last_msg_populate';")
      conn.commit()
    except sqlite3.IntegrityError:
      print("duplicate line, skip") 
  # process STAT msg
  elif msg_raw_type == MSG_T_STAT:
    msg_id_var    = int(msg_pld[2:4], 16)
    msg_var_max   = twos_comp(int(msg_pld[4:8],   16), 16)
    msg_var_avg   = twos_comp(int(msg_pld[8:12],  16), 16)
    msg_var_min   = twos_comp(int(msg_pld[12:16], 16), 16)
    msg_var_inst  = twos_comp(int(msg_pld[16:20], 16), 16)

    # insert into database
    sql_command = ("INSERT INTO sig_stat (`object_id`,`var_id`, " +
                   "`rx_timestamp`, `var_max`, `var_avg`, `var_min`, " +
                   "`var_inst`) " + 
                   "VALUES ('"+str(object_id)+"', '"+str(msg_id_var)+"', '" +
                   str(msg_timestamp)+"', '"+str(msg_var_max)+"', '" +
                   str(msg_var_avg)+"', '"+str(msg_var_min)+"', '" +
                   str(msg_var_inst)+"');")
    print("%s" % (sql_command))    
    try:
      c.execute(sql_command)
      c.execute("UPDATE vars SET var='"+ str(message_id) + "' "+
                "WHERE var_name='id_last_msg_populate';")
      conn.commit()
    except sqlite3.IntegrityError:
      print("duplicate line, skip")
