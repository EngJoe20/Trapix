#!/usr/bin/env python3
# =============================================================================
# main.py — Entry point for the malware triage framework
# =============================================================================
# Usage:
#   python main.py --file samples/test.exe
#   python main.py --dir samples/ --recursive
#   python main.py --file sample.exe --no-vt --no-json
#   python main.py --file sample.exe --vt-key YOUR_API_KEY
#
# Requirements:
#   pip install -r requirements.txt

import sys
import os
import argparse
from pathlib import Path
from colorama import Fore, Style, init

# ─── Add project folder to import path ─────────────────────────────────
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from core.file_analyzer import FileAnalyzer
from utils.logger import get_logger
from config import LOGS_DIR, VT_API_KEY

init(autoreset=True)
logger = get_logger("main", log_dir=LOGS_DIR)


# ─── Banner ───────────────────────────────────────────────────────────────────
BANNER = f"""
{Fore.CYAN}
 ███╗   ███╗ █████╗ ██╗     ██╗    ██╗ █████╗ ██████╗ ███████╗
 ████╗ ████║██╔══██╗██║     ██║    ██║██╔══██╗██╔══██╗██╔════╝
 ██╔████╔██║███████║██║     ██║ █╗ ██║███████║██████╔╝█████╗
 ██║╚██╔╝██║██╔══██║██║     ██║███╗██║██╔══██║██╔══██╗██╔══╝
 ██║ ╚═╝ ██║██║  ██║███████╗╚███╔███╔╝██║  ██║██║  ██║███████╗
 ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝ ╚══╝╚══╝ ╚═╝  ╚═╝╚═╝  ╚═╝╚══════╝
{Style.RESET_ALL}
{Fore.WHITE}         ████████╗██████╗ ██╗ █████╗  ██████╗ ███████╗{Style.RESET_ALL}
{Fore.WHITE}            ██╔══╝██╔══██╗██║██╔══██╗██╔════╝ ██╔════╝{Style.RESET_ALL}
{Fore.WHITE}            ██║   ██████╔╝██║███████║██║  ███╗█████╗{Style.RESET_ALL}
{Fore.WHITE}            ██║   ██╔══██╗██║██╔══██║██║   ██║██╔══╝{Style.RESET_ALL}
{Fore.WHITE}            ██║   ██║  ██║██║██║  ██║╚██████╔╝███████╗{Style.RESET_ALL}
{Fore.WHITE}            ╚═╝   ╚═╝  ╚═╝╚═╝╚═╝  ╚═╝ ╚═════╝ ╚══════╝{Style.RESET_ALL}
{Fore.YELLOW}
         ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
          Professional Malware Triage Framework v1.0
          Professional Malware Triage Framework
          Powered by: pefile | VirusTotal | Shannon Entropy
         ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
{Style.RESET_ALL}
"""


def parse_arguments() -> argparse.Namespace:
    """
    Parse command line arguments.

    Supports three run modes:
    1. Check single file:   --file <path>
    2. Check folder:        --dir  <path> [--recursive]
    3. Show help:           --help
    """
    parser = argparse.ArgumentParser(
        prog="malware_triage",
        description="Professional malware triage framework",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples of usage:
  python main.py --file samples/suspicious.exe
  python main.py --dir samples/ --recursive
  python main.py --file malware.exe --vt-key abc123 --no-json
  python main.py --dir C:/malware_samples/ --no-vt
        """
    )

    # ─── Input source group (one is required) ────────────────────────────────
    input_group = parser.add_mutually_exclusive_group(required=True)
    input_group.add_argument(
        "--file", "-f",
        type=str,
        metavar="PATH",
        help="Path of file to analyze"
    )
    input_group.add_argument(
        "--dir", "-d",
        type=str,
        metavar="DIR",
        help="Path of folder to scan all files in it"
    )

    # ─── Additional options ───────────────────────────────────────────────────────
    parser.add_argument(
        "--recursive", "-r",
        action="store_true",
        default=True,
        help="Search in subdirectories (default: enabled)"
    )
    parser.add_argument(
        "--vt-key",
        type=str,
        default=None,
        metavar="API_KEY",
        help="VirusTotal API key (optional — can be placed in config.py)"
    )
    parser.add_argument(
        "--no-vt",
        action="store_true",
        default=False,
        help="Skip VirusTotal query"
    )
    parser.add_argument(
        "--no-json",
        action="store_true",
        default=False,
        help="Do not export JSON report"
    )
    parser.add_argument(
        "--quiet", "-q",
        action="store_true",
        default=False,
        help="Quiet mode — show only important results"
    )

    return parser.parse_args()


def print_summary(results: list[dict]) -> None:
    """
    Print overall summary for all analyzed files.
    Useful when scanning folders containing multiple files.
    """
    if len(results) <= 1:
        return

    print(f"\n{Fore.CYAN}{'═'*70}{Style.RESET_ALL}")
    print(f"{Fore.WHITE}{'  ANALYSIS SUMMARY':^70}{Style.RESET_ALL}")
    print(f"{Fore.CYAN}{'═'*70}{Style.RESET_ALL}\n")

    total     = len(results)
    malicious = sum(1 for r in results if r.get("virustotal", {}).get("malicious", 0) > 0)
    packed    = sum(1 for r in results if r.get("packer", {}).get("is_packed", False))
    with_apis = sum(1 for r in results if r.get("suspicious_apis"))

    from tabulate import tabulate
    rows = [
        ["Total files analyzed",  total],
        [f"{Fore.RED}Malicious (VT){Style.RESET_ALL}",                 malicious],
        [f"{Fore.YELLOW}Packed{Style.RESET_ALL}",         packed],
        [f"{Fore.YELLOW}Contains suspicious APIs{Style.RESET_ALL}",       with_apis],
    ]
    print(tabulate(rows, tablefmt="rounded_outline"))
    print()


def main() -> int:
    """
    Main function — entry point.

    Returns:
        0 on success, 1 on error
    """
    # ─── Print Banner ────────────────────────────────────────────────────────
    print(BANNER)

    # ─── Parse Arguments ─────────────────────────────────────────────────────
    args = parse_arguments()

    # ─── Setup Analyzer ───────────────────────────────────────────────────────
    vt_key = args.vt_key or VT_API_KEY

    analyzer = FileAnalyzer(
        vt_api_key  = vt_key,
        export_json = not args.no_json,
        skip_vt     = args.no_vt,
    )

    results = []

    # ─── Single File Mode ────────────────────────────────────────────────────
    if args.file:
        filepath = Path(args.file)
        if not filepath.exists():
            logger.error(f"❌ File not found: {filepath}")
            return 1

        logger.info(f"📁 Analyzing file: {filepath.absolute()}")
        result = analyzer.analyze_file(filepath)
        results.append(result)

    # ─── Directory Mode ──────────────────────────────────────────────────────
    elif args.dir:
        dirpath = Path(args.dir)
        if not dirpath.is_dir():
            logger.error(f"❌ Directory not found: {dirpath}")
            return 1

        logger.info(f"📂 Scanning directory: {dirpath.absolute()}")
        results = analyzer.analyze_directory(dirpath, recursive=args.recursive)

    # ─── Overall Summary ─────────────────────────────────────────────────────
    print_summary(results)

    return 0


if __name__ == "__main__":
    sys.exit(main())
