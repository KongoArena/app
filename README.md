# 🏟️ Kongo Arena

Plataforma completa de gestão esportiva para atletas, clubes e competições.

## 📋 Sobre o Projeto

O Kongo Arena é um sistema web desenvolvido em PHP 8.5+ que permite a gestão completa de competições esportivas.

### Funcionalidades Principais

- 📊 **Painel Administrativo**: Gestão de atletas, modalidades, clubes, equipas, competições, jogos e licenças
- 👥 **Área do Cliente**: Acompanhamento de equipa, jogos, calendário e licenças
- 🔐 **Autenticação JWT**: Sistema seguro de login para admin e clientes
- 🏆 **Competições**: Organização de campeonatos e gerenciamento de resultados
- 📋 **Licenças**: Controle de licenças de atletas com renovação automática

## 🛠️ Stack Tecnológica

- **Backend:** PHP 8.5+ com PDO
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Banco de Dados:** MySQL (Locaweb)
- **API:** REST com JWT (JSON Web Tokens)
- **Servidor:** Apache/Linux
- **Hospedagem:** Tanque Digital + Locaweb (DB)
- **Versionamento:** Git + GitHub

## 📁 Estrutura do Projeto
kongoarena/
├── index.html # Página de apresentação (GitHub Pages)
├── admin.html # Painel Administrativo
├── cliente.html # Painel do Cliente (FC Kongo)
├── login.html # Login/Registro de usuários
├── login_funcional.html # Login de Emergência (admin/admin123)
├── README.md # Documentação
├── api/ # API REST
│ ├── index.php # Router principal
│ ├── config/
│ │ ├── database.php # Configuração do banco
│ │ └── jwt.php # Configuração JWT
│ ├── controllers/
│ │ └── AuthController.php
│ ├── models/
│ │ └── Utilizador.php
│ └── middleware/
│ └── Auth.php
├── .htaccess # Configurações de segurança
└── varredura_completa_v3.php # Script de diagnóstico

## 🚀 Acesso ao Sistema

### Página de Apresentação
- **URL:** `/index.html` (GitHub Pages)
- **Descrição:** Página inicial com informações do projeto e links de acesso

### Painel Administrativo
- **URL:** `/admin.html`
- **Menu:** Visão geral, Atletas, Modalidades, Clubes, Equipas, Competições, Jogos, Licenças

### Área do Cliente
- **URL:** `/cliente.html`
- **Menu:** Visão Geral, Minha Equipa, Jogos e Calendário, Competições, Licenças
- **Exemplo:** FC Kongo - Temporada 2025/2026

### Login/Registro
- **URL:** `/login.html`
- **Funcionalidades:** Entrar na conta ou criar novo cadastro

### Login de Emergência
- **URL:** `/login_funcional.html`
- **Credenciais:** admin / admin123

## 🌐 API Endpoints

### Autenticação
- `POST /api/index.php?rota=login` - Login de usuário
- `POST /api/index.php?rota=registrar` - Registro de novo usuário

### Atletas
- `GET /api/index.php?rota=atletas` - Listar todos os atletas
- `POST /api/index.php?rota=atletas` - Criar novo atleta
- `GET /api/index.php?rota=atletas/{ID}` - Obter atleta específico
- `PUT /api/index.php?rota=atletas/{ID}` - Atualizar atleta

### Clubes
- `GET /api/index.php?rota=clubes` - Listar clubes
- `POST /api/index.php?rota=clubes` - Criar clube
- `DELETE /api/index.php?rota=clubes/{ID}` - Deletar clube

### Competições
- `GET /api/index.php?rota=competicoes` - Listar competições
- `GET /api/index.php?rota=competicoes/{ID}/classificacao` - Ver classificação

### Jogos
- `GET /api/index.php?rota=jogos` - Listar jogos
- `PUT /api/index.php?rota=jogos/{ID}/resultado` - Atualizar resultado

### Rankings
- `GET /api/index.php?rota=rankings/competicao/{ID}` - Ranking da competição
- `POST /api/index.php?rota=rankings/competicao/{ID}/recalcular` - Recalcular ranking

## 🔐 Autenticação

O sistema usa JWT (JSON Web Tokens) para autenticação. Após fazer login, você receberá um token que deve ser enviado no header das requisições:

```javascript
fetch('/api/index.php?rota=atletas', {
    headers: {
        'Authorization': 'Bearer SEU_TOKEN_AQUI'
    }
});
