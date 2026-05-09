# Requirements: pip install fpdf2 matplotlib
import json
import os
import sys
import argparse
from datetime import datetime
import matplotlib.pyplot as plt
from fpdf import FPDF, XPos, YPos

# Ensure charts look good
plt.style.use('ggplot')

class PDFReport(FPDF):
    def __init__(self, file_name):
        super().__init__()
        self.file_name = file_name
        self.set_auto_page_break(auto=True, margin=15)
        
        # Try to load Windows Arial TTF for better unicode support
        self.has_ttf = False
        font_path = "C:\\Windows\\Fonts\\arial.ttf"
        font_path_b = "C:\\Windows\\Fonts\\arialbd.ttf"
        font_path_i = "C:\\Windows\\Fonts\\ariali.ttf"
        if os.path.exists(font_path):
            try:
                self.add_font("Arial", "", font_path)
                if os.path.exists(font_path_b): self.add_font("Arial", "B", font_path_b)
                if os.path.exists(font_path_i): self.add_font("Arial", "I", font_path_i)
                self.has_ttf = True
            except Exception:
                self.has_ttf = False
                
    def safe_text(self, text):
        if not text: return ""
        text = str(text)
        if not self.has_ttf:
            return text.encode('latin-1', 'replace').decode('latin-1')
        return text
        
    def header(self):
        self.set_font("Arial" if self.has_ttf else "Helvetica", "B", 10)
        self.set_text_color(100, 100, 100)
        self.cell(0, 10, self.safe_text(f"Malware Intelligence Report | Target: {self.file_name}"), border=False, new_x=XPos.LMARGIN, new_y=YPos.NEXT, align="R")
        self.line(10, 20, 200, 20)
        self.ln(5)

    def footer(self):
        self.set_y(-15)
        self.set_font("Arial" if self.has_ttf else "Helvetica", "I", 8)
        self.set_text_color(150, 150, 150)
        self.cell(0, 10, f"Page {self.page_no()}", align="C")

    def chapter_title(self, title):
        self.set_font("Arial" if self.has_ttf else "Helvetica", "B", 16)
        self.set_text_color(0, 51, 102)
        self.cell(0, 10, self.safe_text(title), new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(2)

    def section_title(self, title):
        self.set_font("Arial" if self.has_ttf else "Helvetica", "B", 12)
        self.set_text_color(40, 40, 40)
        self.cell(0, 8, self.safe_text(title), new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(1)

    def normal_text(self, text, style=""):
        self.set_font("Arial" if self.has_ttf else "Helvetica", style, 10)
        self.set_text_color(60, 60, 60)
        self.multi_cell(0, 6, self.safe_text(text), new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(1)

    def kv_pair(self, key, value):
        self.set_font("Arial" if self.has_ttf else "Helvetica", "B", 10)
        self.set_text_color(0, 0, 0)
        self.cell(40, 6, self.safe_text(f"{key}:"), border=False, new_x=XPos.RIGHT, new_y=YPos.TOP)
        self.set_font("Arial" if self.has_ttf else "Helvetica", "", 10)
        self.set_text_color(50, 50, 50)
        self.multi_cell(0, 6, self.safe_text(str(value)), new_x=XPos.LMARGIN, new_y=YPos.NEXT)

def generate_vt_chart(vt_percent, output_path):
    plt.figure(figsize=(4, 4))
    sizes = [vt_percent, max(0, 100 - vt_percent)]
    colors = ['#d9534f', '#5cb85c'] # Red for malicious, green for clean
    labels = ['Malicious', 'Clean/Undetected']
    
    plt.pie(sizes, labels=labels, colors=colors, autopct='%1.1f%%', startangle=140, 
            wedgeprops={'edgecolor': 'w', 'linewidth': 2})
    plt.title("VirusTotal Engines", fontweight="bold")
    plt.tight_layout()
    plt.savefig(output_path, dpi=150)
    plt.close()

def generate_entropy_chart(sections, output_path):
    if not sections:
        return False
        
    names = [sec["name"] for sec in sections]
    entropies = [sec["entropy"] for sec in sections]
    colors = ['#d9534f' if e > 7.0 else '#5bc0de' for e in entropies]

    plt.figure(figsize=(6, 4))
    plt.bar(names, entropies, color=colors, edgecolor='black')
    plt.axhline(y=7.0, color='r', linestyle='--', label='High Entropy (>7.0)')
    plt.ylabel('Entropy')
    plt.title('PE Section Entropy', fontweight="bold")
    plt.ylim(0, 8.5)
    plt.legend()
    plt.tight_layout()
    plt.savefig(output_path, dpi=150)
    plt.close()
    return True

def build_pdf(json_path):
    print(f"[*] Reading analysis data from: {json_path}")
    try:
        with open(json_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception as e:
        print(f"[-] Error reading JSON: {e}")
        return

    # Extract metadata
    meta = data.get("analysis_metadata", {})
    file_name = meta.get("file_name", "Unknown_File.exe")
    risk_info = data.get("risk_assessment", {})
    risk_level = risk_info.get("overall_risk_level", "UNKNOWN")
    threat_info = data.get("threat_classification", {})
    
    pdf = PDFReport(file_name)
    pdf.add_page()
    
    # Title Page
    pdf.set_font("Arial" if pdf.has_ttf else "Helvetica", "B", 22)
    if "HIGH" in risk_level:
        pdf.set_text_color(178, 34, 34) # Firebrick red
    elif "MEDIUM" in risk_level:
        pdf.set_text_color(204, 119, 34) # Ochre orange
    else:
        pdf.set_text_color(0, 51, 102) # Dark blue
        
    pdf.cell(0, 15, "EXECUTIVE THREAT INTELLIGENCE REPORT", new_x=XPos.LMARGIN, new_y=YPos.NEXT, align="C")
    
    pdf.set_font("Arial" if pdf.has_ttf else "Helvetica", "B", 14)
    pdf.set_text_color(50, 50, 50)
    pdf.cell(0, 10, pdf.safe_text(f"File: {file_name}"), new_x=XPos.LMARGIN, new_y=YPos.NEXT, align="C")
    
    pdf.set_font("Arial" if pdf.has_ttf else "Helvetica", "", 11)
    pdf.set_text_color(100, 100, 100)
    analysis_time = meta.get('analysis_time', datetime.now().strftime('%Y-%m-%d %H:%M:%S'))
    pdf.cell(0, 10, pdf.safe_text(f"Date: {analysis_time} | Risk Level: {risk_level}"), new_x=XPos.LMARGIN, new_y=YPos.NEXT, align="C")
    pdf.ln(10)

    # Executive Summary (Story-driven narrative)
    pdf.chapter_title("Executive Summary")
    primary_type = threat_info.get("primary_type", "Unknown Malware Family")
    vt_ratio = risk_info.get("virustotal_detection_ratio", "N/A")
    capabilities = ", ".join(threat_info.get("capabilities", [])) if threat_info.get("capabilities") else "a range of suspicious system modifications"
    
    story = (f"On {analysis_time}, an automated triage and behavioral analysis was conducted on the binary '{file_name}'. "
             f"Based on static artifacts and inferred behavior, the file is classified primarily as a {primary_type}. "
             f"The overall risk level of this binary is assessed as {risk_level}. This is strongly corroborated by its "
             f"VirusTotal detection ratio of {vt_ratio} engines flagging it as malicious.\n\n"
             f"The malware exhibits capabilities indicative of {capabilities}. ")
             
    if data.get("packing", {}).get("is_packed"):
        packer_name = data.get("packing", {}).get("packer", "an unknown packer")
        story += f"Furthermore, the binary is obfuscated using {packer_name}. This is a common evasion technique aimed at hindering static analysis and hiding malicious payloads."
        
    pdf.normal_text(story)
    pdf.ln(5)

    # Basic File Identity
    pdf.chapter_title("File Identification")
    hashes = data.get("hashes", {})
    pdf.kv_pair("MD5", hashes.get("md5", "N/A"))
    pdf.kv_pair("SHA1", hashes.get("sha1", "N/A"))
    pdf.kv_pair("SHA256", hashes.get("sha256", "N/A"))
    pdf.kv_pair("File Size", data.get("file_size", "N/A") if "file_size" in data else "Check Original Report")
    pdf.kv_pair("Packed", str(data.get("packing", {}).get("is_packed", False)))
    pdf.ln(5)

    # Visuals Generation
    temp_dir = os.path.dirname(os.path.abspath(json_path))
    vt_chart_path = os.path.join(temp_dir, f"vt_chart_{os.getpid()}.png")
    entropy_chart_path = os.path.join(temp_dir, f"entropy_chart_{os.getpid()}.png")
    
    print("[*] Generating charts...")
    vt_percent = risk_info.get("virustotal_detection_percent", 0)
    generate_vt_chart(vt_percent, vt_chart_path)
    
    sections = data.get("entropy_analysis", {}).get("sections", [])
    has_entropy_chart = generate_entropy_chart(sections, entropy_chart_path)

    # Visuals Layout
    pdf.add_page()
    pdf.chapter_title("Analysis Metrics & Artifacts")
    y_before_images = pdf.get_y()
    
    # Place VT Chart
    pdf.image(vt_chart_path, x=20, y=y_before_images, w=80)
    
    # Place Entropy Chart
    if has_entropy_chart and os.path.exists(entropy_chart_path):
        pdf.image(entropy_chart_path, x=110, y=y_before_images, w=90)
        
    pdf.set_y(y_before_images + 80) # Move cursor below images
    
    # Imports & Suspicious APIs
    import_analysis = data.get("import_analysis", {})
    if import_analysis.get("visible_apis") or import_analysis.get("hidden_apis_from_strings"):
        pdf.section_title("API & Import Analysis")
        if import_analysis.get("note"):
            pdf.normal_text(import_analysis["note"], style="I")
            pdf.ln(2)
            
        if import_analysis.get("visible_apis"):
            pdf.normal_text("Notable Visible APIs:", style="B")
            for api in import_analysis["visible_apis"]:
                pdf.normal_text(f"- {api.get('api', 'Unknown')} ({api.get('dll', '')}): {api.get('role', '')}")
                
        if import_analysis.get("hidden_apis_from_strings"):
            pdf.ln(2)
            pdf.normal_text("Hidden/Dynamically Resolved APIs (inferred from strings):", style="B")
            for api in import_analysis["hidden_apis_from_strings"]:
                pdf.normal_text(f"- {api.get('api', 'Unknown')}: {api.get('likely_use', '')}")
    
    pdf.ln(5)

    # Behavioral Insights
    pdf.add_page()
    pdf.chapter_title("Behavioral Insights & TTPs")
    insights = data.get("behavioral_insights", [])
    
    if not insights:
        pdf.normal_text("No specific behavioral insights were extracted by the LLM.")
    else:
        for insight in insights:
            sev = str(insight.get('severity', 'INFO')).upper()
            pdf.set_font("Arial" if pdf.has_ttf else "Helvetica", "B", 11)
            if sev in ["CRITICAL", "HIGH"]:
                pdf.set_text_color(178, 34, 34)
            elif sev == "MEDIUM":
                pdf.set_text_color(204, 119, 34)
            else:
                pdf.set_text_color(0, 0, 0)
                
            title = f"[{sev}] {insight.get('title', 'Unknown Behavior')}"
            pdf.cell(0, 8, pdf.safe_text(title), new_x=XPos.LMARGIN, new_y=YPos.NEXT)
            
            pdf.normal_text(f"Category: {insight.get('category', 'N/A')}", style="B")
            pdf.normal_text(insight.get('detail', ''))
            
            mitre = insight.get('mitre_attack', {})
            if isinstance(mitre, dict):
                mitre_str = f"{mitre.get('tactic', 'Unknown Tactic')} - {mitre.get('technique', 'Unknown Technique')}"
                pdf.normal_text(f"MITRE ATT&CK: {mitre_str}", style="I")
            elif isinstance(mitre, str):
                pdf.normal_text(f"MITRE ATT&CK: {mitre}", style="I")
                
            pdf.ln(4)

    # IOCs
    pdf.add_page()
    pdf.chapter_title("Indicators of Compromise (IOCs)")
    iocs = data.get("iocs", {})
    
    has_any_ioc = False
    
    for category, items in iocs.items():
        if items and isinstance(items, list):
            has_any_ioc = True
            # Format category name nicely (e.g., "ip_addresses" -> "Ip Addresses")
            display_cat = str(category).replace('_', ' ').title()
            pdf.section_title(display_cat)
            for item in items:
                pdf.normal_text(f"- {item}")
            pdf.ln(3)
            
    if not has_any_ioc:
        pdf.normal_text("No indicators of compromise were identified.")
    
    pdf.ln(5)
    
    # Recommended Actions
    pdf.chapter_title("Recommended Actions")
    actions = threat_info.get("recommended_actions", {})
    
    pdf.section_title("For Malware Analysts (Reverse Engineering / Sandboxing)")
    analyst_actions = actions.get("analyst", [])
    if analyst_actions:
        for action in analyst_actions:
            pdf.normal_text(f"- {action}")
    else:
        pdf.normal_text("No specific recommendations provided.")
            
    pdf.ln(4)
    pdf.section_title("For Defenders (SOC / IR Teams)")
    defender_actions = actions.get("defender", [])
    if defender_actions:
        for action in defender_actions:
            pdf.normal_text(f"- {action}")
    else:
        pdf.normal_text("No specific recommendations provided.")

    # Cleanup temp images
    try:
        if os.path.exists(vt_chart_path): os.remove(vt_chart_path)
        if os.path.exists(entropy_chart_path): os.remove(entropy_chart_path)
    except:
        pass

    # Save PDF
    out_name = os.path.splitext(os.path.basename(json_path))[0] + "_report.pdf"
    out_path = os.path.join(temp_dir, out_name)
    pdf.output(out_path)
    print(f"[+] Professional PDF report generated successfully: {out_path}")

def main():
    parser = argparse.ArgumentParser(description="Generate PDF from Malware Analysis JSON")
    parser.add_argument("-f", "--file", required=True, help="Path to the analysis JSON file")
    args = parser.parse_args()
    
    if not os.path.exists(args.file):
        print(f"[-] File not found: {args.file}")
        sys.exit(1)
        
    build_pdf(args.file)

if __name__ == "__main__":
    main()
