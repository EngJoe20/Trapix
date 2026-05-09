# =============================================================================
# core/report_generator.py — Professional report generation
# =============================================================================
# Produces organized and detailed reports for each analyzed file
# Supports colored terminal display and JSON export for automation

import os
import json
from datetime import datetime
from pathlib import Path
from colorama import Fore, Back, Style, init
from tabulate import tabulate
from config import REPORTS_DIR
from utils.logger import get_logger

init(autoreset=True)
logger = get_logger(__name__)


class ReportGenerator:
    """
    Professional report generator for malware triage results.

    Supports:
    - Terminal display with colors and professional formatting
    - JSON export for automation and integration with other systems
    - Plain text export for archive saving
    """

    SEPARATOR = "═" * 80
    THIN_SEP  = "─" * 80

    def __init__(self):
        os.makedirs(REPORTS_DIR, exist_ok=True)

    # ─────────────────────────────────────────────────────────────────────────
    # Terminal display
    # ─────────────────────────────────────────────────────────────────────────

    def print_report(self, analysis_data: dict) -> None:
        """
        Print detailed and colored report in terminal.

        Parameters:
            analysis_data : Comprehensive dictionary of all analysis results
        """
        print()
        self._print_header(analysis_data)
        self._print_identification(analysis_data)
        self._print_vt_results(analysis_data)
        self._print_packer_results(analysis_data)
        self._print_entropy_results(analysis_data)
        self._print_pe_info(analysis_data)
        self._print_imports(analysis_data)
        self._print_exports(analysis_data)
        self._print_suspicious_apis(analysis_data)
        self._print_iocs(analysis_data)
        self._print_footer(analysis_data)
        print()

    def _print_header(self, d: dict) -> None:
        """Print report header with file name."""
        print(f"\n{Fore.CYAN}{self.SEPARATOR}{Style.RESET_ALL}")
        print(
            f"{Fore.WHITE}{Back.BLUE}{'  MALWARE TRIAGE FRAMEWORK — FILE ANALYSIS REPORT  ':^80}{Style.RESET_ALL}"
        )
        print(f"{Fore.CYAN}{self.SEPARATOR}{Style.RESET_ALL}")
        print(f"  {Fore.YELLOW}📁 File:{Style.RESET_ALL} {d.get('file_path', 'N/A')}")
        print(f"  {Fore.YELLOW}📅 Analysis Time:{Style.RESET_ALL} {d.get('analysis_time', 'N/A')}")
        print(f"{Fore.CYAN}{self.THIN_SEP}{Style.RESET_ALL}")

    def _print_identification(self, d: dict) -> None:
        """Identification section: hashes and file size."""
        print(f"\n{Fore.BLUE}{'[ IDENTIFICATION ]':^80}{Style.RESET_ALL}")
        hashes = d.get("hashes", {})
        rows = [
            ["SHA-256",   hashes.get("sha256", "N/A")],
            ["SHA-1",     hashes.get("sha1",   "N/A")],
            ["MD5",       hashes.get("md5",    "N/A")],
            ["File Size", d.get("file_size", "N/A")],
            ["File Type", d.get("file_type", "Unknown")],
        ]
        print(tabulate(rows, tablefmt="rounded_outline"))

    def _print_vt_results(self, d: dict) -> None:
        """VirusTotal results section."""
        print(f"\n{Fore.BLUE}{'[ VIRUSTOTAL RESULTS ]':^80}{Style.RESET_ALL}")
        vt = d.get("virustotal", {})

        if not vt.get("queried"):
            error = vt.get("error", "Not queried")
            print(f"  {Fore.YELLOW}⚠️  {error}{Style.RESET_ALL}")
            return

        if not vt.get("found"):
            print(f"  {Fore.CYAN}ℹ️  File not found in VirusTotal database{Style.RESET_ALL}")
            return

        malicious = vt.get("malicious", 0)
        total     = vt.get("total_engines", 0)
        ratio     = vt.get("detection_ratio", "0/0")
        label     = vt.get("threat_label", "")
        link      = vt.get("permalink", "")

        # Determine result color based on detection ratio
        if malicious == 0:
            color = Fore.GREEN
            icon  = "✅"
            verdict = "CLEAN"
        elif malicious < 5:
            color = Fore.YELLOW
            icon  = "⚠️ "
            verdict = "SUSPICIOUS"
        else:
            color = Fore.RED
            icon  = "🔴"
            verdict = "MALICIOUS"

        print(f"  {icon} {color}Result: {verdict}{Style.RESET_ALL}")

        rows = [
            ["Detection Ratio",    f"{color}{ratio}{Style.RESET_ALL}"],
            ["Malicious",          f"{Fore.RED}{malicious}{Style.RESET_ALL}"],
            ["Suspicious",         f"{Fore.YELLOW}{vt.get('suspicious', 0)}{Style.RESET_ALL}"],
            ["Total Engines", total],
            ["Classification",       label or "N/A"],
            ["Report Link",  link or "N/A"],
        ]
        print(tabulate(rows, tablefmt="rounded_outline"))

    def _print_packer_results(self, d: dict) -> None:
        """Packer detection results section."""
        print(f"\n{Fore.BLUE}{'[ PACKER DETECTION ]':^80}{Style.RESET_ALL}")
        packer = d.get("packer", {})

        is_packed = packer.get("is_packed", False)
        if is_packed:
            print(
                f"  {Fore.RED}🔴 Warning: File is packed / encrypted!\n"
                f"  Static analysis may be unreliable due to packing/encryption.{Style.RESET_ALL}"
            )
        else:
            print(f"  {Fore.GREEN}✅ No obvious packing detected{Style.RESET_ALL}")

        rows = [
            ["Packed",           f"{Fore.RED}Yes{Style.RESET_ALL}" if is_packed else f"{Fore.GREEN}No{Style.RESET_ALL}"],
            ["Packer Tool",     packer.get("packer_name", "N/A")],
            ["Confidence Level",      packer.get("confidence",  "N/A")],
            ["Detection Methods",        ", ".join(packer.get("detection_methods", [])) or "N/A"],
            ["Detected Signatures", ", ".join(packer.get("detected_signatures", [])) or "None"],
        ]
        print(tabulate(rows, tablefmt="rounded_outline"))

        # Display warnings
        for warning in packer.get("warnings", []):
            print(f"  {Fore.YELLOW}  ⚠️  {warning}{Style.RESET_ALL}")

        # UPX results
        upx = d.get("upx", {})
        if upx:
            print(f"\n  {Fore.CYAN}[ UPX Handler ]{Style.RESET_ALL}")
            if upx.get("skipped"):
                print(f"  {Fore.YELLOW}⚠️  {upx.get('error_message', 'UPX not available')}{Style.RESET_ALL}")
            elif upx.get("success"):
                print(f"  {Fore.GREEN}✅ Unpacking successful{Style.RESET_ALL}")
            else:
                print(f"  {Fore.RED}❌ Unpacking failed: {upx.get('error_message', '')}{Style.RESET_ALL}")

    def _print_entropy_results(self, d: dict) -> None:
        """Entropy analysis results section."""
        print(f"\n{Fore.BLUE}{'[ ENTROPY ANALYSIS ]':^80}{Style.RESET_ALL}")
        print(f"  {Fore.CYAN}Note: Entropy is a heuristic indicator, not definitive proof{Style.RESET_ALL}")

        entropy = d.get("entropy", {})
        file_entropy = entropy.get("file_entropy", 0.0)

        # Visual bar for entropy
        bar = self._entropy_bar(file_entropy)
        print(f"\n  Overall file entropy: {bar}")

        # Table of section entropies
        sections = entropy.get("section_entropies", {})
        if sections:
            rows = []
            for name, val in sections.items():
                color  = Fore.RED if val > 7.0 else (Fore.YELLOW if val > 6.0 else Fore.GREEN)
                flag   = " ⚠️" if val > 7.0 else ""
                rows.append([name, f"{color}{val:.4f}{flag}{Style.RESET_ALL}"])

            print(tabulate(rows, headers=["Section", "Entropy"], tablefmt="rounded_outline"))

    def _print_pe_info(self, d: dict) -> None:
        """PE information section."""
        pe_info = d.get("pe_info", {})
        if not pe_info.get("is_pe"):
            return

        print(f"\n{Fore.BLUE}{'[ PE FILE INFORMATION ]':^80}{Style.RESET_ALL}")
        rows = [
            ["Processor Type",   pe_info.get("machine_type", "N/A")],
            ["Compilation Date", pe_info.get("timestamp",    "N/A")],
            ["Entry Point",   pe_info.get("entry_point",  "N/A")],
            ["Application Type",   pe_info.get("subsystem",    "N/A")],
            ["Is DLL?",       "Yes" if pe_info.get("is_dll") else "No"],
            ["Imphash",       pe_info.get("imphash",      "N/A")],
            ["Number of Sections",   pe_info.get("section_count", 0)],
            ["Number of Imported DLLs", pe_info.get("import_dll_count", 0)],
            ["Number of Imported Functions", pe_info.get("import_func_count", 0)],
        ]
        print(tabulate(rows, tablefmt="rounded_outline"))

    def _print_imports(self, d: dict) -> None:
        """Detailed imports section."""
        pe_info = d.get("pe_info", {})
        imports = pe_info.get("imports", {})

        print(f"\n{Fore.BLUE}{'[ IMPORTS (PE Studio Style) ]':^80}{Style.RESET_ALL}")

        if not imports:
            print(f"  {Fore.YELLOW}No imports{Style.RESET_ALL}")
            return

        for dll, funcs in imports.items():
            print(f"\n  {Fore.CYAN}📦 {dll.upper()}{Style.RESET_ALL}")
            if funcs:
                rows = []
                for f in funcs[:10]:  # First 10 functions only in terminal
                    hint = f.get("hint", "")
                    ordinal = f.get("ordinal", "")
                    name = f.get("name", "")
                    thunk_rva = f.get("thunk_rva", "")
                    bound = "✓" if f.get("bound") else ""
                    rows.append([hint, ordinal, name, thunk_rva, bound])
                print(tabulate(rows, headers=["Hint", "Ordinal", "Function Name", "Thunk RVA", "Bound"], tablefmt="simple"))
                if len(funcs) > 10:
                    print(f"    {Fore.YELLOW}... and {len(funcs) - 10} additional functions (in JSON){Style.RESET_ALL}")
            else:
                print(f"    {Fore.GRAY}No functions{Style.RESET_ALL}")

    def _print_exports(self, d: dict) -> None:
        """Detailed exports section."""
        pe_info = d.get("pe_info", {})
        exports = pe_info.get("exports", [])

        print(f"\n{Fore.BLUE}{'[ EXPORTS (PE Studio Style) ]':^80}{Style.RESET_ALL}")

        if not exports:
            print(f"  {Fore.YELLOW}No exports{Style.RESET_ALL}")
            return

        rows = []
        for e in exports[:20]:  # First 20 only in terminal
            ordinal = e.get("ordinal", "")
            name = e.get("name", "")
            rva = e.get("rva", "")
            forwarded = e.get("forwarded_to", "")
            rows.append([ordinal, name, rva, forwarded])
        print(tabulate(rows, headers=["Ordinal", "Function Name", "RVA", "Forwarded To"], tablefmt="simple"))
        if len(exports) > 20:
            print(f"    {Fore.YELLOW}... and {len(exports) - 20} additional exports (in JSON){Style.RESET_ALL}")

    def _print_suspicious_apis(self, d: dict) -> None:
        """Suspicious APIs section — most important section in analysis."""
        sus_apis = d.get("suspicious_apis", {})

        print(f"\n{Fore.BLUE}{'[ SUSPICIOUS API CALLS ]':^80}{Style.RESET_ALL}")

        if not sus_apis:
            print(f"  {Fore.GREEN}✅ No suspicious API calls detected{Style.RESET_ALL}")
            return

        print(f"  {Fore.RED}🔴 {len(sus_apis)} suspicious API calls detected!{Style.RESET_ALL}\n")
        rows = [
            [f"{Fore.RED}{api}{Style.RESET_ALL}", reason]
            for api, reason in sus_apis.items()
        ]
        print(tabulate(rows, headers=["API", "Reason / Risk"], tablefmt="rounded_outline"))

    def _print_iocs(self, d: dict) -> None:
        """Extracted indicators of compromise section."""
        iocs = d.get("iocs", {})

        print(f"\n{Fore.BLUE}{'[ INDICATORS OF COMPROMISE (IOCs) ]':^80}{Style.RESET_ALL}")

        if not iocs:
            print(f"  {Fore.GREEN}No valuable IOCs extracted{Style.RESET_ALL}")
            return

        for category, items in iocs.items():
            if not items:
                continue
            print(f"\n  {Fore.CYAN}▶ {category} ({len(items)} item):{Style.RESET_ALL}")
            # Display first 10 items only in terminal to avoid overflow
            for item in items[:10]:
                print(f"    {Fore.WHITE}• {item}{Style.RESET_ALL}")
            if len(items) > 10:
                print(f"    {Fore.YELLOW}  ... and {len(items) - 10} additional items (in JSON report){Style.RESET_ALL}")

    def _print_footer(self, d: dict) -> None:
        """Report footer with summary and risk level."""
        print(f"\n{Fore.CYAN}{self.THIN_SEP}{Style.RESET_ALL}")

        # Calculate overall risk level
        risk = self._calculate_risk_level(d)
        risk_colors = {
            "CRITICAL": Fore.RED + Style.BRIGHT,
            "HIGH":     Fore.RED,
            "MEDIUM":   Fore.YELLOW,
            "LOW":      Fore.GREEN,
            "CLEAN":    Fore.GREEN,
        }
        color = risk_colors.get(risk, Fore.WHITE)

        print(f"  {Fore.WHITE}Overall Risk Level: {color}{'[ ' + risk + ' ]':^20}{Style.RESET_ALL}")

        report_path = d.get("report_path", "")
        if report_path:
            print(f"  {Fore.CYAN}📄 Report saved to: {report_path}{Style.RESET_ALL}")

        print(f"{Fore.CYAN}{self.SEPARATOR}{Style.RESET_ALL}\n")

    # ─────────────────────────────────────────────────────────────────────────
    # JSON export
    # ─────────────────────────────────────────────────────────────────────────

    def export_json(self, analysis_data: dict, output_path: str | None = None) -> str:
        """
        Export analysis results to JSON file.

        JSON is useful for automation and integration with SIEM and incident management systems.

        Parameters:
            analysis_data : complete analysis data
            output_path   : save path (optional — generated automatically if not specified)

        Returns:
            path of saved JSON file
        """
        if not output_path:
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            safe_name = Path(analysis_data.get("file_path", "unknown")).stem
            safe_name = "".join(c for c in safe_name if c.isalnum() or c in "-_")
            filename  = f"report_{safe_name}_{timestamp}.json"
            output_path = os.path.join(REPORTS_DIR, filename)

        os.makedirs(os.path.dirname(output_path), exist_ok=True)

        # Clean data from non-serializable objects
        clean_data = self._sanitize_for_json(analysis_data)

        with open(output_path, "w", encoding="utf-8") as f:
            json.dump(clean_data, f, ensure_ascii=False, indent=2)

        logger.info(f"📄 Report exported: {output_path}")
        return output_path

    def _sanitize_for_json(self, data) -> dict:
        """Clean data to ensure JSON serializability."""
        if isinstance(data, dict):
            return {k: self._sanitize_for_json(v) for k, v in data.items()}
        elif isinstance(data, list):
            return [self._sanitize_for_json(i) for i in data]
        elif isinstance(data, (str, int, float, bool)) or data is None:
            return data
        else:
            return str(data)

    # ─────────────────────────────────────────────────────────────────────────
    # Helper Functions
    # ─────────────────────────────────────────────────────────────────────────

    def _safe_str(self, s: any) -> str:
        """Sanitize string for FPDF (Core fonts only support Latin-1)."""
        if s is None: return "N/A"
        s = str(s)
        # Replace non-Latin-1 characters with a placeholder
        return s.encode('latin-1', 'replace').decode('latin-1')

    def _entropy_bar(self, entropy: float, width: int = 30) -> str:
        """Draw visual bar for entropy value."""
        filled = int((entropy / 8.0) * width)
        color  = Fore.RED if entropy > 7.0 else (Fore.YELLOW if entropy > 6.0 else Fore.GREEN)
        bar    = f"{color}{'█' * filled}{'░' * (width - filled)}{Style.RESET_ALL}"
        return f"[{bar}] {entropy:.4f} / 8.0"

    def _calculate_risk_level(self, d: dict) -> str:
        """
        Calculate overall risk level based on all analysis results.
        """
        vt       = d.get("virustotal", {})
        packer   = d.get("packer", {})
        sus_apis = d.get("suspicious_apis", {})
        iocs     = d.get("iocs", {})

        malicious  = vt.get("malicious", 0)
        is_packed  = packer.get("is_packed", False)
        api_count  = len(sus_apis)
        ioc_count  = sum(len(v) for v in iocs.values())

        if malicious >= 10 and api_count >= 3:
            return "CRITICAL"
        elif malicious >= 5 or api_count >= 5:
            return "HIGH"
        elif malicious > 0 or (is_packed and ioc_count > 5):
            return "MEDIUM"
        elif is_packed or api_count > 0 or ioc_count > 0:
            return "LOW"
        else:
            return "CLEAN"

    # ─────────────────────────────────────────────────────────────────────────
    # PDF generation
    # ─────────────────────────────────────────────────────────────────────────

    def generate_pdf(self, analysis_data: dict, output_path: str) -> str:
        """
        Generate a professional PDF report.
        """
        try:
            from fpdf import FPDF
        except ImportError:
            logger.warning("fpdf2 not installed. Skipping PDF generation.")
            return ""

        class TrapixPDF(FPDF):
            def header(self):
                self.set_fill_color(3, 7, 18) # Dark background
                self.rect(0, 0, 210, 30, 'F')
                self.set_font('helvetica', 'B', 20)
                self.set_text_color(34, 211, 238) # Cyan
                self.cell(0, 20, ' TRAPIX SECURITY REPORT', 0, 1, 'L')
                self.ln(5)

            def footer(self):
                self.set_y(-15)
                self.set_font('helvetica', 'I', 8)
                self.set_text_color(128)
                self.cell(0, 10, f'Page {self.page_no()} | Generated by Trapix Malware Triage Framework', 0, 0, 'C')

        pdf = TrapixPDF()
        pdf.add_page()
        pdf.set_auto_page_break(auto=True, margin=15)
        
        # Identification
        pdf.set_font('helvetica', 'B', 16)
        pdf.set_text_color(34, 211, 238) # Cyan
        pdf.cell(0, 10, '1. Identification', 0, 1, 'L')
        pdf.set_text_color(0)
        pdf.set_font('helvetica', '', 10)
        
        hashes = analysis_data.get('hashes', {})
        pdf.cell(40, 7, 'File Name:', 0, 0)
        pdf.cell(0, 7, self._safe_str(analysis_data.get('file_name', 'N/A')), 0, 1)
        pdf.cell(40, 7, 'SHA-256:', 0, 0)
        pdf.cell(0, 7, self._safe_str(hashes.get('sha256', 'N/A')), 0, 1)
        pdf.cell(40, 7, 'Size:', 0, 0)
        pdf.cell(0, 7, self._safe_str(analysis_data.get('file_size', 'N/A')), 0, 1)
        pdf.cell(40, 7, 'File Type:', 0, 0)
        pdf.cell(0, 7, self._safe_str(analysis_data.get('file_type', 'Unknown')), 0, 1)
        pdf.ln(5)

        # Risk Level
        risk = self._calculate_risk_level(analysis_data)
        pdf.set_font('helvetica', 'B', 14)
        pdf.cell(40, 10, 'Overall Risk:', 0, 0)
        
        if risk in ['CRITICAL', 'HIGH']:
            pdf.set_text_color(220, 38, 38) # Red
        elif risk == 'MEDIUM':
            pdf.set_text_color(217, 119, 6) # Yellow
        else:
            pdf.set_text_color(22, 163, 74) # Green
            
        pdf.cell(0, 10, f'[{risk}]', 0, 1)
        pdf.set_text_color(0)
        pdf.ln(10)

        # VT Results
        pdf.set_font('helvetica', 'B', 16)
        pdf.set_text_color(34, 211, 238) # Cyan
        pdf.cell(0, 10, '2. VirusTotal Analysis', 0, 1, 'L')
        pdf.set_text_color(0)
        pdf.set_font('helvetica', '', 10)
        vt = analysis_data.get('virustotal', {})
        if vt.get('queried'):
            pdf.cell(40, 7, 'Detection Ratio:', 0, 0)
            pdf.cell(0, 7, self._safe_str(vt.get('detection_ratio', '0/0')), 0, 1)
            pdf.cell(40, 7, 'Malicious:', 0, 0)
            pdf.cell(0, 7, self._safe_str(vt.get('malicious', 0)), 0, 1)
            pdf.cell(40, 7, 'Threat Label:', 0, 0)
            pdf.cell(0, 7, self._safe_str(vt.get('threat_label', 'N/A')), 0, 1)
        else:
            pdf.cell(0, 7, 'VirusTotal query skipped or failed.', 0, 1)
        pdf.ln(10)

        # Packer & Entropy
        pdf.set_font('helvetica', 'B', 16)
        pdf.set_text_color(34, 211, 238) # Cyan
        pdf.cell(0, 10, '3. Packer & Entropy Detection', 0, 1, 'L')
        pdf.set_text_color(0)
        pdf.set_font('helvetica', '', 10)
        packer = analysis_data.get('packer', {})
        pdf.cell(40, 7, 'Is Packed:', 0, 0)
        pdf.cell(0, 7, 'YES' if packer.get('is_packed') else 'NO', 0, 1)
        if packer.get('is_packed'):
            pdf.cell(40, 7, 'Packer Name:', 0, 0)
            pdf.cell(0, 7, self._safe_str(packer.get('packer_name', 'Unknown')), 0, 1)
        
        entropy = analysis_data.get('entropy', {})
        pdf.cell(40, 7, 'File Entropy:', 0, 0)
        pdf.cell(0, 7, f"{entropy.get('file_entropy', 0.0):.4f} / 8.0", 0, 1)
        pdf.ln(10)

        # PE Info
        pe_info = analysis_data.get('pe_info', {})
        if pe_info.get('is_pe'):
            pdf.set_font('helvetica', 'B', 16)
            pdf.set_text_color(34, 211, 238) # Cyan
            pdf.cell(0, 10, '4. PE Structure Information', 0, 1, 'L')
            pdf.set_text_color(0)
            pdf.set_font('helvetica', '', 10)
            pdf.cell(40, 7, 'Machine:', 0, 0)
            pdf.cell(0, 7, self._safe_str(pe_info.get('machine_type', 'N/A')), 0, 1)
            pdf.cell(40, 7, 'Subsystem:', 0, 0)
            pdf.cell(0, 7, self._safe_str(pe_info.get('subsystem', 'N/A')), 0, 1)
            pdf.cell(40, 7, 'Sections:', 0, 0)
            pdf.cell(0, 7, self._safe_str(pe_info.get('section_count', 0)), 0, 1)
            pdf.cell(40, 7, 'Imphash:', 0, 0)
            pdf.cell(0, 7, self._safe_str(pe_info.get('imphash', 'N/A')), 0, 1)
            pdf.ln(10)

        # Suspicious APIs
        sus_apis = analysis_data.get('suspicious_apis', {})
        if sus_apis:
            pdf.set_font('helvetica', 'B', 16)
            pdf.set_text_color(34, 211, 238) # Cyan
            pdf.cell(0, 10, '5. Suspicious API Calls', 0, 1, 'L')
            pdf.set_text_color(0)
            pdf.set_font('helvetica', '', 9)
            for api, reason in sus_apis.items():
                pdf.multi_cell(0, 6, f'- {self._safe_str(api)}: {self._safe_str(reason)}', 0, 'L')
            pdf.ln(10)

        # IOCs
        iocs = analysis_data.get('iocs', {})
        if any(iocs.values()):
            pdf.set_font('helvetica', 'B', 16)
            pdf.set_text_color(34, 211, 238) # Cyan
            pdf.cell(0, 10, '6. Indicators of Compromise (IOCs)', 0, 1, 'L')
            pdf.set_text_color(0)
            pdf.set_font('helvetica', '', 9)
            for cat, items in iocs.items():
                if items:
                    pdf.set_font('helvetica', 'B', 10)
                    pdf.cell(0, 7, f'[{cat}]', 0, 1)
                    pdf.set_font('helvetica', '', 9)
                    for item in items[:15]: # Limit to 15 per category
                        pdf.cell(0, 5, f'  - {item}', 0, 1)
            pdf.ln(10)

        # Save
        pdf.output(output_path)
        return output_path

    def generate_summary_pdf(self, payload: dict, output_path: str) -> str:
        """
        Generate a professional summary PDF for directory scans (multiple files).
        """
        try:
            from fpdf import FPDF
        except ImportError:
            logger.warning("fpdf2 not installed. Skipping summary PDF generation.")
            return ""

        class TrapixSummaryPDF(FPDF):
            def header(self):
                self.set_fill_color(3, 7, 18) # Dark
                self.rect(0, 0, 210, 30, 'F')
                self.set_font('helvetica', 'B', 20)
                self.set_text_color(34, 211, 238) # Cyan
                self.cell(0, 20, ' TRAPIX BULK ANALYSIS SUMMARY', 0, 1, 'L')
                self.ln(5)

            def footer(self):
                self.set_y(-15)
                self.set_font('helvetica', 'I', 8)
                self.set_text_color(128)
                self.cell(0, 10, f'Page {self.page_no()} | Generated by Trapix Framework', 0, 0, 'C')

        pdf = TrapixSummaryPDF()
        pdf.add_page()
        pdf.set_auto_page_break(auto=True, margin=15)

        summary = payload.get('summary', {})
        results = payload.get('results', [])

        pdf.set_font('helvetica', 'B', 16)
        pdf.cell(0, 10, 'Analysis Overview', 0, 1, 'L')
        pdf.set_font('helvetica', '', 10)
        pdf.cell(40, 7, 'Total Files:', 0, 0)
        pdf.cell(0, 7, str(summary.get('total_files', 0)), 0, 1)
        pdf.cell(40, 7, 'Overall Risk:', 0, 0)
        
        risk = summary.get('risk_level', 'Unknown')
        if risk in ['CRITICAL', 'HIGH']:
            pdf.set_text_color(220, 38, 38)
        else:
            pdf.set_text_color(22, 163, 74)
        pdf.cell(0, 7, f'[{risk}]', 0, 1)
        pdf.set_text_color(0)
        pdf.ln(10)

        # File List Table
        pdf.set_font('helvetica', 'B', 14)
        pdf.cell(0, 10, 'Detailed File Results', 0, 1, 'L')
        pdf.set_font('helvetica', 'B', 9)
        
        # Table Header
        pdf.set_fill_color(230, 230, 230)
        pdf.cell(80, 8, ' File Name', 1, 0, 'L', True)
        pdf.cell(40, 8, ' Risk', 1, 0, 'C', True)
        pdf.cell(60, 8, ' SHA-256 (Partial)', 1, 1, 'L', True)
        
        pdf.set_font('helvetica', '', 8)
        for res in results:
            name = self._safe_str(res.get('file_name', 'N/A'))
            risk = self._safe_str(res.get('risk_level', 'Unknown'))
            sha  = self._safe_str(res.get('hashes', {}).get('sha256', 'N/A'))[:20] + '...'
            
            pdf.cell(80, 7, f' {name}', 1, 0, 'L')
            
            # Risk color
            if risk in ['CRITICAL', 'HIGH']:
                pdf.set_text_color(220, 38, 38)
            elif risk == 'MEDIUM':
                pdf.set_text_color(217, 119, 6)
            else:
                pdf.set_text_color(22, 163, 74)
                
            pdf.cell(40, 7, f' {risk}', 1, 0, 'C')
            pdf.set_text_color(0)
            pdf.cell(60, 7, f' {sha}', 1, 1, 'L')

        pdf.output(output_path)
        return output_path
