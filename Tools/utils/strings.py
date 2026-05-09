# =============================================================================
# utils/strings.py — Extract strings from binary files
# =============================================================================
# Extracting strings from executable files is a static analysis technique
# that reveals IOCs (indicators of compromise) like URLs, IP addresses, and file paths
# without needing to run the program (fully static analysis)

import re
from pathlib import Path
from config import STRING_PATTERNS


def extract_ascii_strings(data: bytes, min_len: int = 4) -> list[str]:
    """
    Extract printable ASCII strings from binary data.

    Similar to `strings` tool on Linux/Unix.
    Search for sequences of printable characters with length at least min_len.

    Parameters:
        data    : Binary data of the file
        min_len : Minimum string length (ignore very short strings)

    Returns:
        List of extracted text strings
    """
    # Regex pattern to extract printable ASCII strings
    pattern = re.compile(rb'[ -~]{' + str(min_len).encode() + rb',}')
    return [s.decode("ascii", errors="ignore") for s in pattern.findall(data)]


def extract_unicode_strings(data: bytes, min_len: int = 4) -> list[str]:
    """
    Extract Unicode strings (UTF-16 LE) from binary data.

    Modern programs use Unicode extensively, especially Windows API strings.
    UTF-16 LE places a null byte after each character: H\x00e\x00l\x00l\x00o\x00
    """
    # Pattern to detect Unicode LE: printable character + null byte
    pattern = re.compile(rb'(?:[ -~]\x00){' + str(min_len).encode() + rb',}')
    results = []
    for match in pattern.findall(data):
        try:
            decoded = match.decode("utf-16-le", errors="ignore").strip()
            if len(decoded) >= min_len:
                results.append(decoded)
        except Exception:
            pass
    return results


def categorize_strings(strings: list[str]) -> dict[str, list[str]]:
    """
    Categorize text strings into IOC categories using predefined regex patterns.

    We use the patterns defined in config.py to identify:
    - URLs: Indicator of internet connection or C2 server
    - IPs: Potential command and control server addresses
    - Registry Keys: Indicator of persistence or system manipulation
    - DLL/EXE: Files loaded or created dynamically

    Returns:
        Dictionary where key is category and value is list of unique matches
    """
    results: dict[str, set] = {cat: set() for cat in STRING_PATTERNS}

    for string in strings:
        for category, pattern in STRING_PATTERNS.items():
            matches = re.findall(pattern, string, re.IGNORECASE)
            for match in matches:
                # Convert tuple to string if pattern contains groups
                if isinstance(match, tuple):
                    match = match[0]
                results[category].add(match)

    # Convert sets to sorted lists for easy display
    return {cat: sorted(items) for cat, items in results.items() if items}


def extract_all_strings(filepath: str | Path) -> tuple[list[str], dict[str, list[str]]]:
    """
    Main function: Extract and categorize all text strings from a file.

    Returns:
        (raw_strings, categorized_iocs)
    """
    with open(filepath, "rb") as f:
        data = f.read()

    # Extract both types of strings and merge them
    ascii_strings   = extract_ascii_strings(data)
    unicode_strings = extract_unicode_strings(data)
    all_strings = list(set(ascii_strings + unicode_strings))

    # Categorize strings into IOC categories
    iocs = categorize_strings(all_strings)

    return ascii_strings, unicode_strings, iocs
