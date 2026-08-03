from __future__ import annotations

import hashlib
import re
from typing import Any

from bs4 import BeautifulSoup, Tag

from models import PlcTagValue


FORMAT_TO_DATA_TYPE = {
    "BOOL": "BOOL",
    "FLOATING_POINT": "REAL",
    "DEC+/-": "DINT",
    "DEC": "UDINT",
    "HEX": "DWORD",
    "BIN": "DWORD",
    "CHARACTER": "CHAR",
    "UNICODE CHARACTER": "WCHAR",
    "TIME_OF_DAY": "TIME_OF_DAY",
    "TIME": "TIME",
}

ADDRESS_PATTERN = re.compile(
    r"^%(?:I|Q|M|IB|QB|MB|IW|QW|MW|ID|QD|MD|DB|T|C|PI|PQ)"
    r"[A-Z0-9_.:\[\]-]*$",
    re.IGNORECASE,
)


class SiemensWatchTableParser:
    """
    Collector V2 cho Siemens Watch Table.

    - Ưu tiên tr.var.
    - Nếu số dòng tr.var chưa đủ, đọc thêm các dòng dữ liệu khác trong tbody.
    - Không bỏ dòng khi Address trống.
    - Không phụ thuộc cứng vào đúng 4 cột.
    - source_key tương thích Laravel TagSynchronizationService:
        address:<address chữ thường>
        name:<sha256 tên chuẩn hóa>
    """

    def parse(self, html: str) -> list[PlcTagValue]:
        if not html or not html.strip():
            raise RuntimeError("HTML PLC Portal đang rỗng.")

        soup = BeautifulSoup(html, "lxml")
        table = soup.select_one("table#watch_table")

        if table is None:
            raise RuntimeError(
                'Không tìm thấy table#watch_table.'
            )

        rows = self._candidate_rows(table)

        if not rows:
            raise RuntimeError(
                "Không tìm thấy dòng dữ liệu trong watch_table."
            )

        values: list[PlcTagValue] = []
        seen_row_signatures: set[str] = set()
        source_key_counts: dict[str, int] = {}

        for row_index, row in enumerate(rows, start=1):
            parsed = self._parse_row(row, row_index)

            if parsed is None:
                continue

            signature = self._row_signature(parsed)

            if signature in seen_row_signatures:
                continue

            seen_row_signatures.add(signature)

            base_key = parsed.source_key
            occurrence = source_key_counts.get(base_key, 0) + 1
            source_key_counts[base_key] = occurrence

            if occurrence > 1:
                parsed = PlcTagValue(
                    source_key=f"{base_key}:row:{row_index}",
                    address=parsed.address,
                    source_name=parsed.source_name,
                    data_type=parsed.data_type,
                    display_format=parsed.display_format,
                    value=parsed.value,
                    raw_value=parsed.raw_value,
                    quality=parsed.quality,
                    comment=parsed.comment,
                )

            values.append(parsed)

        if not values:
            raise RuntimeError(
                "Có bảng Watch Table nhưng không đọc được tag."
            )

        return values

    def _candidate_rows(self, table: Tag) -> list[Tag]:
        primary = list(table.select("tr.var"))
        all_rows = list(table.select("tbody tr"))

        if not all_rows:
            all_rows = list(table.find_all("tr"))

        result: list[Tag] = []
        seen_ids: set[int] = set()

        for row in primary + all_rows:
            object_id = id(row)

            if object_id in seen_ids:
                continue

            seen_ids.add(object_id)

            if self._looks_like_data_row(row):
                result.append(row)

        return result

    def _looks_like_data_row(self, row: Tag) -> bool:
        cells = row.find_all("td", recursive=False)

        if not cells:
            cells = row.find_all("td")

        if not cells:
            return False

        row_text = self._clean_text(
            row.get_text(" ", strip=True)
        )

        if row_text is None:
            return False

        lowered = row_text.lower()

        header_markers = {
            "name address",
            "tên địa chỉ",
            "monitor value",
            "modify value",
        }

        if any(marker in lowered for marker in header_markers):
            return False

        return True

    def _parse_row(
        self,
        row: Tag,
        row_index: int,
    ) -> PlcTagValue | None:
        cells = row.find_all("td", recursive=False)

        if not cells:
            cells = row.find_all("td")

        if not cells:
            return None

        extracted = [
            self._extract_cell_value(cell)
            for cell in cells
        ]

        source_name = self._clean_name(
            extracted[0] if len(extracted) > 0 else None
        )

        address = self._normalize_address(
            extracted[1] if len(extracted) > 1 else None
        )

        if address and not ADDRESS_PATTERN.fullmatch(address):
            address = None

        if source_name is None and address is not None:
            source_name = address

        if source_name is None:
            source_name = self._fallback_name(
                row=row,
                row_index=row_index,
                extracted=extracted,
            )

        if source_name is None:
            return None

        format_cell = cells[2] if len(cells) > 2 else None
        value_cell = cells[3] if len(cells) > 3 else None

        display_format = self._read_display_format(
            format_cell
        )
        raw_value = self._read_monitor_value(
            value_cell
        )

        data_type = self._map_data_type(
            display_format=display_format,
            address=address,
            raw_value=raw_value,
        )

        value = self._normalize_value(
            raw_value,
            data_type,
        )

        comment = None

        if len(cells) >= 7:
            comment = self._clean_text(
                self._extract_cell_value(cells[6])
            )
        elif len(cells) >= 5:
            comment = self._clean_text(
                self._extract_cell_value(cells[-1])
            )

        return PlcTagValue(
            source_key=self._build_source_key(
                source_name=source_name,
                address=address,
            ),
            address=address,
            source_name=source_name,
            data_type=data_type,
            display_format=display_format,
            value=value,
            raw_value=raw_value,
            quality=(
                "GOOD"
                if raw_value is not None
                else "UNCERTAIN"
            ),
            comment=comment,
        )

    def _fallback_name(
        self,
        row: Tag,
        row_index: int,
        extracted: list[str | None],
    ) -> str | None:
        for attribute in (
            "data-name",
            "data-variable",
            "name",
            "id",
        ):
            value = self._clean_text(
                str(row.get(attribute, ""))
            )

            if value:
                return value

        for value in extracted:
            cleaned = self._clean_name(value)

            if cleaned:
                return cleaned

        return f"UNNAMED_TAG_{row_index}"

    def _extract_cell_value(
        self,
        cell: Tag | None,
    ) -> str | None:
        if cell is None:
            return None

        for selector, attribute in (
            ("input", "value"),
            ("button", "value"),
            ("option[selected]", "value"),
        ):
            element = cell.select_one(selector)

            if element is not None:
                raw = element.get(attribute)

                if raw is not None:
                    cleaned = self._clean_text(str(raw))

                    if cleaned:
                        return cleaned

        textarea = cell.find("textarea")

        if textarea is not None:
            cleaned = self._clean_text(
                textarea.get_text(" ", strip=True)
            )

            if cleaned:
                return cleaned

        selected = cell.select_one("option[selected]")

        if selected is not None:
            cleaned = self._clean_text(
                selected.get_text(" ", strip=True)
            )

            if cleaned:
                return cleaned

        return self._clean_text(
            cell.get_text(" ", strip=True)
        )

    def _read_display_format(
        self,
        cell: Tag | None,
    ) -> str | None:
        if cell is None:
            return None

        selected = cell.select_one(
            "select.type-selection option[selected]"
        )

        if selected is None:
            selected = cell.select_one(
                "select option[selected]"
            )

        if selected is None:
            selected = cell.select_one(
                "select option"
            )

        if selected is not None:
            return self._clean_text(
                selected.get_text(" ", strip=True)
            )

        return self._extract_cell_value(cell)

    def _read_monitor_value(
        self,
        cell: Tag | None,
    ) -> str | None:
        if cell is None:
            return None

        image = cell.find("img")

        if image is not None:
            marker = " ".join(
                [
                    str(image.get("src", "")),
                    str(image.get("alt", "")),
                    str(image.get("title", "")),
                ]
            ).lower()

            if "true" in marker or "on" in marker:
                return "true"

            if "false" in marker or "off" in marker:
                return "false"

        return self._extract_cell_value(cell)

    def _build_source_key(
        self,
        source_name: str,
        address: str | None,
    ) -> str:
        if address:
            return f"address:{address.lower()}"

        normalized = re.sub(
            r"\s+",
            " ",
            source_name.strip().lower(),
        )

        digest = hashlib.sha256(
            normalized.encode("utf-8")
        ).hexdigest()

        return f"name:{digest}"

    def _row_signature(
        self,
        value: PlcTagValue,
    ) -> str:
        return "|".join(
            [
                value.source_key,
                value.address or "",
                value.source_name,
                value.raw_value or "",
            ]
        )

    def _map_data_type(
        self,
        display_format: str | None,
        address: str | None,
        raw_value: str | None,
    ) -> str | None:
        if display_format:
            key = display_format.strip().upper()

            if key in FORMAT_TO_DATA_TYPE:
                return FORMAT_TO_DATA_TYPE[key]

        if address:
            upper = address.upper()

            if re.fullmatch(
                r"%(?:I|Q|M)\d+\.\d+",
                upper,
            ):
                return "BOOL"

            if re.fullmatch(
                r"%(?:ID|QD|MD)\d+",
                upper,
            ):
                if raw_value and re.fullmatch(
                    r"[+-]?\d+[.,]\d+(?:[eE][+-]?\d+)?",
                    raw_value.strip(),
                ):
                    return "REAL"

                return "DWORD"

            if re.fullmatch(
                r"%(?:IW|QW|MW)\d+",
                upper,
            ):
                return "WORD"

            if re.fullmatch(
                r"%(?:IB|QB|MB)\d+",
                upper,
            ):
                return "BYTE"

        return None

    def _normalize_value(
        self,
        raw_value: str | None,
        data_type: str | None,
    ) -> Any:
        if raw_value is None:
            return None

        value = raw_value.strip()

        if data_type == "BOOL":
            lowered = value.lower()

            if lowered in {"true", "1", "on"}:
                return True

            if lowered in {"false", "0", "off"}:
                return False

            return value

        if data_type in {"REAL", "LREAL"}:
            try:
                return float(value.replace(",", "."))
            except ValueError:
                return value

        if data_type in {
            "BYTE",
            "WORD",
            "DWORD",
            "INT",
            "DINT",
            "UDINT",
        }:
            try:
                return int(value)
            except ValueError:
                return value

        return value

    @staticmethod
    def _clean_name(
        value: str | None,
    ) -> str | None:
        if value is None:
            return None

        value = value.strip()
        value = re.sub(
            r'^[\"“”]+|[\"“”]+$',
            "",
            value,
        ).strip()

        return value or None

    @staticmethod
    def _normalize_address(
        value: str | None,
    ) -> str | None:
        if value is None:
            return None

        value = re.sub(
            r"\s+",
            "",
            value,
        ).upper().strip()

        return value or None

    @staticmethod
    def _clean_text(
        value: str | None,
    ) -> str | None:
        if value is None:
            return None

        value = re.sub(
            r"\s+",
            " ",
            value,
        ).strip()

        return value or None
