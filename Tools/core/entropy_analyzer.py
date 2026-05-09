# =============================================================================
# core/entropy_analyzer.py — Entropy Analysis (Shannon Entropy)
# =============================================================================
# Entropy is a measure of data randomness — inspired by Claude Shannon's information theory
#
# Usage in malware analysis:
# - Normal data (compiled code) → Low entropy (4.0 - 6.5)
# - Compressed or encrypted data → High entropy (> 7.0)
# - Packed files often contain sections with entropy > 7.2
#
# ⚠️ Warning: High entropy is a heuristic indicator, not definitive proof
#    Some legitimate files (compressed PNG images inside EXE) may give the same result

import math
import pefile
from pathlib import Path
from dataclasses import dataclass, field
from utils.logger import get_logger

logger = get_logger(__name__)


@dataclass
class EntropyResult:
    """Result of entropy analysis for PE file"""
    file_entropy: float = 0.0                      # File entropy overall
    section_entropies: dict[str, float] = field(default_factory=dict)  # Entropy of each section
    high_entropy_sections: list[str] = field(default_factory=list)     # Sections with high entropy
    overall_suspicious: bool = False               # Is the file suspicious based on entropy?
    max_section_entropy: float = 0.0              # Highest entropy value among all sections


class EntropyAnalyzer:
    """
    Entropy analyzer for PE files.
    Calculates Shannon entropy for each section of the executable file.
    """

    def __init__(self, threshold: float = 7.0):
        """
        Parameters:
            threshold : Threshold above which we consider the section compressed/encrypted (default: 7.0)
        """
        self.threshold = threshold

    @staticmethod
    def calculate_entropy(data: bytes) -> float:
        """
        Calculate Shannon entropy for a set of data.

        Formula: H = -Σ (p_i × log2(p_i))
        where p_i is the probability of each byte value (0-255)

        Possible values:
        - 0.0 : All bytes identical (no randomness)
        - 8.0 : Completely random distribution (max value for 8-bit data)

        Parameters:
            data : Data to calculate entropy for

        Returns:
            Entropy value between 0.0 and 8.0
        """
        if not data:
            return 0.0

        # Calculate frequency of each byte value (0-255)
        byte_counts = [0] * 256
        for byte in data:
            byte_counts[byte] += 1

        total = len(data)
        entropy = 0.0

        for count in byte_counts:
            if count == 0:
                continue
            # Calculate probability and apply Shannon equation
            probability = count / total
            entropy -= probability * math.log2(probability)

        return round(entropy, 4)

    def analyze_file(self, filepath: str | Path) -> EntropyResult:
        """
        Analyze entropy of complete PE file with all sections.

        We analyze:
        1. File entropy overall (useful for quick detection)
        2. Entropy of each section separately (more precise for identifying suspicious sections)

        Parameters:
            filepath : Path of PE file to analyze

        Returns:
            EntropyResult object containing all analysis results
        """
        result = EntropyResult()

        # ─── Calculate file entropy overall ──────────────────────────────────────
        try:
            with open(filepath, "rb") as f:
                file_data = f.read()
            result.file_entropy = self.calculate_entropy(file_data)
            logger.debug(f"Overall file entropy: {result.file_entropy:.4f}")
        except Exception as e:
            logger.error(f"Failed to read file for entropy calculation: {e}")
            return result

        # ─── Analyze PE sections ───────────────────────────────────────────────────
        try:
            pe = pefile.PE(str(filepath), fast_load=True)

            for section in pe.sections:
                # Extract section name and remove null bytes
                section_name = section.Name.decode("utf-8", errors="ignore").rstrip("\x00").strip()
                if not section_name:
                    section_name = f"<unnamed@{section.VirtualAddress:#x}>"

                # Extract section data and calculate entropy
                section_data    = section.get_data()
                section_entropy = self.calculate_entropy(section_data)

                result.section_entropies[section_name] = section_entropy

                logger.debug(f"  Section '{section_name}': entropy = {section_entropy:.4f}")

                # Update highest entropy value
                if section_entropy > result.max_section_entropy:
                    result.max_section_entropy = section_entropy

                # ─── Apply threshold rule ──────────────────────────────────────
                # If entropy exceeds threshold → mark as warning
                if section_entropy > self.threshold:
                    result.high_entropy_sections.append(section_name)
                    logger.warning(
                        f"  ⚠️  Section '{section_name}' with high entropy: {section_entropy:.4f} "
                        f"(threshold: {self.threshold})"
                    )

            pe.close()

        except pefile.PEFormatError:
            # File is not valid PE (may be script or data file)
            logger.debug(f"File is not valid PE, will use overall file entropy only")

        except Exception as e:
            logger.error(f"Error during PE sections analysis: {e}")

        # ─── Determine if file is generally suspicious ───────────────────────
        # Consider suspicious if:
        # 1. Overall entropy is high, or
        # 2. At least one section has entropy above threshold
        result.overall_suspicious = (
            result.file_entropy > self.threshold
            or len(result.high_entropy_sections) > 0
        )

        return result

    def format_entropy_bar(self, entropy: float, width: int = 20) -> str:
        """
        Draw visual bar for entropy value in terminal.

        Example: [████████████░░░░░░░░] 6.5
        """
        filled = int((entropy / 8.0) * width)
        bar    = "█" * filled + "░" * (width - filled)
        return f"[{bar}] {entropy:.2f}"
