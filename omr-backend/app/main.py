"""
OMR Scanner API - Advanced FastAPI Backend
Features: Template management, student database, session management,
custom marking schemes, analytics, batch processing, export.
"""

import io
import os
import uuid
import json
import base64
import shutil
import zipfile
import asyncio
from datetime import datetime
from typing import Optional
from pathlib import Path

import cv2
import numpy as np
from fastapi import FastAPI, UploadFile, File, Form, HTTPException, Body, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import StreamingResponse, FileResponse
from fastapi.staticfiles import StaticFiles
from pydantic import BaseModel
from openpyxl import Workbook

from app.omr_engine import OMREngine, OMRTemplate, OMRResult, TEMPLATES

app = FastAPI(title="OMR Scanner API", version="2.0.0")

# Disable CORS. Do not remove this for full-stack development.
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Allows all origins
    allow_credentials=True,
    allow_methods=["*"],  # Allows all methods
    allow_headers=["*"],  # Allows all headers
)

# ---- In-memory storage ----
results_store: dict[str, dict] = {}
answer_keys_store: dict[str, dict] = {}
batch_store: dict[str, dict] = {}
templates_store: dict[str, dict] = {}
students_store: dict[str, dict] = {}
sessions_store: dict[str, dict] = {}

# Initialize default templates
for tid, tmpl in TEMPLATES.items():
    templates_store[tid] = {
        "id": tid,
        "is_builtin": True,
        "created_at": datetime.now().isoformat(),
        **tmpl.to_dict(),
    }

# Default engine
engine = OMREngine()

# Processed images storage
PROCESSED_DIR = Path("/tmp/omr_processed")
PROCESSED_DIR.mkdir(parents=True, exist_ok=True)

# Batch processing jobs tracker
batch_jobs: dict[str, dict] = {}


# ---- Pydantic models ----

class TemplateCreate(BaseModel):
    name: str = "Custom Template"
    total_questions: int = 75
    options_per_question: int = 4
    columns: int = 3
    questions_per_column: int = 25
    answer_region_x_start: float = 0.03
    answer_region_x_end: float = 0.93
    answer_region_y_start: float = 0.30
    answer_region_y_end: float = 0.93
    fill_threshold: float = 0.30
    bubble_padding: float = 0.12
    question_number_width: float = 0.15
    correct_marks: float = 1.0
    wrong_marks: float = 0.0
    unanswered_marks: float = 0.0
    has_barcode: bool = True
    use_pivots: bool = True
    barcode_region_x: float = 0.05
    barcode_region_y: float = 0.12
    barcode_region_w: float = 0.30
    barcode_region_h: float = 0.12
    regions: list = []
    image_width: int = 0
    image_height: int = 0


# Template images storage
TEMPLATE_IMAGES_DIR = Path("/tmp/omr_template_images")
TEMPLATE_IMAGES_DIR.mkdir(parents=True, exist_ok=True)


class StudentCreate(BaseModel):
    name: str
    enrollment_no: str
    class_name: str = ""
    section: str = ""
    roll_no: str = ""
    email: str = ""
    phone: str = ""


class SessionCreate(BaseModel):
    name: str
    subject: str = ""
    date: str = ""
    template_id: str = "75q_4opt"
    answer_key_id: str = ""
    correct_marks: float = 1.0
    wrong_marks: float = 0.0
    unanswered_marks: float = 0.0
    total_questions: int = 75


# ---- Helpers ----

def image_to_base64(image: np.ndarray) -> str:
    _, buffer = cv2.imencode('.jpg', image, [cv2.IMWRITE_JPEG_QUALITY, 85])
    return base64.b64encode(buffer).decode('utf-8')


def read_upload_image(contents: bytes) -> np.ndarray:
    nparr = np.frombuffer(contents, np.uint8)
    image = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    if image is None:
        raise HTTPException(status_code=400, detail="Invalid image file")
    return image


def get_engine_for_template(template_id: str) -> OMREngine:
    if template_id in templates_store:
        tmpl_data = templates_store[template_id]
        template = OMRTemplate.from_dict(tmpl_data)
        return OMREngine(template=template)
    return OMREngine()


# ---- Health & Info ----

@app.get("/healthz")
async def healthz():
    return {"status": "ok"}


@app.get("/api/info")
async def api_info():
    return {
        "name": "OMR Scanner API",
        "version": "2.0.0",
        "supported_formats": ["png", "jpg", "jpeg", "bmp", "tiff"],
        "max_questions": 200,
        "options_per_question": 4,
        "features": [
            "template_management",
            "barcode_detection",
            "student_database",
            "session_management",
            "custom_marking_schemes",
            "analytics",
            "batch_processing",
            "csv_excel_export",
        ],
    }


# ---- Dashboard ----

@app.get("/api/dashboard")
async def get_dashboard():
    total_sessions = len(sessions_store)
    total_students = len(students_store)
    total_scanned = len(results_store)
    total_templates = len(templates_store)
    total_answer_keys = len(answer_keys_store)

    recent_sessions = sorted(
        sessions_store.values(),
        key=lambda s: s.get("created_at", ""),
        reverse=True,
    )[:5]

    recent_results = sorted(
        results_store.values(),
        key=lambda r: r.get("processed_at", ""),
        reverse=True,
    )[:10]

    # Compute score distribution from all graded results
    score_dist = {"0-20": 0, "21-40": 0, "41-60": 0, "61-80": 0, "81-100": 0}
    for r in results_store.values():
        g = r.get("grading")
        if g and "percentage" in g:
            pct = g["percentage"]
            if pct <= 20:
                score_dist["0-20"] += 1
            elif pct <= 40:
                score_dist["21-40"] += 1
            elif pct <= 60:
                score_dist["41-60"] += 1
            elif pct <= 80:
                score_dist["61-80"] += 1
            else:
                score_dist["81-100"] += 1

    return {
        "total_sessions": total_sessions,
        "total_students": total_students,
        "total_scanned": total_scanned,
        "total_templates": total_templates,
        "total_answer_keys": total_answer_keys,
        "recent_sessions": recent_sessions,
        "recent_results": recent_results,
        "score_distribution": score_dist,
    }


# ---- Template Management ----

@app.get("/api/templates")
async def list_templates():
    return list(templates_store.values())


@app.get("/api/templates/{template_id}")
async def get_template(template_id: str):
    if template_id not in templates_store:
        raise HTTPException(status_code=404, detail="Template not found")
    return templates_store[template_id]


@app.post("/api/templates")
async def create_template(data: TemplateCreate):
    tid = str(uuid.uuid4())[:8]
    data_dict = data.model_dump()
    regions = data_dict.pop("regions", [])
    image_width = data_dict.pop("image_width", 0)
    image_height = data_dict.pop("image_height", 0)
    tmpl = OMRTemplate.from_dict(data_dict)
    templates_store[tid] = {
        "id": tid,
        "is_builtin": False,
        "created_at": datetime.now().isoformat(),
        "regions": regions,
        "image_width": image_width,
        "image_height": image_height,
        **tmpl.to_dict(),
    }
    return templates_store[tid]


@app.post("/api/template-image")
async def upload_template_image(
    image: UploadFile = File(...),
    template_id: str = Form(""),
    regions: str = Form("[]"),
):
    """Upload a template image and associate it with a template."""
    contents = await image.read()
    # Save image to template images dir
    filename = f"{template_id or 'temp'}_{uuid.uuid4().hex[:8]}.jpg"
    filepath = TEMPLATE_IMAGES_DIR / filename
    with open(filepath, "wb") as f:
        f.write(contents)
    
    # Update template with image URL if template exists
    if template_id and template_id in templates_store:
        templates_store[template_id]["image_url"] = f"/api/template-images/{filename}"
        try:
            templates_store[template_id]["regions"] = json.loads(regions)
        except json.JSONDecodeError:
            pass
    
    return {"filename": filename, "url": f"/api/template-images/{filename}"}


@app.get("/api/template-images/{filename}")
async def get_template_image(filename: str):
    """Serve a template image."""
    filepath = TEMPLATE_IMAGES_DIR / filename
    if not filepath.exists():
        raise HTTPException(status_code=404, detail="Image not found")
    return FileResponse(str(filepath), media_type="image/jpeg")


@app.put("/api/templates/{template_id}")
async def update_template(template_id: str, data: TemplateCreate):
    if template_id not in templates_store:
        raise HTTPException(status_code=404, detail="Template not found")
    old = templates_store[template_id]
    tmpl = OMRTemplate.from_dict(data.model_dump())
    templates_store[template_id] = {
        "id": template_id,
        "is_builtin": old.get("is_builtin", False),
        "created_at": old.get("created_at", datetime.now().isoformat()),
        "updated_at": datetime.now().isoformat(),
        **tmpl.to_dict(),
    }
    return templates_store[template_id]


@app.delete("/api/templates/{template_id}")
async def delete_template(template_id: str):
    if template_id not in templates_store:
        raise HTTPException(status_code=404, detail="Template not found")
    if templates_store[template_id].get("is_builtin"):
        raise HTTPException(status_code=400, detail="Cannot delete built-in template")
    del templates_store[template_id]
    return {"status": "deleted"}


# ---- Student Management ----

@app.get("/api/students")
async def list_students():
    return list(students_store.values())


@app.get("/api/students/{student_id}")
async def get_student(student_id: str):
    if student_id not in students_store:
        raise HTTPException(status_code=404, detail="Student not found")
    return students_store[student_id]


@app.post("/api/students")
async def create_student(data: StudentCreate):
    sid = str(uuid.uuid4())[:8]
    students_store[sid] = {
        "id": sid,
        "created_at": datetime.now().isoformat(),
        **data.model_dump(),
    }
    return students_store[sid]


@app.post("/api/students/bulk")
async def bulk_create_students(students: list[StudentCreate]):
    created = []
    for s in students:
        sid = str(uuid.uuid4())[:8]
        students_store[sid] = {
            "id": sid,
            "created_at": datetime.now().isoformat(),
            **s.model_dump(),
        }
        created.append(students_store[sid])
    return {"created": len(created), "students": created}


@app.put("/api/students/{student_id}")
async def update_student(student_id: str, data: StudentCreate):
    if student_id not in students_store:
        raise HTTPException(status_code=404, detail="Student not found")
    old = students_store[student_id]
    students_store[student_id] = {
        "id": student_id,
        "created_at": old.get("created_at", datetime.now().isoformat()),
        "updated_at": datetime.now().isoformat(),
        **data.model_dump(),
    }
    return students_store[student_id]


@app.delete("/api/students/{student_id}")
async def delete_student(student_id: str):
    if student_id not in students_store:
        raise HTTPException(status_code=404, detail="Student not found")
    del students_store[student_id]
    return {"status": "deleted"}


# ---- Session (Exam) Management ----

@app.get("/api/sessions")
async def list_sessions():
    sessions = list(sessions_store.values())
    return sorted(sessions, key=lambda s: s.get("created_at", ""), reverse=True)


@app.get("/api/sessions/{session_id}")
async def get_session(session_id: str):
    if session_id not in sessions_store:
        raise HTTPException(status_code=404, detail="Session not found")
    return sessions_store[session_id]


@app.post("/api/sessions")
async def create_session(data: SessionCreate):
    sid = str(uuid.uuid4())[:8]
    sessions_store[sid] = {
        "id": sid,
        "created_at": datetime.now().isoformat(),
        "results": [],
        "total_scanned": 0,
        "status": "active",
        **data.model_dump(),
    }
    return sessions_store[sid]


@app.put("/api/sessions/{session_id}")
async def update_session(session_id: str, data: SessionCreate):
    if session_id not in sessions_store:
        raise HTTPException(status_code=404, detail="Session not found")
    old = sessions_store[session_id]
    sessions_store[session_id] = {
        "id": session_id,
        "created_at": old.get("created_at", datetime.now().isoformat()),
        "updated_at": datetime.now().isoformat(),
        "results": old.get("results", []),
        "total_scanned": old.get("total_scanned", 0),
        "status": old.get("status", "active"),
        **data.model_dump(),
    }
    return sessions_store[session_id]


@app.delete("/api/sessions/{session_id}")
async def delete_session(session_id: str):
    if session_id not in sessions_store:
        raise HTTPException(status_code=404, detail="Session not found")
    del sessions_store[session_id]
    return {"status": "deleted"}


# ---- OMR Processing ----

@app.post("/api/process")
async def process_single_sheet(
    file: UploadFile = File(...),
    template_id: str = Form("75q_4opt"),
    answer_key_id: Optional[str] = Form(None),
    session_id: Optional[str] = Form(None),
    generate_debug: bool = Form(True),
):
    if not file.content_type or not file.content_type.startswith("image/"):
        raise HTTPException(status_code=400, detail="File must be an image")

    contents = await file.read()
    image = read_upload_image(contents)

    proc_engine = get_engine_for_template(template_id)
    result = proc_engine.process_sheet(image, generate_debug=generate_debug)
    result_id = str(uuid.uuid4())

    # Try to match enrollment to student
    student_name = ""
    if result.enrollment_no:
        for s in students_store.values():
            if s.get("enrollment_no") == result.enrollment_no:
                student_name = s.get("name", "")
                break

    response: dict = {
        "result_id": result_id,
        "filename": file.filename,
        "template_id": template_id,
        "enrollment_no": result.enrollment_no,
        "barcode_data": result.barcode_data,
        "student_name": student_name,
        "answers": {str(k): v for k, v in result.answers.items()},
        "total_questions": result.total_questions,
        "answered_count": result.answered_count,
        "unanswered_count": result.unanswered_count,
        "multiple_marked": result.multiple_marked,
        "confidence": round(result.confidence, 3),
        "processed_at": datetime.now().isoformat(),
    }

    if result.debug_image is not None:
        response["debug_image"] = image_to_base64(result.debug_image)

    # Grade if answer key provided
    if answer_key_id and answer_key_id in answer_keys_store:
        ak = answer_keys_store[answer_key_id]
        grading = proc_engine.grade_sheet(
            result, ak["answers"],
            correct_marks=ak.get("correct_marks"),
            wrong_marks=ak.get("wrong_marks"),
            unanswered_marks=ak.get("unanswered_marks"),
        )
        response["grading"] = grading
    elif session_id and session_id in sessions_store:
        sess = sessions_store[session_id]
        akid = sess.get("answer_key_id", "")
        if akid and akid in answer_keys_store:
            ak = answer_keys_store[akid]
            grading = proc_engine.grade_sheet(
                result, ak["answers"],
                correct_marks=sess.get("correct_marks"),
                wrong_marks=sess.get("wrong_marks"),
                unanswered_marks=sess.get("unanswered_marks"),
            )
            response["grading"] = grading

    results_store[result_id] = response

    # Add to session
    if session_id and session_id in sessions_store:
        sessions_store[session_id]["results"].append(response)
        sessions_store[session_id]["total_scanned"] = len(
            sessions_store[session_id]["results"]
        )
        response["session_id"] = session_id

    return response


@app.post("/api/process-batch")
async def process_batch(
    files: list[UploadFile] = File(...),
    template_id: str = Form("75q_4opt"),
    answer_key_id: Optional[str] = Form(None),
    session_id: Optional[str] = Form(None),
):
    batch_id = str(uuid.uuid4())
    batch_results = []

    proc_engine = get_engine_for_template(template_id)

    answer_key = None
    marking = {}
    if answer_key_id and answer_key_id in answer_keys_store:
        ak = answer_keys_store[answer_key_id]
        answer_key = ak["answers"]
        marking = {
            "correct_marks": ak.get("correct_marks"),
            "wrong_marks": ak.get("wrong_marks"),
            "unanswered_marks": ak.get("unanswered_marks"),
        }
    elif session_id and session_id in sessions_store:
        sess = sessions_store[session_id]
        akid = sess.get("answer_key_id", "")
        if akid and akid in answer_keys_store:
            answer_key = answer_keys_store[akid]["answers"]
            marking = {
                "correct_marks": sess.get("correct_marks"),
                "wrong_marks": sess.get("wrong_marks"),
                "unanswered_marks": sess.get("unanswered_marks"),
            }

    for file in files:
        try:
            contents = await file.read()
            image = read_upload_image(contents)
            result = proc_engine.process_sheet(image, generate_debug=False)
            result_id = str(uuid.uuid4())

            student_name = ""
            if result.enrollment_no:
                for s in students_store.values():
                    if s.get("enrollment_no") == result.enrollment_no:
                        student_name = s.get("name", "")
                        break

            result_data: dict = {
                "result_id": result_id,
                "filename": file.filename,
                "template_id": template_id,
                "enrollment_no": result.enrollment_no,
                "barcode_data": result.barcode_data,
                "student_name": student_name,
                "answers": {str(k): v for k, v in result.answers.items()},
                "total_questions": result.total_questions,
                "answered_count": result.answered_count,
                "unanswered_count": result.unanswered_count,
                "multiple_marked": result.multiple_marked,
                "confidence": round(result.confidence, 3),
                "status": "success",
            }

            if answer_key:
                grading = proc_engine.grade_sheet(result, answer_key, **marking)
                result_data["grading"] = grading

            results_store[result_id] = result_data
            batch_results.append(result_data)

        except Exception as e:
            batch_results.append({
                "filename": file.filename,
                "status": "error",
                "error": str(e),
            })

    batch_data = {
        "batch_id": batch_id,
        "template_id": template_id,
        "total_sheets": len(files),
        "successful": sum(
            1 for r in batch_results if r.get("status") == "success"
        ),
        "failed": sum(
            1 for r in batch_results if r.get("status") == "error"
        ),
        "results": batch_results,
        "processed_at": datetime.now().isoformat(),
    }

    # Add results to session
    if session_id and session_id in sessions_store:
        for r in batch_results:
            if r.get("status") == "success":
                sessions_store[session_id]["results"].append(r)
        sessions_store[session_id]["total_scanned"] = len(
            sessions_store[session_id]["results"]
        )
        batch_data["session_id"] = session_id

    batch_store[batch_id] = batch_data
    return batch_data


@app.get("/api/results/{result_id}")
async def get_result(result_id: str):
    if result_id not in results_store:
        raise HTTPException(status_code=404, detail="Result not found")
    return results_store[result_id]


@app.get("/api/batches/{batch_id}")
async def get_batch(batch_id: str):
    if batch_id not in batch_store:
        raise HTTPException(status_code=404, detail="Batch not found")
    return batch_store[batch_id]


# ---- Answer Key Management ----

@app.post("/api/answer-keys")
async def create_answer_key(
    name: str = Form(...),
    total_questions: int = Form(75),
    answers_json: str = Form(...),
    correct_marks: float = Form(1.0),
    wrong_marks: float = Form(0.0),
    unanswered_marks: float = Form(0.0),
):
    try:
        answers = json.loads(answers_json)
        answers = {int(k): int(v) for k, v in answers.items()}
    except (json.JSONDecodeError, ValueError) as e:
        raise HTTPException(
            status_code=400, detail="Invalid answers JSON: " + str(e)
        )

    key_id = str(uuid.uuid4())
    answer_keys_store[key_id] = {
        "id": key_id,
        "name": name,
        "total_questions": total_questions,
        "answers": answers,
        "correct_marks": correct_marks,
        "wrong_marks": wrong_marks,
        "unanswered_marks": unanswered_marks,
        "created_at": datetime.now().isoformat(),
    }
    return answer_keys_store[key_id]


@app.get("/api/answer-keys")
async def list_answer_keys():
    return list(answer_keys_store.values())


@app.get("/api/answer-keys/{key_id}")
async def get_answer_key(key_id: str):
    if key_id not in answer_keys_store:
        raise HTTPException(status_code=404, detail="Answer key not found")
    return answer_keys_store[key_id]


@app.delete("/api/answer-keys/{key_id}")
async def delete_answer_key(key_id: str):
    if key_id not in answer_keys_store:
        raise HTTPException(status_code=404, detail="Answer key not found")
    del answer_keys_store[key_id]
    return {"status": "deleted"}


# ---- Analytics ----

@app.get("/api/analytics/session/{session_id}")
async def session_analytics(session_id: str):
    if session_id not in sessions_store:
        raise HTTPException(status_code=404, detail="Session not found")
    sess = sessions_store[session_id]
    results = sess.get("results", [])
    graded = [r for r in results if "grading" in r]
    if not graded:
        return {
            "session_id": session_id,
            "total_scanned": len(results),
            "total_graded": 0,
            "message": "No graded results yet",
        }

    scores = [r["grading"]["score"] for r in graded]
    percentages = [r["grading"]["percentage"] for r in graded]
    correct_counts = [r["grading"]["correct"] for r in graded]

    avg_score = sum(scores) / len(scores) if scores else 0
    avg_pct = sum(percentages) / len(percentages) if percentages else 0
    max_score = max(scores) if scores else 0
    min_score = min(scores) if scores else 0

    # Topper list (top 10)
    sorted_results = sorted(graded, key=lambda r: r["grading"]["score"], reverse=True)
    toppers = []
    for i, r in enumerate(sorted_results[:10], 1):
        toppers.append({
            "rank": i,
            "enrollment_no": r.get("enrollment_no", ""),
            "student_name": r.get("student_name", ""),
            "filename": r.get("filename", ""),
            "score": r["grading"]["score"],
            "percentage": r["grading"]["percentage"],
            "correct": r["grading"]["correct"],
            "wrong": r["grading"]["wrong"],
        })

    # Score distribution
    dist = {"0-20": 0, "21-40": 0, "41-60": 0, "61-80": 0, "81-100": 0}
    for p in percentages:
        if p <= 20:
            dist["0-20"] += 1
        elif p <= 40:
            dist["21-40"] += 1
        elif p <= 60:
            dist["41-60"] += 1
        elif p <= 80:
            dist["61-80"] += 1
        else:
            dist["81-100"] += 1

    # Question-wise analysis
    total_q = sess.get("total_questions", 75)
    q_analysis = []
    for q in range(1, total_q + 1):
        q_correct = 0
        q_wrong = 0
        q_unanswered = 0
        for r in graded:
            details = r["grading"].get("details", [])
            for d in details:
                if d["question"] == q:
                    if d["status"] == "correct":
                        q_correct += 1
                    elif d["status"] == "wrong":
                        q_wrong += 1
                    elif d["status"] == "unanswered":
                        q_unanswered += 1
                    break
        total = q_correct + q_wrong + q_unanswered
        difficulty = round(
            (1 - q_correct / max(total, 1)) * 100, 1
        )
        q_analysis.append({
            "question": q,
            "correct": q_correct,
            "wrong": q_wrong,
            "unanswered": q_unanswered,
            "difficulty_index": difficulty,
        })

    # Pass/fail counts (50% threshold)
    passed = sum(1 for p in percentages if p >= 50)
    failed = len(percentages) - passed

    return {
        "session_id": session_id,
        "session_name": sess.get("name", ""),
        "subject": sess.get("subject", ""),
        "total_scanned": len(results),
        "total_graded": len(graded),
        "average_score": round(avg_score, 2),
        "average_percentage": round(avg_pct, 2),
        "highest_score": max_score,
        "lowest_score": min_score,
        "passed": passed,
        "failed": failed,
        "toppers": toppers,
        "score_distribution": dist,
        "question_analysis": q_analysis,
    }


@app.get("/api/analytics/student/{enrollment_no}")
async def student_analytics(enrollment_no: str):
    student_results = []
    for r in results_store.values():
        if r.get("enrollment_no") == enrollment_no:
            student_results.append(r)
    if not student_results:
        return {
            "enrollment_no": enrollment_no,
            "total_exams": 0,
            "message": "No results found for this student",
        }

    graded = [r for r in student_results if "grading" in r]
    scores = [r["grading"]["score"] for r in graded]
    percentages = [r["grading"]["percentage"] for r in graded]

    student_info = None
    for s in students_store.values():
        if s.get("enrollment_no") == enrollment_no:
            student_info = s
            break

    return {
        "enrollment_no": enrollment_no,
        "student_info": student_info,
        "total_exams": len(student_results),
        "total_graded": len(graded),
        "average_score": round(
            sum(scores) / len(scores), 2
        ) if scores else 0,
        "average_percentage": round(
            sum(percentages) / len(percentages), 2
        ) if percentages else 0,
        "highest_score": max(scores) if scores else 0,
        "lowest_score": min(scores) if scores else 0,
        "history": [
            {
                "result_id": r.get("result_id"),
                "filename": r.get("filename"),
                "processed_at": r.get("processed_at"),
                "grading": r.get("grading"),
            }
            for r in sorted(
                student_results,
                key=lambda x: x.get("processed_at", ""),
                reverse=True,
            )
        ],
    }


# ---- Export ----

@app.post("/api/export/csv")
async def export_results_csv(
    result_ids: Optional[str] = Form(None),
    batch_id: Optional[str] = Form(None),
    session_id: Optional[str] = Form(None),
):
    results_to_export = _gather_export_results(
        result_ids, batch_id, session_id
    )
    if not results_to_export:
        raise HTTPException(
            status_code=404, detail="No results found to export"
        )

    max_q = max(r.get("total_questions", 75) for r in results_to_export)
    has_grading = any("grading" in r for r in results_to_export)

    lines = []
    header = [
        "S.No", "Filename", "Enrollment No", "Student Name",
        "Answered", "Unanswered", "Multiple Marked"
    ]
    for q in range(1, max_q + 1):
        header.append("Q" + str(q))
    if has_grading:
        header.extend(["Correct", "Wrong", "Score", "Max Score", "Percentage"])
    lines.append(",".join(header))

    for idx, r in enumerate(results_to_export, 1):
        row = [
            str(idx),
            r.get("filename", ""),
            r.get("enrollment_no", ""),
            r.get("student_name", ""),
            str(r.get("answered_count", 0)),
            str(r.get("unanswered_count", 0)),
            str(r.get("multiple_marked", 0)),
        ]
        answers = r.get("answers", {})
        for q in range(1, max_q + 1):
            ans = answers.get(str(q), answers.get(q, 0))
            if ans == -1:
                row.append("M")
            elif ans == 0:
                row.append("")
            else:
                row.append(str(ans))

        if has_grading and "grading" in r:
            g = r["grading"]
            row.extend([
                str(g.get("correct", 0)),
                str(g.get("wrong", 0)),
                str(g.get("score", 0)),
                str(g.get("max_score", 0)),
                str(g.get("percentage", 0)),
            ])
        lines.append(",".join(row))

    csv_content = "\n".join(lines)
    ts = datetime.now().strftime("%Y%m%d_%H%M%S")
    return StreamingResponse(
        io.BytesIO(csv_content.encode()),
        media_type="text/csv",
        headers={
            "Content-Disposition": "attachment; filename=omr_results_" + ts + ".csv"
        },
    )


@app.post("/api/export/excel")
async def export_results_excel(
    result_ids: Optional[str] = Form(None),
    batch_id: Optional[str] = Form(None),
    session_id: Optional[str] = Form(None),
):
    results_to_export = _gather_export_results(
        result_ids, batch_id, session_id
    )
    if not results_to_export:
        raise HTTPException(
            status_code=404, detail="No results found to export"
        )

    max_q = max(r.get("total_questions", 75) for r in results_to_export)
    has_grading = any("grading" in r for r in results_to_export)

    wb = Workbook()
    ws = wb.active
    if ws is None:
        raise HTTPException(
            status_code=500, detail="Failed to create workbook"
        )
    ws.title = "OMR Results"

    header = [
        "S.No", "Filename", "Enrollment No", "Student Name",
        "Answered", "Unanswered", "Multiple Marked"
    ]
    for q in range(1, max_q + 1):
        header.append("Q" + str(q))
    if has_grading:
        header.extend(["Correct", "Wrong", "Score", "Max Score", "Percentage"])
    ws.append(header)

    for idx, r in enumerate(results_to_export, 1):
        row: list = [
            idx,
            r.get("filename", ""),
            r.get("enrollment_no", ""),
            r.get("student_name", ""),
            r.get("answered_count", 0),
            r.get("unanswered_count", 0),
            r.get("multiple_marked", 0),
        ]
        answers = r.get("answers", {})
        for q in range(1, max_q + 1):
            ans = answers.get(str(q), answers.get(q, 0))
            if ans == -1:
                row.append("M")
            elif ans == 0:
                row.append("")
            else:
                row.append(ans)

        if has_grading and "grading" in r:
            g = r["grading"]
            row.extend([
                g.get("correct", 0),
                g.get("wrong", 0),
                g.get("score", 0),
                g.get("max_score", 0),
                g.get("percentage", 0),
            ])
        ws.append(row)

    buffer = io.BytesIO()
    wb.save(buffer)
    buffer.seek(0)

    ts = datetime.now().strftime("%Y%m%d_%H%M%S")
    return StreamingResponse(
        buffer,
        media_type="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        headers={
            "Content-Disposition": "attachment; filename=omr_results_" + ts + ".xlsx"
        },
    )


def _gather_export_results(
    result_ids: Optional[str],
    batch_id: Optional[str],
    session_id: Optional[str],
) -> list[dict]:
    results_to_export: list[dict] = []
    if session_id and session_id in sessions_store:
        results_to_export = sessions_store[session_id].get("results", [])
    elif batch_id and batch_id in batch_store:
        results_to_export = [
            r for r in batch_store[batch_id]["results"]
            if r.get("status") == "success"
        ]
    elif result_ids:
        for rid in result_ids.split(","):
            rid = rid.strip()
            if rid in results_store:
                results_to_export.append(results_store[rid])
    return results_to_export


# ---- Settings ----

@app.post("/api/settings/threshold")
async def update_threshold(fill_threshold: float = Form(0.35)):
    if not 0.1 <= fill_threshold <= 0.9:
        raise HTTPException(
            status_code=400,
            detail="Threshold must be between 0.1 and 0.9",
        )
    engine.template.fill_threshold = fill_threshold
    return {"fill_threshold": fill_threshold}


@app.get("/api/settings")
async def get_settings():
    t = engine.template
    return {
        "fill_threshold": t.fill_threshold,
        "total_questions": t.total_questions,
        "options_per_question": t.options_per_question,
    }


# ---- Image Saving & Batch Job Processing ----

def _save_processed_image(
    image: np.ndarray,
    debug_image: np.ndarray | None,
    enrollment_no: str,
    filename: str,
    session_name: str,
    index: int,
) -> dict:
    """Save original and debug images organized by session/enrollment."""
    session_dir = PROCESSED_DIR / _safe_filename(session_name or "default")
    session_dir.mkdir(parents=True, exist_ok=True)

    # Determine the save name: enrollment_no or original filename
    if enrollment_no:
        safe_name = _safe_filename(enrollment_no)
    else:
        safe_name = _safe_filename(Path(filename).stem) if filename else f"sheet_{index}"

    # Save original image
    orig_path = session_dir / f"{safe_name}_original.jpg"
    cv2.imwrite(str(orig_path), image, [cv2.IMWRITE_JPEG_QUALITY, 90])

    # Save debug/annotated image if available
    debug_path = None
    if debug_image is not None:
        debug_path = session_dir / f"{safe_name}_result.jpg"
        cv2.imwrite(str(debug_path), debug_image, [cv2.IMWRITE_JPEG_QUALITY, 90])

    return {
        "original_path": str(orig_path),
        "debug_path": str(debug_path) if debug_path else None,
        "save_name": safe_name,
    }


def _safe_filename(name: str) -> str:
    """Make a string safe for use as a filename."""
    return "".join(c if c.isalnum() or c in "-_." else "_" for c in name).strip("_")[:100]


@app.post("/api/process-single-save")
async def process_single_and_save(
    file: UploadFile = File(...),
    template_id: str = Form("75q_4opt"),
    answer_key_id: Optional[str] = Form(None),
    session_id: Optional[str] = Form(None),
    session_name: str = Form("default"),
    sheet_index: int = Form(0),
):
    """Process a single sheet and save image with enrollment name."""
    contents = await file.read()
    image = read_upload_image(contents)

    proc_engine = get_engine_for_template(template_id)
    result = proc_engine.process_sheet(image, generate_debug=True)
    result_id = str(uuid.uuid4())

    # Match enrollment to student
    student_name = ""
    if result.enrollment_no:
        for s in students_store.values():
            if s.get("enrollment_no") == result.enrollment_no:
                student_name = s.get("name", "")
                break

    # Save images with enrollment name
    save_info = _save_processed_image(
        image, result.debug_image,
        result.enrollment_no, file.filename or "",
        session_name, sheet_index,
    )

    response: dict = {
        "result_id": result_id,
        "filename": file.filename,
        "template_id": template_id,
        "enrollment_no": result.enrollment_no,
        "barcode_data": result.barcode_data,
        "student_name": student_name,
        "answers": {str(k): v for k, v in result.answers.items()},
        "total_questions": result.total_questions,
        "answered_count": result.answered_count,
        "unanswered_count": result.unanswered_count,
        "multiple_marked": result.multiple_marked,
        "confidence": round(result.confidence, 3),
        "processed_at": datetime.now().isoformat(),
        "saved_as": save_info["save_name"],
        "status": "success",
    }

    # Grade if answer key provided
    answer_key = None
    marking = {}
    if answer_key_id and answer_key_id in answer_keys_store:
        ak = answer_keys_store[answer_key_id]
        answer_key = ak["answers"]
        marking = {
            "correct_marks": ak.get("correct_marks"),
            "wrong_marks": ak.get("wrong_marks"),
            "unanswered_marks": ak.get("unanswered_marks"),
        }
    elif session_id and session_id in sessions_store:
        sess = sessions_store[session_id]
        akid = sess.get("answer_key_id", "")
        if akid and akid in answer_keys_store:
            answer_key = answer_keys_store[akid]["answers"]
            marking = {
                "correct_marks": sess.get("correct_marks"),
                "wrong_marks": sess.get("wrong_marks"),
                "unanswered_marks": sess.get("unanswered_marks"),
            }

    if answer_key:
        grading = proc_engine.grade_sheet(result, answer_key, **marking)
        response["grading"] = grading

    results_store[result_id] = response

    # Add to session
    if session_id and session_id in sessions_store:
        sessions_store[session_id]["results"].append(response)
        sessions_store[session_id]["total_scanned"] = len(
            sessions_store[session_id]["results"]
        )
        response["session_id"] = session_id

    return response


@app.get("/api/download-processed/{session_name}")
async def download_processed_images(session_name: str):
    """Download all processed images for a session as a ZIP file."""
    session_dir = PROCESSED_DIR / _safe_filename(session_name)
    if not session_dir.exists() or not any(session_dir.iterdir()):
        raise HTTPException(
            status_code=404,
            detail="No processed images found for this session"
        )

    # Create ZIP file
    zip_path = PROCESSED_DIR / f"{_safe_filename(session_name)}_results.zip"
    with zipfile.ZipFile(str(zip_path), "w", zipfile.ZIP_DEFLATED) as zf:
        for img_file in sorted(session_dir.iterdir()):
            if img_file.is_file() and img_file.suffix.lower() in (
                ".jpg", ".jpeg", ".png", ".bmp"
            ):
                zf.write(str(img_file), img_file.name)

    return FileResponse(
        str(zip_path),
        media_type="application/zip",
        filename=f"{session_name}_processed_images.zip",
    )


@app.get("/api/processed-images/{session_name}")
async def list_processed_images(session_name: str):
    """List all processed images for a session."""
    session_dir = PROCESSED_DIR / _safe_filename(session_name)
    if not session_dir.exists():
        return {"session_name": session_name, "images": [], "count": 0}

    images = []
    for img_file in sorted(session_dir.iterdir()):
        if img_file.is_file() and img_file.suffix.lower() in (
            ".jpg", ".jpeg", ".png", ".bmp"
        ):
            images.append({
                "filename": img_file.name,
                "size": img_file.stat().st_size,
                "path": str(img_file),
            })

    return {
        "session_name": session_name,
        "images": images,
        "count": len(images),
    }


@app.delete("/api/processed-images/{session_name}")
async def clear_processed_images(session_name: str):
    """Clear all processed images for a session."""
    session_dir = PROCESSED_DIR / _safe_filename(session_name)
    if session_dir.exists():
        shutil.rmtree(str(session_dir))
    return {"status": "cleared", "session_name": session_name}


@app.get("/api/batch-status/{batch_id}")
async def get_batch_status(batch_id: str):
    """Get the status of a batch processing job."""
    if batch_id not in batch_jobs:
        raise HTTPException(status_code=404, detail="Batch job not found")
    return batch_jobs[batch_id]


# ---- Serve Frontend Static Files ----
STATIC_DIR = Path(__file__).parent.parent / "static"

@app.get("/")
async def serve_index():
    index_file = STATIC_DIR / "index.html"
    if index_file.exists():
        return FileResponse(str(index_file), media_type="text/html")
    return {"message": "OMR Scanner API v2.0 - No frontend found"}

@app.get("/app.js")
async def serve_app_js():
    js_file = STATIC_DIR / "app.js"
    if js_file.exists():
        return FileResponse(str(js_file), media_type="application/javascript")
    raise HTTPException(status_code=404)

@app.get("/style.css")
async def serve_style_css():
    css_file = STATIC_DIR / "style.css"
    if css_file.exists():
        return FileResponse(str(css_file), media_type="text/css")
    raise HTTPException(status_code=404)

@app.get("/template-editor.js")
async def serve_template_editor_js():
    js_file = STATIC_DIR / "template-editor.js"
    if js_file.exists():
        return FileResponse(str(js_file), media_type="application/javascript")
    raise HTTPException(status_code=404)
