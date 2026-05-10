# Project Trapix: AI-Powered Malware Analysis Platform

## Overview
Trapix is a comprehensive malware analysis and triage platform that combines traditional static analysis tools with a hybrid AI expert system. It is designed to take files or directories, perform deep inspection, and generate professional reports for malware analysts.

## Core Components

### 1. Web Application (`Trapix/Trapix`)
- **Technology**: Laravel (PHP).
- **Purpose**: User management, file upload orchestration, job queueing, and result presentation.
- **Key Files**:
    - `app/Services/AnalysisService.php`: Orchestrates the upload → store → dispatch → result cycle.
    - `app/Services/PythonBridgeService.php`: The bridge that executes the Python analysis engine.
    - `app/Services/QuotaService.php`: Manages usage limits for guest and registered users.
    - `app/Http/Controllers/AnalysisController.php`: Handles API requests for job creation and status tracking.

### 2. Analysis Engine (`Tools`)
- **Technology**: Python.
- **Purpose**: Performs the actual technical analysis of the files.
- **Key Files**:
    - `main.py`: Entry point for the analysis engine.
    - `core/file_analyzer.py`: Base analysis logic and metadata extraction.
    - `core/static_analyzer.py`: Deep PE file inspection.
    - `core/vt_client.py`: VirusTotal API integration.
    - `core/packer_detector.py`: Detects if a file is packed (e.g., UPX).
    - `core/upx_handler.py`: Handles unpacking for UPX-packed files.

### 3. AI Expert System (`AI Agent`)
- **Technology**: Python + Google AI Studio (Gemini).
- **Purpose**: Provides behavioral insights and high-level threat classification.
- **Key Files**:
    - `malware_analyzer.py`: Uses deterministic rules (Expert System) and falls back to Gemini for complex analysis (strings/behavior).
    - `pdf_generator.py`: Generates professional malware analysis reports.

---

## Workflow Implementation

### 1. Analysis Lifecycle
1. **Input**: User uploads a file or a directory (or a ZIP archive which is automatically extracted).
2. **Metadata Extraction**:
    - File Name, Path, Size, and Type (e.g., PE file).
    - Job ID and Analysis Timestamp are generated.
3. **Static Analysis**:
    - **Hashes**: SHA256, SHA1, MD5 (Default is all, customizable).
    - **Entropy**: Calculated per section to detect encryption/packing.
    - **PE Info**: Machine type, Timestamp, Entry Point, Subsystem, DLL status, Imphash.
    - **Sections**: Detailed breakdown (VAddr, VSize, RSize, Characteristics, Entropy).
    - **Imports/Exports**: Full list of functions, ordinals, and modules.
    - **API Detection**: Flags suspicious Windows APIs commonly used by malware.
    - **IoC Extraction**: URLs, IP Addresses, Registry Keys, Emails, Base64 blobs, and referenced files.
    - **Strings**: ASCII and Unicode extraction with total counts.
4. **External Services**:
    - **VirusTotal**: Submits hashes to VT for detection ratios and verdicts (Malicious/Clean/Suspicious).
5. **Packing & Unpacking**:
    - Detects packers (Packer name, signatures, methods, confidence).
    - **UPX Support**: Automatically unpacks UPX-packed files for deeper analysis.
6. **Scoring**:
    - Generates a risk score and level based on IAT, VT ratio, Packed status, API count, and IoCs.
7. **AI Integration**:
    - Resulting JSON is sent to the **AI Expert System**.
    - If the Expert System determines deep behavioral analysis is needed (or if configured), it passes context (strings, APIs) to **Google AI Studio (Gemini)**.
8. **Final Output**:
    - A comprehensive JSON result.
    - A professional PDF Report suitable for malware analysts.

### 2. User Access & Quota System
- **Guest Users**: 
    - Limited to **3 analyses**.
    - Redirected to login after reaching the limit.
- **Logged-in Users**:
    - Initial limit of **10 analyses**.
    - History of previous analyses is maintained.
    - Option to download previous reports.
    - Redirected to pricing/subscription after reaching the limit.

---

## How to Connect Components

### Laravel to Python Engine
The `PythonBridgeService` in Laravel invokes `Tools/main.py` via a subprocess:
```bash
python Tools/main.py --file [PATH_TO_FILE] --output-dir [WORK_DIR] --vt-key [API_KEY]
```
The engine outputs a `result.json` which the Laravel service reads and persists.

### Laravel to AI Agent
The `AiAnalysisService` in Laravel (or background job) triggers the AI analysis after the primary engine completes. It uses the `result.json` as input for `AI Agent/malware_analyzer.py`.

### AI Expert System Workflow
1. **Expert System**: Deterministically extracts known facts (hashes, entropy, VT score).
2. **LLM Analysis**: If possible, it calls Gemini with string/API context to infer "Intent" and "Capabilities".
3. **Merge**: Combines both into a final high-level analysis.

## History & Reports
Logged-in users can access their history via the `/dashboard/history` route. Reports are served via the `ReportController` which retrieves the generated PDF from the storage disk.
