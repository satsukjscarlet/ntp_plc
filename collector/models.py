from __future__ import annotations

from dataclasses import dataclass
from typing import Any


@dataclass(frozen=True)
class PlcTagValue:
    source_key: str
    address: str | None
    source_name: str
    data_type: str | None
    display_format: str | None
    value: Any
    raw_value: str | None
    quality: str = "GOOD"
    comment: str | None = None

    def to_api_dict(self, collected_at: str) -> dict[str, Any]:
        return {
            "source_key": self.source_key,
            "address": self.address,
            "source_name": self.source_name,
            "data_type": self.data_type,
            "display_format": self.display_format,
            "value": self.value,
            "raw_value": self.raw_value,
            "quality": self.quality,
            "comment": self.comment,
            "collected_at": collected_at,
        }
