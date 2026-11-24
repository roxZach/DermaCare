"""
Skin lesion classifier: loading, preprocessing, prediction, risk level and Grad-CAM.

A checkpoint (.pt) is created by training/train.py and contains:
    arch, classes, img_size, mean, std, state_dict, version, thresholds, metrics
"""
from __future__ import annotations

import base64
import io
from pathlib import Path

import cv2
import numpy as np
import timm
import torch
import torch.nn.functional as F
from PIL import Image

from . import config

DEVICE = torch.device("cuda" if torch.cuda.is_available() else "cpu")


class LesionClassifier:
    def __init__(self):
        self.model = None
        self.meta: dict = {}
        self.file_name: str | None = None

    # ------------------------------------------------------------------ loading
    @property
    def loaded(self) -> bool:
        return self.model is not None

    def info(self) -> dict:
        return {
            "trained": self.loaded,
            "file": self.file_name,
            "version": self.meta.get("version"),
            "architecture": self.meta.get("arch"),
            "img_size": self.meta.get("img_size"),
            "classes": self.meta.get("classes", config.CLASSES),
            "metrics": self.meta.get("metrics"),
        }

    def load(self, file_name: str = "latest") -> dict:
        path = self._resolve(file_name)
        if path is None:
            raise FileNotFoundError("No model file found in " + str(config.MODELS_DIR))
        ckpt = torch.load(path, map_location="cpu", weights_only=False)
        arch = ckpt["arch"]
        classes = ckpt.get("classes", config.CLASSES)
        model = timm.create_model(arch, pretrained=False, num_classes=len(classes))
        model.load_state_dict(ckpt["state_dict"])
        model.eval().to(DEVICE)
        self.model = model
        self.meta = {k: v for k, v in ckpt.items() if k != "state_dict"}
        self.meta.setdefault("classes", classes)
        self.meta.setdefault("img_size", 224)
        self.meta.setdefault("mean", [0.485, 0.456, 0.406])
        self.meta.setdefault("std", [0.229, 0.224, 0.225])
        self.meta.setdefault("version", path.stem)
        self.file_name = path.name
        return self.info()

    @staticmethod
    def _resolve(file_name: str) -> Path | None:
        if file_name and file_name != "latest":
            p = config.MODELS_DIR / Path(file_name).name
            return p if p.exists() else None
        files = sorted(config.MODELS_DIR.glob("*.pt*"), key=lambda p: p.stat().st_mtime)
        return files[-1] if files else None

    # ------------------------------------------------------------------ inference
    def preprocess(self, img: Image.Image) -> torch.Tensor:
        """Resize (keeping the centre), convert to tensor and normalise with ImageNet statistics."""
        size = int(self.meta["img_size"])
        img = img.convert("RGB")
        w, h = img.size
        s = min(w, h)  # centre crop to a square, as during evaluation
        img = img.crop(((w - s) // 2, (h - s) // 2, (w - s) // 2 + s, (h - s) // 2 + s)).resize((size, size), Image.BICUBIC)
        x = torch.from_numpy(np.asarray(img, dtype=np.float32) / 255.0).permute(2, 0, 1)
        mean = torch.tensor(self.meta["mean"]).view(3, 1, 1)
        std = torch.tensor(self.meta["std"]).view(3, 1, 1)
        return ((x - mean) / std).unsqueeze(0).to(DEVICE)

    @torch.no_grad()
    def predict(self, img: Image.Image, tta: bool = True) -> dict:
        x = self.preprocess(img)
        logits = self.model(x)
        if tta:  # test-time augmentation: average with flipped versions
            logits = (logits + self.model(torch.flip(x, [3])) + self.model(torch.flip(x, [2]))) / 3
        probs = F.softmax(logits, dim=1)[0].cpu().numpy()
        classes = self.meta["classes"]
        prob_map = {c: round(float(p), 4) for c, p in zip(classes, probs)}
        top = classes[int(probs.argmax())]
        return {
            "predicted_class": top,
            "confidence": round(float(probs.max()), 4),
            "probabilities": prob_map,
            **self.risk(prob_map),
            "model_version": self.meta.get("version"),
        }

    def risk(self, probs: dict) -> dict:
        """
        Risk level from the class probabilities.
        High   : melanoma probability above the tuned threshold, or weighted risk score >= 0.5
        Medium : weighted risk score >= 0.2
        Low    : otherwise
        The melanoma threshold is tuned on the validation set during training to favour sensitivity.
        """
        th = self.meta.get("thresholds", {})
        mel_th = float(th.get("melanoma", 0.30))
        score = sum(probs.get(c, 0) * w for c, w in config.RISK_WEIGHTS.items())
        if probs.get("mel", 0) >= mel_th or score >= th.get("high", 0.5):
            level = "high"
        elif score >= th.get("medium", 0.2):
            level = "medium"
        else:
            level = "low"
        return {"risk_level": level, "risk_score": round(float(score), 4)}

    def gradcam(self, img: Image.Image, class_name: str | None = None) -> dict:
        """
        Grad-CAM (Selvaraju et al., 2017) on the last convolutional feature map.
        Works for timm CNNs (EfficientNet, ResNet, ...) via forward_features / forward_head.
        """
        x = self.preprocess(img)
        self.model.zero_grad(set_to_none=True)
        feats = self.model.forward_features(x)
        feats.retain_grad()
        logits = self.model.forward_head(feats)
        classes = self.meta["classes"]
        idx = classes.index(class_name) if class_name in classes else int(logits.argmax(1))
        logits[0, idx].backward()
        weights = feats.grad.mean(dim=(2, 3), keepdim=True)
        cam = F.relu((weights * feats).sum(dim=1))[0].detach().cpu().numpy()
        cam = (cam - cam.min()) / (cam.max() - cam.min() + 1e-8)

        size = int(self.meta["img_size"])
        rgb = np.asarray(img.convert("RGB"))
        h, w = rgb.shape[:2]
        s = min(h, w)
        crop = rgb[(h - s) // 2:(h - s) // 2 + s, (w - s) // 2:(w - s) // 2 + s]
        crop = cv2.resize(crop, (size * 2, size * 2))
        heat = cv2.applyColorMap((cv2.resize(cam, (size * 2, size * 2)) * 255).astype(np.uint8), cv2.COLORMAP_JET)
        overlay = cv2.addWeighted(cv2.cvtColor(crop, cv2.COLOR_RGB2BGR), 0.55, heat, 0.45, 0)
        ok, png = cv2.imencode(".png", overlay)
        return {"class": classes[idx], "heatmap_png": base64.b64encode(png.tobytes()).decode()}


def read_image(data: bytes) -> Image.Image:
    img = Image.open(io.BytesIO(data))
    img.load()
    return img


def to_bgr(img: Image.Image) -> np.ndarray:
    return cv2.cvtColor(np.asarray(img.convert("RGB")), cv2.COLOR_RGB2BGR)
