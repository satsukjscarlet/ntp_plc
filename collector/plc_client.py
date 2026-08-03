from __future__ import annotations

import requests

from config import Settings


class PlcPortalClient:
    def __init__(self, settings: Settings) -> None:
        self.settings = settings
        self.session = requests.Session()
        self.session.headers.update(
            {
                "User-Agent": settings.http_user_agent,
                "Accept": "text/html,application/xhtml+xml",
                "Cache-Control": "no-cache",
                "Pragma": "no-cache",
            }
        )

    def fetch_html(self) -> str:
        response = self.session.get(
            self.settings.plc_url,
            timeout=self.settings.request_timeout_seconds,
            verify=self.settings.verify_ssl,
        )
        response.raise_for_status()

        if not response.encoding:
            response.encoding = "utf-8"

        return response.text
