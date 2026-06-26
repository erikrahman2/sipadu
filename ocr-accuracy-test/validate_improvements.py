#!/usr/bin/env python3
"""
Validate OCR Improvements Against Test Images
===============================================
Tests the 4 main improvements:
1. Better confidence scoring (0.70+ acceptance)
2. Expanded field headers
3. Fallback NIK extraction
4. Better address handling
"""

import json
import sys
import requests
from pathlib import Path
from typing import Dict, List, Tuple

# OCR Service URL
OCR_SERVICE_URL = "http://localhost:5001"
OCR_SECRET_KEY = "change_me"

# Test samples mapping
TEST_SAMPLES = {
    "siti_amalia_dewi": {
        "image": "sample (41).jpg",
        "expected": {
            "nik": "3509014503980002",
            "nama": "SITI AMALIA DEWI",
            "alamat": "JL. RAYA SUKODONO NO. 45",
            "rt_rw": "005/002",
            "kelurahan": "SUKODONO",
            "kecamatan": "SUKODONO",
        },
        "min_confidence": 0.70,
    },
    "rianto_prabowo": {
        "image": "sample (48).jpg",
        "expected": {
            "nik": "7203051506920003",
            "nama": "RIANTO PRABOWO",
            "alamat": "JL. VETERAN NO. 45",
            "rt_rw": "005/002",
            "kelurahan": "20 ILIR D. I",
            "kecamatan": "ILIR BARAT I",
        },
        "min_confidence": 0.70,
    },
}

class TestValidator:
    def __init__(self):
        self.results: List[Dict] = []
        self.passed = 0
        self.failed = 0
    
    def test_field_match(self, field: str, expected: str, actual: str) -> bool:
        """Check if actual value matches expected (case-insensitive, ignoring spaces)"""
        if not actual:
            return False
        
        # Normalize for comparison
        norm_expected = "".join(expected.upper().split())
        norm_actual = "".join(actual.upper().split())
        
        # Exact match or partial match (important for longer fields)
        return norm_actual == norm_expected or norm_expected in norm_actual
    
    def validate_sample(self, name: str, sample_info: Dict) -> None:
        """Validate single KTP sample"""
        image_path = Path(__file__).parent / "results" / sample_info["image"]
        
        if not image_path.exists():
            print(f"\n❌ {name}: Image not found at {image_path}")
            self.failed += 1
            return
        
        print(f"\n📋 Testing: {name}")
        print(f"   Image: {image_path.name}")
        print("   " + "─" * 60)
        
        try:
            # Call OCR service
            with open(image_path, "rb") as f:
                response = requests.post(
                    f"{OCR_SERVICE_URL}/ocr/process",
                    files={"file": f},
                    headers={"X-OCR-Secret": OCR_SECRET_KEY},
                    timeout=30
                )
            
            if response.status_code != 200:
                print(f"   ❌ OCR Service error: {response.status_code}")
                print(f"   {response.text}")
                self.failed += 1
                return
            
            result = response.json()
            
            # Validate each field
            test_passed = True
            for field, expected_value in sample_info["expected"].items():
                actual_value = result.get(field) or ""
                confidence = result.get("confidence", {}).get(field, 0.0)
                
                is_match = self.test_field_match(field, expected_value, actual_value)
                conf_ok = confidence >= sample_info.get("min_confidence", 0.70)
                
                if is_match and conf_ok:
                    print(f"   ✅ {field:12} | {actual_value:30} | {confidence:.0%}")
                else:
                    print(f"   ❌ {field:12} | Expected: {expected_value}")
                    print(f"      {field:12} | Got:      {actual_value} (conf: {confidence:.0%})")
                    test_passed = False
            
            # Overall score
            raw_text_len = len(result.get("raw_text", {}).get("full", ""))
            print(f"\n   📊 Overall:")
            print(f"      Raw text length: {raw_text_len} chars")
            print(f"      Fields extracted: {len([v for v in result.values() if v and v != ''])}")
            
            if test_passed:
                print(f"\n   ✅ TEST PASSED")
                self.passed += 1
            else:
                print(f"\n   ❌ TEST FAILED")
                self.failed += 1
        
        except Exception as e:
            print(f"   ❌ Error: {e}")
            self.failed += 1
    
    def run_all_tests(self) -> None:
        """Run all validation tests"""
        print("╔" + "═" * 62 + "╗")
        print("║" + " " * 15 + "OCR IMPROVEMENTS VALIDATION" + " " * 21 + "║")
        print("╚" + "═" * 62 + "╝")
        print("\nChecking:")
        print("  ✓ Confidence scoring (0.70+ acceptance)")
        print("  ✓ Expanded field headers")
        print("  ✓ Fallback NIK extraction")
        print("  ✓ Address field handling")
        
        for name, sample_info in TEST_SAMPLES.items():
            self.validate_sample(name, sample_info)
        
        # Summary
        print("\n" + "═" * 64)
        print(f"RESULTS: {self.passed} PASSED, {self.failed} FAILED")
        print("═" * 64)
        
        if self.failed == 0:
            print("🎉 All improvements validated successfully!")
            return 0
        else:
            print(f"⚠️  {self.failed} test(s) failed. Review extraction logic.")
            return 1

if __name__ == "__main__":
    validator = TestValidator()
    exit_code = validator.run_all_tests()
    sys.exit(exit_code)
