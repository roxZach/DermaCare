"""
Train the DermaCare skin lesion classifier on HAM10000 (7 classes) with transfer learning.

Approach (based on Esteva et al. 2017; Tschandl et al. 2018/2019; ISIC challenge practice):
  * Pretrained CNN from timm (EfficientNet-B0 by default, ResNet-50 as a baseline)
  * Train / validation / test split BY LESION (lesion_id) so the same lesion never appears in two splits
  * Class imbalance: class-weighted cross-entropy + data augmentation
  * Model selection on validation BALANCED ACCURACY (mean recall over classes)
  * Melanoma threshold tuned on validation data to reach a target sensitivity
  * Final evaluation on the held-out test set (see evaluate.py for the metrics)

Dataset folder (download from Kaggle "skin-cancer-mnist-ham10000" or Harvard Dataverse):
  HAM10000_metadata.csv   + all images (*.jpg) in any sub-folder(s)

Example (Google Colab GPU, ~25-40 min):
  python training/train.py --data-dir /content/ham10000 --arch efficientnet_b0 --epochs 15
Output (in --out):
  <name>.pt  metrics.json  confusion_matrix.png  training_curves.png  splits.csv
"""
import argparse
import json
import math
import random
import time
from pathlib import Path

import numpy as np
import pandas as pd
import timm
import torch
import torch.nn as nn
from PIL import Image
from sklearn.model_selection import StratifiedGroupKFold
from torch.utils.data import DataLoader, Dataset
from torchvision import transforms

from evaluate import compute_metrics, plot_confusion, predict_loader, tune_melanoma_threshold

CLASSES = ["akiec", "bcc", "bkl", "df", "mel", "nv", "vasc"]
MEAN, STD = [0.485, 0.456, 0.406], [0.229, 0.224, 0.225]


# --------------------------------------------------------------------------- data
class CenterSquare:
    """Crop the largest centred square (the AI Engine does exactly the same at inference)."""
    def __call__(self, img):
        w, h = img.size
        s = min(w, h)
        return img.crop(((w - s) // 2, (h - s) // 2, (w - s) // 2 + s, (h - s) // 2 + s))


def build_transforms(size):
    train = transforms.Compose([
        CenterSquare(),
        transforms.RandomResizedCrop(size, scale=(0.6, 1.0), ratio=(0.85, 1.18)),
        transforms.RandomHorizontalFlip(),
        transforms.RandomVerticalFlip(),
        transforms.RandomRotation(45),
        transforms.ColorJitter(0.2, 0.2, 0.15, 0.03),
        transforms.ToTensor(),
        transforms.Normalize(MEAN, STD),
    ])
    evaluate = transforms.Compose([
        CenterSquare(), transforms.Resize((size, size), interpolation=transforms.InterpolationMode.BICUBIC),
        transforms.ToTensor(), transforms.Normalize(MEAN, STD),
    ])
    return train, evaluate


class LesionDataset(Dataset):
    def __init__(self, df, tf):
        self.paths = df["path"].tolist()
        self.labels = df["label"].tolist()
        self.tf = tf

    def __len__(self):
        return len(self.paths)

    def __getitem__(self, i):
        img = Image.open(self.paths[i]).convert("RGB")
        return self.tf(img), self.labels[i]


def load_metadata(data_dir: Path) -> pd.DataFrame:
    meta = pd.read_csv(next(data_dir.rglob("HAM10000_metadata.csv")))
    images = {p.stem: str(p) for p in data_dir.rglob("*.jpg")}
    meta["path"] = meta["image_id"].map(images)
    missing = meta["path"].isna().sum()
    if missing:
        print(f"Warning: {missing} images listed in the metadata were not found and are skipped.")
    meta = meta.dropna(subset=["path"])
    meta["label"] = meta["dx"].map({c: i for i, c in enumerate(CLASSES)})
    return meta


def split_by_lesion(meta: pd.DataFrame, seed: int):
    """About 70% train / 15% validation / 15% test, stratified by class and grouped by lesion_id."""
    sgkf = StratifiedGroupKFold(n_splits=7, shuffle=True, random_state=seed)
    rest_idx, test_idx = next(sgkf.split(meta, meta["label"], groups=meta["lesion_id"]))
    rest = meta.iloc[rest_idx]
    sgkf2 = StratifiedGroupKFold(n_splits=6, shuffle=True, random_state=seed + 1)
    tr_idx, va_idx = next(sgkf2.split(rest, rest["label"], groups=rest["lesion_id"]))
    return rest.iloc[tr_idx], rest.iloc[va_idx], meta.iloc[test_idx]


# --------------------------------------------------------------------------- training
def set_seed(seed):
    random.seed(seed)
    np.random.seed(seed)
    torch.manual_seed(seed)
    torch.cuda.manual_seed_all(seed)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--data-dir", required=True)
    ap.add_argument("--arch", default="efficientnet_b0", help="any timm CNN, e.g. efficientnet_b0, efficientnet_b3, resnet50")
    ap.add_argument("--img-size", type=int, default=224)
    ap.add_argument("--epochs", type=int, default=15)
    ap.add_argument("--batch-size", type=int, default=32)
    ap.add_argument("--lr", type=float, default=3e-4)
    ap.add_argument("--weight-decay", type=float, default=1e-4)
    ap.add_argument("--workers", type=int, default=2)
    ap.add_argument("--seed", type=int, default=42)
    ap.add_argument("--mel-sensitivity", type=float, default=0.85, help="target melanoma sensitivity for the risk threshold")
    ap.add_argument("--no-pretrained", action="store_true", help="train from scratch (only for quick tests)")
    ap.add_argument("--limit", type=int, default=0, help="use only N images (quick test)")
    ap.add_argument("--out", default=str(Path(__file__).resolve().parent.parent / "models"))
    ap.add_argument("--name", default=None)
    args = ap.parse_args()

    set_seed(args.seed)
    device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
    out = Path(args.out)
    out.mkdir(parents=True, exist_ok=True)
    name = args.name or f"dermacare_{args.arch}_{time.strftime('%Y%m%d_%H%M')}"
    print(f"Device: {device} | model: {args.arch} | output: {out / name}.pt")

    meta = load_metadata(Path(args.data_dir))
    if args.limit:
        meta = meta.groupby("dx", group_keys=False).apply(lambda g: g.head(max(2, args.limit // 7))).reset_index(drop=True)
    train_df, val_df, test_df = split_by_lesion(meta, args.seed)
    pd.concat([train_df.assign(split="train"), val_df.assign(split="val"), test_df.assign(split="test")])[
        ["image_id", "lesion_id", "dx", "split"]].to_csv(out / f"{name}_splits.csv", index=False)
    print("Images  train/val/test:", len(train_df), len(val_df), len(test_df))
    print("Class counts (train):", train_df["dx"].value_counts().to_dict())

    tf_train, tf_eval = build_transforms(args.img_size)
    kw = dict(num_workers=args.workers, pin_memory=device.type == "cuda")
    train_dl = DataLoader(LesionDataset(train_df, tf_train), batch_size=args.batch_size, shuffle=True, drop_last=len(train_df) > args.batch_size, **kw)
    val_dl = DataLoader(LesionDataset(val_df, tf_eval), batch_size=args.batch_size * 2, **kw)
    test_dl = DataLoader(LesionDataset(test_df, tf_eval), batch_size=args.batch_size * 2, **kw)

    # Class weights: inverse square root of class frequency (milder than plain inverse frequency)
    counts = np.bincount(train_df["label"], minlength=len(CLASSES)).astype(float)
    weights = 1.0 / np.sqrt(np.maximum(counts, 1))
    weights = weights / weights.mean()
    criterion = nn.CrossEntropyLoss(weight=torch.tensor(weights, dtype=torch.float32, device=device), label_smoothing=0.1)

    model = timm.create_model(args.arch, pretrained=not args.no_pretrained, num_classes=len(CLASSES), drop_rate=0.3).to(device)
    optimizer = torch.optim.AdamW(model.parameters(), lr=args.lr, weight_decay=args.weight_decay)
    steps = args.epochs * max(1, len(train_dl))
    warmup = max(1, len(train_dl))
    scheduler = torch.optim.lr_scheduler.LambdaLR(
        optimizer, lambda s: min(1.0, (s + 1) / warmup) * 0.5 * (1 + math.cos(math.pi * min(1.0, s / steps))))
    scaler = torch.amp.GradScaler("cuda", enabled=device.type == "cuda")

    history, best_bacc, best_state = [], -1, None
    for epoch in range(1, args.epochs + 1):
        model.train()
        t0, total_loss, n = time.time(), 0.0, 0
        for x, y in train_dl:
            x, y = x.to(device, non_blocking=True), y.to(device, non_blocking=True)
            optimizer.zero_grad(set_to_none=True)
            with torch.autocast(device_type=device.type, enabled=device.type == "cuda"):
                loss = criterion(model(x), y)
            scaler.scale(loss).backward()
            scaler.step(optimizer)
            scaler.update()
            scheduler.step()
            total_loss += loss.item() * len(y)
            n += len(y)
        y_val, p_val = predict_loader(model, val_dl, device, tta=False)
        m = compute_metrics(y_val, p_val, CLASSES)
        history.append({"epoch": epoch, "train_loss": total_loss / max(n, 1), "val_balanced_accuracy": m["balanced_accuracy"],
                        "val_accuracy": m["accuracy"], "val_macro_f1": m["macro_f1"]})
        flag = ""
        if m["balanced_accuracy"] > best_bacc:
            best_bacc, best_state, flag = m["balanced_accuracy"], {k: v.detach().cpu().clone() for k, v in model.state_dict().items()}, "  <- best"
        print(f"Epoch {epoch:2d}/{args.epochs}  loss {total_loss / max(n, 1):.4f}  val bal.acc {m['balanced_accuracy']:.4f}  "
              f"val acc {m['accuracy']:.4f}  ({time.time() - t0:.0f}s){flag}")

    # ----------------------------------------------------------------- final evaluation
    model.load_state_dict(best_state)
    y_val, p_val = predict_loader(model, val_dl, device, tta=True)
    mel_th = tune_melanoma_threshold(y_val, p_val, CLASSES, args.mel_sensitivity)
    y_test, p_test = predict_loader(model, test_dl, device, tta=True)
    metrics = compute_metrics(y_test, p_test, CLASSES, mel_threshold=mel_th)
    metrics.update({"arch": args.arch, "img_size": args.img_size, "epochs": args.epochs, "train_images": len(train_df),
                    "val_images": len(val_df), "test_images": len(test_df), "melanoma_threshold": mel_th,
                    "best_val_balanced_accuracy": best_bacc, "history": history})
    plot_confusion(np.array(metrics["confusion_matrix"]), CLASSES, out / f"{name}_confusion_matrix.png")
    _plot_history(history, out / f"{name}_training_curves.png")

    ckpt = {
        "arch": args.arch, "classes": CLASSES, "img_size": args.img_size, "mean": MEAN, "std": STD,
        "version": name, "thresholds": {"melanoma": mel_th, "high": 0.5, "medium": 0.2},
        "metrics": {k: metrics[k] for k in ("accuracy", "balanced_accuracy", "macro_f1", "roc_auc_macro",
                                            "melanoma_sensitivity", "melanoma_specificity")},
        "state_dict": best_state,
    }
    torch.save(ckpt, out / f"{name}.pt")
    (out / f"{name}_metrics.json").write_text(json.dumps(metrics, indent=2))
    print("\nTest results:", json.dumps({k: metrics[k] for k in ckpt["metrics"]}, indent=2))
    print(f"Saved {out / name}.pt  (upload it in Admin > AI model versions, together with {name}_metrics.json)")


def _plot_history(history, path):
    import matplotlib
    matplotlib.use("Agg")
    import matplotlib.pyplot as plt
    ep = [h["epoch"] for h in history]
    fig, ax = plt.subplots(1, 2, figsize=(10, 3.5))
    ax[0].plot(ep, [h["train_loss"] for h in history], marker="o")
    ax[0].set_title("Training loss"); ax[0].set_xlabel("epoch")
    ax[1].plot(ep, [h["val_balanced_accuracy"] for h in history], marker="o", label="balanced accuracy")
    ax[1].plot(ep, [h["val_accuracy"] for h in history], marker="o", label="accuracy")
    ax[1].set_title("Validation"); ax[1].set_xlabel("epoch"); ax[1].legend()
    fig.tight_layout(); fig.savefig(path, dpi=150); plt.close(fig)


if __name__ == "__main__":
    main()
