"""
FAQ chatbot: finds the stored FAQ that is most similar to the user's question.

Engines
  * tfidf : TF-IDF vectors and cosine similarity (default, no download needed).
            Text is normalised first: lower-case, stop-word removal, light stemming and a small
            dermatology-clinic synonym list (e.g. "price"/"cost"/"charge" -> "fee").
            Similarity = 80% word 1-2 grams + 20% character 3-5 grams (robust to typos).
  * sbert : Sentence-BERT embeddings (Reimers & Gurevych, 2019) and cosine similarity.
            Understands meaning, e.g. "When are you open?" ~ "opening hours". Needs
            `pip install sentence-transformers` and a one-time model download.

Each FAQ is represented by its question AND its paraphrases (score = best matching variant),
blended with the similarity to the FAQ's answer text (35%), which adds useful vocabulary.
"""
from __future__ import annotations

import json
import os
import re

import numpy as np
from sklearn.feature_extraction.text import ENGLISH_STOP_WORDS, TfidfVectorizer
from sklearn.preprocessing import normalize

from . import config

INDEX_FILE = config.DATA_DIR / "faq_index.json"

CHAR_WEIGHT = 0.2      # share of character n-gram similarity (tfidf engine)
ANSWER_WEIGHT = 0.35   # share of similarity to the answer text
# Below this score the question is escalated to staff. Chosen with training/chatbot_eval.py.
THRESHOLDS = {"tfidf": float(os.getenv("DC_CHATBOT_THRESHOLD", 0.40)), "sbert": 0.45}

KEEP = {"no", "not", "open", "off", "online", "before", "after", "can"}
STOP_WORDS = set(ENGLISH_STOP_WORDS) - KEEP

# Domain synonyms: map different words with the same meaning to one token
SYNONYMS = {
    "timing": "hour", "time": "hour", "open": "hour", "close": "hour", "weekend": "hour",
    "cost": "fee", "price": "fee", "charge": "fee", "much": "fee", "expensive": "fee",
    "pay": "payment", "card": "payment", "bill": "payment", "invoice": "receipt",
    "cancel": "reschedule", "change": "reschedule", "move": "reschedule", "postpone": "reschedule",
    "book": "appointment", "schedule": "appointment", "visit": "appointment", "make": "appointment",
    "picture": "photo", "image": "photo", "pic": "photo",
    "result": "report", "test": "report",
    "doctor": "dermatologist", "doc": "dermatologist", "specialist": "dermatologist",
    "address": "location", "where": "location", "locat": "location",
    "register": "account", "sign": "account", "signup": "account",
    "code": "verification", "otp": "verification",
    "kid": "child", "children": "child", "son": "child", "daughter": "child", "baby": "child",
    "pimple": "acne", "zit": "acne",
    "itchy": "itch", "safe": "privacy", "private": "privacy", "secure": "privacy",
    "accurate": "ai", "artificial": "ai", "intelligence": "ai",
    "online": "teledermatology", "remote": "teledermatology",
    "call": "contact", "phone": "contact", "email": "contact",
    "discount": "package", "cheaper": "package", "offer": "package",
    "need": "bring", "long": "duration",
    "spf": "sunscreen", "sun": "sunscreen",
}


def stem(word: str) -> str:
    for suf in ("ings", "ing", "ies", "ied", "es", "ed", "ly", "s"):
        if len(word) > 4 and word.endswith(suf):
            return word[: -len(suf)] + ("y" if suf in ("ies", "ied") else "")
    return word


def normalise(text: str) -> str:
    words = [stem(w) for w in re.findall(r"[a-z0-9]+", text.lower()) if w not in STOP_WORDS]
    return " ".join(SYNONYMS.get(w, w) for w in words)


class FaqChatbot:
    def __init__(self, engine: str = "auto"):
        self.requested_engine = engine
        self.engine = "tfidf"
        self.faqs: list[dict] = []
        self.variants: list[str] = []
        self.variant_faq: list[int] = []
        self.answers: list[str] = []
        self.sbert = None
        self._sbert_failed = False
        self.word_vec = self.char_vec = None
        self.Vw = self.Vc = self.Aw = None   # tfidf matrices
        self.Ve = self.Ae = None             # sbert embeddings

    # ------------------------------------------------------------------ build
    def build(self, faqs: list[dict], persist: bool = True) -> dict:
        self.faqs = [f for f in faqs if f.get("question") and f.get("answer")]
        self.variants, self.variant_faq = [], []
        for i, f in enumerate(self.faqs):
            for v in [f["question"], *f.get("paraphrases", [])]:
                if v and v.strip():
                    self.variants.append(v)
                    self.variant_faq.append(i)
        self.answers = [f["question"] + " " + f["answer"] for f in self.faqs]
        if not self.variants:
            return self.info()

        self.engine = self._pick_engine()
        if self.engine == "sbert":
            self.Ve = self.sbert.encode(self.variants, normalize_embeddings=True, show_progress_bar=False)
            self.Ae = self.sbert.encode(self.answers, normalize_embeddings=True, show_progress_bar=False)
        else:
            nv = [normalise(v) for v in self.variants]
            na = [normalise(a) for a in self.answers]
            self.word_vec = TfidfVectorizer(ngram_range=(1, 2), sublinear_tf=True).fit(nv + na)
            self.char_vec = TfidfVectorizer(analyzer="char_wb", ngram_range=(3, 5), sublinear_tf=True).fit(nv)
            self.Vw = normalize(self.word_vec.transform(nv))
            self.Vc = normalize(self.char_vec.transform(nv))
            self.Aw = normalize(self.word_vec.transform(na))
        if persist:
            INDEX_FILE.parent.mkdir(parents=True, exist_ok=True)
            INDEX_FILE.write_text(json.dumps(self.faqs, ensure_ascii=False))
        return self.info()

    def load_saved(self) -> bool:
        if INDEX_FILE.exists():
            self.build(json.loads(INDEX_FILE.read_text()), persist=False)
            return True
        return False

    def _pick_engine(self) -> str:
        if self.requested_engine == "tfidf" or self._sbert_failed:
            return "tfidf"
        try:
            if self.sbert is None:
                from sentence_transformers import SentenceTransformer
                self.sbert = SentenceTransformer(config.SBERT_MODEL)
            return "sbert"
        except Exception:
            self._sbert_failed = True
            if self.requested_engine == "sbert":
                raise
            return "tfidf"

    # ------------------------------------------------------------------ query
    def scores(self, question: str) -> np.ndarray:
        """Similarity of the question to every FAQ."""
        if self.engine == "sbert":
            q = self.sbert.encode([question], normalize_embeddings=True, show_progress_bar=False)[0]
            sv, sa = self.Ve @ q, self.Ae @ q
        else:
            nq = normalise(question)
            qw = normalize(self.word_vec.transform([nq]))
            qc = normalize(self.char_vec.transform([nq]))
            sv = (1 - CHAR_WEIGHT) * (self.Vw @ qw.T).toarray().ravel() + CHAR_WEIGHT * (self.Vc @ qc.T).toarray().ravel()
            sa = (self.Aw @ qw.T).toarray().ravel()
        best = np.zeros(len(self.faqs))
        for s, owner in zip(sv, self.variant_faq):
            best[owner] = max(best[owner], s)
        return (1 - ANSWER_WEIGHT) * best + ANSWER_WEIGHT * sa

    def ask(self, question: str, top_k: int = 3) -> dict:
        if not self.faqs or not self.variants:
            raise LookupError("index_empty")
        s = self.scores(question)
        order = np.argsort(-s)[:top_k]
        top = [{"faq_id": self.faqs[i].get("id"), "question": self.faqs[i]["question"], "score": round(float(s[i]), 4)} for i in order]
        best = self.faqs[order[0]]
        return {"faq_id": best.get("id"), "question": best["question"], "answer": best["answer"],
                "score": top[0]["score"], "threshold": THRESHOLDS[self.engine], "top": top, "engine": self.engine}

    def info(self) -> dict:
        return {"engine": self.engine, "count": len(self.faqs), "variants": len(self.variants),
                "threshold": THRESHOLDS[self.engine]}
