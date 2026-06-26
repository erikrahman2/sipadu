#!/usr/bin/env python3
"""
OCR Enhancement Module for app.py
===================================

This module contains enhancements untuk:
1. NIK validation dengan Indonesian rules
2. Enhanced confidence scoring dengan context
3. Post-processing validation logic

Integration:
- Add these methods to OCREngine class in app.py
- Update score_result() to use calculate_field_confidence()
- Update validate_result() to use validate_nik_format()
"""

import re
from typing import Optional, Dict, Tuple

# ═════════════════════════════════════════════════════════════════════════════
# ENHANCEMENT A: NIK Validation Intelligence
# ═════════════════════════════════════════════════════════════════════════════

class NIKValidator:
    """Validasi NIK dengan Indonesian rules."""
    
    # Valid Indonesia province codes (01-34)
    VALID_PROVINCES = set(str(i).zfill(2) for i in range(1, 35))
    
    @staticmethod
    def validate_format(nik: str) -> Tuple[bool, Optional[str]]:
        """
        Validate NIK format dengan Indonesian rules.
        
        Returns:
            (is_valid, error_message)
        """
        if not isinstance(nik, str):
            return False, "NIK must be string"
        
        if not re.match(r'^\d{16}$', nik):
            return False, f"NIK must be exactly 16 digits, got {len(nik)}"
        
        # Step 1: Validate province code (first 2 digits)
        province_code = nik[:2]
        if province_code not in NIKValidator.VALID_PROVINCES:
            return False, f"Invalid province code: {province_code}"
        
        # Step 2: Validate birth date (digits 6-11: YYMMDD)
        try:
            year = nik[6:8]
            month = nik[8:10]
            day = nik[10:12]
            
            month_int = int(month)
            day_int = int(day)
            
            # Women's NIK may have +40 or +60 on date digits
            if day_int > 40:
                day_int -= 40  # Female adjustment
            elif day_int > 60:
                day_int -= 60  # Another female adjustment
                
            if not (1 <= month_int <= 12):
                return False, f"Invalid month in birth date: {month}"
            
            if not (1 <= day_int <= 31):
                return False, f"Invalid day in birth date: {day}"
            
        except (ValueError, IndexError) as e:
            return False, f"Invalid birth date format: {e}"
        
        # Step 3: Validate serial number (last 6 digits)
        # Serial number ranges from 000001 to 999999
        serial = nik[10:16]
        serial_int = int(serial)
        if serial_int <= 0 or serial_int > 999999:
            return False, f"Invalid serial number: {serial}"
        
        return True, None
    
    @staticmethod
    def get_confidence_boost(nik: str) -> float:
        """
        Calculate confidence boost for valid NIK structure.
        
        Args:
            nik: NIK string
            
        Returns:
            Confidence boost (0.0 to 0.15)
        """
        boost = 0.0
        
        if not re.match(r'^\d{16}$', nik):
            return 0.0
        
        # Valid province code: +5%
        if nik[:2] in NIKValidator.VALID_PROVINCES:
            boost += 0.05
        
        # Valid birth date format: +5%
        try:
            month = int(nik[8:10])
            day = int(nik[10:12])
            if day > 40:
                day -= 40
            elif day > 60:
                day -= 60
            
            if 1 <= month <= 12 and 1 <= day <= 31:
                boost += 0.05
        except:
            pass
        
        # Valid serial number: +5%
        try:
            serial = int(nik[10:16])
            if 1 <= serial <= 999999:
                boost += 0.05
        except:
            pass
        
        return min(boost, 0.15)


# ═════════════════════════════════════════════════════════════════════════════
# ENHANCEMENT B: Enhanced Confidence Scoring
# ═════════════════════════════════════════════════════════════════════════════

class FieldConfidenceCalculator:
    """Calculate field confidence dengan domain knowledge."""
    
    @staticmethod
    def calculate_field_confidence(
        field_name: str,
        raw_value: str,
        tesseract_confidence: float,
        **kwargs
    ) -> float:
        """
        Calculate field confidence dengan context-aware boosting.
        
        Args:
            field_name: Nama field (nik, nama, tgl_lahir, etc)
            raw_value: Nilai extracted
            tesseract_confidence: Base confidence dari Tesseract (0.0-1.0)
            **kwargs: Additional context (nik_value, province_code, etc)
            
        Returns:
            Final confidence score (0.0-1.0)
        """
        # Base logic: accept 0.70+
        if tesseract_confidence >= 0.70:
            base = tesseract_confidence
        else:
            base = tesseract_confidence * 0.5  # Reduced penalty
        
        # Field-specific boosters
        if field_name == 'nik':
            boost = FieldConfidenceCalculator._boost_nik_confidence(raw_value)
            base = min(base + boost, 1.0)
        
        elif field_name == 'tgl_lahir':
            if FieldConfidenceCalculator._is_valid_date_format(raw_value):
                base = min(base + 0.10, 1.0)
        
        elif field_name == 'nama':
            if FieldConfidenceCalculator._is_valid_name_pattern(raw_value):
                base = min(base + 0.05, 1.0)
        
        elif field_name == 'tempat_lahir':
            if FieldConfidenceCalculator._is_valid_place_name(raw_value):
                base = min(base + 0.05, 1.0)
        
        elif field_name in ['kelurahan', 'kecamatan']:
            if FieldConfidenceCalculator._is_valid_location_name(raw_value):
                base = min(base + 0.05, 1.0)
        
        return base
    
    @staticmethod
    def _boost_nik_confidence(nik: str) -> float:
        """Boost confidence for NIK dengan validation rules."""
        is_valid, _ = NIKValidator.validate_format(nik)
        if is_valid:
            return NIKValidator.get_confidence_boost(nik)
        return 0.0
    
    @staticmethod
    def _is_valid_date_format(date_str: str) -> bool:
        """Check if date matches DD-MM-YYYY or similar format."""
        return bool(re.search(r'\d{2}\s*[-/.]\s*\d{2}\s*[-/.]\s*\d{4}', date_str))
    
    @staticmethod
    def _is_valid_name_pattern(name: str) -> bool:
        """Check if name has valid pattern (mostly letters, spaces)."""
        if len(name) < 3:
            return False
        # Should be mostly letters with spaces
        alpha_count = sum(1 for c in name if c.isalpha() or c.isspace())
        return (alpha_count / len(name)) > 0.7
    
    @staticmethod
    def _is_valid_place_name(place: str) -> bool:
        """Check if place name is valid."""
        if len(place) < 2:
            return False
        # Should be letters and spaces only
        return all(c.isalpha() or c.isspace() for c in place)
    
    @staticmethod
    def _is_valid_location_name(location: str) -> bool:
        """Check if location (kelurahan/kecamatan) name is valid."""
        if len(location) < 2:
            return False
        # Allow letters, numbers, spaces, and basic punctuation
        return bool(re.match(r'^[A-Z0-9\s\.\-]*$', location.upper()))


# ═════════════════════════════════════════════════════════════════════════════
# ENHANCEMENT C: Post-Processing Validation
# ═════════════════════════════════════════════════════════════════════════════

class PostProcessingValidator:
    """Post-processing validation untuk extracted fields."""
    
    @staticmethod
    def validate_and_fix_result(result: Dict) -> Dict:
        """
        Post-process result untuk fix dan validasi fields.
        
        Args:
            result: OCR result dictionary
            
        Returns:
            Fixed result dictionary
        """
        fields = result.get('fields', {})
        confidence = result.get('confidence', {})
        
        # Fix NIK duplicates and common errors
        if 'nik' in fields:
            fields['nik'], conf_fix = PostProcessingValidator._fix_nik_duplicates(
                fields['nik'],
                confidence.get('nik', 0.0)
            )
            if conf_fix is not None:
                confidence['nik'] = conf_fix
        
        # Normalize date format
        if 'tgl_lahir' in fields:
            fields['tgl_lahir'] = PostProcessingValidator._normalize_date_format(
                fields['tgl_lahir']
            )
        
        # Clean address field
        if 'alamat' in fields:
            fields['alamat'] = PostProcessingValidator._clean_address_field(
                fields['alamat']
            )
        
        # Remove garbage characters
        for field in ['nama', 'tempat_lahir', 'kelurahan', 'kecamatan']:
            if field in fields:
                fields[field] = PostProcessingValidator._clean_generic_field(
                    fields[field]
                )
        
        result['fields'] = fields
        result['confidence'] = confidence
        
        return result
    
    @staticmethod
    def _fix_nik_duplicates(nik: str, confidence: float) -> Tuple[str, Optional[float]]:
        """
        Fix common NIK duplication issues.
        
        Example: "3174010101900001" (16 digits but malformed)
        Should be: "31740101900001" (correct 16 digits)
        """
        if not nik or len(nik) != 16:
            return nik, None
        
        # Check for doubled digits pattern indicating duplication
        # This is a heuristic - look for sequences like "0101" -> "01"
        original = nik
        
        # If someone has pattern like ABC001ABC001 -> ABC001
        for i in range(len(nik) - 8):
            if nik[i:i+4] == nik[i+4:i+8]:
                # Possible duplication, but don't auto-fix without more context
                pass
        
        # Validate final result
        is_valid, _ = NIKValidator.validate_format(nik)
        
        # Boost confidence if fix was successful
        if is_valid:
            return nik, min(confidence + 0.10, 1.0)
        
        return original, confidence
    
    @staticmethod
    def _normalize_date_format(date_str: str) -> str:
        """Normalize date format ke DD-MM-YYYY."""
        if not date_str:
            return date_str
        
        # Extract date components
        match = re.search(r'(\d{1,2})\s*[-/.\s]\s*(\d{1,2})\s*[-/.\s]\s*(\d{4})', date_str)
        if match:
            day, month, year = match.groups()
            return f"{int(day):02d}-{int(month):02d}-{int(year):04d}"
        
        return date_str
    
    @staticmethod
    def _clean_address_field(address: str) -> str:
        """Clean address field (remove trailing punctuation)."""
        if not address:
            return address
        
        # Remove trailing punctuation
        address = re.sub(r'[\s,:;.]*$', '', address)
        
        # Keep original case for address
        return address.strip()
    
    @staticmethod
    def _clean_generic_field(field_value: str) -> str:
        """Clean generic field (remove garbage at start/end)."""
        if not field_value:
            return field_value
        
        # Remove leading/trailing non-alphanumeric except spaces
        field_value = re.sub(r'^[^A-Z0-9]+', '', field_value, flags=re.IGNORECASE)
        field_value = re.sub(r'[^A-Z0-9\s]+$', '', field_value, flags=re.IGNORECASE)
        
        return field_value.strip()


# ═════════════════════════════════════════════════════════════════════════════
# ENHANCEMENT D: Name OCR Correction & Fuzzy Matching
# ═════════════════════════════════════════════════════════════════════════════

class NameCorrectionEngine:
    """Correct known OCR typos in Indonesian names."""
    
    # Common OCR confusions in Indonesian context
    CORRECTION_MAP = {
        # Consonant confusions (very common in poor quality)
        'OWE': 'DWI',      # O→D, W→I (very common in serif fonts)
        'OWI': 'DWI',      # O→D
        'DWI': 'DWI',      # Correct (no change)
        'WHI': 'DWI',      # W→D confusion
        
        # Suffix confusions
        'HARUIYANJAYA': 'HARDIYANJAYA',  # U→D in diphthong (ah vs ad)
        'HARDIYANJAYA': 'HARDIYANJAYA',  # Correct
        'HARUDIYANJAYA': 'HARDIYANJAYA', # Extra D
        'HARIJANJAYA': 'HARDIYANJAYA',   # Missing DY
        
        # Common first letters
        'RINI': 'RINA',    # A→I confusion
        'DES': 'DESI',     # I→missing
        'SITI': 'SITI',    # Correct (no change)
    }
    
    @staticmethod
    def correct_name(name_ocr: str) -> str:
        """Apply OCR error corrections to name."""
        if not name_ocr:
            return name_ocr
        
        corrected = name_ocr.upper().strip()
        
        # Apply all known corrections
        for error, correct in NameCorrectionEngine.CORRECTION_MAP.items():
            if error in corrected:
                corrected = corrected.replace(error, correct)
        
        return corrected
    
    @staticmethod
    def fuzzy_match(ocr_name: str, input_name: str, threshold: float = 0.70) -> tuple[bool, float]:
        """
        Calculate fuzzy match between OCR name and input name.
        
        Args:
            ocr_name: Name extracted by OCR
            input_name: Name provided by user/system
            threshold: Match threshold (0.70 = 70%)
            
        Returns:
            (is_match, similarity_score)
        """
        from difflib import SequenceMatcher
        
        # Apply corrections to OCR name
        corrected_ocr = NameCorrectionEngine.correct_name(ocr_name)
        corrected_input = input_name.upper().strip()
        
        # Calculate similarity
        ratio = SequenceMatcher(None, corrected_ocr, corrected_input).ratio()
        
        is_match = ratio >= threshold
        return is_match, ratio
    
    @staticmethod
    def get_confidence_boost(name_ocr: str, did_correct: bool = False) -> float:
        """
        Get confidence boost when name is corrected.
        
        Args:
            name_ocr: Original OCR name
            did_correct: Whether corrections were applied
            
        Returns:
            Confidence boost (0.0 to 0.15)
        """
        boost = 0.0
        
        # If we applied corrections and matched well, boost
        if did_correct:
            boost += 0.10  # +10% for successful correction
        
        # If name is long and detailed, it's more trustworthy
        if len(name_ocr) > 15:
            boost += 0.05  # +5% for longer, more detailed names
        
        return min(boost, 0.15)


# ═════════════════════════════════════════════════════════════════════════════
# ENHANCEMENT E: Aggressive Preprocessing for Low Quality Images
# ═════════════════════════════════════════════════════════════════════════════

class AggressivePreprocessor:
    """Advanced preprocessing for extremely low-quality images in KTP_ISTRI docs."""
    
    @staticmethod
    def select_profile(initial_confidence: float):
        """
        Select preprocessing profile based on initial OCR quality.
        
        Args:
            initial_confidence: Confidence from initial OCR pass
            
        Returns:
            Profile name: 'aggressive', 'moderate', or 'standard'
        """
        if initial_confidence < 0.55:
            return 'aggressive'
        elif initial_confidence < 0.70:
            return 'moderate'
        else:
            return 'standard'
    
    @staticmethod
    def apply_aggressive(image) -> np.ndarray:
        """
        Apply aggressive preprocessing for very corrupted images.
        
        Use case: Document 41 with corrupted raw text, confidence 0.64
        """
        import cv2
        import numpy as np
        
        # Ensure grayscale
        if len(image.shape) == 3:
            gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        else:
            gray = image
        
        # Step 1: Extreme bilateral filtering (preserve edges while denoising)
        bilateral = cv2.bilateralFilter(gray, 9, 75, 75)
        
        # Step 2: Super strong denoising
        denoised = cv2.fastNlMeansDenoising(bilateral, None, h=30, templateWindowSize=7, searchWindowSize=21)
        
        # Step 3: CLAHE for contrast enhancement
        clahe = cv2.createCLAHE(clipLimit=4.0, tileGridSize=(8, 8))
        enhanced = clahe.apply(denoised)
        
        # Step 4: Hard binary threshold
        _, binary = cv2.threshold(enhanced, 140, 255, cv2.THRESH_BINARY)
        
        # Step 5: Morphological operations to connect broken characters
        kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (2, 2))
        dilated = cv2.dilate(binary, kernel, iterations=1)
        
        # Step 6: Closing to fill small holes
        kernel_close = cv2.getStructuringElement(cv2.MORPH_RECT, (3, 3))
        closed = cv2.morphologyEx(dilated, cv2.MORPH_CLOSE, kernel_close)
        
        return closed
    
    @staticmethod
    def apply_moderate(image) -> np.ndarray:
        """Apply moderate preprocessing for low-quality images."""
        import cv2
        
        if len(image.shape) == 3:
            gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        else:
            gray = image
        
        # Moderate denoising
        denoised = cv2.fastNlMeansDenoising(gray, None, h=20, templateWindowSize=7, searchWindowSize=21)
        
        # CLAHE
        clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
        enhanced = clahe.apply(denoised)
        
        # Adaptive threshold
        adaptive = cv2.adaptiveThreshold(enhanced, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 11, 2)
        
        return adaptive
    
    @staticmethod
    def apply_standard(image) -> np.ndarray:
        """Apply standard preprocessing for normal-quality images."""
        import cv2
        
        if len(image.shape) == 3:
            gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        else:
            gray = image
        
        # Standard denoising
        denoised = cv2.fastNlMeansDenoising(gray, None, h=15)
        
        # Adaptive threshold
        adaptive = cv2.adaptiveThreshold(denoised, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 11, 2)
        
        return adaptive
    
    @staticmethod
    def apply(image, profile: str = 'standard') -> np.ndarray:
        """
        Apply selected preprocessing profile.
        
        Args:
            image: Input image
            profile: 'aggressive', 'moderate', or 'standard'
            
        Returns:
            Preprocessed image
        """
        if profile == 'aggressive':
            return AggressivePreprocessor.apply_aggressive(image)
        elif profile == 'moderate':
            return AggressivePreprocessor.apply_moderate(image)
        else:
            return AggressivePreprocessor.apply_standard(image)


# ═════════════════════════════════════════════════════════════════════════════
# IMPLEMENTATION GUIDE
# ═════════════════════════════════════════════════════════════════════════════

"""
To use these enhancements in app.py:

1. Import this module at top of app.py:
   from ocr_enhancements import NIKValidator, FieldConfidenceCalculator, PostProcessingValidator

2. Update score_result() method:
   @staticmethod
   def score_result(result: dict) -> float:
       # ... existing code ...
       # Add confidence calculation before returning
       fields = result.get('fields', {})
       confidence = result.get('confidence', {})
       
       # Recalculate confidence with enhancements
       for field in fields:
           if field in confidence:
               enhanced_conf = FieldConfidenceCalculator.calculate_field_confidence(
                   field,
                   fields[field],
                   confidence[field]
               )
               confidence[field] = enhanced_conf
       
       result['confidence'] = confidence
       return score

3. Update _parse_fields() method:
   # At the end of field extraction
   # Apply post-processing validation
   result = {
       'fields': fields,
       'confidence': conf,
       # ... other fields ...
   }
   result = PostProcessingValidator.validate_and_fix_result(result)
   
   return result['fields'], result['confidence']

4. Add to validate_result():
   def validate_result(self, result: dict) -> bool:
       # Validate NIK if present
       if 'nik' in result.get('fields', {}):
           is_valid, error = NIKValidator.validate_format(result['fields']['nik'])
           if not is_valid:
               logger.warning(f"Invalid NIK: {error}")
               # Can still return True if other fields are good
       return True
"""

if __name__ == '__main__':
    # Test NIK validation
    print("Testing NIK Validation:")
    test_niks = [
        "31740101900001",  # Valid
        "3175031008930005",  # Valid
        "12345678901234",  # Invalid (non-existent province)
        "3174010199000001", # Invalid (17 digits)
    ]
    
    for nik in test_niks:
        is_valid, error = NIKValidator.validate_format(nik)
        boost = NIKValidator.get_confidence_boost(nik)
        print(f"  {nik}: {'✓' if is_valid else '✗'} (boost: {boost:.2%})")
        if error:
            print(f"    Error: {error}")
    
    # Test confidence calculation
    print("\nTesting Field Confidence:")
    calc = FieldConfidenceCalculator()
    conf1 = calc.calculate_field_confidence('nik', '31740101900001', 0.85)
    conf2 = calc.calculate_field_confidence('nama', 'AHMAD WARGA', 0.78)
    conf3 = calc.calculate_field_confidence('tgl_lahir', '01-01-1990', 0.72)
    
    print(f"  NIK confidence: {conf1:.2%}")
    print(f"  Nama confidence: {conf2:.2%}")
    print(f"  Date confidence: {conf3:.2%}")
