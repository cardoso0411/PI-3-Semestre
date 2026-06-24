import cv2
from helmet_detector import HelmetDetector

# Carrega o modelo treinado
detector = HelmetDetector(
    model_path="models/best_helmet.pt",
    conf_threshold=0.5
)

# Abre webcam do notebook
cap = cv2.VideoCapture(0)

# Verifica câmera
if not cap.isOpened():
    print("Erro ao abrir câmera")
    exit()

while True:
    # Captura frame
    ret, frame = cap.read()

    if not ret:
        break

    # Faz detecção
    result = detector.detect(frame)

    # Desenha caixas e textos
    output = detector.draw_results(frame, result)

    # Mostra tela
    cv2.imshow("Helmet Detection", output)

    # Fecha com tecla Q
    if cv2.waitKey(1) & 0xFF == ord("q"):
        break

# Finaliza
cap.release()
cv2.destroyAllWindows()