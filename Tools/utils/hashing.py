# =============================================================================
# utils/hashing.py — Calculate cryptographic hashes for files
# =============================================================================
# Hash is the digital fingerprint of the file — used to uniquely identify it
# SHA-256 is the standard in malware analysis because it's collision-resistant

import hashlib
import os
from pathlib import Path


def sha256_file(filepath: str | Path, chunk_size: int = 65536) -> str:
    """
    Calculate SHA-256 hash for a file with high efficiency.

    We read the file in chunks instead of loading it entirely into memory
    to support large files without exhausting memory.

    Parameters:
        filepath   : Path of the file to hash
        chunk_size : Size of each chunk in bytes (default: 64KB)

    Returns:
        String representing SHA-256 hash in lowercase hex
    """
    hasher = hashlib.sha256()

    with open(filepath, "rb") as f:
        while chunk := f.read(chunk_size):
            hasher.update(chunk)

    return hasher.hexdigest()


def md5_file(filepath: str | Path, chunk_size: int = 65536) -> str:
    """
    Calculate MD5 hash (sometimes used for comparison with old databases).
    Note: MD5 is not cryptographically secure but is still used for file indexing.
    """
    hasher = hashlib.md5()

    with open(filepath, "rb") as f:
        while chunk := f.read(chunk_size):
            hasher.update(chunk)

    return hasher.hexdigest()


def sha1_file(filepath: str | Path, chunk_size: int = 65536) -> str:
    """Calculate SHA-1 hash for compatibility with some old VirusTotal systems."""
    hasher = hashlib.sha1()

    with open(filepath, "rb") as f:
        while chunk := f.read(chunk_size):
            hasher.update(chunk)

    return hasher.hexdigest()


def get_all_hashes(filepath: str | Path) -> dict:
    """
    Calculate all common hashes for a single file in one read pass.

    Returns:
        Dictionary containing sha256, sha1, md5
    """
    sha256_h = hashlib.sha256()
    sha1_h   = hashlib.sha1()
    md5_h    = hashlib.md5()

    with open(filepath, "rb") as f:
        while chunk := f.read(65536):
            sha256_h.update(chunk)
            sha1_h.update(chunk)
            md5_h.update(chunk)

    return {
        "sha256": sha256_h.hexdigest(),
        "sha1":   sha1_h.hexdigest(),
        "md5":    md5_h.hexdigest(),
    }


def file_size_human(filepath: str | Path) -> str:
    """Convert file size to human-readable format (KB, MB, GB)."""
    size = os.path.getsize(filepath)
    for unit in ["B", "KB", "MB", "GB"]:
        if size < 1024:
            return f"{size:.1f} {unit}"
        size /= 1024
    return f"{size:.1f} TB"
