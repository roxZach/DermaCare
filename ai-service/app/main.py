"""
DermaCare AI Engine (FastAPI).

Run:  uvicorn app.main:app --host 127.0.0.1 --port 8001
Docs: http://127.0.0.1:8001/docs

Endpoints used by the Laravel application:
  GET  /health            status of the model and chatbot
  POST /quality           image quality check only
  POST /analyze           quality check + preprocessing + classification + risk level
  POST /gradcam           Grad-CAM explanation heatmap (base64 PNG)
  POST /model/upload      upload a trained .pt file
  POST /model/reload      load a model file
  POST /chatbot/rebuild   (re)build the FAQ index
  POST /chatbot/ask       answer a question
"""
from pathlib import Path

from fastapi import FastAPI, File, HTTPException, UploadFile
from pydantic import BaseModel

from . import config
from .chatbot import FaqChatbot
from .model import DEVICE, LesionClassifier, read_image, to_bgr
from .quality import check_quality

app = FastAPI(title="DermaCare AI Engine", version="1.0")
classifier = LesionClassifier()
chatbot = FaqChatbot(engine=config.CHATBOT_ENGINE)

MAX_IMAGE_BYTES = 15 * 1024 * 1024


@app.on_event("startup")
def startup():
    try:
        info = classifier.load(config.MODEL_FILE)
        print(f"[AI Engine] Loaded model {info['file']} ({info['architecture']}) on {DEVICE}")
    except FileNotFoundError:
        print("[AI Engine] No trained model found in models/. Lesion analysis is disabled until you add one.")
    except Exception as e:  # corrupt or incompatible file
        print(f"[AI Engine] Could not load model: {e}")
    if chatbot.load_saved():
        print(f"[AI Engine] Chatbot index loaded: {chatbot.info()}")


async def _read(file: UploadFile):
    data = await file.read()
    if len(data) > MAX_IMAGE_BYTES:
        raise HTTPException(413, "Image is too large.")
    try:
        return read_image(data)
    except Exception:
        raise HTTPException(400, "The file is not a valid image.")


def _require_model():
    if not classifier.loaded:
        raise HTTPException(503, "No trained model is loaded in the AI Engine. Train a model (ai-service/training) "
                                 "and upload/activate it from Admin > AI model versions.")


@app.get("/health")
def health():
    return {"status": "ok", "device": str(DEVICE), "model": classifier.info(), "chatbot": chatbot.info()}


@app.post("/quality")
async def quality(file: UploadFile = File(...)):
    img = await _read(file)
    return {"quality": check_quality(to_bgr(img))}


@app.post("/analyze")
async def analyze(file: UploadFile = File(...)):
    img = await _read(file)
    q = check_quality(to_bgr(img))
    if not q["ok"]:
        # Alternative flow 4a: image rejected, nothing is classified
        return {"quality": q, "predicted_class": None}
    _require_model()
    return {"quality": q, **classifier.predict(img)}


@app.post("/gradcam")
async def gradcam(file: UploadFile = File(...)):
    _require_model()
    img = await _read(file)
    return classifier.gradcam(img)


@app.post("/model/upload")
async def model_upload(file: UploadFile = File(...)):
    name = Path(file.filename or "model.pt").name
    if not name.endswith((".pt", ".pth")):
        raise HTTPException(400, "Only .pt / .pth files are accepted.")
    (config.MODELS_DIR / name).write_bytes(await file.read())
    return {"saved": name}


class ReloadRequest(BaseModel):
    file_name: str


@app.post("/model/reload")
def model_reload(req: ReloadRequest):
    try:
        return {"model": classifier.load(req.file_name)}
    except FileNotFoundError:
        raise HTTPException(404, f"Model file {req.file_name} was not found in the AI Engine's models folder.")
    except Exception as e:
        raise HTTPException(400, f"Could not load the model: {e}")


class Faq(BaseModel):
    id: int | None = None
    question: str
    answer: str
    paraphrases: list[str] = []


class RebuildRequest(BaseModel):
    faqs: list[Faq]


@app.post("/chatbot/rebuild")
def chatbot_rebuild(req: RebuildRequest):
    return chatbot.build([f.model_dump() for f in req.faqs])


class AskRequest(BaseModel):
    question: str


@app.post("/chatbot/ask")
def chatbot_ask(req: AskRequest):
    try:
        return chatbot.ask(req.question)
    except LookupError:
        raise HTTPException(409, "index_empty")
