import json
import os
import re
import sys

import pdfplumber


def log_message(message):
    script_dir = os.path.dirname(os.path.abspath(__file__))
    log_path = os.path.join(script_dir, "check_scan_debug.txt")
    with open(log_path, "a", encoding="utf-8") as log_file:
        log_file.write(message + "\n")


def parse_pdf(path):
    rows = []
    log_message(f"Starting scan for: {path}")

    with pdfplumber.open(path) as pdf:
        log_message(f"Pages detected: {len(pdf.pages)}")
        claim = {}

        for page in pdf.pages[1:]:
            page_height = page.height
            cutoff_y = page_height * 0.3

            cropped = page.crop((0, cutoff_y, page.width, page_height))
            text = cropped.extract_text()

            if not text:
                log_message("No text extracted for page.")
                continue

            lines = text.split("\n")

            for line in lines:
                if line.startswith(("Provider:", "Post Max Copay")):
                    continue

                if line.startswith("Patient:"):
                    log_message(f"Patient line: {line}")
                    claim = {}
                    raw_name = line.replace("Patient:", "").split("Member")[0].strip()
                    parts = raw_name.split()

                    claim["name"] = " ".join([p for p in parts if len(p) > 1])

                    members = re.findall(r"Member#:\s*([0-9]+)", line)
                    if len(members) >= 2:
                        claim["mrn"] = members[1]
                    elif members:
                        claim["mrn"] = members[0]

                    if "Claim#:" in line:
                        claim["claim_number"] = line.split("Claim#:")[1].replace(" ", "").strip()

                    continue

                if re.match(r"^\d{2}/\d{2}/\d{4}-\d{2}/\d{2}/\d{4}\b", line):
                    log_message(f"Service line: {line}")
                    tokens = line.split()

                    date_range = tokens[0]
                    service_date = date_range.split("-")[0]

                    numbers = re.findall(r"\d+\.\d{2}", line)
                    billed = numbers[0] if len(numbers) > 0 else ""
                    paid = numbers[-2] if len(numbers) >= 2 else ""

                    money_start_idx = None
                    for i, t in enumerate(tokens):
                        if re.match(r"^\d+\.\d{2}$", t):
                            money_start_idx = i
                            break

                    header = tokens[:money_start_idx] if money_start_idx is not None else tokens
                    n_items = len(header)

                    service_code = header[1] if n_items >= 2 else ""
                    modifier = ""
                    units = ""

                    if n_items >= 4:
                        modifier = header[2]
                        units = header[3]
                    elif n_items == 3:
                        if paid in ("", "0.00"):
                            modifier = header[2]
                            units = ""
                        else:
                            modifier = ""
                            units = header[2]

                    rows.append(
                        {
                            "name": claim.get("name", ""),
                            "mrn": claim.get("mrn", ""),
                            "claim_number": claim.get("claim_number", ""),
                            "service_date": service_date,
                            "service_code": service_code,
                            "modifier": modifier,
                            "units": units,
                            "billed": billed,
                            "paid": paid,
                        }
                    )

    log_message(f"Total rows parsed: {len(rows)}")
    return rows


if __name__ == "__main__":
    if len(sys.argv) < 2:
        log_message("No file path provided to scanner.")
        print("[]")
        sys.exit(0)

    file_path = sys.argv[1]
    data = parse_pdf(file_path)
    log_message("Scan completed.")
    print(json.dumps(data))
