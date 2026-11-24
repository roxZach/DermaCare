"""
Evaluate the FAQ chatbot (top-1 / top-3 accuracy) and choose the confidence threshold.

Method (leave-one-out over paraphrases):
  For every paraphrase of every FAQ, build the index WITHOUT that paraphrase, ask it as a
  question and check whether the correct FAQ is ranked 1st (top-1) or in the first 3 (top-3).
  Out-of-domain questions (the clinic cannot answer them) are used to choose the threshold
  below which the chatbot escalates to staff instead of answering.

Usage (from the ai-service folder):
  python training/chatbot_eval.py --faqs ../database/data/faqs.json --engine tfidf
  python training/chatbot_eval.py --engine sbert      # needs: pip install sentence-transformers
"""
import argparse
import json
import sys
from pathlib import Path

import numpy as np

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
from app.chatbot import FaqChatbot  # noqa: E402

OUT_OF_DOMAIN = [
    "do you sell cars", "what is the weather tomorrow", "who won the football match", "can you write my homework",
    "how do I fix my laptop", "recommend a good movie", "what is the capital of france", "how to cook rice",
    "is bitcoin a good investment", "tell me a joke", "how far is the moon", "where can I buy a phone",
    "do you have a dentist", "can I get an eye test here", "what is your favourite colour", "how do I renew my passport",
]


def evaluate(faqs, engine):
    hits1 = hits3 = total = 0
    in_scores = []
    for i, f in enumerate(faqs):
        for j, para in enumerate(f["paraphrases"]):
            held = [dict(x) for x in faqs]
            held[i] = dict(f, paraphrases=[p for k, p in enumerate(f["paraphrases"]) if k != j])
            bot = FaqChatbot(engine=engine)
            bot.build(held, persist=False)
            r = bot.ask(para)
            ids = [t["faq_id"] for t in r["top"]]
            total += 1
            hits1 += ids[0] == f["id"]
            hits3 += f["id"] in ids
            in_scores.append(r["score"] if ids[0] == f["id"] else None)
    bot = FaqChatbot(engine=engine)
    bot.build(faqs, persist=False)
    ood_scores = [bot.ask(q)["score"] for q in OUT_OF_DOMAIN]
    return bot.engine, hits1 / total, hits3 / total, total, [s for s in in_scores if s is not None], ood_scores


def best_threshold(correct_scores, ood_scores):
    """Threshold that maximises (correct answers kept + out-of-domain questions rejected)."""
    best = (0, 0.0)
    for t in np.arange(0.05, 0.95, 0.01):
        kept = np.mean([s >= t for s in correct_scores])
        rejected = np.mean([s < t for s in ood_scores])
        score = (kept + rejected) / 2
        if score > best[0]:
            best = (score, float(t), kept, rejected)
    return best


if __name__ == "__main__":
    ap = argparse.ArgumentParser()
    ap.add_argument("--faqs", default=str(Path(__file__).resolve().parent / "faqs.json"))
    ap.add_argument("--engine", default="tfidf", choices=["tfidf", "sbert"])
    args = ap.parse_args()
    faqs = json.load(open(args.faqs))
    for i, f in enumerate(faqs):
        f.setdefault("id", i + 1)
    engine, top1, top3, n, ok_scores, ood = evaluate(faqs, args.engine)
    bal, t, kept, rej = best_threshold(ok_scores, ood)
    report = {
        "engine": engine, "faqs": len(faqs), "test_questions": n,
        "top1_accuracy": round(top1, 4), "top3_accuracy": round(top3, 4),
        "mean_score_correct": round(float(np.mean(ok_scores)), 3), "mean_score_out_of_domain": round(float(np.mean(ood)), 3),
        "max_score_out_of_domain": round(float(np.max(ood)), 3),
        "recommended_threshold": round(t, 2), "kept_at_threshold": round(float(kept), 3), "ood_rejected_at_threshold": round(float(rej), 3),
    }
    print(json.dumps(report, indent=2))
