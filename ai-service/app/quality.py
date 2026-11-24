"""
Image quality check (use case: Validate Image Quality / AI Image Quality Check).
Classical computer vision with OpenCV, no training needed:
  * resolution      - the shorter side must be at least 150 px
  * blur            - variance of the Laplacian (low variance = few edges = blurry)
  * brightness      - mean brightness must not be too dark or over-exposed
  * contrast        - very low contrast usually means the lesion is not visible
  * framing         - warns if the darkest region (likely the lesion) is far from the centre
"""
import os

import cv2
import numpy as np

# Thresholds can be calibrated on real images with training/calibrate_quality.py
MIN_SIDE = 150
BLUR_THRESHOLD = float(os.getenv("DC_BLUR_THRESHOLD", 15.0))
DARK_THRESHOLD = 45
BRIGHT_THRESHOLD = 235
CONTRAST_THRESHOLD = 12


def check_quality(img_bgr: np.ndarray) -> dict:
    h, w = img_bgr.shape[:2]
    issues, warnings = [], []

    if min(h, w) < MIN_SIDE:
        issues.append(f"The image is too small ({w}x{h} px). Please use a photo of at least {MIN_SIDE} px.")

    # Work on a resized copy so thresholds do not depend on camera resolution
    scale = 512 / max(h, w)
    small = cv2.resize(img_bgr, (int(w * scale), int(h * scale))) if scale < 1 else img_bgr
    gray = cv2.cvtColor(small, cv2.COLOR_BGR2GRAY)

    blur = float(cv2.Laplacian(gray, cv2.CV_64F).var())
    brightness = float(gray.mean())
    contrast = float(gray.std())

    if blur < BLUR_THRESHOLD:
        issues.append("The photo looks blurry. Hold the camera steady and tap the screen to focus.")
    if brightness < DARK_THRESHOLD:
        issues.append("The photo is too dark. Use daylight or a brighter room.")
    elif brightness > BRIGHT_THRESHOLD:
        issues.append("The photo is over-exposed. Avoid flash and direct light.")
    if contrast < CONTRAST_THRESHOLD:
        issues.append("The lesion is not clearly visible (very low contrast).")

    # Framing: find the largest dark region with Otsu thresholding
    centre_offset = None
    try:
        g = cv2.GaussianBlur(gray, (7, 7), 0)
        _, mask = cv2.threshold(g, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)
        contours, _ = cv2.findContours(mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
        if contours:
            c = max(contours, key=cv2.contourArea)
            m = cv2.moments(c)
            if m["m00"] > 0:
                cx, cy = m["m10"] / m["m00"], m["m01"] / m["m00"]
                gh, gw = gray.shape
                centre_offset = float(np.hypot(cx / gw - 0.5, cy / gh - 0.5))
                if centre_offset > 0.3:
                    warnings.append("The lesion may not be centred. Try to keep it in the middle of the photo.")
    except cv2.error:
        pass

    return {
        "ok": not issues,
        "issues": issues,
        "warnings": warnings,
        "metrics": {
            "width": w, "height": h,
            "blur_variance": round(blur, 1),
            "brightness": round(brightness, 1),
            "contrast": round(contrast, 1),
            "centre_offset": None if centre_offset is None else round(centre_offset, 3),
        },
    }
