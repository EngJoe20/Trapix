# =============================================================================
# core/packer_detector.py — Detection of packing and protection tools (Packer Detection)
# =============================================================================
# Packing tools (Packers) are used to compress or encrypt executable files
# Legitimate purpose: Reduce file size
# Malicious purpose: Hide code from antivirus programs and analysts
#
# Detection methods used:
# 1. Signatures: Known PE section names for each packer
# 2. Entropy: Compressed/encrypted data is too random
# 3. PE Header properties: Unusual flags or entry point outside normal sections

import pefile
from pathlib import Path
from dataclasses import dataclass, field
from config import KNOWN_PACKER_SECTIONS
from core.entropy_analyzer import EntropyAnalyzer, EntropyResult
from utils.logger import get_logger

logger = get_logger(__name__)


@dataclass
class PackerResult:
    """Comprehensive result for packer detection process"""
    is_packed: bool = False                           # Is the file packed?
    packer_name: str = "Unknown"                      # Name of packing tool if known
    detected_signatures: list[str] = field(default_factory=list)  # Detected signatures
    entropy_result: EntropyResult | None = None       # Entropy analysis results
    detection_methods: list[str] = field(default_factory=list)    # Detection methods used
    confidence: str = "Low"                           # Confidence level: Low / Medium / High
    warnings: list[str] = field(default_factory=list) # Additional warnings


class PackerDetector:
    """
    Detector for packing and protection tools in executable files.
    Combines signature detection and entropy analysis for accurate results.
    """

    # Map linking known section names to their responsible packing tools
    PACKER_SECTION_MAP = {
        ".UPX0":      "UPX",
        ".UPX1":      "UPX",
        ".UPX2":      "UPX",
        "UPX0":       "UPX",
        "UPX1":       "UPX",
        "UPX2":       "UPX",
        ".aspack":    "ASPack",
        ".adata":     "ASPack",
        ".packed":    "Generic Packer",
        ".themida":   "Themida/WinLicense",
        ".winlicence":"WinLicense",
        ".vmp0":      "VMProtect",
        ".vmp1":      "VMProtect",
        ".vmp2":      "VMProtect",
        ".enigma1":   "Enigma Protector",
        ".enigma2":   "Enigma Protector",
        ".nsp0":      "NsPack",
        ".nsp1":      "NsPack",
        ".nsp2":      "NsPack",
        "pec1":       "PECompact",
        "pec2":       "PECompact",
        ".ccg":       "CCG",
        "!EPack":     "EPack",
    }

    # Signatures in PE section data indicating UPX (Magic bytes)
    UPX_MAGIC_BYTES = [b"UPX!", b"UPX0", b"UPX1", b"UPX2"]

    def __init__(self, entropy_threshold: float = 7.0):
        self.entropy_analyzer = EntropyAnalyzer(threshold=entropy_threshold)

    def detect(self, filepath: str | Path) -> PackerResult:
        """
        Run complete packer detection process on PE file.

        Inspection sequence:
        1. Check section names against known signature list
        2. Entropy analysis for each section
        3. Check PE Header properties
        4. Calculate overall confidence level

        Parameters:
            filepath : Path of file to inspect

        Returns:
            PackerResult object containing complete detection results
        """
        result = PackerResult()

        # ─── 1. Entropy Analysis ──────────────────────────────────────────────
        logger.info(f"Analyzing entropy for: {Path(filepath).name}")
        result.entropy_result = self.entropy_analyzer.analyze_file(filepath)

        if result.entropy_result.overall_suspicious:
            result.is_packed = True
            result.detection_methods.append("Entropy Analysis")
            result.warnings.append(
                f"High entropy detected (highest section: {result.entropy_result.max_section_entropy:.2f})"
            )

        # ─── 2. Check PE Headers and Sections ─────────────────────────────────────
        try:
            pe = pefile.PE(str(filepath), fast_load=True)
            self._check_section_signatures(pe, result)
            self._check_pe_header_anomalies(pe, result)
            self._check_upx_magic(pe, result)
            # Additional fallback UPX detection
            for section in pe.sections:

                section_name = (
                    section.Name.decode(
                        "utf-8",
                        errors="ignore"
                    )
                    .rstrip("\x00")
                    .strip()
                    .lower()
                )

                if "upx" in section_name:

                    result.is_packed = True
                    result.packer_name = "UPX"

                    if "UPX Fallback Detection" not in result.detection_methods:
                        result.detection_methods.append(
                            "UPX Fallback Detection"
                        )

                    logger.warning(
                        f"  🔴 UPX fallback signature detected in section: "
                        f"{section_name}"
                    )
                    break
                else:
                    result.packer_name = self.PACKER_SECTION_MAP.get(section_name, result.packer_name)

            pe.close()

        except pefile.PEFormatError:
            logger.debug(f"File is not valid PE, skipping PE Headers check")
        except Exception as e:
            logger.error(f"Error during PE analysis: {e}")

        # ─── 3. Calculate Confidence Level ─────────────────────────────────────────────
        result.confidence = self._calculate_confidence(result)

        if result.is_packed:
            logger.warning(
                f"🔴 Packed file detected! "
                f"Tool: {result.packer_name} | "
                f"Confidence: {result.confidence}"
            )
        else:
            logger.info(f"✅ No clear packing detected in file")

        return result

    def _check_section_signatures(self, pe: pefile.PE, result: PackerResult) -> None:
        """
        Check PE section names against known signature list.
        Unusual section names or those known to be associated with packers are a strong indicator.
        """
        for section in pe.sections:
            section_name = section.Name.decode("utf-8", errors="ignore").rstrip("\x00").strip()
            section_name_lower = section_name.lower()

            # Check against KNOWN_PACKER_SECTIONS signatures
            for known_sig in KNOWN_PACKER_SECTIONS:
                if known_sig.lower() in section_name_lower:
                    result.is_packed = True
                    result.detected_signatures.append(section_name)

                    # Extract packer name from map
                    # Stronger UPX detection
                if "upx" in section_name_lower:
                    packer = "UPX"
                else:
                    packer = self.PACKER_SECTION_MAP.get(section_name, "Unknown Packer")
                    if result.packer_name == "Unknown":
                        result.packer_name = packer

                    if "Section Signatures" not in result.detection_methods:
                        result.detection_methods.append("Section Signatures")

                    logger.warning(f"  🔴 Packer signature detected: '{section_name}' → {packer}")

    def _check_pe_header_anomalies(self, pe: pefile.PE, result: PackerResult) -> None:
        """
        Check PE Header properties to detect manipulation signs.

        Packed files often show:
        - Very few imports (real code is hidden)
        - Entry point outside first section
        - Raw data size differs greatly from virtual size
        """
        # ─── Check: Are imports too few? ─────────────────────────────
        try:
            pe.parse_data_directories( directories=[ pefile.DIRECTORY_ENTRY["IMAGE_DIRECTORY_ENTRY_IMPORT"] ] )

            if hasattr(pe, "DIRECTORY_ENTRY_IMPORT"):
                # Number of imported FUNCTIONS/APIs
                import_count = sum(len(entry.imports) for entry in pe.DIRECTORY_ENTRY_IMPORT)

                # Very small import tables are common in packed malware
                if import_count < 10:

                    result.warnings.append(
                        f"Very few imported APIs ({import_count}) "
                        f"— possible packing indicator"
                    )

                    result.is_packed = True

                    if "Few Imports" not in result.detection_methods:
                        result.detection_methods.append(
                            "Few Imports"
                        )

        except Exception:
            pass

        # ─── Check: Raw size to virtual size ratio ─────────────────────────────
        for section in pe.sections:
            raw_size     = section.SizeOfRawData
            virtual_size = section.Misc_VirtualSize

            if raw_size == 0 and virtual_size > 0:
                # Section with raw size = 0 means data is written to memory at runtime
                # This is very common in UPX (UPX0 section has raw size = 0)
                section_name = section.Name.decode("utf-8", errors="ignore").rstrip("\x00")
                result.warnings.append(
                    f"Section '{section_name}' has raw_size=0 with virtual_size>0 → written to memory at runtime"
                )

    def _check_upx_magic(self, pe: pefile.PE, result: PackerResult) -> None:
        """
        Search for UPX magic signature in file data directly.
        UPX leaves a 'UPX!' signature in the packed file.
        """
        for section in pe.sections:
            data = section.get_data()[:512]  # Check first 512 bytes only
            for magic in self.UPX_MAGIC_BYTES:
                if magic in data:
                    result.is_packed = True
                    result.packer_name = "UPX"
                    if "UPX Magic Bytes" not in result.detection_methods:
                        result.detection_methods.append("UPX Magic Bytes")
                    logger.warning(f"  🔴 UPX magic signature detected: {magic}")

    def _calculate_confidence(self, result: PackerResult) -> str:
        """
        Calculate confidence level in detection result.

        Evaluation logic:
        - One detection method: low confidence
        - Two methods: medium confidence
        - Three or more methods: high confidence
        """
        if not result.is_packed:
            return "N/A"

        method_count = len(result.detection_methods)
        if method_count >= 3:
            return "High"
        elif method_count >= 2:
            return "Medium"
        else:
            return "Low"
