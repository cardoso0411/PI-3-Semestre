# Securi&Tech — EPI Guardian (Frontend HTML/CSS/JS)

Versão estática (HTML + CSS + JS puro) pronta para conectar a um backend PHP.

## Estrutura

```
epi-guardian-html/
├── index.html            # Login
├── dashboard.html        # Visão geral (supervisor)
├── monitoramento.html    # Câmera ao vivo + tabela + eventos
├── relatorios.html       # Gráficos (Chart.js) + export CSV
├── configuracoes.html    # IA, funcionários, estações
├── operador.html         # Tela grande do operador
├── css/styles.css        # Tema industrial dark
├── js/
│   ├── api.js            # Cliente fetch → endpoints PHP
│   ├── auth.js           # Login / logout / sessão
│   ├── layout.js         # Sidebar + Header reaproveitáveis
│   ├── realtime.js       # WebSocket + fallback simulado
│   └── mock-data.js      # Dados fictícios (fallback)
└── api/                  # (coloque seus .php aqui)
```

## Como rodar

Como há fetch entre arquivos, abra via servidor HTTP — não use `file://`.

```bash
# qualquer um destes:
php -S localhost:8000        # se já tiver PHP (serve estático + .php)
python3 -m http.server 8000
npx serve .
```

Acesse `http://localhost:8000` → login.
- Supervisor: matrícula `20001`
- Operador: matrícula `10234`
- Qualquer senha funciona (modo mock).

## Conectando ao backend PHP

Em `js/api.js` ajuste:
```js
API.BASE_URL = "/api";              // ou "http://meuservidor/api"
API.USE_MOCK_FALLBACK = false;      // desativa mock quando o PHP estiver pronto
```

Endpoints esperados (todos JSON):

| Método | URL                                | Retorno / corpo                                |
|-------:|------------------------------------|------------------------------------------------|
| POST   | `/api/login.php`                   | `{ matricula, password }` → usuário            |
| POST   | `/api/logout.php`                  | —                                              |
| GET    | `/api/stations.php`                | lista de estações                              |
| GET    | `/api/stations.php?id=st-01`       | estação                                        |
| PUT    | `/api/stations.php?id=st-01`       | atualiza                                       |
| GET    | `/api/employees.php`               | lista                                          |
| POST   | `/api/employees.php`               | cria                                           |
| PUT    | `/api/employees.php`               | edita                                          |
| DELETE | `/api/employees.php?id=e1`         | remove                                         |
| GET    | `/api/events.php`                  | logs de eventos                                |
| GET    | `/api/reports.php?type=trend`      | série diária                                   |
| GET    | `/api/reports.php?type=violations` | violações por operador                         |
| GET    | `/api/settings.php`                | `{ sensitivity, pauseSeconds }`                |
| PUT    | `/api/settings.php`                | salva                                          |
| GET    | `/api/camera.php?stationId=st-01`  | `{ url: "http://.../stream.mjpg" }`            |

## Câmera (monitoramento.html)

Dentro de `<div class="camera">` há:

```html
<video id="camVideo" autoplay muted playsinline></video>
```

Substitua/complemente:
- **MJPEG**: `document.getElementById("camVideo").outerHTML = '<img src="' + url + '">'`
- **HLS** (.m3u8): use `hls.js` apontando para o `<video>`
- **WebRTC / MediaStream**: `videoEl.srcObject = stream`

Em `selectCam()` (monitoramento.html) já existe o trecho comentado pronto:
```js
API.getCameraUrl(s.id).then(({url}) => { document.getElementById("camVideo").src = url; });
```

## Realtime (WebSocket)

Em `js/realtime.js` defina:
```js
REALTIME.WS_URL = "ws://localhost:8081";   // servidor PHP (Ratchet/Swoole)
```
O servidor deve enviar mensagens JSON:
```json
{ "stations": [...] }
{ "event":    { "id":"...", "type":"alert_triggered", "stationId":"st-02", ... } }
```
Se nenhuma WS estiver definida, o frontend simula eventos a cada 4s.
