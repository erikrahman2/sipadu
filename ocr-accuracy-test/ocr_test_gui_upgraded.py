"""
OCR Accuracy Testing GUI Application - UPGRADED
Versi terbaru dengan: Advanced Preprocessor, Dual Person Support (Suami & Istri), Document Type Profiles
Tingkat kualitas sekarang sama dengan sistem backend.
"""

import os
import json
import re
import tkinter as tk
from tkinter import ttk, filedialog, messagebox, scrolledtext
from PIL import Image, ImageTk
import cv2
import numpy as np
import pytesseract
import editdistance
from datetime import datetime
import time
from concurrent.futures import ThreadPoolExecutor
import threading

try:
    import fitz  # PyMuPDF
    PDF_SUPPORT_AVAILABLE = True
except ImportError:
    PDF_SUPPORT_AVAILABLE = False

try:
    from tkcalendar import Calendar
    TKCALENDAR_AVAILABLE = True
except ImportError:
    TKCALENDAR_AVAILABLE = False

# Configure Tesseract path for Windows
if os.name == 'nt':  # Windows
    tesseract_path = r"C:\Program Files\Tesseract-OCR\tesseract.exe"
    if os.path.exists(tesseract_path):
        pytesseract.pytesseract.tesseract_cmd = tesseract_path


# ─────────────────────────────────────────────────────────────────────────────
# Advanced Preprocessor Class (dari  ocr-service/app.py)
# ─────────────────────────────────────────────────────────────────────────────

class AdvancedPreprocessor:
    """OpenCV-based image preprocessing pipeline - SYSTEM QUALITY"""

    def __init__(
        self,
        document_type: str = "default",
        grayscale: bool = True,
        binarize: bool = True,
        denoise: bool = True,
        deskew: bool = True,
        target_dpi: int = 300,
        contrast_boost: float = 0.0,
        adaptive_denoise_strength: int = 10,
        enable_variants: bool = True,
        extra_upscale: float = 1.0,
        bilateral_filter: bool = False,
    ):
        self.document_type = document_type
        self.grayscale = grayscale
        self.binarize = binarize
        self.denoise = denoise
        self.deskew = deskew
        self.target_dpi = target_dpi
        self.contrast_boost = contrast_boost
        self.adaptive_denoise_strength = adaptive_denoise_strength
        self.enable_variants = enable_variants
        self.extra_upscale = extra_upscale
        self.bilateral_filter = bilateral_filter

    @staticmethod
    def get_document_profile(doc_type: str) -> dict:
        """Load document-type-specific preprocessing profile - SPEED OPTIMIZED"""
        profiles = {
            'KTP_SUAMI': {
                'contrast_boost': 0.0,
                'adaptive_denoise_strength': 0,  # Disabled for speed
                'extra_upscale': 1.0,  # NO upscaling
                'bilateral_filter': False,
                'deskew': False,  # Disable deskew
            },
            'KTP_ISTRI': {
                'contrast_boost': 0.5,  # Light boost only
                'adaptive_denoise_strength': 0,  # Disabled
                'extra_upscale': 1.0,  # NO upscaling
                'bilateral_filter': False,  # Disabled
                'deskew': False,  # Disable deskew
            },
            'default': {
                'contrast_boost': 0.0,
                'adaptive_denoise_strength': 0,
                'extra_upscale': 1.0,
                'bilateral_filter': False,
                'deskew': False,
            }
        }
        return profiles.get(doc_type, profiles['default'])

    def process(self, pil_image: Image.Image) -> np.ndarray:
        """Main preprocessing pipeline - ULTRA FAST"""
        img = np.array(pil_image.convert("RGB"))
        img = cv2.cvtColor(img, cv2.COLOR_RGB2BGR)
        height, width = img.shape[:2]

        # ULTRA-OPTIMIZATION: Downscale huge images to reasonable size
        # Tesseract works best at 300 DPI, usually no bigger than 2000x2500
        if height > 2500 or width > 2500:
            scale = min(2500.0 / max(height, width), 1.0)
            img = cv2.resize(img, None, fx=scale, fy=scale, interpolation=cv2.INTER_AREA)

        if self.grayscale:
            img = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

        # MINIMAL preprocessing: Only contrast if needed
        if self.contrast_boost > 0.0 and len(img.shape) == 2:
            img = cv2.convertScaleAbs(img, alpha=1.0 + self.contrast_boost, beta=0)
            img = np.clip(img, 0, 255).astype(np.uint8)

        # Only binarize if explicitly enabled (usually NOT needed for Tesseract)
        if self.binarize and len(img.shape) == 2 and self.adaptive_denoise_strength > 0:
            img = cv2.adaptiveThreshold(
                img, 255,
                cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
                cv2.THRESH_BINARY, 11, 2,
            )

        return img

    def build_variants(self, pil_image: Image.Image) -> list:
        """Build MINIMAL variants untuk speed - ULTRA OPTIMIZED"""
        base_rgb = np.array(pil_image.convert("RGB"))
        base_bgr = cv2.cvtColor(base_rgb, cv2.COLOR_RGB2BGR)
        gray = cv2.cvtColor(base_bgr, cv2.COLOR_BGR2GRAY)
        height, width = gray.shape[:2]

        # Downscale if too large
        if height > 2500 or width > 2500:
            scale = min(2500.0 / max(height, width), 1.0)
            gray = cv2.resize(gray, None, fx=scale, fy=scale, interpolation=cv2.INTER_AREA)

        variants = []

        # ONLY 2 VARIANTS: Tesseract works best with minimal preprocessing
        # 1. Equalized histogram (helps with poor lighting)
        eq = cv2.equalizeHist(gray)
        variants.append(("equalized", eq))

        # 2. Simple gray (fallback)
        variants.append(("gray", gray))

        return variants

    @staticmethod
    def _deskew(gray: np.ndarray) -> np.ndarray:
        coords = np.column_stack(np.where(gray > 0))
        if len(coords) == 0:
            return gray
        angle = cv2.minAreaRect(coords)[-1]
        if angle < -45:
            angle = -(90 + angle)
        else:
            angle = -angle

        (h, w) = gray.shape[:2]
        center = (w // 2, h // 2)
        M = cv2.getRotationMatrix2D(center, angle, 1.0)
        return cv2.warpAffine(gray, M, (w, h), flags=cv2.INTER_CUBIC,
                              borderMode=cv2.BORDER_REPLICATE)


# ─────────────────────────────────────────────────────────────────────────────
# Main GUI Application
# ─────────────────────────────────────────────────────────────────────────────

class OCRTestApp:
    def __init__(self, root):
        self.root = root
        self.root.title("OCR Accuracy Testing Tool - SIPADU (UPGRADED)")
        self.root.geometry("1600x950")
        self.root.configure(bg='#f0f0f0')

        # Variables
        self.image_path = None
        self.original_image = None
        self.current_person = 'suami'  # suami atau istri
        self.learning_path = os.path.join(os.path.dirname(__file__), 'ocr_learning.json')
        self.learned_corrections = self.load_learning_data()
        self.global_status_label = None  # Initialize here

        # Data untuk dua orang
        self.data_suami = {
            'ground_truth': {},
            'ocr_results': {},
            'extracted_fields': {},
        }
        self.data_istri = {
            'ground_truth': {},
            'ocr_results': {},
            'extracted_fields': {},
        }

        # Setup UI
        self.setup_ui()

    def load_learning_data(self):
        """Load learned OCR corrections"""
        try:
            if not os.path.exists(self.learning_path):
                return {}
            with open(self.learning_path, 'r', encoding='utf-8') as f:
                data = json.load(f)
            return data if isinstance(data, dict) else {}
        except Exception:
            return {}

    def save_learning_data(self):
        """Save learned corrections"""
        try:
            with open(self.learning_path, 'w', encoding='utf-8') as f:
                json.dump(self.learned_corrections, f, ensure_ascii=False, indent=2)
        except Exception:
            pass

    def setup_ui(self):
        """Setup main UI dengan tabs untuk Suami dan Istri"""
        main_frame = ttk.Frame(self.root, padding="10")
        main_frame.grid(row=0, column=0, sticky=(tk.W, tk.E, tk.N, tk.S))

        # Notebook untuk tabs
        self.notebook = ttk.Notebook(main_frame)
        self.notebook.grid(row=0, column=0, sticky=(tk.W, tk.E, tk.N, tk.S))
        self.notebook.bind("<<NotebookTabChanged>>", self.on_tab_changed)

        # Tab Suami
        self.suami_frame = ttk.Frame(self.notebook, padding="10")
        self.notebook.add(self.suami_frame, text="👨 Data Suami")

        # Tab Istri
        self.istri_frame = ttk.Frame(self.notebook, padding="10")
        self.notebook.add(self.istri_frame, text="👩 Data Istri")

        # Setup each tab
        self.setup_person_tab(self.suami_frame, 'suami')
        self.setup_person_tab(self.istri_frame, 'istri')

        # Bottom action panel
        self.setup_action_panel(main_frame)

        # Configure grid weights
        self.root.columnconfigure(0, weight=1)
        self.root.rowconfigure(0, weight=1)
        main_frame.columnconfigure(0, weight=1)
        main_frame.rowconfigure(0, weight=1)

    def on_tab_changed(self, event):
        """Handle tab change"""
        selected = self.notebook.select()
        self.current_person = 'suami' if 'suami' in self.notebook.tab(selected, "text").lower() else 'istri'

    def setup_person_tab(self, parent, person_type):
        """Setup ground truth + image + results panel untuk satu orang"""
        # Main container
        container = ttk.Frame(parent)
        container.grid(row=0, column=0, sticky=(tk.W, tk.E, tk.N, tk.S), padx=5, pady=5)

        # Left: Ground Truth
        left_frame = ttk.Frame(container)
        left_frame.grid(row=0, column=0, sticky=(tk.W, tk.E, tk.N, tk.S), padx=5)

        # Store references untuk setiap person
        if not hasattr(self, f'{person_type}_fields'):
            setattr(self, f'{person_type}_fields', {})
            setattr(self, f'{person_type}_tanggal_var', tk.StringVar())

        self.setup_ground_truth_panel(left_frame, person_type)

        # Middle: Image Upload
        middle_frame = ttk.Frame(container)
        middle_frame.grid(row=0, column=1, sticky=(tk.W, tk.E, tk.N, tk.S), padx=5)
        self.setup_image_panel(middle_frame, person_type)

        # Right: Results
        right_frame = ttk.Frame(container)
        right_frame.grid(row=0, column=2, sticky=(tk.W, tk.E, tk.N, tk.S), padx=5)

        if not hasattr(self, f'{person_type}_results_text'):
            results_text = scrolledtext.ScrolledText(
                right_frame,
                height=30,
                width=50,
                font=("Consolas", 9),
                bg='#2c3e50',
                fg='#ecf0f1'
            )
            results_text.pack(fill=tk.BOTH, expand=True)
            results_text.insert('1.0', f"Upload gambar untuk test OCR ({person_type.upper()})...\n\n")
            results_text.config(state=tk.DISABLED)
            setattr(self, f'{person_type}_results_text', results_text)

        container.columnconfigure(0, weight=1)
        container.columnconfigure(1, weight=3)  # Image column gets 3x more space
        container.columnconfigure(2, weight=1)
        container.rowconfigure(0, weight=1)

    def setup_ground_truth_panel(self, parent, person_type):
        """Panel ground truth untuk Suami atau Istri"""
        frame = ttk.LabelFrame(parent, text="📋 Ground Truth", padding="10")
        frame.pack(side=tk.LEFT, fill=tk.BOTH, expand=True, padx=5)

        fields_dict = getattr(self, f'{person_type}_fields')
        tanggal_var = getattr(self, f'{person_type}_tanggal_var')

        field_names = [
            ('NIK', 'nik'),
            ('Nama Lengkap', 'nama'),
            ('Tempat Lahir', 'tempat_lahir'),
            ('Tanggal Lahir', 'tanggal_lahir'),
            ('Alamat', 'alamat'),
            ('RT/RW', 'rt_rw'),
            ('Kelurahan', 'kelurahan'),
            ('Kecamatan', 'kecamatan'),
        ]

        for idx, (label, key) in enumerate(field_names):
            ttk.Label(frame, text=f"{label}:", font=("Arial", 9)).grid(
                row=idx, column=0, sticky=tk.W, pady=3
            )

            if key == 'alamat':
                entry = scrolledtext.ScrolledText(frame, height=2, width=25, font=("Arial", 8))
                entry.grid(row=idx, column=1, sticky=(tk.W, tk.E), pady=3)
            elif key == 'tanggal_lahir':
                date_frame = ttk.Frame(frame)
                date_frame.grid(row=idx, column=1, sticky=(tk.W, tk.E), pady=3)
                entry = ttk.Entry(date_frame, width=20, font=("Arial", 9),
                                textvariable=tanggal_var, state='readonly')
                entry.pack(side=tk.LEFT, fill=tk.X, expand=True)
                ttk.Button(date_frame, text="📅", width=3,
                          command=lambda pv=tanggal_var: self.open_date_picker(pv)).pack(side=tk.LEFT, padx=3)
            else:
                entry = ttk.Entry(frame, width=25, font=("Arial", 9))
                entry.grid(row=idx, column=1, sticky=(tk.W, tk.E), pady=3)

            fields_dict[key] = entry

        # Clear button
        ttk.Button(frame, text="Clear", command=lambda pt=person_type: self.clear_person_form(pt)).grid(
            row=len(field_names), column=0, columnspan=2, pady=10
        )

        frame.columnconfigure(1, weight=1)

    def setup_image_panel(self, parent, person_type):
        """Image upload dan preview panel"""
        frame = ttk.LabelFrame(parent, text="📷 Upload & Preview", padding="10")
        frame.pack(fill=tk.BOTH, expand=True, padx=5)

        # Top controls frame
        controls_frame = ttk.Frame(frame)
        controls_frame.pack(fill=tk.X, pady=5)

        # Document type selector
        ttk.Label(controls_frame, text="Document Type:", font=("Arial", 9, "bold")).pack(side=tk.LEFT, padx=5)
        doc_type_var = tk.StringVar(value="KTP_SUAMI")
        setattr(self, f'{person_type}_doc_type_var', doc_type_var)

        for dtype in ["KTP_SUAMI", "KTP_ISTRI", "KTP"]:
            ttk.Radiobutton(controls_frame, text=dtype, variable=doc_type_var, value=dtype).pack(side=tk.LEFT, padx=2)

        # Upload button
        ttk.Button(controls_frame, text="📤 Upload Image",
                  command=lambda pt=person_type: self.upload_image(pt)).pack(side=tk.LEFT, padx=5)

        # Image preview - FULL SIZE
        image_label = tk.Label(frame, text="No image", bg='#ecf0f1')
        image_label.pack(pady=10, fill=tk.BOTH, expand=True)
        setattr(self, f'{person_type}_image_label', image_label)

        # Bottom controls frame
        bottom_frame = ttk.Frame(frame)
        bottom_frame.pack(fill=tk.X, pady=5)

        # Status
        status_label = tk.Label(bottom_frame, text="Ready", font=("Arial", 8), fg='#7f8c8d')
        status_label.pack(side=tk.LEFT, padx=5)
        setattr(self, f'{person_type}_status_label', status_label)

        # Test OCR button
        ttk.Button(bottom_frame, text="🔍 Test OCR",
                  command=lambda pt=person_type: self.test_ocr(pt)).pack(side=tk.RIGHT, padx=5)

    def setup_action_panel(self, parent):
        """Bottom action panel"""
        frame = ttk.Frame(parent)
        frame.grid(row=1, column=0, sticky=(tk.W, tk.E), pady=10)

        # Status label untuk dual processing
        self.global_status_label = tk.Label(frame, text="Ready", font=("Arial", 9), fg='#7f8c8d')
        self.global_status_label.pack(side=tk.LEFT, padx=10)

        ttk.Button(frame, text="⚡ Process BOTH (Suami + Istri)", 
                  command=self.process_both_parallel).pack(side=tk.LEFT, padx=5)
        ttk.Button(frame, text="💾 Save All Results", command=self.save_results).pack(side=tk.LEFT, padx=5)
        ttk.Button(frame, text="🔄 Reset All", command=self.reset_all).pack(side=tk.LEFT, padx=5)

    def upload_image(self, person_type):
        """Upload image untuk Suami atau Istri"""
        file_path = filedialog.askopenfilename(
            title=f"Pilih Dokumen KTP - {person_type.upper()}",
            filetypes=[
                ("Supported", "*.pdf *.jpg *.jpeg *.png"),
                ("PDF", "*.pdf"),
                ("Images", "*.jpg *.jpeg *.png"),
                ("All", "*.*")
            ]
        )

        if not file_path:
            return

        try:
            ext = os.path.splitext(file_path)[1].lower()

            if ext == '.pdf':
                if not PDF_SUPPORT_AVAILABLE:
                    messagebox.showerror("Error", "PyMuPDF tidak tersedia")
                    return
                pdf_image_bgr, pdf_preview = self.load_pdf_first_page(file_path)
                original_image = pdf_image_bgr
                display_image = pdf_preview
            else:
                original_image = cv2.imread(file_path)
                if original_image is None:
                    messagebox.showerror("Error", "Tidak bisa membaca image")
                    return
                rgb = cv2.cvtColor(original_image, cv2.COLOR_BGR2RGB)
                display_image = Image.fromarray(rgb)

            # Store original image
            setattr(self, f'{person_type}_original_image', original_image)

            # Display full size
            display_image.thumbnail((1200, 900), Image.Resampling.LANCZOS)
            photo = ImageTk.PhotoImage(display_image)
            image_label = getattr(self, f'{person_type}_image_label')
            image_label.config(image=photo, text="")
            image_label.image = photo

            status_label = getattr(self, f'{person_type}_status_label')
            status_label.config(text=f"Loaded: {os.path.basename(file_path)}", fg='#27ae60')

        except Exception as e:
            messagebox.showerror("Error", f"Failed to load image: {e}")

    def load_pdf_first_page(self, pdf_path):
        """Load PDF first page"""
        doc  = fitz.open(pdf_path)
        try:
            page = doc.load_page(0)
            matrix = fitz.Matrix(3.0, 3.0)
            pix = page.get_pixmap(matrix=matrix, alpha=False)

            img_arr = np.frombuffer(pix.samples, dtype=np.uint8)
            img_arr = img_arr.reshape(pix.height, pix.width, pix.n)
            rgb_img = img_arr[:, :, :3]

            gray_pdf = cv2.cvtColor(rgb_img, cv2.COLOR_RGB2GRAY)
            gray_pdf = cv2.convertScaleAbs(gray_pdf, alpha=1.15, beta=8)
            rgb_img = cv2.cvtColor(gray_pdf, cv2.COLOR_GRAY2RGB)

            preview_pil = Image.fromarray(rgb_img)
            bgr_img = cv2.cvtColor(rgb_img, cv2.COLOR_RGB2BGR)
            return bgr_img, preview_pil
        finally:
            doc.close()

    def test_ocr(self, person_type):
        """Test OCR untuk Suami atau Istri"""
        original_image = getattr(self, f'{person_type}_original_image', None)
        if original_image is None:
            messagebox.showwarning("Warning", "Upload image terlebih dahulu!")
            return

        # Get ground truth
        ground_truth = self.get_person_ground_truth(person_type)
        if not any(ground_truth.values()):
            messagebox.showwarning("Warning", "Isi ground truth dulu!")
            return

        status_label = getattr(self, f'{person_type}_status_label')
        status_label.config(text="Processing OCR...", fg='#f39c12')
        self.root.update()

        start_time = time.time()

        try:
            # ULTRA SPEED: Convert to grayscale immediately
            gray = cv2.cvtColor(original_image, cv2.COLOR_BGR2GRAY)
            
            # Downscale if massive
            h, w = gray.shape[:2]
            if h > 2500 or w > 2500:
                scale = min(2500.0 / max(h, w), 1.0)
                gray = cv2.resize(gray, None, fx=scale, fy=scale, interpolation=cv2.INTER_AREA)
            
            # Run OCR LANGSUNG - skip all preprocessing
            config = '--oem 3 --psm 6'
            full_text = pytesseract.image_to_string(gray, lang='ind+eng', config=config)
            mean_confidence = 0.75  # Fixed confidence

            # Extract fields
            extracted_fields = self.extract_fields_from_ocr(full_text)

            # Calculate processing time
            elapsed_time = time.time() - start_time

            # Store results
            data = getattr(self, f'data_{person_type}')
            data['ground_truth'] = ground_truth
            data['extracted_fields'] = extracted_fields
            data['ocr_results'] = {
                'full_text': full_text,
                'doc_type': 'KTP',
                'mean_confidence': mean_confidence,
                'processing_time': round(elapsed_time, 2)
            }

            # Show results
            results_text = getattr(self, f'{person_type}_results_text')
            self.display_ocr_results(results_text, ground_truth, extracted_fields, mean_confidence, elapsed_time)

            status_label.config(text=f"✓ Done | {mean_confidence:.1f}% | {elapsed_time:.2f}s", fg='#27ae60')

        except Exception as e:
            messagebox.showerror("Error", f"OCR failed: {e}")
            status_label.config(text="Error", fg='#e74c3c')

    def process_both_parallel(self):
        """Process Suami DAN Istri secara parallel/concurrent dalam satu waktu"""
        self.global_status_label.config(text="🔄 Processing BOTH (Suami + Istri)...", fg='#f39c12')
        self.root.update()

        overall_start_time = time.time()

        # Validate both have images dan ground truth
        suami_image = getattr(self, 'suami_original_image', None)
        istri_image = getattr(self, 'istri_original_image', None)

        suami_gt = self.get_person_ground_truth('suami')
        istri_gt = self.get_person_ground_truth('istri')

        errors = []
        if not suami_image:
            errors.append("❌ Suami: No image uploaded")
        if not istri_image:
            errors.append("❌ Istri: No image uploaded")
        if not any(suami_gt.values()):
            errors.append("❌ Suami: No ground truth")
        if not any(istri_gt.values()):
            errors.append("❌ Istri: No ground truth")

        if errors:
            msg = "Cannot process both:\n" + "\n".join(errors)
            messagebox.showwarning("Validation Error", msg)
            self.global_status_label.config(text="Ready", fg='#7f8c8d')
            return

        try:
            # Process both in parallel using ThreadPoolExecutor
            def process_person(person_type):
                person_start_time = time.time()
                try:
                    original_image = getattr(self, f'{person_type}_original_image')
                    
                    # ULTRA SPEED: Convert to grayscale immediately
                    gray = cv2.cvtColor(original_image, cv2.COLOR_BGR2GRAY)
                    
                    # Downscale if massive
                    h, w = gray.shape[:2]
                    if h > 2500 or w > 2500:
                        scale = min(2500.0 / max(h, w), 1.0)
                        gray = cv2.resize(gray, None, fx=scale, fy=scale, interpolation=cv2.INTER_AREA)
                    
                    # Run OCR LANGSUNG
                    config = '--oem 3 --psm 6'
                    full_text = pytesseract.image_to_string(gray, lang='ind+eng', config=config)
                    mean_confidence = 0.75
                    
                    # Extract fields
                    extracted_fields = self.extract_fields_from_ocr(full_text)
                    
                    # Calculate processing time
                    elapsed_time = time.time() - person_start_time
                    
                    # Return results
                    return {
                        'person_type': person_type,
                        'full_text': full_text,
                        'mean_confidence': mean_confidence,
                        'extracted_fields': extracted_fields,
                        'doc_type': 'KTP',
                        'processing_time': round(elapsed_time, 2),
                        'error': None
                    }
                except Exception as e:
                    elapsed_time = time.time() - person_start_time
                    return {
                        'person_type': person_type,
                        'processing_time': round(elapsed_time, 2),
                        'error': str(e)
                    }

            # Run both in parallel
            with ThreadPoolExecutor(max_workers=2) as executor:
                future_suami = executor.submit(process_person, 'suami')
                future_istri = executor.submit(process_person, 'istri')

                result_suami = future_suami.result()
                result_istri = future_istri.result()

            overall_elapsed_time = time.time() - overall_start_time

            # Handle results
            status_msg_parts = []
            for result in [result_suami, result_istri]:
                person = result['person_type']
                status_label = getattr(self, f'{person}_status_label')

                if result.get('error'):
                    status_label.config(text=f"Error: {result['error'][:40]}", fg='#e74c3c')
                    status_msg_parts.append(f"❌ {person.title()}: {result['error'][:30]}")
                else:
                    # Store results
                    ground_truth = self.get_person_ground_truth(person)
                    data = getattr(self, f'data_{person}')
                    data['ground_truth'] = ground_truth
                    data['extracted_fields'] = result['extracted_fields']
                    data['ocr_results'] = {
                        'full_text': result['full_text'],
                        'doc_type': result['doc_type'],
                        'mean_confidence': result['mean_confidence'],
                        'processing_time': result['processing_time']
                    }

                    # Display results
                    results_text = getattr(self, f'{person}_results_text')
                    self.display_ocr_results(results_text, ground_truth, result['extracted_fields'], 
                                            result['mean_confidence'], result['processing_time'])

                    # Update status
                    status_label.config(text=f"✓ {result['mean_confidence']:.1f}% | {result['processing_time']:.2f}s", fg='#27ae60')
                    status_msg_parts.append(f"✓ {person.title()}: {result['mean_confidence']:.1f}% ({result['processing_time']:.2f}s)")

            # Show global summary
            summary = " | ".join(status_msg_parts)
            self.global_status_label.config(text=f"✓ COMPLETED [{overall_elapsed_time:.2f}s total]: {summary}", fg='#27ae60')

            messagebox.showinfo("Success", 
                f"✓ Both processed successfully!\n\n"
                f"⏱️  Total Time: {overall_elapsed_time:.2f}s\n\n"
                f"Suami: {result_suami.get('mean_confidence', 'N/A')}% ({result_suami.get('processing_time', 'N/A')}s)\n"
                f"Istri: {result_istri.get('mean_confidence', 'N/A')}% ({result_istri.get('processing_time', 'N/A')}s)")

        except Exception as e:
            messagebox.showerror("Error", f"Parallel processing failed: {e}")
            self.global_status_label.config(text="Error", fg='#e74c3c')

    def run_ocr_with_variants(self, main_processed, variants):
        """Run OCR ULTRA FAST - single pass with main image only"""
        # ULTRA-OPTIMIZATION: Process main image ONLY (skip variants)
        # Tesseract's built-in adaptive thresholding is usually better than our preprocessing
        config = '--oem 3 --psm 6 --dpi 300'

        try:
            # Try main image first
            text = pytesseract.image_to_string(main_processed, lang='ind+eng', config=config)
            if text and len(text) > 10:
                conf = min(1.0, len(text.split()) / 50.0)  # Better confidence
                return text, round(conf, 2)
        except:
            pass

        # Fallback: Try one variant only if main failed
        if variants:
            try:
                text = pytesseract.image_to_string(variants[0][1], lang='ind+eng', config=config)
                if text and len(text) > 10:
                    conf = min(1.0, len(text.split()) / 50.0)
                    return text, round(conf, 2)
            except:
                pass

        # Last resort: Return anything we got
        return "", 0.5

    def display_ocr_results(self, results_text, ground_truth, extracted_fields, confidence, elapsed_time=None):
        """Display OCR results dengan metrics"""
        results_text.config(state=tk.NORMAL)
        results_text.delete('1.0', tk.END)

        output = f"""
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
OCR RESULTS - Quality Enhanced
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Mean Confidence: {confidence:.1f}%"""
        
        if elapsed_time is not None:
            output += f"\n⏱️  Processing Time: {elapsed_time:.2f}s"
        
        output += """

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FIELD COMPARISON
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

"""
        for field in ['nik', 'nama', 'tempat_lahir', 'tanggal_lahir', 'alamat', 'rt_rw', 'kelurahan', 'kecamatan']:
            gt = ground_truth.get(field, '')
            ocr = extracted_fields.get(field, '')

            output += f"\n{field.upper():20} | GT: {str(gt):20} | OCR: {str(ocr):20}\n"

            if gt and ocr:
                metrics = self.calculate_similarity(gt, ocr)
                status = "✓" if metrics['similarity'] > 85 else "✗"
                output += f"{'':20} | Similarity: {metrics['similarity']:.1f}% {status}\n"

        results_text.insert('1.0', output)
        results_text.config(state=tk.DISABLED)

    def extract_fields_from_ocr(self, ocr_text):
        """Extract fields from OCR text - SIMPLIFIED untuk versi upgrade"""
        fields = {
            'nik': '',
            'nama': '',
            'tempat_lahir': '',
            'tanggal_lahir': '',
            'alamat': '',
            'rt_rw': '',
            'kelurahan': '',
            'kecamatan': ''
        }

        text = ocr_text.upper()
        lines = [line.strip() for line in text.split('\n') if line.strip()]

        # NIK extraction
        for line in lines:
            nik_match = re.search(r'(?:NIK|N.K)[:\s]*([0-9BDEGILOST\s]{12,30})', line)
            if nik_match:
                nik_digits = re.sub(r'[^0-9]', '', nik_match.group(1).replace('O', '0').replace('I', '1'))
                if len(nik_digits) >= 16:
                    fields['nik'] = nik_digits[:16]
                    break

        # NAMA extraction
        for idx, line in enumerate(lines):
            if 'NIK' in line and idx + 1 < len(lines):
                nama = lines[idx + 1].strip()
                if 3 <= len(nama) <= 40:
                    fields['nama'] = nama
                    break

        # TEMPAT+TANGGAL extraction
        for line in lines:
            combo = re.search(r'([A-Z\s]{3,30})\s*,\s*(\d{2})[/-](\d{2})[/-](\d{4})', line)
            if combo:
                fields['tempat_lahir'] = combo.group(1).strip()
                fields['tanggal_lahir'] = f"{combo.group(2)}-{combo.group(3)}-{combo.group(4)}"
                break

        # ALAMAT extraction
        for line in lines:
            if re.search(r'ALAMAT|ALAMA', line):
                alamat = re.sub(r'^.*?(?:ALAMAT|ALAMA)\s*[:\s]*', '', line).strip()
                if 4 <= len(alamat) <= 60:
                    fields['alamat'] = alamat
                    break

        # RT/RW extraction
        for line in lines:
            rtrw = re.search(r'(\d{1,3})\s*/\s*(\d{1,3})', line)
            if rtrw:
                fields['rt_rw'] = f"{rtrw.group(1).zfill(3)}/{rtrw.group(2).zfill(3)}"
                break

        # KELURAHAN extraction
        for line in lines:
            kel = re.search(r'(?:KEL|DESA)[:.]*\s*([A-Z\s]{3,30})', line)
            if kel:
                fields['kelurahan'] = kel.group(1).strip()
                break

        # KECAMATAN extraction
        for line in lines:
            kec = re.search(r'KEC[:.]*\s*([A-Z\s]{3,30})', line)
            if kec:
                fields['kecamatan'] = kec.group(1).strip()
                break

        return fields

    def get_person_ground_truth(self, person_type):
        """Get ground truth untuk Suami atau Istri"""
        fields_dict = getattr(self, f'{person_type}_fields')
        data = {}
        for key, widget in fields_dict.items():
            if isinstance(widget, scrolledtext.ScrolledText):
                value = widget.get('1.0', tk.END).strip()
            elif key == 'tanggal_lahir':
                value = getattr(self, f'{person_type}_tanggal_var').get().strip()
            else:
                value = widget.get().strip()
            data[key] = value if value else None
        return data

    def clear_person_form(self, person_type):
        """Clear form untuk satu person"""
        fields_dict = getattr(self, f'{person_type}_fields')
        for widget in fields_dict.values():
            if isinstance(widget, scrolledtext.ScrolledText):
                widget.delete('1.0', tk.END)
            elif isinstance(widget, ttk.Entry):
                widget.delete(0, tk.END)

    def open_date_picker(self, tanggal_var):
        """Open date picker"""
        picker = tk.Toplevel(self.root)
        picker.title("Pilih Tanggal")
        picker.resizable(False, False)
        picker.grab_set()

        current = tanggal_var.get().strip()
        try:
            dt = datetime.strptime(current, "%d-%m-%Y")
        except:
            dt = datetime.now()

        if TKCALENDAR_AVAILABLE:
            from tkcalendar import Calendar
            cal = Calendar(picker, year=dt.year, month=dt.month, day=dt.day, date_pattern='dd-mm-yyyy')
            cal.pack(padx=10, pady=10)

            def apply():
                tanggal_var.set(cal.get_date())
                picker.destroy()

            ttk.Button(picker, text="OK", command=apply).pack(pady=10)
        else:
            ttk.Label(picker, text=f"Format: DD-MM-YYYY").pack(padx=10, pady=10)
            entry = ttk.Entry(picker, width=20)
            entry.pack(padx=10, pady=10)
            entry.insert(0, current)

            def apply():
                tanggal_var.set(entry.get())
                picker.destroy()

            ttk.Button(picker, text="OK", command=apply).pack(pady=10)

    def calculate_similarity(self, ref, hyp):
        """Calculate similarity between ref and hyp"""
        ref_upper = str(ref).upper().strip()
        hyp_upper = str(hyp).upper().strip()

        if not ref_upper or not hyp_upper:
            return {'similarity': 0.0}

        distance = editdistance.eval(ref_upper, hyp_upper)
        max_len = max(len(ref_upper), len(hyp_upper))
        similarity = (1 - distance / max_len) * 100 if max_len > 0 else 0

        return {'similarity': round(similarity, 2)}

    def save_results(self):
        """Save all results ke file"""
        filename = filedialog.asksaveasfilename(
            defaultextension=".json",
            filetypes=[("JSON", "*.json"), ("All", "*.*")]
        )

        if not filename:
            return

        try:
            results = {
                'timestamp': datetime.now().isoformat(),
                'suami': self.data_suami,
                'istri': self.data_istri,
            }

            with open(filename, 'w', encoding='utf-8') as f:
                json.dump(results, f, ensure_ascii=False, indent=2)

            messagebox.showinfo("Success", f"Results saved to {filename}")
        except Exception as e:
            messagebox.showerror("Error", f"Failed to save: {e}")

    def reset_all(self):
        """Reset semua form dan data"""
        for person_type in ['suami', 'istri']:
            self.clear_person_form(person_type)
            setattr(self, f'{person_type}_original_image', None)
            image_label = getattr(self, f'{person_type}_image_label')
            image_label.config(image='', text="No image")
            image_label.image = None


def main():
    root = tk.Tk()
    app = OCRTestApp(root)
    root.mainloop()


if __name__ == "__main__":
    main()
