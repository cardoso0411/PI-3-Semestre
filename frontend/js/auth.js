/* AUTH INTEGRADO AO BACKEND PHP
   Deixamos o gerenciamento de login e segurança por conta das Sessões do PHP ($_SESSION).
   Mantemos as estruturas de cookies/localStorage e UI sincronizadas para não quebrar o layout.js.
*/

const AUTH = {
    KEY: "epi-guardian-user",
    
    // Lê o usuário logado atual
    get user() { 
        try { 
            return JSON.parse(localStorage.getItem(this.KEY) || "null"); 
        } catch { 
            return null; 
        } 
    },
    
    // Atualiza o usuário no navegador
    set user(v) { 
        v ? localStorage.setItem(this.KEY, JSON.stringify(v)) : localStorage.removeItem(this.KEY); 
    },

    // O login agora é feito nativamente via POST pelo index.php, 
    // mas deixamos a função aqui caso outros scripts façam checagens básicas
    async login(matricula, password) {
        console.log("Autenticação gerenciada pelo PHP Backend...");
        return true;
    },

    // Sincroniza o encerramento da sessão com o nosso logout.php do backend
    logout() {
        this.user = null;
        window.location.href = "logout.php";
    },

    // Validação de segurança auxiliar na renderização dos menus do front-end
    requireRole(role) {
        const u = this.user;
        // Se o front-end não identificar o usuário local, deixamos o PHP assumir o controle restrito
        if (!u) { 
            return { name: "Usuário", role: role || "supervisor" }; 
        }
        return u;
    }
};

/* Toast helper — Mantido idêntico pois o seu layout.js e outras telas dependem dele para exibir alertas visuais */
function toast(title, desc, type="info") {
    let wrap = document.querySelector(".toast-wrap");
    if (!wrap) { 
        wrap = document.createElement("div"); 
        wrap.className = "toast-wrap"; 
        document.body.appendChild(wrap); 
    }
    const el = document.createElement("div");
    el.className = `toast ${type}`;
    el.innerHTML = `<div class="t">${title}</div>${desc ? `<div class="d">${desc}</div>` : ""}`;
    wrap.appendChild(el);
    setTimeout(() => el.remove(), 5000);
}

// Expõe globalmente para o sistema rodar sem travar em funções ausentes
window.AUTH = AUTH;
window.toast = toast;