from __future__ import annotations

from datetime import datetime
from typing import Any

import requests

from config import Settings
from models import PlcTagValue


class LaravelApiError(RuntimeError):
    pass


class LaravelSender:
    def __init__(self, settings: Settings) -> None:
        self.settings = settings
        self.session = requests.Session()
        self.session.headers.update(
            {
                "Authorization": f"Bearer {settings.collector_token}",
                "Accept": "application/json",
                "Content-Type": "application/json",
                "User-Agent": settings.http_user_agent,
            }
        )

    def send_values(
        self,
        values: list[PlcTagValue],
        collected_at: datetime,
        duration_ms: int,
    ) -> dict[str, Any]:
        collected_at_iso = collected_at.isoformat()

        payload = {
            "plc_code": self.settings.plc_code,
            "collected_at": collected_at_iso,
            "duration_ms": duration_ms,
            "values": [
                item.to_api_dict(collected_at_iso)
                for item in values
            ],
        }

        return self._post(
            self.settings.plc_data_url,
            payload
        )

    def send_heartbeat(
        self,
        status: str = "ONLINE",
        message: str | None = None,
    ) -> dict[str, Any]:
        payload = {
            "plc_code": self.settings.plc_code,
            "status": status,
            "message": message,
            "sent_at": datetime.now().astimezone().isoformat(),
        }

        return self._post(
            self.settings.heartbeat_url,
            payload
        )

    def _post(
        self,
        url: str,
        payload: dict[str, Any],
    ) -> dict[str, Any]:
        try:
            response = self.session.post(
                url,
                json=payload,
                timeout=self.settings.request_timeout_seconds,
                verify=self.settings.verify_ssl,
            )
        except requests.RequestException as exc:
            raise LaravelApiError(
                f"Không thể kết nối Laravel API: {exc}"
            ) from exc

        if not response.ok:
            raise LaravelApiError(
                f"Laravel API HTTP {response.status_code}: "
                f"{response.text[:4000]}"
            )

        if not response.content:
            return {}

        try:
            data = response.json()
        except ValueError:
            return {"raw_response": response.text}

        return data if isinstance(data, dict) else {"data": data}
