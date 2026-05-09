# =============================================================================
# utils/logger.py — Central Logging System
# =============================================================================
# Provides a unified log for all system components with terminal color support
# and automatic saving to text file for later review

import logging
import os
import sys
from datetime import datetime
from colorama import Fore, Style, init

# Initialize colorama library to support colors on Windows too
init(autoreset=True)

# ─── Custom Colored Formatter ─────────────────────────────────────────────────
class ColoredFormatter(logging.Formatter):
    """Custom formatter that adds colors to different log levels"""

    LEVEL_COLORS = {
        logging.DEBUG:    Fore.CYAN,
        logging.INFO:     Fore.GREEN,
        logging.WARNING:  Fore.YELLOW,
        logging.ERROR:    Fore.RED,
        logging.CRITICAL: Fore.RED + Style.BRIGHT,
    }

    def format(self, record: logging.LogRecord) -> str:
        color = self.LEVEL_COLORS.get(record.levelno, "")
        # Format message with time, level, and content
        time_str = datetime.fromtimestamp(record.created).strftime("%H:%M:%S")
        level    = f"{color}{record.levelname:<8}{Style.RESET_ALL}"
        name     = f"{Fore.BLUE}{record.name}{Style.RESET_ALL}"
        msg      = f"{color}{record.getMessage()}{Style.RESET_ALL}"
        return f"[{time_str}] {level} {name}: {msg}"


def get_logger(name: str, log_dir: str | None = None) -> logging.Logger:
    """
    Create or retrieve a logger with a specific name.

    Parameters:
        name    : Logger name (usually the module name)
        log_dir : Directory to save log file (optional)

    Returns:
        Ready-to-use Logger object
    """
    logger = logging.getLogger(name)

    # Avoid adding duplicate handlers when function is called multiple times
    if logger.handlers:
        return logger

    logger.setLevel(logging.DEBUG)

    # ─── Console Handler ────────────────────────────────────────────
    console_handler = logging.StreamHandler(sys.stdout)
    console_handler.setLevel(logging.INFO)
    console_handler.setFormatter(ColoredFormatter())
    logger.addHandler(console_handler)

    # ─── File Handler ────────────────────────────────────────────
    if log_dir:
        os.makedirs(log_dir, exist_ok=True)
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        log_path  = os.path.join(log_dir, f"triage_{timestamp}.log")

        file_handler = logging.FileHandler(log_path, encoding="utf-8")
        file_handler.setLevel(logging.DEBUG)
        # Log file without ANSI colors for easy reading
        file_formatter = logging.Formatter(
            "[%(asctime)s] %(levelname)-8s %(name)s: %(message)s",
            datefmt="%Y-%m-%d %H:%M:%S"
        )
        file_handler.setFormatter(file_formatter)
        logger.addHandler(file_handler)

    return logger
