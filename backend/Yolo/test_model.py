from ultralytics import YOLO

model = YOLO("runs/detect/train/weights/best.pt")

print("Modelo carregado!")
print(model.names)