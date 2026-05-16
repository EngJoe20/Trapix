import sys
import os

# Add Tools to path
TOOLS_PATH = os.path.normpath(os.path.join(os.path.dirname(__file__), '..', '..', '..', 'Tools'))
sys.path.append(TOOLS_PATH)

from core.report_generator import ReportGenerator

rg = ReportGenerator()
data = {
    'file_name': 'test_file.exe',
    'hashes': {'sha256': '1234567890abcdef'},
    'file_size': '1.2 MB',
    'file_type': 'Win32 EXE',
    'virustotal': {'queried': True, 'malicious': 0, 'detection_ratio': '0/70'},
    'packer': {'is_packed': False},
    'entropy': {'file_entropy': 4.5},
    'pe_info': {'is_pe': True, 'machine_type': 'x64', 'subsystem': 'GUI', 'section_count': 3, 'imphash': 'abc'},
    'suspicious_apis': {'CreateRemoteThread': 'Process Injection'},
    'iocs': {'IPs': ['1.1.1.1']}
}

try:
    path = rg.generate_pdf(data, 'test_report.pdf')
    print(f"Success: {path}")
except Exception as e:
    import traceback
    print(f"Error: {e}")
    traceback.print_exc()
