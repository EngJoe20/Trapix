#!/usr/bin/env python3
# =============================================================================
# bridge.py — HTTP API wrapper around the Trapix malware triage engine
# =============================================================================
# This script is the standardized entry point called by the Laravel backend.
#
# Usage (called by PythonBridgeService.php):
#   python bridge.py --file /path/to/file --output-dir /tmp/job_xyz --format json
#   python bridge.py --dir  /path/to/dir  --output-dir /tmp/job_xyz --format json
#
# The script:
#   1. Reads an optional analysis_request.json from --output-dir
#   2. Runs the full triage pipeline
#   3. Writes result.json (and optionally report.pdf) to --output-dir
#   4. Exits 0 on success, non-zero on failure
#
# Communication Contract:
#   Input:  analysis_request.json  (optional; CLI args take precedence)
#   Output: result.json            (always)
#           report.pdf             (when PDF generation is supported)
#           bridge.log             (always — captured by Laravel)

import sys
import os
import json
import argparse
import traceback
from pathlib import Path
from datetime import datetime

# ─── Add parent directory to path so we can import core modules ───────────────
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from core.file_analyzer import FileAnalyzer
from config import VT_API_KEY, LOGS_DIR


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        prog="trapix-bridge",
        description="Trapix analysis bridge — called by the Laravel backend"
    )

    # ── Input source (one required) ────────────────────────────────────────────
    group = parser.add_mutually_exclusive_group(required=True)
    group.add_argument("--file", "-f", type=str, metavar="PATH", help="Single file to analyze")
    group.add_argument("--dir",  "-d", type=str, metavar="DIR",  help="Directory to scan")

    # ── Output ─────────────────────────────────────────────────────────────────
    parser.add_argument(
        "--output-dir", "-o",
        type=str, required=True, metavar="DIR",
        help="Directory where result.json and report.pdf will be written"
    )
    parser.add_argument(
        "--format", type=str, default="json", choices=["json"],
        help="Output format (currently only json is supported)"
    )

    # ── Options ────────────────────────────────────────────────────────────────
    parser.add_argument("--recursive",  action="store_true", default=True)
    parser.add_argument("--no-vt",      action="store_true", default=False)
    parser.add_argument("--vt-key",     type=str, default=None, metavar="API_KEY")

    return parser.parse_args()


def read_request_overrides(output_dir: str) -> dict:
    """
    Read analysis_request.json from the output directory if it exists.
    These values supplement (but don't override) CLI arguments.
    """
    req_path = Path(output_dir) / "analysis_request.json"
    if req_path.exists():
        try:
            with open(req_path) as f:
                return json.load(f)
        except Exception:
            pass
    return {}


def write_result(output_dir: str, result: dict | list) -> str:
    """Write the analysis result to result.json."""
    out_path = Path(output_dir) / "result.json"
    out_path.parent.mkdir(parents=True, exist_ok=True)

    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2, default=str)

    return str(out_path)


def write_error(output_dir: str, error: str, trace: str = "") -> None:
    """Write error.json when the analysis itself fails."""
    err_path = Path(output_dir) / "error.json"
    with open(err_path, "w", encoding="utf-8") as f:
        json.dump({
            "success": False,
            "error":   error,
            "trace":   trace,
            "time":    datetime.now().isoformat(),
        }, f, indent=2)


def main() -> int:
    args = parse_args()
    overrides = read_request_overrides(args.output_dir)

    # ── Resolve VT key (CLI > request.json > config) ──────────────────────────
    vt_key  = args.vt_key or overrides.get("vt_api_key") or VT_API_KEY
    skip_vt = args.no_vt or overrides.get("skip_vt", False)

    # ── Resolve tool selection from the analysis_request.json options ──────────
    job_options     = overrides.get("options", {})
    selected_tools  = job_options.get("tools", [])        # e.g. ["hashes","vt","pe_info"]
    hash_algorithms = job_options.get("hash_algorithms", [])  # e.g. ["sha256","md5"]

    # If no tools selected → run everything (default)
    if not selected_tools:
        selected_tools = None   # None = all tools
    if not hash_algorithms:
        hash_algorithms = None  # None = all algorithms

    # Skip VT if 'vt' not in selected tools
    if selected_tools and "vt" not in selected_tools:
        skip_vt = True

    # ── Build analyzer ─────────────────────────────────────────────────────────
    analyzer = FileAnalyzer(
        vt_api_key      = vt_key,
        export_json     = False,   # We handle JSON output ourselves
        skip_vt         = skip_vt,
        selected_tools  = selected_tools,
        hash_algorithms = hash_algorithms,
    )

    try:
        # ── Single file mode ───────────────────────────────────────────────────
        if args.file:
            target = Path(args.file)
            if not target.exists():
                write_error(args.output_dir, f"File not found: {target}")
                print(f"ERROR: File not found: {target}", file=sys.stderr)
                return 1

            result = analyzer.analyze_file(target)
            
            # Use original name if provided by backend (important for ZIP extraction)
            if overrides.get("original_name"):
                result["file_name"] = overrides["original_name"]

            # Normalize to list for consistent contract
            payload = {
                "success": True,
                "job_type": "file",
                "results": [result],
                "summary": {
                    "total_files":   1,
                    "risk_level":    result.get("risk_level", "Unknown"),
                    "analysis_time": result.get("analysis_time"),
                }
            }

        # ── Directory mode ─────────────────────────────────────────────────────
        else:
            target = Path(args.dir)
            if not target.is_dir():
                write_error(args.output_dir, f"Directory not found: {target}")
                print(f"ERROR: Directory not found: {target}", file=sys.stderr)
                return 1

            results = analyzer.analyze_directory(target, recursive=args.recursive)

            # Update names from map if available
            file_map = overrides.get("file_map", {})
            for res in results:
                stored_name = Path(res.get("file_path", "")).name
                if stored_name in file_map:
                    res["file_name"] = file_map[stored_name]

            risk_counts = {}
            for r in results:
                lvl = r.get("risk_level", "Unknown")
                risk_counts[lvl] = risk_counts.get(lvl, 0) + 1

            payload = {
                "success":  True,
                "job_type": "folder",
                "results":  results,
                "summary": {
                    "total_files":  len(results),
                    "risk_counts":  risk_counts,
                    "analysis_time": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                }
            }

        # ── Write result.json ──────────────────────────────────────────────────
        out_path = write_result(args.output_dir, payload)
        
        # ── Generate PDF report ───────────────────────────────────────────────
        pdf_path = Path(args.output_dir) / "report.pdf"
        if args.file:
            analyzer.report_generator.generate_pdf(result, str(pdf_path))
        else:
            # For directory mode, generate a summary report
            analyzer.report_generator.generate_summary_pdf(payload, str(pdf_path))

        print(f"OK: result written to {out_path}", flush=True)
        return 0

    except Exception as exc:
        trace = traceback.format_exc()
        write_error(args.output_dir, str(exc), trace)
        print(f"ERROR: {exc}", file=sys.stderr)
        print(trace, file=sys.stderr)
        return 2


if __name__ == "__main__":
    sys.exit(main())
