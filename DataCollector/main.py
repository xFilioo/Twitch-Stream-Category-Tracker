import requests
import mysql.connector
import time
from datetime import datetime

DB_CONFIG = {
    'host': 'your_hostname_here',
    'port': 0000,
    'user': 'your_username_here',
    'password': 'your_password_here',
    'database': 'your_database_name_here',
}

CLIENT_ID = "your_client_id_here"
ACCESS_TOKEN = "your_access_token_here"
HEADERS = {
    "Client-ID": CLIENT_ID,
    "Authorization": f"Bearer {ACCESS_TOKEN}"
}

# List of Twitch streamers to monitor - these are the most popular polish streamers
STREAMERS = [
"neexcsgo",
"rybsonlol_",
"youngmulti",
"h2p_gucio",
"xntentacion",
"ewroon", 
"slayproxx",
"delordione",
"xmerghani",
"mrdzinold",
"newmultishow",
"szelioficjalnie",
"cygan___",
"grendy",
"szzalony",
"nervarien",
"angela35", 
"mamm0n",
"banduracartel",
"n3utr4lsf",
"overpow",
"tamae_senpai",
"maharadzza",
"bonkol",
"kubon_",
"nimuena_",
"kasix",
"demonzz1",
"gluhammer",
"achtenwlodar",
"Graf", 
"cygus134",
"worldofwarships",
"blackfireice",
"spartiatix",
"rallencs2",
"discokarol",
"natanzgorzyk",
"nieuczesana",
"parisplatynov",
"besi523",
"pago3",
"lukisteve",
"katbabis",
"pr0tzyq",
"awizopocztowe",
"darkocsgo",
"pevor13",
"remsua",
"arquel",
"kangurek_kao_pej",
"nexe_",
"pyka97",
"sevel07",
"diables",
"tubson_",
"blachu_",
"officialhyper",
"ortis",
"tojadenis",
"dorotka22_",
"randombrucetv",
"xth0rek",
"monsteryoo",
"tuttekhs",
"vysotzky",
"xtom223",
"piotrmaciejczak",
"cinkrofwest",
"lotharhs",
"zwierzaczunio",
"bruz777",
"keremekesze",
"STOMPcsgo",
"menders_7",
"chopo_kaify",
"jaskol95",
"spzoomario",
"bykmateo",
"revo_toja",
"xemtek",
"mokrysuchar",
"1wron3k",
"putrefy",
"papapawian",
"chesscompl",
"maxigashi",
"alanzqtft",
"matiskaterryfl",
"haemhype",
"qreii",
"masle1",
"ospanno",
"izakooo",
"kamileater",
"pirlo444",
"lala_style",
"kondyss",
"crownycro",
"patrycjusz"]

def get_stream_data(streamer_login):
    url = f"https://api.twitch.tv/helix/streams?user_login={streamer_login}"
    response = requests.get(url, headers=HEADERS)
    data = response.json()
    if data.get("data"):
        return data["data"][0]
    return None

def get_category_info(category_id):
    url = f"https://api.twitch.tv/helix/games?id={category_id}"
    response = requests.get(url, headers=HEADERS)
    data = response.json()
    if data.get("data"):
        return data["data"][0]["id"], data["data"][0]["name"]
    return category_id, "Unknown"

def ensure_streamer_in_db(cursor, login):
    cursor.execute("SELECT id FROM streamers WHERE login=%s", (login,))
    result = cursor.fetchone()
    if result:
        return result[0]
    cursor.execute("INSERT INTO streamers (login) VALUES (%s)", (login,))
    return cursor.lastrowid

def ensure_category_in_db(cursor, category_id, category_name):
    cursor.execute("SELECT id FROM categories WHERE id=%s", (category_id,))
    if not cursor.fetchone():
        cursor.execute("INSERT INTO categories (id, name) VALUES (%s, %s)", (category_id, category_name))

def update_or_insert_stream(cursor, streamer_id, category_id):
    cursor.execute(
        "SELECT id, category_id FROM streams WHERE streamer_id=%s AND end_time IS NULL ORDER BY start_time DESC LIMIT 1",
        (streamer_id,)
    )
    last_stream = cursor.fetchone()
    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    if last_stream:
        last_id, last_category_id = last_stream
        if str(last_category_id) != str(category_id):
            cursor.execute(
                "UPDATE streams SET end_time=%s WHERE id=%s",
                (now, last_id)
            )
            cursor.execute(
                "INSERT INTO streams (streamer_id, category_id, start_time) VALUES (%s, %s, %s)",
                (streamer_id, category_id, now)
            )
    else:
        cursor.execute(
            "INSERT INTO streams (streamer_id, category_id, start_time) VALUES (%s, %s, %s)",
            (streamer_id, category_id, now)
        )

def set_end_time_for_offline(cursor, streamer_id):
    cursor.execute(
        "SELECT id FROM streams WHERE streamer_id=%s AND end_time IS NULL ORDER BY start_time DESC LIMIT 1",
        (streamer_id,)
    )
    last_stream = cursor.fetchone()
    if last_stream:
        now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        cursor.execute(
            "UPDATE streams SET end_time=%s WHERE id=%s",
            (now, last_stream[0])
        )

def main_loop():
    while True:
        try:
            conn = mysql.connector.connect(**DB_CONFIG)
            cursor = conn.cursor()
            for streamer in STREAMERS:
                stream_data = get_stream_data(streamer)
                if stream_data:
                    category_id = stream_data["game_id"]
                    category_id, category_name = get_category_info(category_id)
                    streamer_id = ensure_streamer_in_db(cursor, streamer)
                    ensure_category_in_db(cursor, category_id, category_name)
                    update_or_insert_stream(cursor, streamer_id, category_id)
                    print(f"{streamer} online in category: {category_name} ({category_id})")
                else:
                    streamer_id = ensure_streamer_in_db(cursor, streamer)
                    set_end_time_for_offline(cursor, streamer_id)
                    print(f"{streamer} offline")
            conn.commit()
            cursor.close()
            conn.close()
        except Exception as e:
            print("Error:", e)
        print("Waiting 3 minutes...")
        time.sleep(180)

if __name__ == "__main__":
    main_loop()
