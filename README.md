# Kongo Arena

Plataforma completa de gestão esportiva para atletas, clubes e competições.

## Sobre o Projeto

O Kongo Arena é um sistema web desenvolvido em PHP 8.5+ que permite a gestão completa de competições esportivas, incluindo:

- Cadastro e gestão de atletas
- Administração de clubes e equipas
- Organização de competições e temporadas
- Gerenciamento de jogos e resultados
- Sistema de rankings automático
- Controle de licenças de atletas
- Dashboard administrativo e área do cliente
- API REST com autenticação JWT

## Stack Tecnológica

- **Backend:** PHP 8.5+ com PDO
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Banco de Dados:** MySQL (Locaweb)
- **API:** REST com JWT (JSON Web Tokens)
- **Servidor:** Apache/Linux
- **Hospedagem:** Tanque Digital + Locaweb (DB)
- **Versionamento:** Git + GitHub

## Estrutura do Projeto
kongo/
|-- admin/ # Painel administrativo
| |-- login.html
| |-- index.html
| |-- dashboard.html
|-- cliente/ # Área do cliente
| |-- login.html
| |-- index.html
| |-- dashboard.html
|-- api/ # API REST
| |-- index.php # Router principal
| |-- config/
| | |-- database.php # Configuração do banco
| | |-- jwt.php # Configuração JWT
| |-- controllers/
| | |-- AuthController.php
| |-- models/
| | |-- Utilizador.php
| |-- middleware/
| |-- Auth.php
|-- .htaccess # Configurações de segurança
|-- README.md


