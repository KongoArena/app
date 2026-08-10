# Relatório — Cadastro do Cliente e Número de Série (Kongo ID)

## 1. Como o cliente se cadastra

1. Na página `login.html` (raiz do site), o cliente clica na aba **"Criar Conta"**.
2. Preenche: nome completo, e-mail, telefone (opcional) e senha (mínimo 6 caracteres, com confirmação).
3. O formulário envia um `POST` para `api/index.php?rota=register`.
4. O backend (`AuthController::register` → `Utilizador::create`) faz:
   - Gera um hash seguro da senha (`password_hash`, bcrypt)
   - Insere um novo registo na tabela `cong_utilizadores`, com `status = 'ativo'` e `tipo = 'atleta'` (valor padrão)
5. Se tudo correr bem, a conta é criada e o cliente é redirecionado de volta para a aba de login, podendo entrar com e-mail e senha.

**Importante:** neste passo, o cliente só ganha uma **conta de acesso** (login). Ele ainda **não tem** um perfil de atleta nem o número de série.

## 2. Como o cliente recebe o número de série (Kongo ID)

O número de série (formato `KA-000123`) **não é gerado automaticamente no cadastro**. Ele só é criado quando um **perfil de atleta** é registado no sistema — e isso só pode ser feito por um utilizador do tipo **admin** ou **gestor** (o código bloqueia explicitamente qualquer outro tipo de conta: `AtletaController::criar()`, linha 39).

Fluxo completo:

1. Cliente cria a conta (passo 1 acima) e faz login
2. Um **admin ou gestor** entra no painel administrativo e cria o perfil de atleta desse cliente, preenchendo dados como nome, data de nascimento, modalidade, etc.
3. Nesse momento, o sistema (`Atleta::create`, em `api/models/Atleta.php`) gera o Kongo ID automaticamente:
   - Pega o maior `id` já existente na tabela `cong_atletas`
   - Soma 1
   - Formata como `KA-` seguido do número com 6 dígitos (ex.: `KA-000124`)
4. Ao mesmo tempo, o sistema já cria automaticamente uma **licença** para esse atleta (`criarLicenca`), válida por 1 ano a partir da data de emissão, com status inicial `pendente` até ser confirmada.
5. O cliente passa a ver o Kongo ID no seu perfil, na área do cliente (`admin/cliente.html`).

## 3. Ponto de atenção

Como está hoje, o cliente **não consegue se autogerar** um número de série — depende sempre de uma ação manual do admin/gestor. Se a ideia é que o cadastro já gere o Kongo ID automaticamente (self-service), isso exigiria uma mudança no backend: fazer `Utilizador::create` já chamar `Atleta::create` na sequência, ou criar uma rota própria para isso. Não fiz essa mudança porque pode ser proposital (controle de quem entra como atleta oficial) — me avisa se quiser que eu implemente o fluxo automático.

## 4. Recuperação de acesso

Não existe hoje nenhuma funcionalidade de "esqueci minha senha" no sistema (nem na tela de login, nem na API). Preparei um script (`tools/recuperar_acesso.php`) para uso manual do admin, protegido para rodar **apenas via linha de comando (SSH)** — nunca pela web — evitando repetir o problema do antigo `login_emergencia.php`, que era um script público sem autenticação.

**Como usar** (no servidor, via terminal/SSH):
```
php tools/recuperar_acesso.php email@doexemplo.com NovaSenha123
```

Isso redefine a senha do utilizador com aquele e-mail e garante que a conta fique com `status = 'ativo'`.
