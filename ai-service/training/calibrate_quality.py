"""
Calibrate the image quality thresholds on real images.

Runs the quality measures on a sample of good-quality dataset images (e.g. HAM10000) and prints
percentiles. Choose BLUR_THRESHOLD below the 1st percentile of good images so that real
dermoscopic photos are almost never rejected, then set it with the DC_BLUR_THRESHOLD
environment variable (or edit app/quality.py).

  python training/calibrate_quality.py --images /content/ham10000 --sample 500
"""
import argparse
import random
import sys
from pathlib import Path

import cv2
import numpy as np

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
from app.quality import check_quality  # noqa: E402

ap = argparse.ArgumentParser()
ap.add_argument("--images", required=True)
ap.add_argument("--sample", type=int, default=500)
args = ap.parse_args()

files = [p for p in Path(args.images).rglob("*") if p.suffix.lower() in (".jpg", ".jpeg", ".png")]
random.seed(0)
files = random.sample(files, min(args.sample, len(files)))
rows = [check_quality(cv2.imread(str(f)))["metrics"] for f in files]
for key in ("blur_variance", "brightness", "contrast"):
    v = np.array([r[key] for r in rows])
    print(f"{key:14s} p1={np.percentile(v, 1):8.1f}  p5={np.percentile(v, 5):8.1f}  median={np.median(v):8.1f}  p99={np.percentile(v, 99):8.1f}")
rejected = sum(not check_quality(cv2.imread(str(f)))["ok"] for f in files[:200])
print(f"With current thresholds, {rejected} of {min(200, len(files))} good images would be rejected.")
