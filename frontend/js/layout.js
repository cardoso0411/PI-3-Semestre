/* Renderiza sidebar + header em qualquer página. Use <div data-layout="supervisor|operador" data-active="dashboard"></div> */
function renderLayout() {
  const slot = document.querySelector("[data-layout]");
  if (!slot) return;
  const u = AUTH.requireRole(slot.dataset.layout);
  if (!u) return;
  const active = slot.dataset.active || "";

  const navSup = [
    { id:"dashboard",     label:"Dashboard",     href:"dashboard.html",     icon:"📊" },
    { id:"monitoramento", label:"Monitoramento", href:"monitoramento.html", icon:"📹" },
    { id:"relatorios",    label:"Relatórios",    href:"relatorios.html",    icon:"📈" },
    { id:"configuracoes", label:"Configurações", href:"configuracoes.html", icon:"⚙️" },
  ];
  const navOp = [
    { id:"operador", label:"Minha Estação", href:"operador.html", icon:"🦺" },
  ];
  const nav = u.role === "supervisor" ? navSup : navOp;
  const initials = u.name.split(" ").map(w=>w[0]).slice(0,2).join("").toUpperCase();

  slot.outerHTML = `
    <div class="app">
      <aside class="sidebar">
        <div class="brand">🛡️ <span>Securi&<b>Tech</b></span></div>
        <nav>
          ${nav.map(n => `<a href="${n.href}" class="${n.id===active?'active':''}"><span>${n.icon}</span> ${n.label}</a>`).join("")}
        </nav>
        <hr/>
        <button class="btn ghost" style="width:100%" onclick="AUTH.logout()">↩ Sair</button>
      </aside>
      <div class="main">
        <header class="header">
          <div>
            <div class="muted" style="font-size:11px;text-transform:uppercase">EPI Guardian</div>
            <div style="font-weight:700">${document.title.split("—")[0].trim() || "Painel"}</div>
          </div>
          <div class="user">
            <div style="text-align:right">
              <div style="font-weight:600">${u.name}</div>
              <div class="muted" style="font-size:12px">${u.role} • ${u.sector}</div>
            </div>
            <div class="avatar">${initials}</div>
          </div>
        </header>
        <main class="content" id="page-content"></main>
      </div>
    </div>`;
}
document.addEventListener("DOMContentLoaded", renderLayout);
