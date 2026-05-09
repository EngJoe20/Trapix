# =============================================================================
# core/file_analyzer.py — Main orchestrator for file triage process
# =============================================================================
# This class is the heart of the system — coordinates calling all components in the correct order
# and applies branching logic based on results of each stage:
#
# 1. Hashing
# 2. Query VirusTotal
# 3. Packer Detection
# 4. UPX handling (if needed)
# 5. Deep static analysis
# 6. Report generation

import os
import magic  # python-magic to determine file type
from pathlib import Path
from datetime import datetime
from dataclasses import dataclass, field
from concurrent.futures import ProcessPoolExecutor, as_completed

from config import MIN_FILE_SIZE, MAX_WORKERS, LOGS_DIR
from utils.hashing import get_all_hashes, file_size_human
from utils.logger import get_logger
from core.packer_detector import PackerDetector
from core.static_analyzer import StaticAnalyzer, categorize_risk
from core.vt_client import VirusTotalClient
from core.upx_handler import UPXHandler
from core.report_generator import ReportGenerator

logger = get_logger(__name__, log_dir=LOGS_DIR)


def _get_file_type(filepath: str | Path) -> str:
    """
    Determine file type using magic bytes.
    More reliable than relying on extension alone — malware changes it.
    """
    try:
        return magic.from_file(str(filepath), mime=False)
    except Exception:
        try:
            # fallback: manually read first 4 bytes
            with open(filepath, "rb") as f:
                header = f.read(4)
            if header[:2] == b"MZ":
                return "PE executable (MZ)"
            return "Unknown"
        except Exception:
            return "Unreadable"


class FileAnalyzer:
    """
    Main orchestrator for malware triage.

    Manages the complete analysis lifecycle for a single file or group of files.
    Applies branching logic:
    - Packed + UPX → Unpack → Re-analyze
    - Packed without UPX → Warning + Limited analysis
    - Not packed → Full static analysis
    """

    def __init__(
        self,
        vt_api_key: str | None = None,
        export_json: bool = True,
        skip_vt: bool = False,
    ):
        """
        Parameters:
            vt_api_key  : API key for VirusTotal (uses config.py if not specified)
            export_json : Whether to export JSON report automatically?
            skip_vt     : Skip VT query (for testing without internet)
        """
        self.packer_detector  = PackerDetector()
        self.static_analyzer  = StaticAnalyzer()
        self.upx_handler      = UPXHandler()
        self.report_generator = ReportGenerator()
        self.vt_client        = VirusTotalClient(api_key=vt_api_key) if vt_api_key else VirusTotalClient()
        self.export_json      = export_json
        self.skip_vt          = skip_vt

    def analyze_file(self, filepath: str | Path) -> dict:
        """
        Run full triage on a single file.

        Workflow:
        ┌─────────────────────────────────────────────────┐
        │ 1. File verification (existence, size, permissions) │
        │ 2. Calculate hashes (SHA-256, SHA-1, MD5)          │
        │ 3. Query VirusTotal                                │
        │ 4. Packer detection (Signatures + Entropy)         │
        │ 5. If UPX → Attempt unpacking                      │
        │ 6. Deep static analysis (IAT + Strings)            │
        │ 7. Generate and display report                     │
        └─────────────────────────────────────────────────┘

        Parameters:
            filepath : Path of the file to analyze

        Returns:
            Comprehensive dictionary with all analysis results
        """
        filepath = Path(filepath)
        logger.info(f"\n{'='*60}")
        logger.info(f"🚀 Starting file triage: {filepath.name}")
        logger.info(f"{'='*60}")

        # ─── Main data structure ────────────────────────────────────────────
        analysis = {
            "file_path":     str(filepath.absolute()),
            "file_name":     filepath.name,
            "analysis_time": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "hashes":        {},
            "file_size":     "N/A",
            "file_type":     "Unknown",
            "virustotal":    {},
            "packer":        {},
            "upx":           {},
            "entropy":       {},
            "pe_info":       {},
            "suspicious_apis": {},
            "iocs":          {},
            "strings":       {},
            "risk_score_for_IAT": 0,
            "risk_level_for_IAT": "Unknown",
            "risk_level":    "Unknown",
            "report_path":   "",
        }

        # ─── Stage 0: File verification ──────────────────────────────────────
        if not self._validate_file(filepath, analysis):
            return analysis

        # ─── Stage 1: Calculate hashes ────────────────────────────────────────
        logger.info("🔑 [1/5] Calculating hashes...")
        try:
            analysis["hashes"]    = get_all_hashes(filepath)
            analysis["file_size"] = file_size_human(filepath)
            analysis["file_type"] = _get_file_type(filepath)
            logger.info(f"  SHA-256: {analysis['hashes']['sha256']}")
        except Exception as e:
            logger.error(f"❌ Failed to calculate hashes: {e}")

        # ─── Stage 2: Query VirusTotal ───────────────────────────────
        if not self.skip_vt and analysis["hashes"].get("sha256"):
            logger.info("🌐 [2/5] Querying VirusTotal...")
            vt_result = self.vt_client.query_hash(analysis["hashes"]["sha256"])
            analysis["virustotal"] = {
                "queried":         vt_result.queried,
                "found":           vt_result.found,
                "malicious":       vt_result.malicious,
                "suspicious":      vt_result.suspicious,
                "total_engines":   vt_result.total_engines,
                "detection_ratio": vt_result.detection_ratio,
                "threat_label":    vt_result.threat_label,
                "permalink":       vt_result.permalink,
                "error":           vt_result.error,
            }
        else:
            logger.info("⏭️  [2/5] Skipping VirusTotal")

        # ─── Stage 3: Packer detection ──────────────────────────────────────────
        logger.info("🔍 [3/5] Packer and entropy detection...")
        packer_result = self.packer_detector.detect(filepath)

        analysis["packer"] = {
            "is_packed":          packer_result.is_packed,
            "packer_name":        packer_result.packer_name,
            "detected_signatures": packer_result.detected_signatures,
            "detection_methods":  packer_result.detection_methods,
            "confidence":         packer_result.confidence,
            "warnings":           packer_result.warnings,
        }

        # Save entropy results in the dedicated section
        if packer_result.entropy_result:
            er = packer_result.entropy_result
            analysis["entropy"] = {
                "file_entropy":         er.file_entropy,
                "section_entropies":    er.section_entropies,
                "high_entropy_sections": er.high_entropy_sections,
                "max_section_entropy":  er.max_section_entropy,
                "overall_suspicious":   er.overall_suspicious,
            }

        # ─── Stage 4: UPX handling (if file is packed with UPX) ────────────
        analysis_target = filepath  # May change if unpacking succeeds

        if packer_result.is_packed:
            logger.warning(
                "⚠️  Warning: Static analysis may be unreliable due to packing/encryption"
            )

            # We try unpacking only if the packer is UPX
            if packer_result.packer_name == "UPX":
                logger.info("📦 [4/5] Attempting UPX unpacking...")
                upx_result = self.upx_handler.unpack(filepath)

                analysis["upx"] = {
                    "success":       upx_result.success,
                    "upx_available": upx_result.upx_available,
                    "upx_path":      upx_result.upx_path,
                    "unpacked_path": upx_result.unpacked_path,
                    "error_message": upx_result.error_message,
                    "skipped":       upx_result.skipped,
                }

                if upx_result.success:
                    logger.info("✅ Unpacking succeeded — recalculating hashes...")
                    

                    # Recalculate hashes for the unpacked file
                    try:
                        new_hashes = get_all_hashes(analysis_target)
                        analysis["hashes"]["sha256_unpacked"] = new_hashes["sha256"]
                        analysis["hashes"]["sha1_unpacked"]   = new_hashes["sha1"]
                        analysis["hashes"]["md5_unpacked"]    = new_hashes["md5"]

                        # Re-query VT for the unpacked file
                        if not self.skip_vt:
                            logger.info("🌐 Re-querying VT for unpacked file...")
                            vt2 = self.vt_client.query_hash(new_hashes["sha256"])
                            analysis["virustotal"]["unpacked"] = {
                                "detection_ratio": vt2.detection_ratio,
                                "malicious":       vt2.malicious,
                                "threat_label":    vt2.threat_label,
                            }
                    except Exception as e:
                        logger.error(f"Failed to recalculate hashes: {e}")

                elif upx_result.skipped:
                    logger.warning(f"⚠️  {upx_result.error_message}")
            else:
                logger.info(f"⏭️  [4/5] Packer '{packer_result.packer_name}' does not support automatic unpacking")
        else:
            logger.info("⏭️  [4/5] File not packed, skipping UPX stage")

        # ─── Stage 5: Deep static analysis ───────────────────────────────
        logger.info("🔬 [5/5] Deep static analysis (IAT + Strings + IOCs)...")
        static_result = self.static_analyzer.analyze(analysis_target)

        analysis["pe_info"] = {
            "is_pe":             static_result.is_pe,
            "machine_type":      static_result.machine_type,
            "timestamp":         static_result.timestamp,
            "entry_point":       static_result.entry_point,
            "subsystem":         static_result.subsystem,
            "is_dll":            static_result.is_dll,
            "imphash":           static_result.imphash,
            "section_count":     len(static_result.sections),
            "import_dll_count":  len(static_result.imports),
            "import_func_count": sum(len(v) for v in static_result.imports.values()),
            "imports":           {dll: [vars(f) for f in funcs] for dll, funcs in static_result.imports.items()},
            "exports":           [vars(e) for e in static_result.exports],
            "sections":          static_result.sections,
        }

        analysis["suspicious_apis"] = static_result.suspicious_apis
        analysis["iocs"]            = static_result.iocs

         # 🔥 FIX: safe string extraction (NO crashes even if missing fields)
        analysis["strings"] = {
            "total": getattr(static_result, "total_strings", 0),
            "ascii": getattr(static_result, "ascii_strings", []),
            "unicode": getattr(static_result, "unicode_strings", []),
        }

        analysis["risk_score_for_IAT"] = getattr(static_result, "risk_score", 0)
        analysis["risk_level_for_IAT"] = getattr(static_result, "risk_level_for_IAT", "Unknown")
        
        analysis["risk_level"] = categorize_risk(static_result, 
                                                 self.vt_client.query_hash(get_all_hashes(analysis_target)["sha256"]), 
                                                 self.packer_detector.detect(filepath)
                                                )

        # Clean up temporary UPX files
        if analysis["upx"].get("success") and analysis["upx"].get("unpacked_path"):
            self.upx_handler.cleanup_temp(analysis["upx"]["unpacked_path"], filepath)

        # ─── Generate report ────────────────────────────────────────────────────
        self.report_generator.print_report(analysis)

        if self.export_json:
            report_path = self.report_generator.export_json(analysis)
            analysis["report_path"] = report_path

        return analysis

    def analyze_directory(self, dirpath: str | Path, recursive: bool = True) -> list[dict]:
        """
        Scan entire folder in parallel using multiprocessing.

        Parameters:
            dirpath   : Path of folder to scan
            recursive : Also search in subdirectories

        Returns:
            List of analysis results for all files
        """
        dirpath = Path(dirpath)
        if not dirpath.is_dir():
            logger.error(f"❌ Path is not a directory: {dirpath}")
            return []

        # Collect files for analysis
        pattern = "**/*" if recursive else "*"
        files   = [
            f for f in dirpath.glob(pattern)
            if f.is_file() and f.stat().st_size >= MIN_FILE_SIZE
        ]

        logger.info(f"📂 Directory: {dirpath} | {len(files)} files to analyze")

        results = []

        # Use ThreadPoolExecutor instead of ProcessPoolExecutor
        # Because pefile and magic do not support pickle required for processes
        from concurrent.futures import ThreadPoolExecutor
        with ThreadPoolExecutor(max_workers=MAX_WORKERS) as executor:
            future_to_file = {
                executor.submit(self.analyze_file, f): f
                for f in files
            }

            for future in as_completed(future_to_file):
                filepath = future_to_file[future]
                try:
                    result = future.result()
                    results.append(result)
                except Exception as e:
                    logger.error(f"❌ Error analyzing {filepath.name}: {e}")

        logger.info(f"✅ Completed analysis {len(results)}/{len(files)} files")
        return results

    def _validate_file(self, filepath: Path, analysis: dict) -> bool:
        """
        Verify that the file exists, is readable, and has appropriate size.

        Returns:
            True if file is valid for analysis
        """
        if not filepath.exists():
            msg = f"File does not exist: {filepath}"
            logger.error(f"❌ {msg}")
            analysis["error"] = msg
            return False

        if not filepath.is_file():
            msg = f"Path is not a file: {filepath}"
            logger.error(f"❌ {msg}")
            analysis["error"] = msg
            return False

        if not os.access(filepath, os.R_OK):
            msg = f"No read permission: {filepath}"
            logger.error(f"❌ {msg}")
            analysis["error"] = msg
            return False

        file_size = filepath.stat().st_size
        if file_size < MIN_FILE_SIZE:
            msg = f"File is too small ({file_size} bytes) — skip"
            logger.warning(f"⚠️  {msg}")
            analysis["error"] = msg
            return False

        return True
