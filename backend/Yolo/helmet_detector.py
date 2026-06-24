# helmet_detector.py
from ultralytics import YOLO
import cv2
import numpy as np
import logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class HelmetDetector:

    def __init__(self, model_path: str = "models/best_helmet.pt", conf_threshold: float = 0.7):
        self.model = YOLO(model_path)
        self.conf_threshold = conf_threshold
        self.class_names = {
            0: 'helmet_lifted',
            1: 'with_helmet',
            2: 'without_helmet'
        }
        self.face_cascade = cv2.CascadeClassifier(
            cv2.data.haarcascades + "haarcascade_frontalface_default.xml"
        )

    def detect(self, image: np.ndarray):
        results = self.model.predict(source=image, conf=0.1, verbose=False)[0]

        detections = []
        violations = []

        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        faces = self.face_cascade.detectMultiScale(
            gray, scaleFactor=1.05, minNeighbors=7, minSize=(30, 30)
        )

        face_detected = False
        valid_faces = []

        for (x, y, w, h) in faces:
            if w > 50 and h > 50:  # Ajustado para capturar rostos normais em webcam
                face_detected = True
                valid_faces.append((x, y, w, h))

        for box in results.boxes:
            x1, y1, x2, y2 = map(int, box.xyxy[0])
            conf = float(box.conf[0])
            cls_id = int(box.cls[0])

            class_name = self.class_names.get(cls_id, f"classe_{cls_id}")

            detection = {
                "class": class_name,
                "confidence": round(conf, 4),
                "bbox": [x1, y1, x2, y2]
            }

            # CORREÇÃO: Filtro de área reduzido drasticamente para evitar falsos resets do timer
            area = (x2 - x1) * (y2 - y1)
            if area < 1000: 
                continue
                
            detections.append(detection)

            if class_name in ["without_helmet", "helmet_lifted"] and conf >= self.conf_threshold:
                if class_name not in violations:
                    violations.append(class_name)

        if face_detected and not any(d["class"] == "with_helmet" for d in detections):
            if "face_visible" not in violations:
                violations.append("face_visible")

        # Define as regras de saída do status
        if violations:
            status = "incorrect_use"
        elif any(d["class"] == "with_helmet" for d in detections):
            status = "correct_use"
        else:
            status = "no_helmet_detected"

        return {
            "status": status,
            "violations": violations,
            "detections": detections,
            "faces": valid_faces,
            "num_faces": len(valid_faces),
            "num_detections": len(detections)
        }

    def draw_results(self, image: np.ndarray, result: dict):
        img_copy = image.copy()

        for det in result["detections"]:
            x1, y1, x2, y2 = det["bbox"]
            cls = det["class"]
            conf = det["confidence"]

            color = (0, 255, 0) if cls == "with_helmet" else (0, 0, 255)
            cv2.rectangle(img_copy, (x1, y1), (x2, y2), color, 3)
            label = f"{cls} {conf:.2f}"
            cv2.putText(img_copy, label, (x1, y1 - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.7, color, 2)

        for (x, y, w, h) in result["faces"]:
            cv2.rectangle(img_copy, (x, y), (x + w, y + h), (255, 0, 0), 2)
            cv2.putText(img_copy, "FACE", (x, y - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 0, 0), 2)

        status_color = (0, 255, 0) if result["status"] == "correct_use" else (0, 0, 255)
        cv2.putText(img_copy, f"STATUS: {result['status'].upper()}", (10, 40), cv2.FONT_HERSHEY_SIMPLEX, 1.0, status_color, 3)

        if result["violations"]:
            violations_text = ", ".join(result["violations"])
            cv2.putText(img_copy, f"VIOLATIONS: {violations_text}", (10, 80), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 0, 255), 2)

        return img_copy