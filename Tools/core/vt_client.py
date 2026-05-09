# =============================================================================
# core/vt_client.py — VirusTotal API Client
# =============================================================================
# VirusTotal is a free file analysis service that aggregates results from more than 70 antivirus engines
# We use the modern API v3 to query files by SHA-256 hash
#
# Important limitations:
# - Free version: 4 requests/minute and 500 requests/day
# - We handle these limits professionally without breaking them

import time
import json
import os
from pathlib import Path
from dataclasses import dataclass, field
import requests
from config import VT_API_KEY, VT_API_URL, VT_RATE_LIMIT_DELAY, REPORTS_DIR
from utils.logger import get_logger

logger = get_logger(__name__)


@dataclass
class VTResult:
    """VirusTotal query result for a specific file"""
    queried: bool = False              # Was the query successful?
    found: bool = False                # Is the file present in VT database?
    malicious: int = 0                 # Number of detection engines that classified it as malicious
    suspicious: int = 0                # Number of engines that classified it as suspicious
    total_engines: int = 0             # Total number of scanning engines
    detection_ratio: str = "0/0"       # Detection ratio in "X/Y" format
    threat_label: str = ""             # General threat classification (e.g.: trojan.agent)
    permalink: str = ""                # Link to full VT report
    error: str = ""                    # Error message if any
    raw_stats: dict = field(default_factory=dict)  # Raw statistics from API


class VirusTotalClient:
    """
    Professional client for communicating with VirusTotal API v3.

    Features:
    - Rate limiting control
    - Result caching to avoid duplicate queries
    - Comprehensive error handling
    - Support for running without API key (test mode)
    """

    CACHE_DIR = os.path.join(REPORTS_DIR, ".vt_cache")

    def __init__(self, api_key: str = VT_API_KEY):
        self.api_key   = api_key
        self.headers   = {"x-apikey": api_key}
        self._last_request_time = 0.0
        os.makedirs(self.CACHE_DIR, exist_ok=True)

    def _is_api_key_valid(self) -> bool:
        """Check that API key is set and not the default value."""
        return (
            self.api_key
            and self.api_key != "YOUR_VT_API_KEY_HERE"
            and len(self.api_key) > 10
        )

    def _cache_path(self, file_hash: str) -> str:
        """Cache file path for a specific hash."""
        return os.path.join(self.CACHE_DIR, f"{file_hash}.json")

    def _load_from_cache(self, file_hash: str) -> dict | None:
        """
        Load VT result from local cache if available.
        Saves time and avoids consuming API quota for previously analyzed files.
        """
        cache_file = self._cache_path(file_hash)
        if os.path.isfile(cache_file):
            try:
                with open(cache_file, "r", encoding="utf-8") as f:
                    logger.debug(f"Loaded result from cache: {file_hash[:16]}...")
                    return json.load(f)
            except Exception:
                pass
        return None

    def _save_to_cache(self, file_hash: str, data: dict) -> None:
        """Save VT result to local cache for future use."""
        try:
            with open(self._cache_path(file_hash), "w", encoding="utf-8") as f:
                json.dump(data, f, indent=2)
        except Exception as e:
            logger.debug(f"Cache save failed: {e}")

    def _respect_rate_limit(self) -> None:
        """
        Control request rate to respect VirusTotal API limits.
        Free version allows 4 requests/minute, i.e., one request every 15 seconds.
        """
        elapsed = time.time() - self._last_request_time
        if elapsed < VT_RATE_LIMIT_DELAY:
            wait_time = VT_RATE_LIMIT_DELAY - elapsed
            logger.info(f"⏳ Waiting {wait_time:.1f} seconds (Rate Limit)...")
            time.sleep(wait_time)
        self._last_request_time = time.time()

    def query_hash(self, file_hash: str) -> VTResult:
        """
        Query a file in VirusTotal by its hash.

        Workflow:
        1. Check API key validity
        2. Search local cache first
        3. Respect rate limits
        4. Send request to VT API
        5. Analyze response and return result

        Parameters:
            file_hash : SHA-256 hash of the file

        Returns:
            VTResult object containing scan results
        """
        result = VTResult()

        # ─── Check API key ────────────────────────────────────────────────
        if not self._is_api_key_valid():
            result.error = "VT_API_KEY not set. Add it in config.py or environment variables."
            logger.warning(f"⚠️  {result.error}")
            return result

        # ─── Search cache ───────────────────────────────────────────────────
        cached = self._load_from_cache(file_hash)
        if cached:
            return self._parse_vt_response(cached, result)

        # ─── Rate limiting ───────────────────────────────────────────
        self._respect_rate_limit()

        # ─── Send request to VT API ──────────────────────────────────────────
        url = VT_API_URL.format(hash=file_hash)
        logger.info(f"🔍 Querying {file_hash[:16]}... in VirusTotal")

        try:
            response = requests.get(
                url,
                headers=self.headers,
                timeout=30
            )

            if response.status_code == 200:
                data = response.json()
                self._save_to_cache(file_hash, data)
                result.queried = True
                return self._parse_vt_response(data, result)

            elif response.status_code == 404:
                # File not found in VT database — not analyzed before
                result.queried = True
                result.found   = False
                result.error   = "File not found in VirusTotal database"
                logger.info(f"ℹ️  File unknown to VirusTotal")

            elif response.status_code == 401:
                result.error = "API key invalid or expired"
                logger.error(f"❌ {result.error}")

            elif response.status_code == 429:
                result.error = "Rate limit exceeded. Try again later."
                logger.warning(f"⚠️  {result.error}")

            else:
                result.error = f"Unexpected response from VT API: HTTP {response.status_code}"
                logger.error(f"❌ {result.error}")

        except requests.exceptions.Timeout:
            result.error = "Connection timeout to VirusTotal"
            logger.error(f"❌ {result.error}")

        except requests.exceptions.ConnectionError:
            result.error = "Failed to connect to VirusTotal — check your internet connection"
            logger.error(f"❌ {result.error}")

        except Exception as e:
            result.error = f"Unexpected error: {e}"
            logger.error(f"❌ {result.error}")

        return result

    def _parse_vt_response(self, data: dict, result: VTResult) -> VTResult:
        """
        Parse VirusTotal API response and extract useful information.

        Data structure: data → attributes → last_analysis_stats
        """
        try:
            attrs = data.get("data", {}).get("attributes", {})
            stats = attrs.get("last_analysis_stats", {})

            result.found          = True
            result.queried        = True
            result.malicious      = stats.get("malicious", 0)
            result.suspicious     = stats.get("suspicious", 0)
            result.total_engines  = sum(stats.values())
            result.raw_stats      = stats

            # Format detection ratio
            result.detection_ratio = f"{result.malicious}/{result.total_engines}"

            # Extract general classification if available
            popular_threat = attrs.get("popular_threat_classification", {})
            if popular_threat:
                label = popular_threat.get("suggested_threat_label", "")
                result.threat_label = label

            # Link to full VT report
            result.permalink = (
                f"https://www.virustotal.com/gui/file/{data.get('data', {}).get('id', '')}"
            )

            # Log the result
            if result.malicious > 0:
                logger.warning(
                    f"🔴 VirusTotal: {result.detection_ratio} engines classify it as malicious"
                    + (f" ({result.threat_label})" if result.threat_label else "")
                )
            else:
                logger.info(f"✅ VirusTotal: Clean ({result.detection_ratio})")

        except Exception as e:
            result.error = f"Error parsing VT response: {e}"
            logger.error(f"❌ {result.error}")

        return result
