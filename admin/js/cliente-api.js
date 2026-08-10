// js/cliente-api.js
const KongoAPI = {
    // Caminho para a sua API
    baseUrl: '/api/index.php?rota=',

    // Pega o token salvo no navegador após o login
    getToken() {
        return localStorage.getItem('kongo_token');
    },

    // Monta os cabeçalhos de segurança para a API
    getHeaders() {
        return {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${this.getToken()}` // O seu middleware Auth.php vai ler isso
        };
    },

    // Método genérico para fazer requisições
    async request(endpoint, method = 'GET', body = null) {
        try {
            const options = {
                method: method,
                headers: this.getHeaders()
            };

            if (body && (method === 'POST' || method === 'PUT')) {
                options.body = JSON.stringify(body);
            }

            const response = await fetch(this.baseUrl + endpoint, options);

            // Se o token expirou ou é inválido, joga para o login
            if (response.status === 401 || response.status === 403) {
                alert('Sessão expirada. Faça login novamente.');
                localStorage.removeItem('kongo_token');
                window.location.href = 'login.html';
                return;
            }

            return await response.json();
        } catch (error) {
            console.error('Erro na API Kongo:', error);
            return { error: 'Falha de conexão com o servidor.' };
        }
    },

    // ==========================================
    // MÉTODOS ESPECÍFICOS PARA O PAINEL DO CLIENTE
    // ==========================================

    async getMeuClube(clubeId) {
        return this.request(`clubes/${clubeId}`);
    },

    async getMinhasEquipas() {
        return this.request('equipas');
    },

    async getJogos() {
        return this.request('jogos');
    },

    async getLicencas() {
        return this.request('licencas');
    },

    async getEstatisticas() {
        return this.request('dashboard/estatisticas');
    }
};