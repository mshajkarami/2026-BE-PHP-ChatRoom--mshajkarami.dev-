import re
import sys
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8')

with open(r'D:/App/Freelance/LIVE_DATABASE_BACKUP_GOLABI_20261119.sql', 'r', encoding='utf-8') as f:
    text = f.read()

print("--- USERS IN BACKUP ---")
for m in re.finditer(r"INSERT INTO `users`[^;]+;", text):
    stmt = m.group(0)
    values_part = stmt[stmt.find("VALUES"):]
    print(values_part[:200])

print("\n--- TABLE RECORD COUNTS IN BACKUP ---")
for table in ['users', 'messages', 'private_messages', 'blocked_users', 'banned_users', 'reports', 'anti_shoot', 'hidden_shoot', 'bots', 'bot_rules']:
    count = len(re.findall(rf"INSERT INTO `{table}`", text))
    print(f"{table}: {count}")
