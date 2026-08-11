/**
 * KONGO ARENA - Painel Administrativo (SPA Logic)
 * Gerencia a comunicação com a API PHP e atualização dinâmica da UI
 */

const API_BASE = '../api/index.php?rota=';

// Estado global da aplicação
const state = {
    token: localStorage.getItem('token') || null,
    user: JSON.parse(localStorage.getItem('user') || '{}'),
    currentSection: 'dashboard'
};

// ======================================================
// INICIALIZAÇÃO
// ======================================================
document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
    initSidebar();
    loadSection('dashboard');
});

function checkAuth() {
    if (!state.token) {
        window.location.href = '../login.html';
        return;
    }
    
    // Atualizar UI com dados do usuário
    const userName = document.getElementById('userName');
    const userType = document.getElementById('userType');
    const userAvatar = document.getElementById('userAvatar');

    if (userName) userName.textContent = state.user.nome || 'Administrador';
    if (userType) userType.textContent = (state.user.tipo || 'admin').toUpperCase();
    if (userAvatar) userAvatar.textContent = (state.user.nome || 'A').charAt(0).toUpperCase();
}

function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = '../login.html';
}

function initSidebar() {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', () => {
            const section = item.dataset.section;
            if (section) {
                document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
                item.classList.add('active');
                loadSection(section);
            }
        });
    });
}

// ======================================================
// HELPER PARA CHAMADAS DE API
// ======================================================
async function apiFetch(endpoint, options = {}) {
    const headers = {
        'Content-Type': 'application/json',
        ...(state.token ? { 'Authorization': `Bearer ${state.token}` } : {}),
        ...(options.headers || {})
    };

    try {
        const response = await fetch(`${API_BASE}${endpoint}`, {
            ...options,
            headers
        });

        if (response.status === 401) {
            showToast('Sessão expirada. Por favor faça login novamente.', 'error');
            setTimeout(logout, 1500);
            return null;
        }

        const data = await response.json();
        if (!response.ok && data.error) {
            throw new Error(data.error);
        }
        return data;
    } catch (err) {
        showToast(err.message || 'Erro de ligação ao servidor', 'error');
        console.error('API Error:', err);
        return null;
    }
}

// ======================================================
// RENDERIZADOR DE SEÇÕES
// ======================================================
async function loadSection(section) {
    state.currentSection = section;
    const main = document.getElementById('mainContent');
    main.innerHTML = '<div class="loading">⏳ A carregar dados...</div>';

    switch (section) {
        case 'dashboard':
            await renderDashboard(main);
            break;
        case 'atletas':
            await renderAtletas(main);
            break;
        case 'modalidades':
            await renderModalidades(main);
            break;
        case 'clubes':
            await renderClubes(main);
            break;
        case 'equipas':
            await renderEquipas(main);
            break;
        case 'competicoes':
            await renderCompeticoes(main);
            break;
        case 'jogos':
            await renderJogos(main);
            break;
        case 'licencas':
            await renderLicencas(main);
            break;
        default:
            main.innerHTML = '<div class="card">Seção não encontrada</div>';
    }
}

// ======================================================
// 1. DASHBOARD
// ======================================================
async function renderDashboard(container) {
    const res = await apiFetch('dashboard/estatisticas');
    const data = res?.data || res || {};

    const stats = [
        { title: 'Atletas Registados', val: data.total_atletas ?? 0, icon: '🆔', color: '#e8b93f' },
        { title: 'Licenças Ativas', val: data.licencas_ativas ?? 0, icon: '📋', color: '#6bcf7f' },
        { title: 'Clubes', val: data.total_clubes ?? 0, icon: '🏛️', color: '#4a90e2' },
        { title: 'Equipas', val: data.total_equipas ?? 0, icon: '👥', color: '#9b59b6' },
        { title: 'Competições', val: data.total_competicoes ?? 0, icon: '🏆', color: '#e67e22' },
        { title: 'Jogos Agendados', val: data.jogos_agendados ?? 0, icon: '⚽', color: '#1abc9c' }
    ];

    container.innerHTML = `
        <div class="page-header">
            <h2>Visão Geral da Plataforma</h2>
            <p style="color:var(--muted);margin:4px 0 0;">Resumo de atividades e métricas em tempo real</p>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px; margin-bottom:30px;">
            ${stats.map(s => `
                <div class="card" style="display:flex; align-items:center; gap:16px; padding:20px;">
                    <div style="font-size:32px; background:rgba(255,255,255,0.05); padding:12px; border-radius:12px;">${s.icon}</div>
                    <div>
                        <div style="font-size:24px; font-weight:bold; color:${s.color};">${s.val}</div>
                        <div style="font-size:12px; color:var(--muted);">${s.title}</div>
                    </div>
                </div>
            `).join('')}
        </div>

        <div class="card">
            <h3>Ações Rápidas</h3>
            <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:12px;">
                <button class="logout-btn" style="border-color:var(--gold); color:var(--gold);" onclick="loadSection('atletas')">+ Novo Atleta</button>
                <button class="logout-btn" style="border-color:var(--gold); color:var(--gold);" onclick="loadSection('jogos')">+ Agendar Jogo</button>
                <button class="logout-btn" style="border-color:var(--success); color:var(--success);" onclick="atualizarLicencasExpiradas()">🔄 Atualizar Status de Licenças</button>
            </div>
        </div>
    `;
}

// ======================================================
// 2. ATLETAS
// ======================================================
async function renderAtletas(container) {
    const atletas = await apiFetch('atletas');
    const list = Array.isArray(atletas) ? atletas : (atletas?.data || []);

    container.innerHTML = `
        <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div>
                <h2>Gestão de Atletas</h2>
                <p style="color:var(--muted);margin:4px 0 0;">Registo de passaportes desportivos e Kongo IDs</p>
            </div>
            <button onclick="modalAtleta()" style="background:var(--gold); color:#000; border:none; padding:10px 18px; font-weight:bold; border-radius:6px; cursor:pointer;">+ Criar Atleta</button>
        </div>

        <div class="card" style="padding:0; overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
                <thead>
                    <tr style="border-bottom:1px solid var(--panel-border); color:var(--muted);">
                        <th style="padding:14px 16px;">Kongo ID</th>
                        <th style="padding:14px 16px;">Nome Completo</th>
                        <th style="padding:14px 16px;">Data Nasc.</th>
                        <th style="padding:14px 16px;">Gênero</th>
                        <th style="padding:14px 16px;">Clube</th>
                        <th style="padding:14px 16px;">Licença</th>
                        <th style="padding:14px 16px; text-align:right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    ${list.length === 0 ? '<tr><td colspan="7" style="padding:20px; text-align:center; color:var(--muted);">Nenhum atleta encontrado.</td></tr>' : 
                    list.map(a => `
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                            <td style="padding:14px 16px; font-weight:bold; color:var(--gold);">${a.kongo_id}</td>
                            <td style="padding:14px 16px;">${a.nome_completo}</td>
                            <td style="padding:14px 16px;">${a.data_nascimento || '-'}</td>
                            <td style="padding:14px 16px;">${a.genero || '-'}</td>
                            <td style="padding:14px 16px;">${a.clube_nome || '-'}</td>
                            <td style="padding:14px 16px;">
                                <span class="badge ${a.status_licenca === 'ativa' ? 'badge-success' : 'badge-warning'}" style="padding:4px 8px; border-radius:4px; font-size:11px; background:${a.status_licenca === 'ativa' ? 'rgba(107,207,127,0.15)' : 'rgba(224,92,92,0.15)'}; color:${a.status_licenca === 'ativa' ? 'var(--success)' : 'var(--danger)'};">
                                    ${(a.status_licenca || 'expirada').toUpperCase()}
                                </span>
                            </td>
                            <td style="padding:14px 16px; text-align:right;">
                                <button onclick="modalAtleta(${a.id})" style="background:transparent; border:1px solid var(--panel-border); color:var(--text); padding:4px 10px; border-radius:4px; cursor:pointer;">Editar</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

async function modalAtleta(id = null) {
    let data = {};
    if (id) {
        data = await apiFetch(`atletas/${id}`) || {};
    }

    // Busca todos os clubes já cadastrados para preencher o dropdown
    const clubes = await apiFetch('clubes') || [];
    const opcoesClubes = clubes.map(c =>
        `<option value="${c.id}" ${data.clube_id == c.id ? 'selected' : ''}>${c.nome}</option>`
    ).join('');

    const content = `
        <form id="formAtleta" onsubmit="salvarAtleta(event, ${id})">
            <div style="display:flex; flex-direction:column; gap:12px;">
                <label>Nome Completo:
                    <input type="text" id="nome_completo" value="${data.nome_completo || ''}" required style="width:100%; padding:8px; background:var(--panel-2); border:1px solid var(--panel-border); color:#fff; border-radius:4px; margin-top:4px;">
                </label>
                <label>Data de Nascimento:
                    <input type="date" id="data_nascimento" value="${data.data_nascimento || ''}" required style="width:100%; padding:8px; background:var(--panel-2); border:1px solid var(--panel-border); color:#fff; border-radius:4px; margin-top:4px;">
                </label>
                <div style="display:flex; gap:12px;">
                    <label style="flex:1;">Gênero:
                        <select id="genero" style="width:100%; padding:8px; background:var(--panel-2); border:1px solid var(--panel-border); color:#fff; border-radius:4px; margin-top:4px;">
                            <option value="M" ${data.genero === 'M' ? 'selected' : ''}>Masculino</option>
                            <option value="F" ${data.genero === 'F' ? 'selected' : ''}>Feminino</option>
                        </select>
                    </label>
                    <label style="flex:1;">Nacionalidade:
                        <input type="text" id="nacionalidade" value="${data.nacionalidade || 'Angola'}" style="width:100%; padding:8px; background:var(--panel-2); border:1px solid var(--panel-border); color:#fff; border-radius:4px; margin-top:4px;">
                    </label>
                </div>
                <label>Clube:
                    <select id="clube_id" style="width:100%; padding:8px; background:var(--panel-2); border:1px solid var(--panel-border); color:#fff; border-radius:4px; margin-top:4px;">
                        <option value="">— Sem clube —</option>
                        ${opcoesClubes}
                    </select>
                </label>
                <button type="submit" style="background:var(--gold); color:#000; border:none; padding:10px; font-weight:bold; border-radius:4px; cursor:pointer; margin-top:10px;">Salvar Atleta</button>
            </div>
        </form>
    `;
    openModal(id ? 'Editar Atleta' : 'Novo Atleta', content);
}

async function salvarAtleta(e, id) {
    e.preventDefault();
    const clubeSelecionado = document.getElementById('clube_id').value;
    const payload = {
        nome_completo: document.getElementById('nome_completo').value,
        data_nascimento: document.getElementById('data_nascimento').value,
        genero: document.getElementById('genero').value,
        nacionalidade: document.getElementById('nacionalidade').value,
        clube_id: clubeSelecionado ? clubeSelecionado : null
    };

    const res = await apiFetch(id ? `atletas/${id}` : 'atletas', {
        method: id ? 'PUT' : 'POST',
        body: JSON.stringify(payload)
    });

    if (res) {
        showToast(`Atleta ${id ? 'atualizado' : 'criado'} com sucesso!`, 'success');
        closeModal();
        loadSection('atletas');
    }
}

// ======================================================
// 3. MODALIDADES
// ======================================================
async function renderModalidades(container) {
    const res = await apiFetch('modalidades');
    const list = Array.isArray(res) ? res : (res?.data || []);

    container.innerHTML = `
        <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div>
                <h2>Modalidades Desportivas</h2>
                <p style="color:var(--muted);margin:4px 0 0;">Gestão das modalidades suportadas na plataforma</p>
            </div>
            <button onclick="modalModalidade()" style="background:var(--gold); color:#000; border:none; padding:10px 18px; font-weight:bold; border-radius:6px; cursor:pointer;">+ Nova Modalidade</button>
        </div>

        <div class="card" style="padding:0; overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
                <thead>
                    <tr style="border-bottom:1px solid var(--panel-border); color:var(--muted);">
                        <th style="padding:14px 16px;">ID</th>
                        <th style="padding:14px 16px;">Nome</th>
                        <th style="padding:14px 16px;">Descrição</th>
                        <th style="padding:14px 16px;">Status</th>
                        <th style="padding:14px 16px; text-align:right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    ${list.map(m => `
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                            <td style="padding:14px 16px;">#${m.id}</td>
                            <td style="padding:14px 16px; font-weight:bold; color:var(--gold);">${m.nome}</td>
                            <td style="padding:14px 16px;">${m.descricao || '-'}</td>
                            <td style="padding:14px 16px;">${m.status === 'ativo' ? '🟢 Ativo' : '🔴 Inativo'}</td>
                            <td style="padding:14px 16px; text-align:right;">
                                <button onclick="toggleModalidadeStatus(${m.id}, '${m.status}')" style="background:transparent; border:1px solid var(--panel-border); color:var(--text); padding:4px 10px; border-radius:4px; cursor:pointer;">
                                    ${m.status === 'ativo' ? 'Desativar' : 'Ativar'}
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

async function modalModalidade() {
    const content = `
        <form onsubmit="salvarModalidade(event)">
            <div style="display:flex; flex-direction:column; gap:12px;">
                <label>Nome da Modalidade:
                    <input type="text" id="mod_nome" required style="width:100%; padding:8px; background:var(--panel-2); border:1px solid var(--panel-border); color:#fff; border-radius:4px; margin-top:4px;">
                </label>
                <label>Descrição:
                    <textarea id="mod_desc" rows="3" style="width:100%; padding:8px; background:var(--panel-2); border:1px solid var(--panel-border); color:#fff; border-radius:4px; margin-top:4px;"></textarea>
                </label>
                <button type="submit" style="background:var(--gold); color:#000; border:none; padding:10px; font-weight:bold; border-radius:4px; cursor:pointer; margin-top:10px;">Criar Modalidade</button>
            </div>
        </form>
    `;
    openModal('Nova Modalidade', content);
}

async function salvarModalidade(e) {
    e.preventDefault();
    const payload = {
        nome: document.getElementById('mod_nome').value,
        descricao: document.getElementById('mod_desc').value
    };
    const res = await apiFetch('modalidades', { method: 'POST', body: JSON.stringify(payload) });
    if (res) {
        showToast('Modalidade adicionada!', 'success');
        closeModal();
        loadSection('modalidades');
    }
}

async function toggleModalidadeStatus(id, currentStatus) {
    const newStatus = currentStatus === 'ativo' ? 'inativo' : 'ativo';
    const res = await apiFetch(`modalidades/${id}/status`, {
        method: 'POST',
        body: JSON.stringify({ status: newStatus })
    });
    if (res) {
        showToast('Status atualizado!', 'success');
        loadSection('modalidades');
    }
}

// ======================================================
// 4. CLUBES
// ======================================================
async function renderClubes(container) {
    const res = await apiFetch('clubes');
    const list = Array.isArray(res) ? res : (res?.data || []);

    container.innerHTML = `
        <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div>
                <h2>Clubes Desportivos</h2>
                <p style="color:var(--muted);margin:4px 0 0;">Entidades institucionais filiadas</p>
            </div>
            <button onclick="modalClube()" style="background:var(--gold); color:#000; border:none; padding:10px 18px; font-weight:bold; border-radius:6px; cursor:pointer;">+ Criar Clube</button>
        </div>

        <div class="card" style="padding:0; overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
                <thead>
                    <tr style="border-bottom:1px solid var(--panel-border); color:var(--muted);">
                        <th style="padding:14px 16px;">Nome</th>
                        <th style="padding:14px 16px;">Cidade</th>
                        <th style="padding:14px 16px;">Responsável</th>
                        <th style="padding:14px 16px;">Contacto</th>
                    </tr>
                </thead>
                <tbody>
                    ${list.map(c => `
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                            <td style="padding:14px 16px; font-weight:bold; color:var(--gold);">${c.nome}</td>
                            <td style="padding:14px 16px;">${c.cidade || '-'}</td>
                            <td style="padding:14px 16px;">${c.responsavel || '-'}</td>
                            <td style="padding:14px 16px;">${c.contacto || c.email || '-'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

async function modalClube() {
    const content = `
        <form onsubmit="salvarClube(event)">
            <div style="display:flex; flex-direction:column; gap:12px;">
                <label>Nome do Clube:
                    <input type="text" id="clube_nome" required style="width:100%; padding:8px; background:var(--panel-2); border:1px solid var(--panel-border); color:#fff; border-radius:4px; margin-top:4px;">
                </label>
                <label>Cidade:
                    <input type="text" id="clube_cidade" style="width:100%; padding:8px; background:var(--panel-2); border:1px solid var(--panel-border); color:#fff; border-radius:4px; margin-top:4px;">
                </label>
                <label>Responsável:
                    <input type="text" id="clube_resp" style="width:100%; padding:8px; background:var(--panel-2); border:1px solid var(--panel-border); color:#fff; border-radius:4px; margin-top:4px;">
                </label>
                <button type="submit" style="background:var(--gold); color:#000; border:none; padding:10px; font-weight:bold; border-radius:4px; cursor:pointer; margin-top:10px;">Salvar Clube</button>
            </div>
        </form>
    `;
    openModal('Novo Clube', content);
}

async function salvarClube(e) {
    e.preventDefault();
    const payload = {
        nome: document.getElementById('clube_nome').value,
        cidade: document.getElementById('clube_cidade').value,
        responsavel: document.getElementById('clube_resp').value
    };
    const res = await apiFetch('clubes', { method: 'POST', body: JSON.stringify(payload) });
    if (res) {
        showToast('Clube registado!', 'success');
        closeModal();
        loadSection('clubes');
    }
}

// ======================================================
// 5. EQUIPAS
// ======================================================
async function renderEquipas(container) {
    const res = await apiFetch('equipas');
    const list = Array.isArray(res) ? res : (res?.data || []);

    container.innerHTML = `
        <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div>
                <h2>Equipas e Planteis</h2>
                <p style="color:var(--muted);margin:4px 0 0;">Equipas inscritas nas várias modalidades</p>
            </div>
        </div>

        <div class="card" style="padding:0; overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
                <thead>
                    <tr style="border-bottom:1px solid var(--panel-border); color:var(--muted);">
                        <th style="padding:14px 16px;">Nome da Equipa</th>
                        <th style="padding:14px 16px;">Clube</th>
                        <th style="padding:14px 16px;">Categoria</th>
                        <th style="padding:14px 16px;">Treinador</th>
                    </tr>
                </thead>
                <tbody>
                    ${list.map(eq => `
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                            <td style="padding:14px 16px; font-weight:bold; color:var(--gold);">${eq.nome}</td>
                            <td style="padding:14px 16px;">${eq.clube_nome || '-'}</td>
                            <td style="padding:14px 16px;">${eq.categoria || '-'}</td>
                            <td style="padding:14px 16px;">${eq.treinador || '-'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// ======================================================
// 6. COMPETIÇÕES
// ======================================================
async function renderCompeticoes(container) {
    const res = await apiFetch('competicoes');
    const list = Array.isArray(res) ? res : (res?.data || []);

    container.innerHTML = `
        <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div>
                <h2>Competições Desportivas</h2>
                <p style="color:var(--muted);margin:4px 0 0;">Torneios, Ligas e Campeonatos</p>
            </div>
        </div>

        <div class="card" style="padding:0; overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
                <thead>
                    <tr style="border-bottom:1px solid var(--panel-border); color:var(--muted);">
                        <th style="padding:14px 16px;">Competição</th>
                        <th style="padding:14px 16px;">Modalidade</th>
                        <th style="padding:14px 16px;">Categoria</th>
                        <th style="padding:14px 16px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${list.map(c => `
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                            <td style="padding:14px 16px; font-weight:bold; color:var(--gold);">${c.nome}</td>
                            <td style="padding:14px 16px;">${c.modalidade_nome || '-'}</td>
                            <td style="padding:14px 16px;">${c.categoria || '-'}</td>
                            <td style="padding:14px 16px;">${(c.status || 'agendada').toUpperCase()}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// ======================================================
// 7. JOGOS
// ======================================================
async function renderJogos(container) {
    const res = await apiFetch('jogos');
    const list = Array.isArray(res) ? res : (res?.data || []);

    container.innerHTML = `
        <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div>
                <h2>Calendário de Jogos</h2>
                <p style="color:var(--muted);margin:4px 0 0;">Agendamento de partidas e registo de resultados</p>
            </div>
        </div>

        <div class="card" style="padding:0; overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
                <thead>
                    <tr style="border-bottom:1px solid var(--panel-border); color:var(--muted);">
                        <th style="padding:14px 16px;">Data</th>
                        <th style="padding:14px 16px;">Confronto</th>
                        <th style="padding:14px 16px;">Resultado</th>
                        <th style="padding:14px 16px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${list.map(j => `
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                            <td style="padding:14px 16px;">${j.data_jogo || '-'}</td>
                            <td style="padding:14px 16px; font-weight:bold;">${j.equipa_casa_nome || 'Casa'} vs ${j.equipa_fora_nome || 'Fora'}</td>
                            <td style="padding:14px 16px; font-weight:bold; color:var(--gold);">${j.golos_casa ?? '-'} : ${j.golos_fora ?? '-'}</td>
                            <td style="padding:14px 16px;">${(j.status || 'agendado').toUpperCase()}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// ======================================================
// 8. LICENÇAS
// ======================================================
async function renderLicencas(container) {
    const res = await apiFetch('licencas');
    const list = Array.isArray(res) ? res : (res?.data || []);

    container.innerHTML = `
        <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div>
                <h2>Licenças de Atletas</h2>
                <p style="color:var(--muted);margin:4px 0 0;">Estado das anotações e revalidações de passaportes</p>
            </div>
            <button onclick="atualizarLicencasExpiradas()" style="background:var(--success); color:#000; border:none; padding:10px 18px; font-weight:bold; border-radius:6px; cursor:pointer;">🔄 Atualizar Expiradas</button>
        </div>

        <div class="card" style="padding:0; overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
                <thead>
                    <tr style="border-bottom:1px solid var(--panel-border); color:var(--muted);">
                        <th style="padding:14px 16px;">Atleta</th>
                        <th style="padding:14px 16px;">Emissão</th>
                        <th style="padding:14px 16px;">Expiração</th>
                        <th style="padding:14px 16px;">Status</th>
                        <th style="padding:14px 16px; text-align:right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    ${list.map(l => `
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                            <td style="padding:14px 16px; font-weight:bold; color:var(--gold);">${l.nome_completo || 'Atleta #' + l.atleta_id}</td>
                            <td style="padding:14px 16px;">${l.data_emissao || '-'}</td>
                            <td style="padding:14px 16px;">${l.data_expiracao || '-'}</td>
                            <td style="padding:14px 16px;">
                                <span style="padding:4px 8px; border-radius:4px; font-size:11px; background:${l.status === 'ativa' ? 'rgba(107,207,127,0.15)' : 'rgba(224,92,92,0.15)'}; color:${l.status === 'ativa' ? 'var(--success)' : 'var(--danger)'};">
                                    ${(l.status || 'expirada').toUpperCase()}
                                </span>
                            </td>
                            <td style="padding:14px 16px; text-align:right;">
                                <button onclick="renovarLicenca(${l.atleta_id})" style="background:transparent; border:1px solid var(--gold); color:var(--gold); padding:4px 10px; border-radius:4px; cursor:pointer;">Renovar 1 Ano</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

async function renovarLicenca(atletaId) {
    const res = await apiFetch(`licencas/${atletaId}/renovar`, { method: 'POST' });
    if (res) {
        showToast('Licença renovada com sucesso por 1 ano!', 'success');
        loadSection('licencas');
    }
}

async function atualizarLicencasExpiradas() {
    const res = await apiFetch('licencas/atualizar-expiradas', { method: 'POST' });
    if (res) {
        showToast(`Status atualizado! ${res.expiradas_atualizadas ?? 0} licenças expiradas.`, 'success');
        loadSection('licencas');
    }
}

// ======================================================
// COMPONENTES DE UI (MODAL & TOAST)
// ======================================================
function openModal(title, bodyHtml) {
    const overlay = document.getElementById('overlay');
    overlay.innerHTML = `
        <div class="card" style="max-width:500px; width:90%; margin:40px auto; position:relative; background:var(--panel);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid var(--panel-border); padding-bottom:12px;">
                <h3 style="margin:0; color:var(--gold);">${title}</h3>
                <button onclick="closeModal()" style="background:none; border:none; color:var(--muted); font-size:20px; cursor:pointer;">&times;</button>
            </div>
            ${bodyHtml}
        </div>
    `;
    overlay.style.display = 'block';
}

function closeModal() {
    const overlay = document.getElementById('overlay');
    overlay.style.display = 'none';
    overlay.innerHTML = '';
}

function showToast(msg, type = 'info') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = msg;
    toast.style.background = type === 'error' ? 'var(--danger)' : (type === 'success' ? '#27ae60' : 'var(--panel-2)');
    toast.style.color = '#fff';
    toast.style.padding = '12px 20px';
    toast.style.borderRadius = '6px';
    toast.style.position = 'fixed';
    toast.style.bottom = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';
    toast.style.display = 'block';

    setTimeout(() => {
        toast.style.display = 'none';
    }, 4000);
}
