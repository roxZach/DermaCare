# DermaCare AI Engine

A Python service (FastAPI) used by the Laravel app for the two intelligent features.

```
ai-service/
├── app/
│   ├── main.py        FastAPI endpoints
│   ├── quality.py     image quality check (OpenCV)
│   ├── model.py       lesion classifier: preprocessing, prediction, risk level, Grad-CAM
│   ├── chatbot.py     FAQ chatbot (TF-IDF + synonyms, optional Sentence-BERT)
│   └── config.py      settings (environment variables)
├── models/            trained .pt files go here (the newest loads at start-up)
├── data/              saved chatbot index
└── training/
    ├── DermaCare_Train_Colab.ipynb   step-by-step training on Google Colab
    ├── train.py                      training script (HAM10000)
    ├── evaluate.py                   metrics + stand-alone evaluation on other datasets
    ├── calibrate_quality.py          calibrate the image quality thresholds
    ├── chatbot_eval.py               chatbot top-1/top-3 accuracy + threshold selection
    └── faqs.json                     FAQ dataset (same as database/data/faqs.json)
```

## Run
```bash
pip install -r requirements.txt
uvicorn app.main:app --host 127.0.0.1 --port 8001
```
Interactive API docs: http://127.0.0.1:8001/docs

| Endpoint | Purpose (use case) |
|---|---|
| `GET /health` | status of the model and chatbot |
| `POST /quality` | Validate Image Quality / AI Image Quality Check |
| `POST /analyze` | Analyze Skin Lesion: quality → preprocess → classify → risk level |
| `POST /gradcam` | Generate Grad-CAM Heatmap |
| `POST /model/upload`, `POST /model/reload` | Upload & Activate Model |
| `POST /chatbot/rebuild` | Rebuild Chatbot Index |
| `POST /chatbot/ask` | Match Question to FAQ Intent |

Settings (environment variables): `DC_MODEL_FILE` (default: newest file), `DC_BLUR_THRESHOLD` (15), `DC_CHATBOT_ENGINE` (`tfidf` default, `sbert`, `auto`), `DC_CHATBOT_THRESHOLD` (0.40).

## 1. Skin lesion analysis

**Dataset:** HAM10000 (Tschandl et al., 2018): 10,015 dermoscopic images, 7 classes:
`akiec` actinic keratosis / intraepithelial carcinoma, `bcc` basal cell carcinoma, `bkl` benign keratosis, `df` dermatofibroma, `mel` melanoma, `nv` melanocytic nevus, `vasc` vascular lesion.

**Pipeline**
1. *Image quality check* (`quality.py`): resolution, blur (variance of the Laplacian), brightness, contrast and framing. Run `training/calibrate_quality.py` on the dataset to choose thresholds that don't reject real images.
2. *Preprocessing*: centre square crop, resize to 224×224, ImageNet normalisation (the same in training and in the service).
3. *Classification*: EfficientNet-B0 pretrained on ImageNet (timm), fine-tuned on HAM10000. Test-time augmentation averages the predictions over horizontal and vertical flips.
4. *Risk level*: weighted score = 1.0·P(mel) + 0.8·P(bcc) + 0.6·P(akiec). **High** if P(mel) ≥ the melanoma threshold or the score is ≥ 0.5; **Medium** if the score is ≥ 0.2; **Low** otherwise. The melanoma threshold is tuned on the validation set to reach 85 % melanoma sensitivity, because missing a melanoma is the worst error.
5. *Explanation*: Grad-CAM (Selvaraju et al., 2017) on the last convolutional layer.

**Training choices** (`train.py`)
* Train / validation / test split **by lesion_id** (about 70 / 15 / 15), so images of the same lesion never appear in both training and test data. Without this, results look too good.
* Class imbalance (about 67 % of the images are nevi): class-weighted cross-entropy (inverse square root of frequency) + label smoothing + augmentation (crop, flips, rotation, colour jitter).
* AdamW, cosine learning-rate schedule with warm-up, mixed precision on GPU.
* The best epoch is chosen by **validation balanced accuracy**.

**Metrics reported** (`*_metrics.json`): accuracy, balanced accuracy (main ISIC metric), macro F1, per-class precision/recall/F1, macro ROC-AUC, melanoma sensitivity and specificity, confusion matrix (PNG) and training curves (PNG).

**Extra evaluation for the report**
* Compare architectures: run `train.py --arch resnet50` (baseline) against `efficientnet_b0`.
* Test on smartphone images (PAD-UFES-20) or on images labelled with skin type (Fitzpatrick17k, DDI): make a CSV with `path,label[,group]` and run
  `python training/evaluate.py --model models/<file>.pt --csv test.csv`. The `group` column (e.g. Fitzpatrick type) gives balanced accuracy per group, which shows any skin-tone bias.
  Suggested PAD-UFES-20 mapping: ACK→akiec, BCC→bcc, MEL→mel, NEV→nv, SEK→bkl, SCC→akiec (approximation).

**Limitations to mention:** HAM10000 contains mostly lighter skin types and dermoscopic (not phone) images, so accuracy will be lower on darker skin and on ordinary photos. The tool is for decision support only.

## 2. FAQ chatbot

* Each FAQ = a question + paraphrases + an answer. The question text is normalised: lower-case, stop words removed, light stemming, and a domain synonym list (e.g. *price / cost / charge → fee*).
* **TF-IDF engine** (default): 80 % word 1–2-gram cosine similarity + 20 % character 3–5-gram similarity (to handle typos). The FAQ score is the best matching variant, blended with 35 % similarity to the answer text.
* **Sentence-BERT engine** (optional): `pip install sentence-transformers`, then set `DC_CHATBOT_ENGINE=sbert` (the first run downloads `all-MiniLM-L6-v2`, about 90 MB). Run `chatbot_eval.py --engine sbert` and use its recommended threshold (set `THRESHOLDS["sbert"]` in `app/chatbot.py`).
* If the best score is below the threshold, the question is escalated to staff and appears under *Admin → Unanswered questions*.

**Evaluation** (`python training/chatbot_eval.py`): leave-one-out over 101 paraphrases of 32 FAQs, plus 16 off-topic questions.

| Engine | Top-1 accuracy | Top-3 accuracy | Threshold | Correct answers kept | Off-topic rejected |
|---|---|---|---|---|---|
| TF-IDF, word + character n-grams (first version) | 34.7 % | 69.3 % | 0.37 | 77.1 % | 62.5 % |
| TF-IDF, words only + stop words + stemming | 31.7 % | 61.4 % | – | – | – |
| TF-IDF + stop words + stemming + synonyms + answer text | **60.4 %** | **86.1 %** | 0.40 | 78.7 % | 87.5 % |
| Sentence-BERT | run the script with `--engine sbert` | | | | |

The leave-one-out test is strict, because the held-out wording is never in the index. Adding more paraphrases per FAQ is the easiest way to improve accuracy.
