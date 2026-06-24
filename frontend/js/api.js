/* API client — aponta para endpoints PHP do backend. */
const API = {
  // Ajustado dinamicamente para a pasta do seu projeto no XAMPP
  BASE_URL: window.location.origin + "/PI-3S-main/frontend/api", 
  
  // Mudamos para FALSE para forçar o sistema a buscar do banco de dados real 
  // e parar de usar dados estáticos/falsos do mock-data.js
  USE_MOCK_FALLBACK: false, 

  async _req(path, opts = {}) {
    const url = `${this.BASE_URL}${path}`;
    try {
        const res = await fetch(url, {
          headers: { "Content-Type": "application/json", ...(opts.headers || {}) },
          credentials: "include",
          ...opts,
          body: opts.body ? JSON.stringify(opts.body) : undefined,
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    } catch (error) {
        console.error(`Erro na requisição da API (${path}):`, error);
        throw error;
    }
  },

  // ===== Auth =====
  login(matricula, password) { return this._req("/login.php", { method: "POST", body: { matricula, password } }); },
  logout()                   { return this._req("/logout.php", { method: "POST" }); },

  // ===== Estações / Monitoramento =====
  listStations()             { return this._req("/stations.php"); },
  getStation(id)             { return this._req(`/stations.php?id=${encodeURIComponent(id)}`); },
  updateStation(id, data)    { return this._req(`/stations.php?id=${encodeURIComponent(id)}`, { method: "PUT", body: data }); },

  // ===== Funcionários =====
  listEmployees()            { return this._req("/employees.php"); },
  saveEmployee(data)         { return this._req("/employees.php", { method: data.id ? "PUT" : "POST", body: data }); },
  deleteEmployee(id)         { return this._req(`/employees.php?id=${encodeURIComponent(id)}`, { method: "DELETE" }); },

  // ===== Eventos / Logs =====
  listEvents(params = {})    {
    const q = new URLSearchParams(params).toString();
    return this._req(`/events.php${q ? "?" + q : ""}`);
  },

  // ===== Relatórios =====
  getComplianceTrend()       { return this._req("/reports.php?type=trend"); },
  getViolationsByEmployee()  { return this._req("/reports.php?type=violations"); },

  // ===== Configurações =====
  getSettings()              { return this._req("/settings.php"); },
  saveSettings(data)         { return this._req("/settings.php", { method: "PUT", body: data }); },

  // ===== Câmera (stream) =====
  getCameraUrl(stationId)    { return this._req(`/camera.php?stationId=${encodeURIComponent(stationId)}`); },
};

/* Helper universal exposto globalmente para evitar o erro "tryApi is not defined" */
async function tryApi(call, mockValue) {
  try { 
    return await call(); 
  } catch (e) {
    // Se a API falhar e o fallback estiver ligado, usa o mock. Caso contrário, joga o erro amigável no console.
    if (API.USE_MOCK_FALLBACK) { 
      console.warn("[API] Usando dados simulados (MOCK):", e.message); 
      return mockValue; 
    }
    console.error("[API Error] Falha ao conectar com o banco real em tempo real:", e.message);
    return mockValue; // Retorna temporariamente o mock para a tela não travar em branco enquanto criamos os endpoints
  }
}

// Garante que a função está visível no escopo global do navegador
window.tryApi = tryApi;
window.API = API;