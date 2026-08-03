from __future__ import annotations

import json
import os
import re
import sys
from dataclasses import dataclass, asdict
from pathlib import Path
from typing import Iterable

import requests
from bs4 import BeautifulSoup
from dotenv import load_dotenv


ADDRESS_PATTERN = re.compile(r"^%[IQM][A-Z]*\d+(?:\.\d+)?$", re.IGNORECASE)
TYPE_PATTERN = re.compile(
    r"^(BOOL|BYTE|WORD|DWORD|INT|DINT|REAL|LREAL|STRING)$",
    re.IGNORECASE,
)


@dataclass
class PlcTag:
    address: str | None
    source_name: str
    portal_name: str
    data_type: str | None = None
    unit: str | None = None


def normalize_text(value: str) -> str:
    return re.sub(r"\s+", " ", value).strip()


def parse_tags(html: str) -> list[PlcTag]:
    soup = BeautifulSoup(html, "lxml")
    output: dict[str, PlcTag] = {}

    for row in soup.select("table tr"):
        cells = [normalize_text(cell.get_text(" ", strip=True))
                 for cell in row.select("th, td")]

        cells = [cell for cell in cells if cell]

        if len(cells) < 2:
            continue

        address = next(
            (cell for cell in cells if ADDRESS_PATTERN.match(cell)),
            None,
        )

        data_type = next(
            (cell.upper() for cell in cells if TYPE_PATTERN.match(cell)),
            None,
        )

        source_name = None

        for cell in cells:
            if cell in {address, data_type}:
                continue

            if cell.lower() in {
                "name",
                "address",
                "type",
                "value",
                "comment",
                "unit",
            }:
                continue

            source_name = cell
            break

        if not source_name and not address:
            continue

        source_name = source_name or address

        tag = PlcTag(
            address=address,
            source_name=source_name,
            portal_name=source_name,
            data_type=data_type,
        )

        key = f"{address or 'NAME'}|{source_name}"
        output[key] = tag

    return list(output.values())


def get_required_env(name: str) -> str:
    value = os.getenv(name, "").strip()

    if not value:
        raise RuntimeError(f"Missing environment variable: {name}")

    return value


def fetch_portal(url: str) -> str:
    response = requests.get(url, timeout=30)
    response.raise_for_status()
    response.encoding = response.apparent_encoding or "utf-8"
    return response.text


def send_tags(api_url: str, token: str, tags: Iterable[PlcTag]) -> dict:
    payload = {
        "tags": [asdict(tag) for tag in tags],
    }

    response = requests.post(
        api_url,
        headers={
            "Authorization": f"Bearer {token}",
            "Accept": "application/json",
        },
        json=payload,
        timeout=60,
    )

    if not response.ok:
        raise RuntimeError(
            f"Laravel API HTTP {response.status_code}: {response.text}"
        )

    return response.json()


def main() -> int:
    project_root = Path(__file__).resolve().parents[1]
    load_dotenv(project_root / ".env")

    portal_url = get_required_env("PLC_M62_PORTAL_URL")
    api_url = os.getenv(
        "PLC_SYNC_TAGS_API_URL",
        "http://127.0.0.1:8000/api/collector/sync-tags",
    ).strip()
    token = get_required_env("PLC_COLLECTOR_TOKEN_M62")

    print(f"Portal: {portal_url}")
    print(f"API: {api_url}")

    html = fetch_portal(portal_url)
    tags = parse_tags(html)

    if not tags:
        raise RuntimeError("No tags found in PLC Portal HTML")

    print(f"Parsed tags: {len(tags)}")

    result = send_tags(api_url, token, tags)

    print(json.dumps(result, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        raise SystemExit(1)
