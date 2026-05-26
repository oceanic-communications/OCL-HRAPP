#!/usr/bin/env python3
"""Parse an induction policy docx into JSON seeder data."""

from __future__ import annotations

import argparse
import json
import re
import sys
import xml.etree.ElementTree as ET
import zipfile
from html import escape
from pathlib import Path

W = "{http://schemas.openxmlformats.org/wordprocessingml/2006/main}"

LIST_STYLES = {"BulletList", "NumberedList", "Compact"}
NOTE_STYLES = {"ImportantNote", "Disciplinary", "NormalWeb"}


def para_style(p) -> str:
    p_pr = p.find(f"{W}pPr")
    if p_pr is None:
        return "Normal"
    p_style = p_pr.find(f"{W}pStyle")
    if p_style is None:
        return "Normal"
    return p_style.get(f"{W}val") or "Normal"


def para_text(p) -> str:
    texts: list[str] = []
    for t in p.iter(f"{W}t"):
        if t.text:
            texts.append(t.text)
        if t.tail:
            texts.append(t.tail)
    return "".join(texts).strip()


def load_paragraphs(docx: Path) -> list[dict[str, str]]:
    with zipfile.ZipFile(docx) as zf:
        root = ET.fromstring(zf.read("word/document.xml"))

    paras: list[dict[str, str]] = []
    for p in root.iter(f"{W}p"):
        text = para_text(p)
        if not text:
            continue
        paras.append({"style": para_style(p), "text": text})
    return paras


def add_para(target: dict, text: str, style: str) -> None:
    if style in LIST_STYLES:
        target["paragraphs"].append({"type": "li", "text": text})
    elif style in NOTE_STYLES:
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


def build_payload(
    sections: list[dict],
    *,
    name: str,
    abbreviation: str,
    slug: str,
    version_label: str,
    effective_date: str | None,
    seed_marker_section_title: str,
) -> dict:
    payload = {
        "policy": {
            "name": name,
            "abbreviation": abbreviation,
            "slug": slug,
            "version_label": version_label,
            "effective_date": effective_date,
            "seed_marker_section_title": seed_marker_section_title,
            "numbering_scheme": {
                "section": {"style": "decimal", "separator": ".", "start": "1"},
                "clause": {"style": "decimal", "separator": ".", "start": "1", "inherit_preview": "1.1"},
                "sub_clause": {"style": "decimal", "separator": ".", "prefix": "", "start": "1"},
            },
        },
        "sections": [],
    }

    for idx, section in enumerate(sections, start=1):
        intro_html = paragraphs_to_html(section["intro"])
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
            section_row["sub_clauses"].append(
                {
                    "sort_order": sub_idx,
                    "number_prefix": str(sub_idx),
                    "numbering_style": "decimal",
                    "number_separator": ".",
                    "title": sub["title"],
                    "body": paragraphs_to_html(sub),
                }
            )

        payload["sections"].append(section_row)

    return payload


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("docx", type=Path, help="Source Word document")
    parser.add_argument("output", type=Path, help="Output JSON path")
    parser.add_argument("--name", required=True)
    parser.add_argument("--abbreviation", required=True)
    parser.add_argument("--slug", required=True)
    parser.add_argument("--version-label", required=True)
    parser.add_argument("--effective-date", default=None)
    parser.add_argument("--seed-marker", required=True, help="Section title indicating full seed")
    args = parser.parse_args()

    if not args.docx.is_file():
        print(f"Docx not found: {args.docx}", file=sys.stderr)
        return 1

    sections = parse_sections(load_paragraphs(args.docx))
    payload = build_payload(
        sections,
        name=args.name,
        abbreviation=args.abbreviation,
        slug=args.slug,
        version_label=args.version_label,
        effective_date=args.effective_date,
        seed_marker_section_title=args.seed_marker,
    )

    warnings: list[str] = []
    for section in payload["sections"]:
        intro_wc = word_count(section["body"])
        if intro_wc > 3000:
            warnings.append(f"Section {section['sort_order']} intro exceeds 3000 words ({intro_wc}): {section['title']}")
        for sub in section["sub_clauses"]:
            wc = word_count(sub["body"])
            if wc > 3000:
                warnings.append(
                    f"Section {section['sort_order']}.{sub['sort_order']} exceeds 3000 words ({wc}): {sub['title']}"
                )

    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")

    sub_count = sum(len(s["sub_clauses"]) for s in sections)
    print(f"Wrote {args.output}")
    print(f"Sections: {len(sections)}")
    print(f"Sub-clauses: {sub_count}")
    for warning in warnings:
        print(f"WARNING: {warning}")

    return 0


if __name__ == "__main__":
    sys.exit(main())
