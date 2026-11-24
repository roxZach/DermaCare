"""Settings for the DermaCare AI Engine. Override with environment variables."""
import os
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent
MODELS_DIR = Path(os.getenv("DC_MODELS_DIR", BASE_DIR / "models"))
DATA_DIR = Path(os.getenv("DC_DATA_DIR", BASE_DIR / "data"))

# Model file to load at start-up (inside MODELS_DIR). "latest" = newest .pt file.
MODEL_FILE = os.getenv("DC_MODEL_FILE", "latest")

# HAM10000 classes, in the order used during training
CLASSES = ["akiec", "bcc", "bkl", "df", "mel", "nv", "vasc"]

# Risk weights: how much each class contributes to the risk score
RISK_WEIGHTS = {"mel": 1.0, "bcc": 0.8, "akiec": 0.6, "bkl": 0.0, "df": 0.0, "nv": 0.0, "vasc": 0.0}

# Chatbot engine: "tfidf" (default, calibrated), "sbert" (Sentence-BERT) or "auto" (sbert if installed)
CHATBOT_ENGINE = os.getenv("DC_CHATBOT_ENGINE", "tfidf")
SBERT_MODEL = os.getenv("DC_SBERT_MODEL", "sentence-transformers/all-MiniLM-L6-v2")

MODELS_DIR.mkdir(parents=True, exist_ok=True)
DATA_DIR.mkdir(parents=True, exist_ok=True)
