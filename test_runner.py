#!/usr/bin/env python3
"""
Test Runner for Injection Honeypot

Sends payloads to a target bot and checks if the honeypot caught them.
"""

import json
import time
import requests
from pathlib import Path

HONEYPOT_URL = "http://localhost:5555"
PAYLOADS_DIR = Path(__file__).parent / "payloads"


def get_catch_count():
    """Get current number of catches."""
    try:
        resp = requests.get(f"{HONEYPOT_URL}/catches", timeout=5)
        return len(resp.json())
    except:
        return -1


def load_payloads():
    """Load all payload files."""
    payloads = []
    for f in sorted(PAYLOADS_DIR.glob("*.txt")):
        content = f.read_text()
        # Extract just the payload part (after the --- separator)
        if "---" in content:
            payload = content.split("---", 1)[1].strip()
        else:
            payload = content
        payloads.append({
            "name": f.stem,
            "file": f.name,
            "content": payload
        })
    return payloads


def test_payload(payload: dict, before_count: int):
    """Test a single payload and check if it was caught."""
    print(f"\n{'='*60}")
    print(f"Testing: {payload['name']}")
    print(f"{'='*60}")
    print(f"\nPayload content:\n{payload['content'][:200]}...")
    
    # Here you would send the payload to the target bot
    # For now, we just print instructions
    print("\n[MANUAL TEST]")
    print("Send this payload to the bot, then press Enter to check results...")
    input()
    
    after_count = get_catch_count()
    
    if after_count > before_count:
        print(f"❌ VULNERABLE - Bot was caught! (catches: {before_count} → {after_count})")
        return False
    else:
        print(f"✅ PROTECTED - Bot did not execute payload")
        return True


def main():
    print("🍯 Injection Honeypot - Test Runner")
    print("="*60)
    
    # Check honeypot is running
    initial_count = get_catch_count()
    if initial_count < 0:
        print(f"❌ Cannot connect to honeypot at {HONEYPOT_URL}")
        print("Start the honeypot first: python app.py")
        return
    
    print(f"✅ Honeypot connected ({initial_count} catches so far)")
    
    payloads = load_payloads()
    print(f"📦 Loaded {len(payloads)} payloads")
    
    results = []
    for payload in payloads:
        before = get_catch_count()
        protected = test_payload(payload, before)
        results.append({
            "payload": payload["name"],
            "protected": protected
        })
    
    # Summary
    print("\n" + "="*60)
    print("RESULTS SUMMARY")
    print("="*60)
    protected = sum(1 for r in results if r["protected"])
    total = len(results)
    print(f"Protected: {protected}/{total}")
    for r in results:
        status = "✅" if r["protected"] else "❌"
        print(f"  {status} {r['payload']}")


if __name__ == "__main__":
    main()
