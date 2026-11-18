#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
═════════════════════════════════════════════════════════════════════
ChatRoom Backend Local Test Suite Runner (Python Engine)
موتور اعتبارسنجی و اجرای آزمون‌های یونیت و یکپارچگی بک‌اند چت‌روم
═════════════════════════════════════════════════════════════════════
"""

import os
import re
import sys
import time

if sys.platform == 'win32':
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass

class BackendTestSuite:
    def __init__(self, root_dir):
        self.root_dir = os.path.abspath(root_dir)
        self.tests_run = 0
        self.tests_passed = 0
        self.tests_failed = 0
        self.assertions = 0
        self.failures = []

    def assert_true(self, condition, message):
        self.assertions += 1
        if not condition:
            raise AssertionError(message)

    def assert_equal(self, expected, actual, message=""):
        self.assertions += 1
        if expected != actual:
            msg = message or f"Expected {expected!r}, got {actual!r}"
            raise AssertionError(msg)

    def assert_contains(self, needle, haystack, message=""):
        self.assertions += 1
        if needle not in haystack:
            msg = message or f"'{needle}' not found in target"
            raise AssertionError(msg)

    def assert_not_contains(self, needle, haystack, message=""):
        self.assertions += 1
        if needle in haystack:
            msg = message or f"Found forbidden substring '{needle}'"
            raise AssertionError(msg)

    def run_test(self, test_name, test_func):
        self.tests_run += 1
        start = time.time()
        try:
            test_func()
            elapsed = time.time() - start
            self.tests_passed += 1
            print(f"  \033[92m✅ PASS\033[0m {test_name} ({elapsed:.4f}s)")
        except AssertionError as e:
            elapsed = time.time() - start
            self.tests_failed += 1
            self.failures.append((test_name, str(e)))
            print(f"  \033[91m❌ FAIL\033[0m {test_name} ({elapsed:.4f}s): {e}")
        except Exception as e:
            elapsed = time.time() - start
            self.tests_failed += 1
            self.failures.append((test_name, f"Exception: {e}"))
            print(f"  \033[93m⚠️ ERR\033[0m  {test_name} ({elapsed:.4f}s): {e}")

    # ==========================================
    # TEST SUITE 1: Syntax & Structural Analysis
    # ==========================================
    def test_php_syntax_and_delimiters(self):
        php_files = [f for f in os.listdir(self.root_dir) if f.endswith('.php')]
        self.assert_true(len(php_files) >= 30, f"Expected at least 30 PHP files, found {len(php_files)}")

        for fname in php_files:
            fpath = os.path.join(self.root_dir, fname)
            with open(fpath, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()

            self.assert_true(content.strip().startswith('<?php'), f"{fname} must start with <?php")
            
            # Check balanced curly braces
            open_curly = content.count('{')
            close_curly = content.count('}')
            self.assert_equal(open_curly, close_curly, f"Brace mismatch in {fname}: open={open_curly}, close={close_curly}")

            # Check balanced parentheses
            open_paren = content.count('(')
            close_paren = content.count(')')
            self.assert_equal(open_paren, close_paren, f"Paren mismatch in {fname}: open={open_paren}, close={close_paren}")

    # ==========================================
    # TEST SUITE 2: Multi-Tenant & Domain Routing
    # ==========================================
    def test_multi_tenant_configuration(self):
        config_path = os.path.join(self.root_dir, 'config.php')
        self.assert_true(os.path.exists(config_path), "config.php must exist")

        with open(config_path, 'r', encoding='utf-8') as f:
            code = f.read()

        # Check domain resolution keywords
        self.assert_contains('chatgolabi', code, "config.php must check chatgolabi host")
        self.assert_contains('chatiranian', code, "config.php must check chatiranian host")
        self.assert_contains('mnqdvqjc_chatroom_db', code, "config.php must have Golabi DB credentials")
        self.assert_contains('jbztdjmk_chatroom_db', code, "config.php must have Tolou DB credentials")
        self.assert_contains('getBannerFilePath', code, "config.php must implement getBannerFilePath")
        self.assert_contains('UPLOAD_BASE_URL', code, "config.php must define dynamic UPLOAD_BASE_URL")
        self.assert_contains('utf8mb4', code, "config.php must enforce utf8mb4 encoding")

    # ==========================================
    # TEST SUITE 3: Dynamic Image Upload Security
    # ==========================================
    def test_dynamic_image_upload_isolation(self):
        upload_path = os.path.join(self.root_dir, 'upload_image.php')
        self.assert_true(os.path.exists(upload_path), "upload_image.php must exist")

        with open(upload_path, 'r', encoding='utf-8') as f:
            code = f.read()

        self.assert_not_contains("http://chatiranian.ir/uploads/", code, 
            "upload_image.php must NOT contain hardcoded chatiranian.ir URL (must use UPLOAD_BASE_URL)")
        self.assert_contains("UPLOAD_BASE_URL", code, "upload_image.php must use dynamic UPLOAD_BASE_URL")
        self.assert_contains("UPLOAD_DIR", code, "upload_image.php must use UPLOAD_DIR")

    # ==========================================
    # TEST SUITE 4: Modern Custom Color Pipeline
    # ==========================================
    def test_custom_color_support_across_core(self):
        files_to_check = [
            'send_message.php',
            'send_private.php',
            'update_color.php',
            'update_user_code.php',
            'db.php',
            'migrate.php'
        ]

        for fname in files_to_check:
            fpath = os.path.join(self.root_dir, fname)
            with open(fpath, 'r', encoding='utf-8') as f:
                code = f.read()
            self.assert_contains('custom_color', code, f"{fname} must handle custom_color parameter")

    # ==========================================
    # TEST SUITE 5: Database Self-Healing Migration
    # ==========================================
    def test_migration_schema_and_indexes(self):
        migrate_path = os.path.join(self.root_dir, 'migrate.php')
        self.assert_true(os.path.exists(migrate_path), "migrate.php must exist")

        with open(migrate_path, 'r', encoding='utf-8') as f:
            code = f.read()

        required_tables = [
            'users', 'messages', 'private_messages', 'deleted_conversations',
            'blocked_users', 'banned_users', 'anti_shoot', 'hidden_shoot',
            'reports', 'bots'
        ]
        for tbl in required_tables:
            self.assert_contains(f"'{tbl}'", code, f"migrate.php must manage table '{tbl}'")

        required_cols = [
            'users.custom_color', 'users.inbox_color', 'users.avatar_url',
            'users.device_model', 'users.typing_to', 'users.typing_at',
            'users.is_banned', 'users.ban_reason',
            'messages.custom_color', 'private_messages.custom_color',
            'private_messages.is_read'
        ]
        for col in required_cols:
            tbl_name, col_name = col.split('.')
            self.assert_contains(f"'{col_name}'", code, f"migrate.php must check column {col}")

        # Check indexes
        self.assert_contains('idx_from_to_id', code, "migrate.php must create composite index idx_from_to_id")
        self.assert_contains('idx_to_from_id', code, "migrate.php must create composite index idx_to_from_id")
        self.assert_contains('idx_unread_lookup', code, "migrate.php must create composite index idx_unread_lookup")

    # ==========================================
    # TEST SUITE 6: Safety & Secret Protection
    # ==========================================
    def test_git_and_web_security_rules(self):
        gitignore_path = os.path.join(self.root_dir, '.gitignore')
        self.assert_true(os.path.exists(gitignore_path), ".gitignore must exist")

        with open(gitignore_path, 'r', encoding='utf-8') as f:
            gi = f.read()

        self.assert_contains('.env', gi, ".gitignore must ignore .env files")
        self.assert_contains('config.local.php', gi, ".gitignore must ignore config.local.php")
        self.assert_contains('uploads/*', gi, ".gitignore must ignore uploaded images")
        self.assert_contains('!uploads/.gitkeep', gi, ".gitignore must keep uploads directory via .gitkeep")

        htaccess_path = os.path.join(self.root_dir, '.htaccess')
        self.assert_true(os.path.exists(htaccess_path), ".htaccess must exist")
        with open(htaccess_path, 'r', encoding='utf-8') as f:
            ht = f.read()
        self.assert_contains('Options -Indexes', ht, ".htaccess must disable directory listing")
        self.assert_contains('Deny from all', ht, ".htaccess must protect sensitive files")

    # ==========================================
    # TEST SUITE 7: API Endpoint Completeness
    # ==========================================
    def test_api_endpoint_coverage(self):
        required_endpoints = [
            'register_user.php', 'get_user.php', 'get_all_users.php',
            'update_avatar.php', 'update_rank.php', 'update_color.php',
            'update_last_seen.php', 'update_user_code.php', 'send_message.php',
            'get_messages.php', 'clear_messages.php', 'send_private.php',
            'get_private.php', 'get_user_inbox.php', 'get_user_conversation.php',
            'mark_read.php', 'delete_conversation.php', 'set_typing.php',
            'get_typing.php', 'ban_user.php', 'unban_user.php',
            'block_user.php', 'set_anti_shoot.php', 'set_hidden_shoot.php',
            'send_report.php', 'get_banner.php', 'update_banner.php',
            'upload_image.php', 'test.php', 'migrate.php'
        ]

        for ep in required_endpoints:
            ep_path = os.path.join(self.root_dir, ep)
            self.assert_true(os.path.exists(ep_path), f"Endpoint {ep} must be present in backend")
            with open(ep_path, 'r', encoding='utf-8') as f:
                code = f.read()
            self.assert_true(
                ('require_once' in code and ('db.php' in code or 'config.php' in code)) or ep == 'test.php',
                f"Endpoint {ep} must load db.php or config.php"
            )

    def run(self):
        print("\n" + "="*60)
        print(" 🧪 ChatRoom Backend Comprehensive Verification & Test Suite")
        print("="*60 + "\n")

        self.run_test("Suite 1: PHP Syntax & Braces Balance", self.test_php_syntax_and_delimiters)
        self.run_test("Suite 2: Multi-Tenant Host Detection & DB Routing", self.test_multi_tenant_configuration)
        self.run_test("Suite 3: Dynamic Image Upload & Isolation", self.test_dynamic_image_upload_isolation)
        self.run_test("Suite 4: Custom Color Pipeline & Fallbacks", self.test_custom_color_support_across_core)
        self.run_test("Suite 5: Schema Self-Healing & Fast Composite Indexes", self.test_migration_schema_and_indexes)
        self.run_test("Suite 6: Git & Web Security Protection Rules", self.test_git_and_web_security_rules)
        self.run_test("Suite 7: 30+ Core API Endpoints Contract Coverage", self.test_api_endpoint_coverage)

        print("\n" + "-"*60)
        print(f"📊 Results: {self.tests_passed}/{self.tests_run} Passed | {self.tests_failed} Failed")
        print(f"⚡ Total Assertions Evaluated: {self.assertions}")
        print("="*60 + "\n")

        if self.tests_failed > 0:
            print("❌ FAILURE DETAILS:")
            for name, err in self.failures:
                print(f"  • {name}: {err}")
            return 1
        else:
            print("🎉 ALL TEST SUITES PASSED WITH 100% SUCCESS!")
            return 0

if __name__ == '__main__':
    root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    suite = BackendTestSuite(root)
    sys.exit(suite.run())
