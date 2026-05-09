# 🛡️ Malware Triage Framework

Professional Malware Triage Framework — Python-based, Modular, Extensible

---

## 📋 Overview

Integrated static analysis framework for suspicious files, implementing automated multi-layer triage:

| Stage | Description |
|-------|-------------|
| 1️⃣ Identification | Calculate SHA-256/SHA-1/MD5 and query VirusTotal |
| 2️⃣ Packer Detection | Check PE signatures + Shannon entropy analysis |
| 3️⃣ Branching Logic | Automatic UPX handling or warning about limitations |
| 4️⃣ Deep Analysis | IAT + Imphash + Suspicious APIs + Extract IOCs |
| 5️⃣ Reporting | Colored terminal + JSON export |

---

## 🏗️ Project Structure

```
malware_triage/
├── main.py                  # Entry point + CLI
├── config.py                # All central settings
├── requirements.txt
├── README.md
│
├── core/
│   ├── file_analyzer.py     # Main orchestrator
│   ├── packer_detector.py   # Packer detection (Signatures + Entropy)
│   ├── entropy_analyzer.py  # Shannon entropy analysis
│   ├── static_analyzer.py   # Static analysis (IAT + Strings)
│   ├── vt_client.py         # VirusTotal API v3 client
│   ├── upx_handler.py       # Safe UPX unpacking
│   └── report_generator.py  # Report generation
│
├── utils/
│   ├── hashing.py           # Hash calculation
│   ├── strings.py           # String extraction
│   └── logger.py            # Colored logging system
│
├── tools/
│   └── upx.exe              # (Optional) Local UPX for deployment
│
├── samples/                 # Put suspicious files here
└── output/
    ├── reports/             # JSON reports
    └── logs/                # Run logs
```

---

## ⚙️ Installation

```bash
# 1. Clone the project
git clone https://github.com/yourname/malware_triage
cd malware_triage

# 2. Create virtual environment (recommended)
python -m venv venv
source venv/bin/activate       # Linux/macOS
venv\Scripts\activate          # Windows

# 3. Install requirements
pip install -r requirements.txt

# 4. Set up VirusTotal API Key
# In config.py:
#   VT_API_KEY = "your_key_here"
# Or environment variable:
export VT_API_KEY="your_key_here"
```

---

## 🚀 Usage

```bash
# Analyze single file
python main.py --file samples/suspicious.exe

# Analyze entire folder
python main.py --dir samples/ --recursive

# Without VirusTotal (local analysis only)
python main.py --file malware.exe --no-vt

# With API key directly
python main.py --file sample.exe --vt-key YOUR_KEY_HERE

# Without JSON export
python main.py --file sample.exe --no-json
```

---

## 🔧 Configuration

### VirusTotal API Key
```python
# config.py
VT_API_KEY = "your_api_key"          # or environment variable VT_API_KEY
VT_RATE_LIMIT_DELAY = 15             # seconds (4 requests/minute free)
```

### Entropy Threshold
```python
ENTROPY_PACKED_THRESHOLD = 7.0       # above this value = suspicious
```

### Local UPX
```
# Place UPX in:
tools/upx.exe    (Windows)
tools/upx        (Linux/macOS)
```

---

## 📊 Example Output

```
════════════════════════════════════════════════════════════════════════════════
  MALWARE TRIAGE FRAMEWORK — FILE ANALYSIS REPORT
════════════════════════════════════════════════════════════════════════════════
  📁 File: /samples/suspicious.exe
  📅 Analysis Time: 2024-01-15 14:30:22

[ IDENTIFICATION ]
╭──────────┬──────────────────────────────────────────────────────────────╮
│ SHA-256  │ a1b2c3d4e5f6...                                              │
│ SHA-1    │ 9f8e7d6c5b4a...                                              │
│ MD5      │ 1234abcd...                                                   │
╰──────────┴──────────────────────────────────────────────────────────────╯

[ VIRUSTOTAL RESULTS ]
🔴 Result: MALICIOUS
╭─────────────────┬──────────╮
│ Detection Ratio │ 45/72    │
│ Classification  │ trojan   │
╰─────────────────┴──────────╯

[ PACKER DETECTION ]
🔴 Warning: File is packed!
  Tool: UPX | Confidence: High

[ SUSPICIOUS API CALLS ]
🔴 5 suspicious API calls detected!
╭──────────────────────┬─────────────────────────────────────╮
│ VirtualAllocEx       │ Process Injection / Remote Memory   │
│ CreateRemoteThread   │ Remote Thread Injection             │
│ WriteProcessMemory   │ Process Memory Manipulation         │
╰──────────────────────┴─────────────────────────────────────╯

  Overall Risk Level: [    CRITICAL    ]
```

---

## 🔌 Extension

### Add new packer
```python
# config.py
KNOWN_PACKER_SECTIONS = [
    ...
    ".mynewpacker",  # Add here
]

# core/packer_detector.py
PACKER_SECTION_MAP = {
    ...
    ".mynewpacker": "MyNewPacker",
}
```

### Add suspicious API
```python
# config.py
SUSPICIOUS_APIS = {
    ...
    "MyDangerousAPI": "Description of why it's dangerous",
}
```

### Add IOC pattern
```python
# config.py
STRING_PATTERNS = {
    ...
    "Bitcoin Wallets": r'\b[13][a-km-zA-HJ-NP-Z1-9]{25,34}\b',
}
```

---

## ⚠️ Disclaimer

This tool is intended **for security research and digital forensics** only.  
Do not use it to analyze files you do not have permission to examine.  
The user is fully responsible for its use.

---

## 📄 License

MIT License — For free use in research and professional environments.
