"""
Advanced OMR (Optical Mark Recognition) Engine
"""

import cv2
import numpy as np
from typing import Optional
from dataclasses import dataclass, field

try:
    from pyzbar.pyzbar import decode as decode_barcode
    HAS_PYZBAR = True
except ImportError:
    HAS_PYZBAR = False

try:
    import pytesseract
    HAS_TESSERACT = True
except ImportError:
    HAS_TESSERACT = False


@dataclass
class OMRTemplate:
    name: str = "Default 75Q"
    total_questions: int = 75
    options_per_question: int = 4
    columns: int = 3
    questions_per_column: int = 25
    answer_region_x_start: float = 0.03
    answer_region_x_end: float = 0.93
    answer_region_y_start: float = 0.30
    answer_region_y_end: float = 0.93
    fill_threshold: float = 0.30
    multi_mark_ratio: float = 1.4
    bubble_padding: float = 0.12
    question_number_width: float = 0.15
    correct_marks: float = 1.0
    wrong_marks: float = 0.0
    unanswered_marks: float = 0.0
    has_barcode: bool = True
    barcode_region_x: float = 0.05
    barcode_region_y: float = 0.12
    barcode_region_w: float = 0.30
    barcode_region_h: float = 0.12
    use_pivots: bool = True

    def get_column_regions(self) -> list[tuple[float, float, int, int]]:
        regions = []
        total_width = self.answer_region_x_end - self.answer_region_x_start
        col_width = total_width / self.columns
        gap = 0.01
        for i in range(self.columns):
            x_start = self.answer_region_x_start + i * col_width + gap
            x_end = self.answer_region_x_start + (i + 1) * col_width - gap
            q_start = i * self.questions_per_column + 1
            q_end = min((i + 1) * self.questions_per_column, self.total_questions)
            regions.append((x_start, x_end, q_start, q_end))
        return regions

    def to_dict(self) -> dict:
        result = {}
        for k in self.__dataclass_fields__:
            result[k] = self.__dict__[k]
        return result

    @classmethod
    def from_dict(cls, d: dict) -> "OMRTemplate":
        valid = {k: v for k, v in d.items() if k in cls.__dataclass_fields__}
        return cls(**valid)


@dataclass
class OMRResult:
    enrollment_no: str = ""
    student_name: str = ""
    answers: dict[int, int] = field(default_factory=dict)
    total_questions: int = 75
    answered_count: int = 0
    unanswered_count: int = 0
    multiple_marked: int = 0
    confidence: float = 0.0
    is_present: bool = True
    debug_image: Optional[np.ndarray] = None
    barcode_data: str = ""
    raw_fills: dict[int, list[float]] = field(default_factory=dict)


class OMREngine:
    def __init__(self, template: Optional[OMRTemplate] = None):
        self.template = template or OMRTemplate()

    def preprocess_image(self, image: np.ndarray) -> tuple[np.ndarray, np.ndarray, np.ndarray]:
        if len(image.shape) == 3:
            gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        else:
            gray = image.copy()
        clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
        enhanced = clahe.apply(gray)
        blurred = cv2.GaussianBlur(enhanced, (5, 5), 0)
        thresh = cv2.adaptiveThreshold(
            blurred, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
            cv2.THRESH_BINARY_INV, 15, 4
        )
        kernel = np.ones((2, 2), np.uint8)
        thresh = cv2.morphologyEx(thresh, cv2.MORPH_CLOSE, kernel)
        return gray, enhanced, thresh

    def find_sheet_boundary(self, image: np.ndarray, thresh: np.ndarray) -> Optional[np.ndarray]:
        contours, _ = cv2.findContours(
            thresh, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE
        )
        if not contours:
            return None
        contours = sorted(contours, key=cv2.contourArea, reverse=True)
        img_area = image.shape[0] * image.shape[1]
        for contour in contours[:5]:
            peri = cv2.arcLength(contour, True)
            approx = cv2.approxPolyDP(contour, 0.02 * peri, True)
            if len(approx) == 4:
                area = cv2.contourArea(contour)
                # Must cover at least 50% of image to be the full sheet
                if area > img_area * 0.50:
                    return approx
        return None

    def perspective_transform(
        self, image: np.ndarray, pts: np.ndarray,
        target_size: tuple[int, int] = (800, 1100)
    ) -> np.ndarray:
        pts = pts.reshape(4, 2).astype("float32")
        s = pts.sum(axis=1)
        rect = np.zeros((4, 2), dtype="float32")
        rect[0] = pts[np.argmin(s)]
        rect[2] = pts[np.argmax(s)]
        diff = np.diff(pts, axis=1)
        rect[1] = pts[np.argmin(diff)]
        rect[3] = pts[np.argmax(diff)]
        dst = np.array([
            [0, 0],
            [target_size[0] - 1, 0],
            [target_size[0] - 1, target_size[1] - 1],
            [0, target_size[1] - 1]
        ], dtype="float32")
        M = cv2.getPerspectiveTransform(rect, dst)
        return cv2.warpPerspective(image, M, target_size)

    def read_barcode(self, image: np.ndarray) -> str:
        if not HAS_PYZBAR:
            return ""
        t = self.template
        h, w = image.shape[:2]
        x1 = int(w * t.barcode_region_x)
        y1 = int(h * t.barcode_region_y)
        x2 = int(w * (t.barcode_region_x + t.barcode_region_w))
        y2 = int(h * (t.barcode_region_y + t.barcode_region_h))
        barcode_region = image[y1:y2, x1:x2]
        if barcode_region.size == 0:
            return ""
        if len(barcode_region.shape) == 3:
            gray_region = cv2.cvtColor(barcode_region, cv2.COLOR_BGR2GRAY)
        else:
            gray_region = barcode_region
        for method in [None, cv2.THRESH_BINARY, cv2.THRESH_OTSU]:
            if method is None:
                test_img = gray_region
            else:
                _, test_img = cv2.threshold(gray_region, 128, 255, method)
            decoded = decode_barcode(test_img)
            if decoded:
                return decoded[0].data.decode("utf-8", errors="ignore")
        decoded = decode_barcode(barcode_region)
        if decoded:
            return decoded[0].data.decode("utf-8", errors="ignore")
        return ""

    def detect_bubbles(
        self, gray: np.ndarray, thresh: np.ndarray,
        debug_img: Optional[np.ndarray] = None
    ) -> tuple[dict[int, int], dict[int, list[float]]]:
        h, w = gray.shape[:2]
        t = self.template
        answers: dict[int, int] = {}
        raw_fills: dict[int, list[float]] = {}
        col_regions = t.get_column_regions()
        for col_x_start, col_x_end, q_start, q_end in col_regions:
            x_start = int(w * col_x_start)
            y_start = int(h * t.answer_region_y_start)
            y_end = int(h * t.answer_region_y_end)
            col_thresh = thresh[y_start:y_end, x_start:int(w * col_x_end)]
            num_questions = q_end - q_start + 1
            if num_questions <= 0 or col_thresh.size == 0:
                continue
            row_height = col_thresh.shape[0] / num_questions
            col_w = col_thresh.shape[1]
            qnum_width = int(col_w * t.question_number_width)
            options_start = qnum_width
            options_width = col_w - options_start
            for i in range(num_questions):
                q_num = q_start + i
                row_y = int(i * row_height)
                row_y_end = int((i + 1) * row_height)
                if row_y >= col_thresh.shape[0] or row_y_end <= row_y:
                    answers[q_num] = 0
                    raw_fills[q_num] = [0.0] * t.options_per_question
                    continue
                option_fills = []
                for opt in range(t.options_per_question):
                    opt_x = options_start + int(
                        opt * options_width / t.options_per_question
                    )
                    opt_x_end = options_start + int(
                        (opt + 1) * options_width / t.options_per_question
                    )
                    pad_x = int((opt_x_end - opt_x) * t.bubble_padding)
                    pad_y = int((row_y_end - row_y) * t.bubble_padding)
                    by1 = max(row_y + pad_y, 0)
                    by2 = min(row_y_end - pad_y, col_thresh.shape[0])
                    bx1 = max(opt_x + pad_x, 0)
                    bx2 = min(opt_x_end - pad_x, col_thresh.shape[1])
                    if by2 <= by1 or bx2 <= bx1:
                        option_fills.append(0.0)
                        continue
                    bubble = col_thresh[by1:by2, bx1:bx2]
                    if bubble.size == 0:
                        option_fills.append(0.0)
                        continue
                    fill_ratio = float(np.sum(bubble > 0) / bubble.size)
                    option_fills.append(fill_ratio)
                    if debug_img is not None:
                        abs_x1 = x_start + bx1
                        abs_y1 = y_start + by1
                        abs_x2 = x_start + bx2
                        abs_y2 = y_start + by2
                        if fill_ratio > t.fill_threshold:
                            color = (0, 200, 0)
                            thick = 2
                        else:
                            color = (180, 180, 180)
                            thick = 1
                        cv2.rectangle(
                            debug_img,
                            (abs_x1, abs_y1),
                            (abs_x2, abs_y2),
                            color, thick
                        )
                raw_fills[q_num] = option_fills
                max_fill = max(option_fills) if option_fills else 0
                if max_fill < t.fill_threshold:
                    answers[q_num] = 0
                else:
                    filled_opts = [
                        j for j, f in enumerate(option_fills)
                        if f > t.fill_threshold
                    ]
                    if len(filled_opts) > 1:
                        fills_sorted = sorted(option_fills, reverse=True)
                        if fills_sorted[0] > fills_sorted[1] * t.multi_mark_ratio:
                            answers[q_num] = (
                                option_fills.index(max(option_fills)) + 1
                            )
                        else:
                            answers[q_num] = -1
                    else:
                        answers[q_num] = filled_opts[0] + 1
                if debug_img is not None:
                    ans_val = answers.get(q_num, 0)
                    if ans_val == -1:
                        ans_label = "M"
                    elif ans_val == 0:
                        ans_label = "-"
                    else:
                        ans_label = str(ans_val)
                    label = "Q" + str(q_num) + ":" + ans_label
                    cv2.putText(
                        debug_img, label,
                        (x_start + 2,
                         y_start + row_y + int(row_height * 0.7)),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.3, (0, 0, 255), 1
                    )
        return answers, raw_fills

    def process_sheet(
        self, image: np.ndarray, generate_debug: bool = True
    ) -> OMRResult:
        t = self.template
        result = OMRResult(total_questions=t.total_questions)

        # Validate input image
        if image is None or image.size == 0:
            return result

        try:
            max_dim = 2000
            h, w = image.shape[:2]
            if max(h, w) > max_dim:
                scale = max_dim / max(h, w)
                image = cv2.resize(
                    image, None, fx=scale, fy=scale,
                    interpolation=cv2.INTER_AREA
                )

            # Validate minimum size
            h, w = image.shape[:2]
            if h < 100 or w < 100:
                return result

            debug_img = image.copy() if generate_debug else None
            gray, enhanced, thresh = self.preprocess_image(image)
            proc_image = image
            proc_gray = gray
            proc_thresh = thresh

            if t.use_pivots:
                boundary = self.find_sheet_boundary(image, thresh)
                if boundary is not None:
                    try:
                        # Only apply perspective transform if the boundary
                        # is significantly non-rectangular (skewed)
                        pts = boundary.reshape(4, 2).astype('float32')
                        s = pts.sum(axis=1)
                        tl = pts[np.argmin(s)]
                        br = pts[np.argmax(s)]
                        diff = np.diff(pts, axis=1)
                        tr = pts[np.argmin(diff)]
                        bl = pts[np.argmax(diff)]
                        # Check if the sheet is already mostly rectangular
                        top_w = np.linalg.norm(tr - tl)
                        bot_w = np.linalg.norm(br - bl)
                        left_h = np.linalg.norm(bl - tl)
                        right_h = np.linalg.norm(br - tr)
                        w_ratio = min(top_w, bot_w) / max(top_w, bot_w)
                        h_ratio = min(left_h, right_h) / max(left_h, right_h)
                        # Only transform if significantly skewed
                        if w_ratio < 0.95 or h_ratio < 0.95:
                            proc_image = self.perspective_transform(
                                image, boundary
                            )
                            proc_gray, _, proc_thresh = self.preprocess_image(
                                proc_image
                            )
                            if generate_debug:
                                debug_img = proc_image.copy()
                    except Exception:
                        pass

            if t.has_barcode:
                try:
                    barcode = self.read_barcode(proc_image)
                    result.barcode_data = barcode
                    result.enrollment_no = barcode
                except Exception:
                    pass
                if debug_img is not None:
                    bh, bw = proc_image.shape[:2]
                    bx1 = int(bw * t.barcode_region_x)
                    by1 = int(bh * t.barcode_region_y)
                    bx2 = int(bw * (t.barcode_region_x + t.barcode_region_w))
                    by2 = int(bh * (t.barcode_region_y + t.barcode_region_h))
                    cv2.rectangle(
                        debug_img, (bx1, by1), (bx2, by2), (255, 165, 0), 2
                    )
                    if result.barcode_data:
                        bc_text = "Barcode: " + result.barcode_data
                        cv2.putText(
                            debug_img, bc_text,
                            (bx1, by1 - 5),
                            cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 165, 0), 1
                        )

            answers, raw_fills = self.detect_bubbles(
                proc_gray, proc_thresh, debug_img
            )
            result.answers = answers
            result.raw_fills = raw_fills
            result.answered_count = sum(1 for v in answers.values() if v > 0)
            result.unanswered_count = sum(1 for v in answers.values() if v == 0)
            result.multiple_marked = sum(1 for v in answers.values() if v == -1)
            result.confidence = result.answered_count / max(len(answers), 1)
            result.debug_image = debug_img

            # Free intermediate images to save memory during batch processing
            del gray, enhanced, thresh, proc_thresh
            if proc_gray is not gray:
                del proc_gray

        except Exception as e:
            # Return partial result on any error - don't crash the batch
            result.confidence = 0.0

        return result

    def grade_sheet(
        self, result: OMRResult, answer_key: dict[int, int],
        correct_marks: Optional[float] = None,
        wrong_marks: Optional[float] = None,
        unanswered_marks: Optional[float] = None
    ) -> dict:
        t = self.template
        cm = correct_marks if correct_marks is not None else t.correct_marks
        wm = wrong_marks if wrong_marks is not None else t.wrong_marks
        um = (
            unanswered_marks if unanswered_marks is not None
            else t.unanswered_marks
        )
        correct = 0
        wrong = 0
        unanswered = 0
        multiple = 0
        total_score = 0.0
        max_possible = len(answer_key) * cm
        details = []
        for q_num in range(1, t.total_questions + 1):
            student_answer = result.answers.get(q_num, 0)
            correct_answer = answer_key.get(q_num, 0)
            if correct_answer == 0:
                status = "not_in_key"
                q_score = 0.0
            elif student_answer == 0:
                status = "unanswered"
                unanswered += 1
                q_score = um
            elif student_answer == -1:
                status = "multiple_marked"
                multiple += 1
                q_score = wm
            elif student_answer == correct_answer:
                status = "correct"
                correct += 1
                q_score = cm
            else:
                status = "wrong"
                wrong += 1
                q_score = wm
            total_score += q_score
            details.append({
                "question": q_num,
                "student_answer": student_answer,
                "correct_answer": correct_answer,
                "status": status,
                "marks": q_score,
                "fill_values": result.raw_fills.get(q_num, [])
            })
        total_attempted = correct + wrong + multiple
        percentage = (correct / max(total_attempted, 1)) * 100
        return {
            "total_questions": t.total_questions,
            "total_in_key": len(answer_key),
            "correct": correct,
            "wrong": wrong,
            "unanswered": unanswered,
            "multiple_marked": multiple,
            "total_attempted": total_attempted,
            "score": round(total_score, 2),
            "max_score": round(max_possible, 2),
            "percentage": round(percentage, 2),
            "marking_scheme": {
                "correct": cm, "wrong": wm, "unanswered": um
            },
            "details": details
        }


TEMPLATES: dict[str, OMRTemplate] = {
    "75q_4opt": OMRTemplate(
        name="75 Questions - 4 Options (Standard)",
        total_questions=75, options_per_question=4,
        columns=3, questions_per_column=25,
        answer_region_x_start=0.015,
        answer_region_x_end=0.73,
        answer_region_y_start=0.47,
        answer_region_y_end=0.935,
        barcode_region_x=0.03,
        barcode_region_y=0.20,
        barcode_region_w=0.30,
        barcode_region_h=0.13,
        question_number_width=0.12,
        bubble_padding=0.10,
    ),
    "100q_4opt": OMRTemplate(
        name="100 Questions - 4 Options",
        total_questions=100, options_per_question=4,
        columns=4, questions_per_column=25,
    ),
    "50q_4opt": OMRTemplate(
        name="50 Questions - 4 Options",
        total_questions=50, options_per_question=4,
        columns=2, questions_per_column=25,
    ),
    "200q_4opt": OMRTemplate(
        name="200 Questions - 4 Options",
        total_questions=200, options_per_question=4,
        columns=4, questions_per_column=50,
    ),
    "30q_4opt": OMRTemplate(
        name="30 Questions - 4 Options",
        total_questions=30, options_per_question=4,
        columns=2, questions_per_column=15,
    ),
}
