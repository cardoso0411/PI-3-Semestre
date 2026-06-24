# 🎓 Securi&Tech — EPI Guardian

> **Projeto Integrador (PI) - 3º Semestre**  
> Desenvolvido por alunos da **FATEC** (Faculdade de Tecnologia)

![HTML5](https://img.shields.io/badge/HTML5-E34C26?style=flat-square&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat-square&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=black)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white)
![Python](https://img.shields.io/badge/Python-3776AB?style=flat-square&logo=python&logoColor=white)

## 📋 Sobre o Projeto

**EPI Guardian** é um sistema de monitoramento em tempo real de Equipamentos de Proteção Individual (EPIs) usando visão computacional com YOLO. O projeto detecta automaticamente se trabalhadores estão usando capacete e outros EPIs em ambientes industriais, gerando alertas e relatórios.

### Características Principais
- ✅ Monitoramento ao vivo via câmera
- ✅ Detecção automática com IA (YOLOv8)
- ✅ Dashboard de supervisor com estatísticas
- ✅ Painel operador para visualização em tempo real
- ✅ Sistema de relatórios com gráficos
- ✅ Configurações de sensibilidade e alertas

---

## 🔧 Pré-requisitos

Antes de começar, você precisa ter instalado:

- **PHP** 7.4+ (com suporte a JSON)
- **Python** 3.8+ (para o YOLO - detecção de capacete)
- **Node.js** (opcional, para servir a aplicação)

**Para rodar localmente:**
```bash
php --version    # Verificar PHP
python --version # Verificar Python
```

---

## 📦 Instalação

1. **Clone o repositório:**
```bash
git clone https://github.com/seu-usuario/PI-3-Semestre.git
cd PI-3-Semestre
```

2. **Instale as dependências Python (YOLO):**
```bash
cd backend/Yolo
pip install -r requirements.txt
```

3. **Configure o PHP** (se necessário adaptar permissões):
```bash
# No Windows, garanta que o PHP está no PATH
# No Linux/Mac, já deve estar disponível
```

---

## 🚀 Como Rodar

Como há requisições entre arquivos, **abra via servidor HTTP** — não use `file://`.

**Escolha uma opção:**

```bash
# Opção 1: PHP Built-in (recomendado para desenvolvimento)
cd frontend
php -S localhost:8000

# Opção 2: Python HTTP Server
cd frontend
python -m http.server 8000

# Opção 3: Node.js (serve / http-server)
cd frontend
npx serve .
```

**Acesse no navegador:**
```
http://localhost:8000
```

### Credenciais de Teste

| Tipo | Matrícula | Senha |
|------|-----------|-------|
| 👨‍💼 Supervisor | `20001` | qualquer |
| 👨‍💻 Operador | `10234` | qualquer |

*Nota: Em modo desenvolvimento, qualquer senha é aceita.*

---

## 📁 Estrutura

```
PI-3-Semestre/
├── frontend/                    # Interface web (HTML/CSS/JS)
│   ├── index.html              # 🔐 Login
│   ├── dashboard.html          # 📊 Visão geral (supervisor)
│   ├── monitoramento.html      # 📷 Câmera ao vivo + tabela + eventos
│   ├── relatorios.html         # 📈 Gráficos (Chart.js) + export CSV
│   ├── configuracoes.html      # ⚙️  IA, funcionários, estações
│   ├── operador.html           # 👁️  Tela grande do operador
│   ├── css/styles.css          # 🎨 Tema industrial dark
│   ├── js/
│   │   ├── api.js              # 🔌 Cliente fetch → endpoints PHP
│   │   ├── auth.js             # 🔑 Login / logout / sessão
│   │   ├── layout.js           # 📐 Sidebar + Header reutilizáveis
│   │   ├── realtime.js         # ⚡ WebSocket + fallback
│   │   └── mock-data.js        # 📦 Dados fictícios (fallback)
│   └── composer.json           # Dependências PHP
│
├── backend/                     # API e processamento
│   ├── configuracoes_api.php   # Endpoints de configuração
│   ├── dashboard_api.php       # Endpoints de dashboard
│   ├── monitoramento_api.php   # Endpoints de monitoramento
│   ├── reativar_api.php        # Controle de reativação
│   ├── relatorios_api.php      # Geração de relatórios
│   └── Yolo/                   # Detecção com IA
│       ├── helmet_detector.py  # 🤖 Modelo YOLO para capacete
│       ├── camera_stream.py    # 📹 Stream de câmera
│       ├── train.py            # 🎓 Treinamento do modelo
│       ├── requirements.txt    # Dependências Python
│       ├── models/             # Modelos treinados
│       ├── yolod/              # Dataset de treinamento
│       │   ├── train/
│       │   ├── val/
│       │   └── test/
│       └── runs/               # Resultados de treinamento
│
└── README.md                    
```

---

## 🔗 Endpoints da API

### Configuração Inicial

Para conectar o frontend ao backend PHP, edite o arquivo `frontend/js/api.js`:

```js
API.BASE_URL = "/api";              // Altere para sua URL do servidor
API.USE_MOCK_FALLBACK = false;      // Desativa mock quando o PHP estiver pronto
```

### Lista de Endpoints

Todos os endpoints retornam JSON.

| Método | URL | Descrição |
|--------|-----|-----------|
| **POST** | `/api/login.php` | Login: `{ matricula, password }` → dados do usuário |
| **POST** | `/api/logout.php` | Logout do usuário |
| **GET** | `/api/stations.php` | Lista todas as estações |
| **GET** | `/api/stations.php?id=st-01` | Dados de uma estação específica |
| **PUT** | `/api/stations.php?id=st-01` | Atualiza configurações da estação |
| **GET** | `/api/employees.php` | Lista todos os funcionários |
| **POST** | `/api/employees.php` | Cria novo funcionário |
| **PUT** | `/api/employees.php?id=e1` | Edita funcionário existente |
| **DELETE** | `/api/employees.php?id=e1` | Remove funcionário |
| **GET** | `/api/events.php` | Logs de eventos (alertas, detecções) |
| **GET** | `/api/reports.php?type=trend` | Relatório de tendência (série diária) |
| **GET** | `/api/reports.php?type=violations` | Violações detectadas por operador |
| **GET** | `/api/settings.php` | Obter configurações (sensibilidade, pausa, etc) |
| **PUT** | `/api/settings.php` | Salvar configurações `{ sensitivity, pauseSeconds }` |
| **GET** | `/api/camera.php?stationId=st-01` | URL do stream da câmera `{ url: "http://.../stream.mjpg" }` |

---

## 📷 Câmera (monitoramento.html)

O sistema suporta múltiplos tipos de streaming de câmera:

### Implementação Atual

Dentro de `monitoramento.html` há um elemento `<video>`:

```html
<div class="camera">
  <video id="camVideo" autoplay muted playsinline></video>
</div>
```

### Tipos de Stream Suportados

**1. MJPEG (Motion JPEG)**  
Mais comum em câmeras IP antigas:
```js
const url = await API.getCameraUrl(stationId);
document.getElementById("camVideo").outerHTML = `<img src="${url.url}" />`;
```

**2. HLS (HTTP Live Streaming)**  
Para streams `.m3u8`:
```js
// Instale: npm install hls.js
import HLS from 'hls.js';
const hls = new HLS();
hls.loadSource('http://seu-servidor/stream.m3u8');
hls.attachMedia(document.getElementById('camVideo'));
```

**3. WebRTC ou MediaStream**  
Para conexões P2P em tempo real:
```js
const stream = await API.getCameraStream(stationId);
document.getElementById("camVideo").srcObject = stream;
```

Em `js/api.js` você já tem um método comentado pronto:

```js
API.getCameraUrl(stationId).then(({url}) => {
  document.getElementById("camVideo").src = url;
});
```

---

## ⚡ Realtime (WebSocket)

Para atualizações em tempo real, configure um servidor WebSocket em `js/realtime.js`:

```js
REALTIME.WS_URL = "ws://localhost:8081";   // Servidor WebSocket (Ratchet ou Swoole)
REALTIME.ENABLE_MOCK = false;               // Ativa modo simulado se não houver WS
```

### Formato de Mensagens Esperadas

O servidor deve enviar JSON:

```json
{
  "stations": [
    { "id": "st-01", "name": "Estação 1", "active": true, "violations": 2 }
  ]
}
```

```json
{
  "event": {
    "id": "evt-123",
    "type": "alert_triggered",
    "stationId": "st-02",
    "timestamp": "2024-01-15T14:30:00Z",
    "severity": "high"
  }
}
```

### Fallback (Modo Simulado)

Se nenhum WebSocket estiver configurado, o frontend simula eventos automáticos a cada 4 segundos para desenvolvimento.

---

## 👥 Equipe

Este projeto foi desenvolvido como trabalho do **Projeto Integrador (PI)** do 3º semestre da **FATEC**.

### Membros da Equipe

- **Bruno Cardoso** - Desenvolvedor Full Stack
- **[Gian Miguel Oliveira](https://github.com/GianSenai)** - Backend PHP / YOLO
- **[Gabriel Bueno Garcia](https://github.com/gabrielgarcia1206)** - Frontend / UI
- **Gabriel Manrique** - DevOps / Infraestrutura

---

## 📚 Tecnologias Utilizadas

### Frontend
- **HTML5** - Estrutura
- **CSS3** - Estilos (tema industrial dark)
- **JavaScript (Vanilla)** - Lógica do cliente
- **Chart.js** - Gráficos nos relatórios

### Backend
- **PHP 7.4+** - API REST
- **MySQL/SQLite** - Banco de dados
- **Python 3.8+** - Processamento de IA

### IA / Visão Computacional
- **YOLOv8** - Detecção de objetos
- **OpenCV** - Processamento de imagem
- **Roboflow** - Anotação de dataset

### DevOps
- **Docker** (opcional)
- **Git** - Controle de versão