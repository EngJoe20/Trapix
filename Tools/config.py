# =============================================================================
# config.py — Central project settings
# =============================================================================
# This file contains all modifiable settings in one place
# To facilitate maintenance and customization without needing to modify the core code

import os

# ─── VirusTotal ───────────────────────────────────────────────────────────────
# VirusTotal API key — preferably set in environment variable for security
VT_API_KEY = os.getenv("VT_API_KEY", "0215678073104a40a583a383fc9617fc3e5552a009c69bc728d510dc33ef5cc0")
VT_API_URL = "https://www.virustotal.com/api/v3/files/{hash}"

# Maximum requests per minute (free version: 4 requests/minute)
VT_RATE_LIMIT_DELAY = 15  # seconds between each request to control rate

# ─── Entropy Thresholds ───────────────────────────────────────────────────────
# Threshold above which we consider the section compressed or encrypted
# Value 7.0 is an accepted standard in the malware research community
ENTROPY_PACKED_THRESHOLD = 7.0

# ─── Packer Signatures ────────────────────────────────────────────────────────
# Names of sections known to be associated with packing and protection tools
KNOWN_PACKER_SECTIONS = [
    ".UPX0", ".UPX1", ".UPX2",   # UPX Packer
    ".aspack",                    # ASPack
    ".adata",                     # ASPack variant
    ".packed",                    # Generic packed marker
    ".themida",                   # Themida/WinLicense
    ".winlicence",                # WinLicense
    ".vmp0", ".vmp1", ".vmp2",   # VMProtect
    ".enigma1", ".enigma2",       # Enigma Protector
    ".nsp0", ".nsp1", ".nsp2",   # NsPack
    "pec1", "pec2",               # PECompact
    ".ccg",                       # CCG
    "!EPack",                     # EPack
]

# ─── Suspicious API Calls ─────────────────────────────────────────────────────
# Windows API functions associated with injection and malicious code execution techniques
SUSPICIOUS_APIS = {
    # Code injection into other processes (Process Injection)
    "VirtualAllocEx":       "Process Injection / Remote Memory Allocation",
    "VirtualAlloc":         "Memory Allocation (possible shellcode)",
    "CreateRemoteThread":   "Remote Thread Injection",
    "WriteProcessMemory":   "Process Memory Manipulation",
    "ReadProcessMemory":    "Process Memory Reading",
    "NtWriteVirtualMemory": "Native API Memory Write (evasion)",
    "NtCreateThreadEx":     "Native API Thread Creation (evasion)",

    # Dynamic library loading (Dynamic Loading)
    "LoadLibraryA":         "Dynamic Library Loading",
    "LoadLibraryW":         "Dynamic Library Loading (Unicode)",
    "LoadLibraryExA":       "Extended Dynamic Library Loading",
    "GetProcAddress":       "Dynamic Function Resolution",
    "LdrLoadDll":           "Native DLL Loader",

    # Network communication (Network Communication)
    "InternetOpenA":        "Internet Connection Initiation",
    "InternetOpenW":        "Internet Connection Initiation (Unicode)",
    "InternetOpenUrlA":     "URL Connection",
    "HttpSendRequestA":     "HTTP Request Sending",
    "WSAStartup":           "Winsock Initialization",
    "socket":               "Raw Socket Creation",
    "connect":              "Network Connection",
    "send":                 "Data Transmission",
    "recv":                 "Data Reception",

    # Process control (Process Control)
    "OpenProcess":          "Remote Process Handle",
    "CreateProcess":        "Process Creation",
    "CreateProcessA":       "Process Creation (ANSI)",
    "CreateProcessW":       "Process Creation (Unicode)",
    "TerminateProcess":     "Process Termination",
    "ShellExecuteA":        "Shell Execution",
    "ShellExecuteW":        "Shell Execution (Unicode)",
    "WinExec":              "Legacy Process Execution",

    # Registry and system (Registry & System)
    "RegSetValueExA":       "Registry Value Modification (persistence?)",
    "RegSetValueExW":        "Registry Value Modification (Unicode)",
    "RegCreateKeyA":        "Registry Key Creation",
    "RegCreateKeyW":        "Registry Key Creation (Unicode)",
    "RegOpenKeyExA":        "Registry Key Access",

    # Encryption and data hiding (Crypto & Obfuscation)
    "CryptEncrypt":         "Data Encryption",
    "CryptDecrypt":         "Data Decryption",
    "CryptCreateHash":      "Hash Creation",
    "CryptAcquireContextA": "Crypto Provider Initialization",

    # Anti-analysis (Anti-Analysis)
    "IsDebuggerPresent":    "Anti-Debugging Check",
    "CheckRemoteDebuggerPresent": "Remote Debugger Detection",
    "NtQueryInformationProcess":  "Process Info Query (anti-debug)",
    "GetTickCount":         "Timing-Based Anti-Debug",
    "QueryPerformanceCounter": "High-Res Timing (anti-sandbox)",
    "Sleep":                "Execution Delay (anti-sandbox)",
    "OutputDebugStringA":   "Debug Output (anti-analysis trigger)",
}

# ─── String Extraction Patterns ───────────────────────────────────────────────
# Regex patterns to extract indicators (IOCs) from file content
STRING_PATTERNS = {
    "URLs":          r'https?://[^\s\'"<>]{8,}',
    "IP Addresses":  r'\b(?:\d{1,3}\.){3}\d{1,3}\b',
    "Registry Keys": r'HKEY_(?:LOCAL_MACHINE|CURRENT_USER|CLASSES_ROOT|USERS|CURRENT_CONFIG)[\\\/\w]+',
    "DLL Files":     r'\b[\w\-]{2,64}\.dll\b',
    "EXE Files":     r'\b[\w\-]{2,64}\.exe\b',
    "File Paths":    r'[A-Za-z]:\\(?:[\w\s\-\.]+\\)*[\w\s\-\.]+',
    "Email":         r'\b[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Z|a-z]{2,}\b',
    "Base64 Blobs":  r'(?:[A-Za-z0-9+/]{4}){10,}(?:[A-Za-z0-9+/]{2}==|[A-Za-z0-9+/]{3}=)?',
}

# ─── Output Paths ─────────────────────────────────────────────────────────────
BASE_DIR    = os.path.dirname(os.path.abspath(__file__))
OUTPUT_DIR  = os.path.join(BASE_DIR, "output")
REPORTS_DIR = os.path.join(OUTPUT_DIR, "reports")
LOGS_DIR    = os.path.join(OUTPUT_DIR, "logs")
TOOLS_DIR   = os.path.join(BASE_DIR, "tools")

# ─── Multiprocessing ──────────────────────────────────────────────────────────
# Number of parallel processes for scanning large folders
MAX_WORKERS = 4

# ─── Minimum file size to analyze (bytes) ────────────────────────────────────
MIN_FILE_SIZE = 64  # Ignore very small files that do not contain valid PE
