import cv2
from helmet_detector import HelmetDetector

# =========================
# CAMINHO DA IMAGEM
# =========================
IMAGE_PATH = "test3.png"

# =========================
# INICIALIZA DETECTOR
# =========================
detector = HelmetDetector(
    model_path="models/best_helmet.pt",
    conf_threshold=0.7
)

# =========================
# CARREGA IMAGEM
# =========================
image = cv2.imread(IMAGE_PATH)

if image is None:
    print("Erro ao carregar imagem.")
    exit()

# =========================
# DETECÇÃO
# =========================
result = detector.detect(image)

# =========================
# DESENHA RESULTADOS
# =========================
output = detector.draw_results(
    image,
    result
)

# =========================
# PRINT TERMINAL
# =========================
print("STATUS:", result["status"])

print("VIOLAÇÕES:", result["violations"])

print("DETEÇÕES:")
for det in result["detections"]:
    print(det)

# =========================
# SALVA RESULTADO
# =========================
cv2.imwrite("resultado.jpg", output)

# =========================
# MOSTRA TELA
# =========================
cv2.imshow("Resultado", output)

cv2.waitKey(0)

cv2.destroyAllWindows()
print(result)
result = detector.detect(image)