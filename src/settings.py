import os

from dotenv import load_dotenv

load_dotenv()


DEBUG = True if os.getenv("DEBUG", "").lower() == 'true' else False

TELEGRAM_BOT_TOKEN = os.getenv("TELEGRAM_BOT_TOKEN")
TELEGRAM_BOT_CHAT_ID = os.getenv("TELEGRAM_CHAT_ID")