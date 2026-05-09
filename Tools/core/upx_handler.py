# =============================================================================
# core/upx_handler.py — Managing UPX unpacking process
# =============================================================================
# UPX is the most famous tool for compressing executable files — open source and free
# Used legitimately to reduce program size
# But also used extensively to hide malicious software
#
# ⚠️ Security Policy:
# - We do not install UPX automatically
# - We do not download files from the internet
# - We only look for UPX pre-installed on the system or in the tools/ folder

import os
import sys
import shutil
import subprocess
import tempfile
import shutil as sh
from pathlib import Path
from dataclasses import dataclass
from utils.logger import get_logger
from config import TOOLS_DIR, OUTPUT_DIR

logger = get_logger(__name__)


@dataclass
class UPXResult:
    """Result of UPX unpacking attempt"""
    success: bool = False           # Was the unpacking operation successful?
    upx_available: bool = False     # Is UPX available on the system?
    upx_path: str = ""              # Path of UPX used
    unpacked_path: str = ""         # Path of file after unpacking
    error_message: str = ""         # Error message in case of failure
    skipped: bool = False           # Was the operation skipped?


class UPXHandler:
    """
    Safe and gradual UPX unpacking process handler.

    UPX search sequence:
    1. System PATH — highest priority
    2. tools/ folder in the project — for local distribution
    3. If not found → safe skip

    Behavior on failure:
    - Program does not stop
    - Logs warning and continues analysis
    """

    def __init__(self):
        # UPX binary name depends on operating system
        self._upx_binary_name = "upx.exe" if sys.platform == "win32" else "upx"
        self._upx_path: str | None = None

    def find_upx(self) -> str | None:
        """
        Search for UPX using multi-stage strategy.

        Returns:
            UPX path if found, or None if not found
        """
        # ─── Stage 1: Search in system PATH ────────────────────────────────
        # shutil.which searches in PATH variable automatically
        system_upx = shutil.which("upx")
        if system_upx:
            logger.info(f"✅ UPX installed on system: {system_upx}")
            self._upx_path = system_upx
            return system_upx

        # ─── Stage 2: Search in local tools/ folder ──────────────────────────
        local_upx = os.path.join(TOOLS_DIR, self._upx_binary_name)
        if os.path.isfile(local_upx):
            logger.info(f"✅ UPX included locally in: {local_upx}")
            self._upx_path = local_upx
            return local_upx

        # ─── Stage 3: UPX not found ────────────────────────────────────
        logger.warning(
            "⚠️  UPX not available. Unpacking will be skipped.\n"
            "   To enable this feature, do one of the following:\n"
            "   1. Install UPX on the system: https://upx.github.io\n"
            f"  2. Place the file manually in: {local_upx}"
        )
        return None

    def unpack(self, filepath: str | Path) -> UPXResult:
        """
        Attempt to unpack UPX file.

        Process:
        1. Copy file to temporary folder (to protect original file)
        2. Run: upx -d <file>
        3. Verify operation success
        4. Return path of unpacked file

        Note: We always work on a temporary copy to not modify the original file.

        Parameters:
            filepath : Path of packed UPX file

        Returns:
            UPXResult object containing operation details
        """
        result = UPXResult()
        filepath = Path(filepath)

        # ─── Search for UPX ──────────────────────────────────────────────────
        upx_path = self.find_upx()

        if not upx_path:
            result.skipped = True
            result.error_message = "UPX not available. Unpacking skipped. Static analysis may be limited."
            return result

        result.upx_available = True
        result.upx_path = upx_path

        # ─── Create temporary copy to work on ──────────────────────────────────
        try:
            temp_dir    = tempfile.mkdtemp(prefix="mtriage_upx_")
            temp_file   = os.path.join(temp_dir, filepath.name)
            sh.copy2(str(filepath), temp_file)
            logger.debug(f"Temporary copy ready: {temp_file}")
        except Exception as e:
            result.error_message = f"Failed to create temporary copy: {e}"
            logger.error(result.error_message)
            return result

        # ─── Run UPX to unpack ───────────────────────────────────────────
        try:
            logger.info(f"⏳ Unpacking UPX for file: {filepath.name}")

            cmd = [upx_path, "-d", temp_file]
            process = subprocess.run(
                cmd,
                capture_output=True,
                text=True,
                timeout=60  # 60 second timeout for protection from hanging
            )

            if process.returncode == 0:
                # Unpacking successful
                result.success = True
                result.unpacked_path = temp_file
                logger.info(f"✅ Unpacking successful: {temp_file}")
                logger.debug(f"UPX output: {process.stdout.strip()}")

            else:
                # Unpacking failed (may not be UPX or different version)
                stderr = process.stderr.strip()
                result.error_message = f"UPX failed (exit code: {process.returncode}): {stderr}"
                logger.warning(f"⚠️  {result.error_message}")

                # Clean up temporary file on failure
                try:
                    sh.rmtree(temp_dir, ignore_errors=True)
                except Exception:
                    pass

        except subprocess.TimeoutExpired:
            result.error_message = "UPX timeout expired (60 seconds)"
            logger.error(f"⚠️  {result.error_message}")

        except FileNotFoundError:
            result.error_message = f"UPX file not found at path: {upx_path}"
            logger.error(f"❌ {result.error_message}")

        except Exception as e:
            result.error_message = f"Unexpected error while running UPX: {e}"
            logger.error(f"❌ {result.error_message}")

        return result

    def cleanup_temp(self, unpacked_path: str, filepath: Path) -> None:
        """Clean up temporary files after analysis completion."""
        try:
            temp_dir = os.path.dirname(unpacked_path)
            if "mtriage_upx_" in temp_dir:
                # save unpacked file before cleanup if needed in new folder named "unpacked_files" in output folder
                unpacked_files_dir = os.path.join(OUTPUT_DIR, filepath.name)
                sh.copy2(unpacked_path, unpacked_files_dir)
                # sh.rmtree(temp_dir, ignore_errors=True)
                logger.debug(f"Temporary files cleaned up: {temp_dir}")
        except Exception as e:
            logger.debug(f"Warning: Failed to clean up temporary files: {e}")
