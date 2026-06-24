# camera_stream.py
from flask import Flask, Response
import cv2
from helmet_detector import HelmetDetector
import time
import mysql.connector

app = Flask(__name__)

detector = HelmetDetector(model_path="models/best_helmet.pt", conf_threshold=0.45)

SKIP_FRAMES = 3 
FRAME_WIDTH = 640
FRAME_HEIGHT = 480

CAMERA_ID = 1
COLAB_ID = 3

def salvar_alerta_no_banco(id_cam, status_epi, status_maq):
    try:
        # CORREÇÃO: Nome do banco alterado para 'securtech'
        conn = mysql.connector.connect(
            host="localhost", user="root", password="", database="securtech"
        )
        cursor = conn.cursor()
        sql = "INSERT INTO ALERTAS (id_colab, id_cam, Status_EPI, Status_maq, data_hora) VALUES (%s, %s, %s, %s, NOW())"
        cursor.execute(sql, (COLAB_ID, id_cam, status_epi, status_maq))
        conn.commit()
        print(f"🚨 [BANCO] Registro de violação salvo com sucesso para a câmera {id_cam}!")
    except mysql.connector.Error as err:
        print(f"❌ [ERRO BANCO] Falha ao salvar no MySQL: {err}")
    finally:
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()

def generate_frames():
    cap = cv2.VideoCapture(0)
    cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
    cap.set(cv2.CAP_PROP_FRAME_WIDTH, FRAME_WIDTH)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, FRAME_HEIGHT)
    cap.set(cv2.CAP_PROP_FPS, 30)

    violation_start_time = None
    alert_triggered = False
    frame_count = 0
    last_result = None

    while True:
        if frame_count % 5 == 0:
            cap.grab() 

        success, frame = cap.read()
        if not success:
            break

        frame_count += 1

        if frame_count % SKIP_FRAMES == 0:
            last_result = detector.detect(frame)
        
        if last_result is not None:
            frame = detector.draw_results(frame, last_result)
            status = last_result.get("status")

            if status == "incorrect_use":
                if violation_start_time is None:
                    violation_start_time = time.time()
                
                tempo_passado = time.time() - violation_start_time
                tempo_restante = max(0, 30 - int(tempo_passado))

                # Log no terminal para você acompanhar os segundos sem precisar adivinhar
                if not alert_triggered:
                    print(f"⚠️ [IA WARNING] Operador sem capacete! Bloqueio em {tempo_restante}s...", end="\r")

                if not alert_triggered:
                    cv2.putText(frame, f"BLOQUEIO EM: {tempo_restante}s", (10, 450), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 0, 255), 2)
                else:
                    cv2.putText(frame, "ESTACAO BLOQUEADA!", (10, 450), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 0, 255), 2)

                if tempo_passado >= 30 and not alert_triggered:
                    print("\n🛑 [BLOQUEANDO] Alvo atingiu 30s de infração. Disparando banco...")
                    salvar_alerta_no_banco(CAMERA_ID, "Ausência de Capacete", "Bloqueada")
                    alert_triggered = True
            else:
                # Se o operador colocar o capacete ou sair da frente, reseta o perigo
                if violation_start_time is None:
                    print("💚 [STATUS] Operador Seguro / Sem Infrações Ativas.", end="\r")
                violation_start_time = None
                alert_triggered = False

        encode_param = [int(cv2.IMWRITE_JPEG_QUALITY), 70]
        _, buffer = cv2.imencode('.jpg', frame, encode_param)
        
        yield (b'--frame\r\n'
               b'Content-Type: image/jpeg\r\n\r\n' + buffer.tobytes() + b'\r\n')
        
        time.sleep(0.01)

@app.route('/video_feed')
def video_feed():
    return Response(generate_frames(), mimetype='multipart/x-mixed-replace; boundary=frame')

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=False, threaded=True)