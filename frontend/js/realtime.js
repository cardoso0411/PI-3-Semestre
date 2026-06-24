/* Realtime: tenta WebSocket no backend PHP (ratchet/swoole) — se indisponível,
   simula tick a cada 4s sobre os dados em memória. Substitua WS_URL no PHP. */
const REALTIME = {
  WS_URL: null, // ex.: "ws://localhost:8081"
  stations: [],
  events: [],
  listeners: new Set(),
  _started: false,
  _ws: null,

  subscribe(fn) { this.listeners.add(fn); fn(this.stations, this.events); return () => this.listeners.delete(fn); },
  _emit() { this.listeners.forEach(fn => fn(this.stations, this.events)); },

  async start() {
    if (this._started) return; this._started = true;
    this.stations = await tryApi(() => API.listStations(), [...MOCK.stations]);
    this.events   = await tryApi(() => API.listEvents(),   [...MOCK.events]);
    this._emit();

    if (this.WS_URL) {
      try {
        this._ws = new WebSocket(this.WS_URL);
        this._ws.onmessage = (m) => {
          const data = JSON.parse(m.data);
          if (data.stations) this.stations = data.stations;
          if (data.event)    this.events = [data.event, ...this.events].slice(0,200);
          this._emit();
        };
        this._ws.onerror = () => this._simulate();
        return;
      } catch { /* cai para simulação */ }
    }
    this._simulate();
  },

  _simulate() {
    setInterval(() => {
      this.stations = this.stations.map(s => s.epiStatus === "offline" ? s : { ...s, operationMinutes: s.operationMinutes + 1 });
      if (Math.random() < 0.55) {
        const live = this.stations.filter(s => s.epiStatus !== "offline");
        const st = live[Math.floor(Math.random()*live.length)];
        if (!st) return this._emit();
        const r = Math.random(); const conf = +(0.75 + Math.random()*0.24).toFixed(2);
        let next = st.epiStatus, evt = null;
        if (r < 0.15 && st.epiStatus === "compliant") {
          next = "violation";
          evt = { id:"ev-"+Date.now(), timestamp:new Date().toISOString(), stationId:st.id, employeeId:st.employeeId,
                  type:"alert_triggered", message:"EPI removido durante operação", confidence:conf };
          const emp = findEmployee(st.employeeId);
          toast(`Alerta — ${st.name}`, `${emp?.name||"Operador"} • ${evt.message}`, "error");
        } else if (r < 0.3 && st.epiStatus === "violation") {
          next = "compliant";
          evt = { id:"ev-"+Date.now(), timestamp:new Date().toISOString(), stationId:st.id, employeeId:st.employeeId,
                  type:"machine_released", message:"Conformidade restaurada — máquina liberada", confidence:conf };
        } else if (r < 0.4) {
          evt = { id:"ev-"+Date.now(), timestamp:new Date().toISOString(), stationId:st.id, employeeId:st.employeeId,
                  type:"epi_detected", message:"EPI completo confirmado", confidence:conf };
        }
        if (next !== st.epiStatus)
          this.stations = this.stations.map(s => s.id===st.id ? {...s, epiStatus:next, machineLocked:next==="violation", aiConfidence:conf} : s);
        if (evt) this.events = [evt, ...this.events].slice(0,200);
      }
      this._emit();
    }, 4000);
  }
};
function statusBadge(s){ return `<span class="badge ${s}"><span class="dot"></span>${STATUS_LABEL[s]||s}</span>`; }
