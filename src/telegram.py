import logging
import threading
import requests
from .settings import TELEGRAM_BOT_CHAT_ID, TELEGRAM_BOT_TOKEN, DEBUG

logger = logging.getLogger(__name__)


def getDebug():
    return DEBUG 

def _message_text(name, email, phone, print_size=None, comment=None):
    return (
        "📩 Новый контакт с A-Pro-Print\n"
        f"Имя: {name or '-'}\n"
        f"Почта: {email or '-'}\n"
        f"Номер телефона: {phone or '-'}\n"
        f"Размер покраски: {print_size or '-'}\n"
        f"Комментарий: {comment or '-'}"
    )

def send_telegram_notification(name, email, phone, print_size=None, comment=None):
    if not TELEGRAM_BOT_TOKEN or not TELEGRAM_BOT_CHAT_ID:
        logger.error("Telegram bot token или chat id не установлены")
        return False

    url = f"https://api.telegram.org/bot{TELEGRAM_BOT_TOKEN}/sendMessage"

    data = {
        "chat_id": TELEGRAM_BOT_CHAT_ID,
        "text": _message_text(name, email, phone, print_size,comment),
        "disable_web_page_preview": True
    }

    try:
        response = requests.post(url, json=data, timeout=10)

        result = response.json()

        if not result.get("ok"):
            logger.error("Ошибка Telegram: %s", result)
            return False

        return True

    except Exception as e:
        logger.exception("Ошибка отправки Telegram сообщения: %s", e)
        return False

def send_telegram_notification_async(name=None, email=None, phone=None, print_size=None, comment=None):
    t = threading.Thread(
        target=lambda: send_telegram_notification(name, email, phone, print_size, comment),
        daemon=False
    )
    t.start()