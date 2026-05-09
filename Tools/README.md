# 🛡️ Trapix — Malware Triage Framework

**Professional Malware Triage Framework — Python-based, Modular, Extensible**

---

## 📋 Overview

Trapix is an integrated static analysis framework for suspicious PE files, implementing automated multi-layer triage. It orchestrates detection stages in the correct order and applies branching logic based on each stage's findings — automatically unpacking UPX-packed files before deep analysis, for example.

| Stage | Module | Description |
|-------|--------|-------------|
| 1️⃣ Identification | `utils/hashing.py` | Calculate SHA-256 / SHA-1 / MD5 and query VirusTotal |
| 2️⃣ Packer Detection | `core/packer_detector.py` | Check PE section signatures + Shannon entropy analysis |
| 3️⃣ Branching Logic | `core/upx_handler.py` | Automatic UPX unpacking or warn about limitations |
| 4️⃣ Deep Analysis | `core/static_analyzer.py` | IAT + Imphash + Suspicious APIs + Extract IOCs |
| 5️⃣ Reporting | `core/report_generator.py` | Colored terminal output + JSON export |

---

## 🏗️ Project Structure

```
Trapix/
├── main.py                  # Entry point + CLI
├── config.py                # Central settings (API keys, thresholds, signatures)
├── requirements.txt
├── README.md
│
├── core/
│   ├── file_analyzer.py     # Main orchestrator — coordinates all stages
│   ├── packer_detector.py   # Packer detection (signatures + entropy)
│   ├── entropy_analyzer.py  # Shannon entropy analysis per PE section
│   ├── static_analyzer.py   # Static analysis (IAT, strings, IOC extraction)
│   ├── vt_client.py         # VirusTotal API v3 client
│   ├── upx_handler.py       # Safe UPX unpacking
│   └── report_generator.py  # Terminal + JSON report generation
│
├── utils/
│   ├── hashing.py           # SHA-256 / SHA-1 / MD5 calculation
│   ├── strings.py           # String extraction from binary
│   └── logger.py            # Colored logging system
│
├── tools/
│   └── upx / upx.exe        # (Optional) Local UPX binary for unpacking
│
├── samples/                 # Place suspicious files here
└── output/
    ├── reports/             # JSON reports (auto-generated)
    └── logs/                # Run logs (auto-generated)
```

---

## ⚙️ Installation

```bash
# 1. Clone the repository
git clone https://github.com/yourname/trapix
cd trapix

# 2. Create a virtual environment (recommended)
python -m venv venv
source venv/bin/activate        # Linux/macOS
venv\Scripts\activate           # Windows

# 3. Install dependencies
pip install -r requirements.txt

# 4. Configure your VirusTotal API key
# Option A — edit config.py:
#   VT_API_KEY = "your_key_here"
# Option B — environment variable:
export VT_API_KEY="your_key_here"   # Linux/macOS
set VT_API_KEY=your_key_here        # Windows
```

### Dependencies

| Package | Purpose |
|---------|---------|
| `pefile` | PE header parsing (imports, sections, metadata) |
| `requests` | VirusTotal API v3 communication |
| `colorama` | Colored terminal output |
| `tabulate` | Formatted result tables |
| `yara-python` | YARA rule matching (optional extension) |
| `python-magic` | File-type detection via magic bytes |

---

## 🚀 Usage

```bash
# Analyze a single file
python main.py --file samples/suspicious.exe

# Analyze an entire folder (recursive by default)
python main.py --dir samples/

# Skip VirusTotal lookup (local analysis only)
python main.py --file malware.exe --no-vt

# Provide a VirusTotal API key at runtime
python main.py --file sample.exe --vt-key YOUR_KEY_HERE

# Suppress JSON export
python main.py --file sample.exe --no-json

# Quiet mode — show only important results
python main.py --file sample.exe --quiet
```

### CLI Reference

| Flag | Short | Description |
|------|-------|-------------|
| `--file PATH` | `-f` | Single file to analyze |
| `--dir PATH` | `-d` | Directory to scan |
| `--recursive` | `-r` | Scan subdirectories (default: enabled) |
| `--vt-key KEY` | | VirusTotal API key (overrides config.py) |
| `--no-vt` | | Skip VirusTotal query |
| `--no-json` | | Skip JSON report export |
| `--quiet` | `-q` | Suppress verbose output |

---

## 🔧 Configuration (`config.py`)

### VirusTotal
```python
VT_API_KEY = "your_api_key"   # or set env var VT_API_KEY
VT_RATE_LIMIT_DELAY = 15      # seconds between requests (free tier: 4 req/min)
```

### Entropy Threshold
```python
ENTROPY_PACKED_THRESHOLD = 7.0  # sections above this are flagged as packed/encrypted
```

### Parallel Processing
```python
MAX_WORKERS = 4  # concurrent processes when scanning large directories
```

### Minimum File Size
```python
MIN_FILE_SIZE = 64  # bytes — files smaller than this are skipped
```

### Local UPX Binary
Place the UPX executable in the `tools/` directory:
```
tools/upx.exe   (Windows)
tools/upx       (Linux/macOS)
```

---

## 📊 Example Output

```
════════════════════════════════════════════════════════════════════════════════
  TRAPIX — FILE ANALYSIS REPORT
════════════════════════════════════════════════════════════════════════════════
  📁 File: samples/suspicious.exe
  📅 Analysis Time: 2026-05-08 20:54:23

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

## 🔌 Extending Trapix

### Add a new packer signature
```python
# config.py
KNOWN_PACKER_SECTIONS = [
    ...
    ".mynewpacker",
]

# core/packer_detector.py
PACKER_SECTION_MAP = {
    ...
    ".mynewpacker": "MyNewPacker",
}
```

### Add a suspicious API
```python
# config.py
SUSPICIOUS_APIS = {
    ...
    "MyDangerousAPI": "Description of why it's dangerous",
}
```

### Add an IOC extraction pattern
```python
# config.py
STRING_PATTERNS = {
    ...
    "Bitcoin Wallets": r'\b[13][a-km-zA-HJ-NP-Z1-9]{25,34}\b',
}
```

---

## ⚠️ Disclaimer

Trapix is intended **for security research and digital forensics only.**
Do not use it to analyze files you do not have permission to examine.
The user is fully responsible for how this tool is used.

---

## 📄 License

MIT License — Free for use in research and professional environments.
