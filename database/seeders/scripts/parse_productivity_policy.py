#!/usr/bin/env python3
"""Parse Oceanic Productivity Policies docx into seeder data structures."""

from __future__ import annotations

import json
import re
import sys
import xml.etree.ElementTree as ET
import zipfile
from html import escape
from pathlib import Path

W = "{http://schemas.openxmlformats.org/wordprocessingml/2006/main}"
DOCX = Path(r"c:\Users\Kolinio\Documents\Projects\HR\2026 05 - Oceanic Productivity Policies.docx")
OUT_JSON = Path(__file__).resolve().parents[2] / "data" / "oceanic_productivity_policies.json"


def para_style(p) -> str | None:
    p_pr = p.find(f"{W}pPr")
    if p_pr is None:
        return None
    p_style = p_pr.find(f"{W}pStyle")
    if p_style is None:
        return None
    return p_style.get(f"{W}val")


def para_text(p) -> str:
    texts: list[str] = []
    for t in p.iter(f"{W}t"):
        if t.text:
            texts.append(t.text)
        if t.tail:
            texts.append(t.tail)
    return "".join(texts).strip()


def load_paragraphs() -> list[dict[str, str]]:
    with zipfile.ZipFile(DOCX) as zf:
        root = ET.fromstring(zf.read("word/document.xml"))

    paras: list[dict[str, str]] = []
    for p in root.iter(f"{W}p"):
        text = para_text(p)
        if not text:
            continue
        paras.append({"style": para_style(p) or "Normal", "text": text})
    return paras


def add_para(target: dict, text: str, style: str) -> None:
    if style in ("BulletList", "NumberedList"):
        target["paragraphs"].append({"type": "li", "text": text})
    elif style in ("ImportantNote", "Disciplinary", "NormalWeb"):
        target["paragraphs"].append({"type": "note", "text": text, "style": style})
    elif style.startswith("Heading"):
        target["paragraphs"].append({"type": "h", "text": text, "level": style})
    else:
        target["paragraphs"].append({"type": "p", "text": text})


def parse_sections(paras: list[dict[str, str]]) -> list[dict]:
    start = next(i for i, p in enumerate(paras) if p["style"] == "Heading1")

    sections: list[dict] = []
    current_section: dict | None = None
    current_sub: dict | None = None

    def flush_sub() -> None:
        nonlocal current_sub
        if current_sub is not None and current_section is not None:
            current_section["sub_clauses"].append(current_sub)
        current_sub = None

    def flush_section() -> None:
        nonlocal current_section
        flush_sub()
        if current_section is not None:
            sections.append(current_section)
        current_section = None

    for p in paras[start:]:
        style, text = p["style"], p["text"]
        if style == "Heading1":
            flush_section()
            current_section = {"title": text, "intro": {"paragraphs": []}, "sub_clauses": []}
            current_sub = None
        elif style == "Heading2" and current_section is not None:
            flush_sub()
            current_sub = {"title": text, "paragraphs": []}
        elif current_sub is not None:
            add_para(current_sub, text, style)
        elif current_section is not None:
            add_para(current_section["intro"], text, style)

    flush_section()
    return sections


def paragraphs_to_html(block: dict) -> str:
    parts: list[str] = []
    list_open = False

    def close_list() -> None:
        nonlocal list_open
        if list_open:
            parts.append("</ul>")
            list_open = False

    for para in block.get("paragraphs", []):
        ptype = para["type"]
        text = escape(para["text"])

        if ptype == "li":
            if not list_open:
                parts.append("<ul>")
                list_open = True
            parts.append(f"<li>{text}</li>")
            continue

        close_list()

        if ptype == "note":
            css = "important-note" if para.get("style") == "ImportantNote" else "policy-note"
            parts.append(f'<p class="{css}"><strong>{text}</strong></p>')
        elif ptype == "h":
            level = para.get("level", "Heading3")
            num = re.search(r"(\d+)", level)
            tag = f"h{min(int(num.group(1)) if num else 3, 6)}"
            parts.append(f"<{tag}>{text}</{tag}>")
        else:
            parts.append(f"<p>{text}</p>")

    close_list()
    return "".join(parts)


def word_count(html: str) -> int:
    text = re.sub(r"<[^>]+>", " ", html)
    text = re.sub(r"\s+", " ", text).strip()
    return len(text.split()) if text else 0


def main() -> int:
    paras = load_paragraphs()
    sections = parse_sections(paras)

    payload = {
        "policy": {
            "name": "Oceanic Productivity Policies",
            "abbreviation": "OPP",
            "slug": "productivity-policies",
            "version_label": "May 2026",
            "effective_date": "2026-04-25",
            "numbering_scheme": {
                "section": {"style": "decimal", "separator": ".", "start": "1"},
                "clause": {"style": "decimal", "separator": ".", "start": "1", "inherit_preview": "1.1"},
                "sub_clause": {"style": "decimal", "separator": ".", "prefix": "", "start": "1"},
            },
        },
        "sections": [],
    }

    warnings: list[str] = []

    for idx, section in enumerate(sections, start=1):
        intro_html = paragraphs_to_html(section["intro"])
        intro_wc = word_count(intro_html)
        if intro_wc > 3000:
            warnings.append(f"Section {idx} intro exceeds 3000 words ({intro_wc}): {section['title']}")

        section_row = {
            "sort_order": idx,
            "number_prefix": str(idx),
            "numbering_style": "decimal",
            "number_separator": ".",
            "title": section["title"],
            "body": intro_html,
            "sub_clauses": [],
        }

        for sub_idx, sub in enumerate(section["sub_clauses"], start=1):
            body = paragraphs_to_html(sub)
            wc = word_count(body)
            if wc > 3000:
                warnings.append(
                    f"Section {idx}.{sub_idx} exceeds 3000 words ({wc}): {sub['title']}"
                )
            section_row["sub_clauses"].append(
                {
                    "sort_order": sub_idx,
                    "number_prefix": str(sub_idx),
                    "numbering_style": "decimal",
                    "number_separator": ".",
                    "title": sub["title"],
                    "body": body,
                }
            )

        payload["sections"].append(section_row)

    OUT_JSON.parent.mkdir(parents=True, exist_ok=True)
    OUT_JSON.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")

    print(f"Wrote {OUT_JSON}")
    print(f"Sections: {len(sections)}")
    print(f"Sub-clauses: {sum(len(s['sub_clauses']) for s in sections)}")
    for w in warnings:
        print(f"WARNING: {w}")

    return 0


if __name__ == "__main__":
    sys.exit(main())
