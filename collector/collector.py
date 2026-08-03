from __future__ import annotations

import argparse
import logging
import signal
import sys
import time
from datetime import datetime

import urllib3

from config import Settings, load_settings
from laravel_sender import LaravelApiError, LaravelSender
from logging_setup import configure_logging
from plc_client import PlcPortalClient
from siemens_parser import SiemensWatchTableParser


LOGGER = logging.getLogger("collector")
STOP_REQUESTED = False


def request_stop(signum: int, frame: object) -> None:
    global STOP_REQUESTED
    STOP_REQUESTED = True
    LOGGER.info("Đã nhận tín hiệu dừng collector.")


class Collector:
    def __init__(self, settings: Settings) -> None:
        self.settings = settings
        self.plc_client = PlcPortalClient(settings)
        self.parser = SiemensWatchTableParser()
        self.sender = LaravelSender(settings)
        self.last_heartbeat_monotonic = 0.0

    def run_once(self, send: bool = True) -> int:
        started = time.perf_counter()
        collected_at = datetime.now().astimezone()

        html = self.plc_client.fetch_html()
        values = self.parser.parse(html)
        duration_ms = int((time.perf_counter() - started) * 1000)

        LOGGER.info(
            "Đọc PLC thành công: %s tag, thời gian %s ms.",
            len(values),
            duration_ms,
        )

        for item in values[:10]:
            LOGGER.debug(
                "%s | %s | %s | %r",
                item.address,
                item.source_name,
                item.data_type,
                item.value,
            )

        if send:
            response = self.sender.send_values(
                values=values,
                collected_at=collected_at,
                duration_ms=duration_ms,
            )
            LOGGER.info("Laravel nhận dữ liệu thành công: %s", response)

        return len(values)

    def maybe_send_heartbeat(self) -> None:
        now = time.monotonic()

        if (
            now - self.last_heartbeat_monotonic
            < self.settings.heartbeat_interval_seconds
        ):
            return

        response = self.sender.send_heartbeat(
            status="ONLINE",
            message="Collector đang hoạt động.",
        )
        self.last_heartbeat_monotonic = now
        LOGGER.info("Heartbeat thành công: %s", response)

    def run_forever(self) -> None:
        LOGGER.info(
            "Khởi động collector máy %s, chu kỳ %s giây.",
            self.settings.plc_code,
            self.settings.collection_interval_seconds,
        )

        while not STOP_REQUESTED:
            cycle_started = time.monotonic()

            try:
                self.run_once(send=True)
                self.maybe_send_heartbeat()
            except Exception:
                LOGGER.exception("Chu kỳ thu thập thất bại.")

                try:
                    self.sender.send_heartbeat(
                        status="ERROR",
                        message="Chu kỳ thu thập PLC thất bại. Xem collector.log.",
                    )
                except Exception:
                    LOGGER.exception("Không gửi được heartbeat lỗi.")

            elapsed = time.monotonic() - cycle_started
            sleep_seconds = max(
                0.0,
                self.settings.collection_interval_seconds - elapsed,
            )

            deadline = time.monotonic() + sleep_seconds

            while not STOP_REQUESTED and time.monotonic() < deadline:
                time.sleep(min(0.25, deadline - time.monotonic()))

        LOGGER.info("Collector đã dừng an toàn.")


def parse_arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="NTP_PLC Collector cho Siemens S7-1200 M62"
    )
    parser.add_argument(
        "--once",
        action="store_true",
        help="Chạy đúng một chu kỳ rồi thoát.",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Đọc và parser PLC nhưng không gửi Laravel.",
    )
    parser.add_argument(
        "--heartbeat",
        action="store_true",
        help="Chỉ kiểm tra API heartbeat.",
    )

    return parser.parse_args()


def main() -> int:
    args = parse_arguments()
    settings = load_settings()
    configure_logging(settings.log_level, settings.log_file)

    if not settings.verify_ssl:
        urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

    signal.signal(signal.SIGINT, request_stop)

    if hasattr(signal, "SIGTERM"):
        signal.signal(signal.SIGTERM, request_stop)

    collector = Collector(settings)

    try:
        if args.heartbeat:
            response = collector.sender.send_heartbeat(
                status="ONLINE",
                message="Kiểm tra heartbeat thủ công.",
            )
            LOGGER.info("Heartbeat thành công: %s", response)
            return 0

        if args.once or args.dry_run:
            total = collector.run_once(send=not args.dry_run)
            LOGGER.info("Hoàn thành một chu kỳ với %s tag.", total)
            return 0

        collector.run_forever()
        return 0
    except (RuntimeError, LaravelApiError):
        LOGGER.exception("Collector dừng do lỗi.")
        return 1
    except KeyboardInterrupt:
        LOGGER.info("Collector đã dừng bằng bàn phím.")
        return 0


if __name__ == "__main__":
    sys.exit(main())
