from __future__ import annotations

import os
from dataclasses import dataclass
from pathlib import Path

from dotenv import load_dotenv


BASE_DIR = Path(__file__).resolve().parent
load_dotenv(BASE_DIR / ".env")


def _get_bool(name: str, default: bool = False) -> bool:
    value = os.getenv(name)

    if value is None:
        return default

    return value.strip().lower() in {"1", "true", "yes", "on"}


def _get_int(name: str, default: int) -> int:
    value = os.getenv(name)

    if value is None or value.strip() == "":
        return default

    try:
        return int(value)
    except ValueError as exc:
        raise RuntimeError(f"{name} phải là số nguyên, hiện tại: {value}") from exc


@dataclass(frozen=True)
class Settings:
    plc_code: str
    plc_url: str
    laravel_base_url: str
    collector_token: str
    collection_interval_seconds: int
    request_timeout_seconds: int
    heartbeat_interval_seconds: int
    verify_ssl: bool
    http_user_agent: str
    log_level: str
    log_file: Path

    @property
    def plc_data_url(self) -> str:
        return f"{self.laravel_base_url.rstrip('/')}/api/collector/plc-data"

    @property
    def heartbeat_url(self) -> str:
        return f"{self.laravel_base_url.rstrip('/')}/api/collector/heartbeat"


def load_settings() -> Settings:
    plc_code = os.getenv("PLC_CODE", "").strip()
    plc_url = os.getenv("PLC_URL", "").strip()
    laravel_base_url = os.getenv("LARAVEL_BASE_URL", "").strip()
    collector_token = os.getenv("COLLECTOR_TOKEN", "").strip()

    missing = [
        name
        for name, value in {
            "PLC_CODE": plc_code,
            "PLC_URL": plc_url,
            "LARAVEL_BASE_URL": laravel_base_url,
            "COLLECTOR_TOKEN": collector_token,
        }.items()
        if not value
    ]

    if missing:
        raise RuntimeError(
            "Thiếu cấu hình trong file .env: " + ", ".join(missing)
        )

    log_file_value = os.getenv("LOG_FILE", "logs/collector.log").strip()
    log_file = Path(log_file_value)

    if not log_file.is_absolute():
        log_file = BASE_DIR / log_file

    return Settings(
        plc_code=plc_code,
        plc_url=plc_url,
        laravel_base_url=laravel_base_url,
        collector_token=collector_token,
        collection_interval_seconds=max(
            1, _get_int("COLLECTION_INTERVAL_SECONDS", 5)
        ),
        request_timeout_seconds=max(
            1, _get_int("REQUEST_TIMEOUT_SECONDS", 15)
        ),
        heartbeat_interval_seconds=max(
            10, _get_int("HEARTBEAT_INTERVAL_SECONDS", 60)
        ),
        verify_ssl=_get_bool("VERIFY_SSL", False),
        http_user_agent=os.getenv(
            "HTTP_USER_AGENT", "NTP_PLC_Collector/1.0"
        ).strip(),
        log_level=os.getenv("LOG_LEVEL", "INFO").strip().upper(),
        log_file=log_file,
    )
