#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
═════════════════════════════════════════════════════════════════════
Live Servers Integration & Health Test
تست یکپارچگی زنده سرورهای فعال طلوع چت و چت گلابی
═════════════════════════════════════════════════════════════════════
"""

import urllib.request
import json
import sys
import time

if sys.platform == 'win32':
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass

SERVERS = [
    {
        'name': 'طلوع چت (Tolou Chat)',
        'domain': 'chatiranian.ir',
        'api_url': 'http://chatiranian.ir/api/'
    },
    {
        'name': 'چت گلابی (Chat Golabi)',
        'domain': 'chatgolabi.ir',
        'api_url': 'http://chatgolabi.ir/api/'
    }
]

def check_endpoint(url, post_data=None, timeout=6):
    req = urllib.request.Request(url)
    req.add_header('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ChatRoom-HealthCheck/1.0')
    if post_data is not None:
        data_bytes = json.dumps(post_data).encode('utf-8')
        req.add_header('Content-Type', 'application/json; charset=utf-8')
        req.data = data_bytes

    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            status = resp.status
            body = resp.read().decode('utf-8')
            parsed = json.loads(body)
            return True, status, parsed, None
    except Exception as e:
        return False, None, None, str(e)

def run():
    print("\n" + "="*60)
    print(" 🌐 Live Chat Servers Integration & Health Verification")
    print("="*60 + "\n")

    for s in SERVERS:
        print(f"📡 Testing Server: {s['name']} [{s['domain']}]")
        
        # 1. Test get_banner.php
        banner_url = s['api_url'] + 'get_banner.php'
        ok, code, data, err = check_endpoint(banner_url)
        if ok and data.get('success'):
            title = data.get('banner', {}).get('text', '')[:30]
            print(f"   ✅ get_banner.php: OK (200) -> Banner: '{title}...'")
        else:
            print(f"   ⚠️ get_banner.php: {err or 'Failed with data: ' + str(data)}")

        # 2. Test get_messages.php
        msg_url = s['api_url'] + 'get_messages.php'
        ok, code, data, err = check_endpoint(msg_url, post_data={'limit': 5})
        if ok and data.get('success'):
            cnt = len(data.get('messages', []))
            print(f"   ✅ get_messages.php: OK (200) -> Fetched {cnt} messages")
        else:
            print(f"   ⚠️ get_messages.php: {err or 'Failed'}")

        print()

if __name__ == '__main__':
    run()
