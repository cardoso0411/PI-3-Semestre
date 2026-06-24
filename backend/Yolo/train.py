from ultralytics import YOLO

# Carrega modelo base leve
model = YOLO("yolov8n.pt")

# Inicia treinamento
model.train(
    data="yolod/data.yaml",
    epochs=30,
    imgsz=416,
    batch=4,
    name="helmet_train"
)