"""
Evaluation metrics for the lesion classifier (used by train.py, and usable on its own).

Metrics (as reported in the skin lesion classification literature / ISIC challenge):
  accuracy, balanced accuracy (mean per-class recall - main ISIC metric), macro F1,
  per-class precision / recall / F1, macro ROC-AUC (one-vs-rest), confusion matrix,
  melanoma sensitivity and specificity at the tuned threshold.

Stand-alone use on any labelled image set (e.g. PAD-UFES-20 smartphone images mapped to the 7 classes):
  python training/evaluate.py --model models/dermacare_efficientnet_b0.pt --csv my_test.csv
  (CSV columns: path,label   where label is one of akiec,bcc,bkl,df,mel,nv,vasc
   optional column "group", e.g. Fitzpatrick skin type, to report accuracy per group)
"""
import argparse
import json
from pathlib import Path

import numpy as np
import torch
import torch.nn.functional as F
from sklearn.metrics import (accuracy_score, balanced_accuracy_score, confusion_matrix, f1_score,
                             precision_recall_fscore_support, roc_auc_score)


@torch.no_grad()
def predict_loader(model, loader, device, tta=True):
    model.eval()
    ys, ps = [], []
    for x, y in loader:
        x = x.to(device)
        logits = model(x)
        if tta:
            logits = (logits + model(torch.flip(x, [3])) + model(torch.flip(x, [2]))) / 3
        ps.append(F.softmax(logits.float(), 1).cpu().numpy())
        ys.append(np.asarray(y))
    return np.concatenate(ys), np.concatenate(ps)


def tune_melanoma_threshold(y, p, classes, target_sensitivity=0.85):
    """Highest melanoma probability threshold that still reaches the target sensitivity on validation data."""
    mel = classes.index("mel")
    is_mel = y == mel
    if is_mel.sum() == 0:
        return 0.3
    for t in np.arange(0.9, 0.0, -0.01):
        if (p[is_mel, mel] >= t).mean() >= target_sensitivity:
            return round(float(t), 2)
    return 0.05


def compute_metrics(y, p, classes, mel_threshold=None):
    pred = p.argmax(1)
    labels = list(range(len(classes)))
    prec, rec, f1, sup = precision_recall_fscore_support(y, pred, labels=labels, zero_division=0)
    m = {
        "accuracy": round(float(accuracy_score(y, pred)), 4),
        "balanced_accuracy": round(float(balanced_accuracy_score(y, pred)), 4),
        "macro_f1": round(float(f1_score(y, pred, average="macro", labels=labels, zero_division=0)), 4),
        "per_class": {c: {"precision": round(float(prec[i]), 4), "recall": round(float(rec[i]), 4),
                          "f1": round(float(f1[i]), 4), "support": int(sup[i])} for i, c in enumerate(classes)},
        "confusion_matrix": confusion_matrix(y, pred, labels=labels).tolist(),
    }
    try:
        present = [i for i in labels if (y == i).any()]
        m["roc_auc_macro"] = round(float(roc_auc_score(y, p[:, present] / p[:, present].sum(1, keepdims=True),
                                                       multi_class="ovr", labels=present)), 4) if len(present) > 2 else None
    except ValueError:
        m["roc_auc_macro"] = None
    mel = classes.index("mel")
    th = 0.5 if mel_threshold is None else mel_threshold
    is_mel, flagged = y == mel, p[:, mel] >= th
    m["melanoma_threshold_used"] = th
    m["melanoma_sensitivity"] = round(float(flagged[is_mel].mean()), 4) if is_mel.any() else None
    m["melanoma_specificity"] = round(float((~flagged[~is_mel]).mean()), 4) if (~is_mel).any() else None
    return m


def plot_confusion(cm, classes, path):
    import matplotlib
    matplotlib.use("Agg")
    import matplotlib.pyplot as plt
    cmn = cm / np.maximum(cm.sum(1, keepdims=True), 1)
    fig, ax = plt.subplots(figsize=(6.5, 5.5))
    im = ax.imshow(cmn, cmap="Blues", vmin=0, vmax=1)
    ax.set_xticks(range(len(classes)), classes)
    ax.set_yticks(range(len(classes)), classes)
    ax.set_xlabel("Predicted"); ax.set_ylabel("True")
    ax.set_title("Confusion matrix (row-normalised)")
    for i in range(len(classes)):
        for j in range(len(classes)):
            ax.text(j, i, f"{cm[i, j]}\n{cmn[i, j]:.0%}", ha="center", va="center", fontsize=7,
                    color="white" if cmn[i, j] > 0.5 else "black")
    fig.colorbar(im, fraction=0.046)
    fig.tight_layout(); fig.savefig(path, dpi=150); plt.close(fig)


if __name__ == "__main__":
    import sys
    import pandas as pd
    from PIL import Image
    sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
    from app.model import LesionClassifier

    ap = argparse.ArgumentParser()
    ap.add_argument("--model", required=True)
    ap.add_argument("--csv", required=True)
    ap.add_argument("--out", default="evaluation")
    args = ap.parse_args()

    import app.config as cfg
    cfg.MODELS_DIR = Path(args.model).resolve().parent  # load the model from wherever it is
    clf = LesionClassifier()
    clf.load(Path(args.model).name)
    classes = clf.meta["classes"]
    df = pd.read_csv(args.csv)
    ys, ps = [], []
    for _, row in df.iterrows():
        r = clf.predict(Image.open(row["path"]))
        ps.append([r["probabilities"][c] for c in classes])
        ys.append(classes.index(row["label"]))
    y, p = np.array(ys), np.array(ps)
    th = clf.meta.get("thresholds", {}).get("melanoma")
    metrics = compute_metrics(y, p, classes, mel_threshold=th)
    if "group" in df.columns:  # e.g. Fitzpatrick skin type -> fairness check
        metrics["by_group"] = {str(g): {"n": int((df["group"] == g).sum()),
                                        "balanced_accuracy": round(float(balanced_accuracy_score(y[df["group"] == g], p[df["group"] == g].argmax(1))), 4)}
                               for g in sorted(df["group"].unique())}
    out = Path(args.out); out.mkdir(exist_ok=True)
    (out / "metrics.json").write_text(json.dumps(metrics, indent=2))
    plot_confusion(np.array(metrics["confusion_matrix"]), classes, out / "confusion_matrix.png")
    print(json.dumps({k: v for k, v in metrics.items() if k not in ("per_class", "confusion_matrix")}, indent=2))
