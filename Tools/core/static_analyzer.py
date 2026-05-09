# =============================================================================
# core/static_analyzer.py — Deep static analysis for executable files
# =============================================================================
# Static analysis examines the file without running it — safe method that reveals:
# - Import table (IAT): What functions does the program call?
# - Imphash: Fingerprint derived from import order and names — useful for clustering
# - Exports: What symbols does a DLL expose?
# - Strings: IOCs like URLs, IP addresses, registry paths, file paths, etc.

from __future__ import annotations

import datetime
import inspect
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

import pefile

from config import SUSPICIOUS_APIS
from utils.logger import get_logger
from utils.strings import extract_all_strings


logger = get_logger(__name__)


# =============================================================================
# Important APIs Database
# =============================================================================

IMPORTANT_IMPORTS: dict[str, dict[str, str]] = {
    # ───────────────── Injection / Memory ─────────────────
    "virtualalloc": {
        "severity": "HIGH",
        "category": "Memory",
        "reason": "Allocates executable memory",
    },
    "virtualallocex": {
        "severity": "HIGH",
        "category": "Injection",
        "reason": "Allocates memory in remote process",
    },
    "virtualprotect": {
        "severity": "HIGH",
        "category": "Memory",
        "reason": "Changes memory protection",
    },
    "writeprocessmemory": {
        "severity": "CRITICAL",
        "category": "Injection",
        "reason": "Writes shellcode into remote process",
    },
    "readprocessmemory": {
        "severity": "HIGH",
        "category": "Injection",
        "reason": "Reads remote process memory",
    },
    "createremotethread": {
        "severity": "CRITICAL",
        "category": "Injection",
        "reason": "Remote thread injection",
    },
    "ntunmapviewofsection": {
        "severity": "CRITICAL",
        "category": "Process Hollowing",
        "reason": "Used in process hollowing",
    },

    # ───────────────── Dynamic APIs ─────────────────
    "loadlibrarya": {
        "severity": "MEDIUM",
        "category": "Dynamic API",
        "reason": "Loads DLL dynamically",
    },
    "loadlibraryw": {
        "severity": "MEDIUM",
        "category": "Dynamic API",
        "reason": "Loads DLL dynamically",
    },
    "getprocaddress": {
        "severity": "HIGH",
        "category": "Dynamic API",
        "reason": "Dynamic API resolution",
    },

    # ───────────────── Execution ─────────────────
    "winexec": {
        "severity": "HIGH",
        "category": "Execution",
        "reason": "Executes commands or programs",
    },
    "shellexecutea": {
        "severity": "HIGH",
        "category": "Execution",
        "reason": "Executes external program",
    },
    "shellexecutew": {
        "severity": "HIGH",
        "category": "Execution",
        "reason": "Executes external program",
    },
    "createprocessa": {
        "severity": "HIGH",
        "category": "Execution",
        "reason": "Creates new process",
    },
    "createprocessw": {
        "severity": "HIGH",
        "category": "Execution",
        "reason": "Creates new process",
    },

    # ───────────────── Networking ─────────────────
    "internetopena": {
        "severity": "MEDIUM",
        "category": "Networking",
        "reason": "Internet access",
    },
    "internetopenurla": {
        "severity": "HIGH",
        "category": "Networking",
        "reason": "Downloads internet resources",
    },
    "urldownloadtofilea": {
        "severity": "CRITICAL",
        "category": "Downloader",
        "reason": "Downloads payload from internet",
    },
    "wsastartup": {
        "severity": "MEDIUM",
        "category": "Networking",
        "reason": "Initializes Winsock networking",
    },
    "socket": {
        "severity": "MEDIUM",
        "category": "Networking",
        "reason": "Creates socket connection",
    },
    "connect": {
        "severity": "HIGH",
        "category": "Networking",
        "reason": "Connects to remote host",
    },
    "recv": {
        "severity": "MEDIUM",
        "category": "Networking",
        "reason": "Receives network data",
    },
    "send": {
        "severity": "MEDIUM",
        "category": "Networking",
        "reason": "Sends network data",
    },

    # ───────────────── Anti Analysis ─────────────────
    "sleep": {
        "severity": "LOW",
        "category": "Anti Analysis",
        "reason": "Possible sandbox evasion delay",
    },
    "isdebuggerpresent": {
        "severity": "HIGH",
        "category": "Anti Debug",
        "reason": "Debugger detection",
    },
    "checkremotedebuggerpresent": {
        "severity": "HIGH",
        "category": "Anti Debug",
        "reason": "Debugger detection",
    },
    "outputdebugstringa": {
        "severity": "LOW",
        "category": "Anti Debug",
        "reason": "Debugger interaction",
    },

    # ───────────────── Persistence ─────────────────
    "regcreatekeyexa": {
        "severity": "HIGH",
        "category": "Persistence",
        "reason": "Registry persistence creation",
    },
    "regsetvalueexa": {
        "severity": "HIGH",
        "category": "Persistence",
        "reason": "Registry modification",
    },
    "createservicea": {
        "severity": "CRITICAL",
        "category": "Persistence",
        "reason": "Creates Windows service",
    },

    # ───────────────── Crypto ─────────────────
    "cryptencrypt": {
        "severity": "MEDIUM",
        "category": "Crypto",
        "reason": "Encryption routine",
    },
    "cryptdecrypt": {
        "severity": "MEDIUM",
        "category": "Crypto",
        "reason": "Decryption routine",
    },
}


# =============================================================================
# Data Models
# =============================================================================

@dataclass
class ImportFunction:
    """Details of imported function."""
    name: str = ""
    ordinal: int = 0
    thunk_rva: str = ""
    bound: bool = False
    hint: int = 0


@dataclass
class ExportFunction:
    """Details of exported function."""
    name: str = ""
    ordinal: int = 0
    rva: str = ""
    forwarded_to: str = ""


@dataclass
class StaticAnalysisResult:
    """Comprehensive static analysis results for PE file."""

    # ─── Basic PE information ─────────────────────────────────────────────
    is_pe: bool = False
    machine_type: str = ""
    timestamp: str = ""
    entry_point: str = ""
    subsystem: str = ""
    is_dll: bool = False

    # ─── Imports ──────────────────────────────────────────────────────────
    imphash: str = ""
    imports: dict[str, list[ImportFunction]] = field(default_factory=dict)

    # Statistics
    total_imports: int = 0
    total_imported_dlls: int = 0

    # API detections
    suspicious_apis: dict[str, str] = field(default_factory=dict)
    important_imports: dict[str, dict[str, str]] = field(default_factory=dict)

    # ─── Exports ──────────────────────────────────────────────────────────
    exports: list[ExportFunction] = field(default_factory=list)
    total_exports: int = 0

    # ─── Strings and IOCs ────────────────────────────────────────────────
    iocs: dict[str, list[str]] = field(default_factory=dict)
    total_strings: int = 0

        # Optional raw strings data
    ascii_strings: list[str] = field(default_factory=list)
    unicode_strings: list[str] = field(default_factory=list)

    # ─── Sections ────────────────────────────────────────────────────────
    sections: list[dict[str, Any]] = field(default_factory=list)

    # ─── Analysis ────────────────────────────────────────────────────────
    risk_score: int = 0
    risk_level_for_IAT: str = ""  # Clean / Low Risk / Suspicious / High Risk / Malicious

    # ─── Notes and warnings ──────────────────────────────────────────────
    warnings: list[str] = field(default_factory=list)
    errors: list[str] = field(default_factory=list)


# =============================================================================
# Static Analyzer
# =============================================================================

class StaticAnalyzer:
    """
    Deep static analyzer for executable files.

    Uses pefile library to examine:
    - PE Headers and file structure
    - Import table (IAT) and dangerous APIs
    - Export table for DLLs
    - Sections and their properties
    - Strings and IOCs
    """

    MACHINE_TYPES = {
        0x014C: "x86 (32-bit)",
        0x0200: "Intel Itanium",
        0x8664: "x64 (64-bit)",
        0xAA64: "ARM64",
        0x01C4: "ARM Thumb-2",
    }

    SUBSYSTEMS = {
        1: "Native",
        2: "Windows GUI",
        3: "Windows Console (CUI)",
        5: "OS/2 Console",
        7: "POSIX Console",
        9: "Windows CE GUI",
        10: "EFI Application",
        14: "Xbox",
    }

    def __init__(self, min_string_length: int = 4):
        self.min_string_length = min_string_length

    def analyze(self, filepath: str | Path) -> StaticAnalysisResult:
        """
        Run full static analysis on PE file.
        """
        result = StaticAnalysisResult()
        filepath = Path(filepath)

        logger.info(f"🔬 Analyzing: {filepath.name}")

        self._analyze_pe(filepath, result)
        self._extract_strings(filepath, result)
        self._calculate_risk_score(result)

        logger.info(f"✅ Done: {filepath.name}")
        return result

    # =========================================================================

    def _analyze_pe(self, filepath: Path, result: StaticAnalysisResult) -> None:
        """
        Analyze PE headers, sections, imports, and exports.
        """
        pe = None
        try:
            pe = pefile.PE(str(filepath), fast_load=False)
            try:
                pe.parse_data_directories()
            except Exception:
                pass

            result.is_pe = True

            # ───────────────── Headers ─────────────────
            machine = pe.FILE_HEADER.Machine
            result.machine_type = self.MACHINE_TYPES.get(machine, hex(machine))
            result.is_dll = bool(pe.FILE_HEADER.Characteristics & 0x2000)

            try:
                result.timestamp = datetime.datetime.utcfromtimestamp(
                    pe.FILE_HEADER.TimeDateStamp
                ).strftime("%Y-%m-%d %H:%M:%S UTC")
            except Exception:
                result.timestamp = "Invalid"

            result.entry_point = hex(pe.OPTIONAL_HEADER.AddressOfEntryPoint)
            result.subsystem = self.SUBSYSTEMS.get(
                pe.OPTIONAL_HEADER.Subsystem,
                f"Unknown ({pe.OPTIONAL_HEADER.Subsystem})",
            )

            # ───────────────── Sections ─────────────────
            for s in pe.sections:
                try:
                    entropy = float(s.get_entropy())
                except Exception:
                    entropy = None

                name = s.Name.decode(errors="ignore").rstrip("\x00").strip()

                result.sections.append(
                    {
                        "name": name or "<unnamed>",
                        "vaddr": hex(s.VirtualAddress),
                        "vsize": s.Misc_VirtualSize,
                        "rsize": s.SizeOfRawData,
                        "characteristics": hex(s.Characteristics),
                        "entropy": entropy,
                    }
                )

            # ───────────────── Imports ─────────────────
            self._analyze_imports(pe, result)

            # ───────────────── Exports ─────────────────
            self._analyze_exports(pe, result)

        except pefile.PEFormatError:
            result.warnings.append("Not a valid PE file")
            logger.info("File is not a PE — may be script, PDF, or another type")
        except Exception as e:
            result.errors.append(f"Error in PE analysis: {e}")
            logger.error(f"❌ Error in PE analysis: {e}")
        finally:
            if pe is not None:
                try:
                    pe.close()
                except Exception:
                    pass

    # =========================================================================

    def _normalize_api_name(self, name: str) -> str:
        return name.strip().lower()

    def _lookup_suspicious_reason(self, api_name: str) -> str | None:
        """
        Support both lowercase and original-key configs.
        """
        if api_name in SUSPICIOUS_APIS:
            return SUSPICIOUS_APIS[api_name]

        lower = api_name.lower()
        if lower in SUSPICIOUS_APIS:
            return SUSPICIOUS_APIS[lower]

        for key, value in SUSPICIOUS_APIS.items():
            if key.lower() == lower:
                return value

        return None

    def _analyze_imports(self, pe: pefile.PE, result: StaticAnalysisResult) -> None:
        """
        Analyze import table with PE Studio-like detail.
        """
        # ───────────────── Imphash ─────────────────
        try:
            result.imphash = pe.get_imphash()
        except Exception:
            result.imphash = "N/A"

        # ───────────────── Import Table ─────────────────
        imports = getattr(pe, "DIRECTORY_ENTRY_IMPORT", None)

        if not imports:
            result.warnings.append(
                "No imports found (possible packed binary or dynamic API resolution)"
            )
            return

        for entry in imports:
            try:
                dll_name = entry.dll.decode(errors="ignore").lower()
            except Exception:
                dll_name = str(entry.dll).lower()

            funcs: list[ImportFunction] = []

            for imp in entry.imports:
                func = ImportFunction()

                func.name = (
                    imp.name.decode(errors="ignore")
                    if getattr(imp, "name", None)
                    else ""
                )
                func.ordinal = getattr(imp, "ordinal", 0)
                func.thunk_rva = hex(getattr(imp, "address", 0))
                func.bound = bool(getattr(imp, "bound", 0))
                func.hint = getattr(imp, "hint", 0)

                funcs.append(func)

                # ───────────────── API Analysis ─────────────────
                if func.name:
                    api = self._normalize_api_name(func.name)

                    suspicious_reason = self._lookup_suspicious_reason(api)
                    if suspicious_reason:
                        result.suspicious_apis[func.name] = suspicious_reason
                        logger.warning(f"⚠️ Suspicious API: {func.name} -> {suspicious_reason}")

                    if api in IMPORTANT_IMPORTS:
                        info = IMPORTANT_IMPORTS[api]
                        result.important_imports[func.name] = info
                        logger.warning(
                            f"[{info['severity']}] {func.name} ({info['category']})"
                        )

            result.imports[dll_name] = funcs

        result.total_imported_dlls = len(result.imports)
        result.total_imports = sum(len(v) for v in result.imports.values())

        logger.info(
            f"📦 Imports: {result.total_imported_dlls} DLLs | {result.total_imports} APIs | "
            f"{len(result.suspicious_apis)} suspicious | {len(result.important_imports)} important"
        )

    # =========================================================================

    def _analyze_exports(self, pe: pefile.PE, result: StaticAnalysisResult) -> None:
        """
        Analyze export table for DLLs.
        """
        exports = getattr(pe, "DIRECTORY_ENTRY_EXPORT", None)

        if not exports:
            return

        for exp in exports.symbols:
            f = ExportFunction()

            f.name = exp.name.decode(errors="ignore") if exp.name else ""
            f.ordinal = exp.ordinal
            f.rva = hex(exp.address)

            f.forwarded_to = (
                exp.forwarder.decode(errors="ignore")
                if getattr(exp, "forwarder", None)
                else ""
            )

            result.exports.append(f)
            result.total_exports += 1

        logger.info(f"📤 Exports: {result.total_exports}")

    # =========================================================================

    def _extract_strings(self, filepath: Path, result: StaticAnalysisResult) -> None:
        """
        Extract strings and classify IOCs.

        Supports different return shapes from utils.strings.extract_all_strings:
        - (all_strings, iocs)
        - {"strings": ..., "iocs": ...}
        - other compatible shapes, with fallback handling
        """
        try:
            logger.info(f"📝 Extracting strings and IOCs...")
            ascii_strings, unicode_strings, iocs = extract_all_strings(filepath)

            result.total_strings = len(ascii_strings) + len(unicode_strings)
            result.ascii_strings = ascii_strings
            result.unicode_strings = unicode_strings
            result.iocs          = iocs

            # Summarize what was discovered
            total_iocs = sum(len(v) for v in iocs.values())
            logger.info(
                f"🎯 Extracted strings: {result.total_strings} | "
                f"IOCs: {total_iocs} in {len(iocs)} category"
            )

        except Exception as e:
            result.errors.append(f"Error extracting strings: {e}")
            logger.error(f"❌ Error extracting strings: {e}")
    # =========================================================================

    def _calculate_risk_score(self, result: StaticAnalysisResult) -> None:
        """
        Compute a simple heuristic risk score.
        """
        score = 0

        for info in result.important_imports.values():
            sev = info.get("severity", "").upper()

            if sev == "LOW":
                score += 1
            elif sev == "MEDIUM":
                score += 3
            elif sev == "HIGH":
                score += 7
            elif sev == "CRITICAL":
                score += 15

        # suspicious APIs bonus
        score += len(result.suspicious_apis) * 10

        # packed / hidden logic suspicion
        if not result.imports:
            score += 20

        # exports on non-DLLs can be interesting too
        if result.exports and not result.is_dll:
            score += 2

        result.risk_score = score
        result.risk_level_for_IAT = self._categorize_risk_for_IAT(score)

    def _categorize_risk_for_IAT(self, score: int) -> str:
        """
        Calculate overall risk level based on all analysis results and heuristic score.

        Criteria:
        CRITICAL : Detected as malicious in VT + suspicious APIs + packed or very high score
        HIGH RISK    : Detected as malicious in VT or multiple dangerous APIs or high score
        SUSPICIOUS   : Suspicious in VT or packed with IOCs or moderate score
        LOW RISK      : Minor indicators only or low score
        CLEAN    : No clear indicators
        """
        if score >= 100:
            return "CRITICAL"
        elif score >= 80:
            return "HIGH RISK"
        elif score >= 50:
            return "SUSPICIOUS"
        elif score >= 20:
            return "LOW RISK"
        else:
            return "CLEAN - SAFE"


def categorize_risk(result: StaticAnalysisResult, vt2: dict, packer: dict) -> str:
    """
    Improved risk categorization (heuristic + multi-signal model)
    """

    sus_apis = result.suspicious_apis 
    iocs = result.iocs

    score = getattr(result, "risk_score", 0)

    # ─────────────────────────────────────────────
    # Normalized indicators
    # ─────────────────────────────────────────────

    malicious = vt2.malicious
    total_engines = vt2.total_engines

    detection_ratio = malicious / total_engines if total_engines else 0

    is_packed = packer.is_packed if packer else False

    api_count = len(sus_apis)
    ioc_count = sum(len(v) for v in iocs.values())

    # ─────────────────────────────────────────────
    # CORE LOGIC (fixed precedence + real model)
    # ─────────────────────────────────────────────

    vt_clean = (
        total_engines >= 10
        and detection_ratio == 0
    )

    if vt_clean:

        # very rare dangerous combination only
        if (
            score >= 100
            and api_count >= 8
            and is_packed
        ):
            return "SUSPICIOUS جرب مش هتندم"

        return "CLEAN امان يا حجازي"

    if (

        (detection_ratio >= 0.6 and api_count >= 3)
        or (detection_ratio >= 0.4 and is_packed and score >= 100)
        
    ):
        return "CRITICAL واعر جوي يا ابوي"


    if (
        detection_ratio >= 0.25
        or score >= 80
        or api_count >= 5
        or (is_packed and api_count >= 3)
    ):
        return "HIGH RISK خطيير"


    if (
        detection_ratio > 0
        or score >= 50
        or (is_packed and ioc_count > 5)
        or api_count >= 2
    ):
        return "SUSPICIOUS جرب مش هتندم"


    if (
        is_packed
        or api_count > 0
        or ioc_count > 0
        or score >= 20
    ):
        return "LOW RISK خد فكرة و مش هتندم"

    return "CLEAN امان يا حجازي"