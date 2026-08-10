# 🏟️ Kongo Arena

Plataforma de gestão esportiva.

## 📁 Estrutura

- `index.html` - Site público
- `admin/index.html` - Painel Admin
- `admin/cliente.html` - Área do Cliente
- `admin/login.html` - Login/Registro
- `api/` - Backend (PHP + MySQL, API REST + JWT)
- `database/schema.sql` - Esquema do banco de dados

## 🛠️ Stack

- PHP 8.5+
- MySQL
- API REST + JWT
- HTML/CSS/JS

## 📦 Instalação

1. Clone o repositório
2. Copie `.env.example` para `.env` e preencha com suas credenciais reais
   (ou configure as variáveis `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`,
   `JWT_SECRET` diretamente no painel de hospedagem/servidor)
3. Importe `database/schema.sql` no seu banco MySQL
4. **Nunca faça commit do `.env` real** - ele já está no `.gitignore`

## 🔒 Segurança

- As credenciais de banco e a chave JWT NÃO ficam no código-fonte
- O `.htaccess` bloqueia listagem de diretórios e acesso a arquivos sensíveis
- Scripts de diagnóstico/emergência não fazem parte do deploy de produção

---
Kongo Arena © 2026
