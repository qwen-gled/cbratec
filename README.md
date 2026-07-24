# Congress Management System - Sistema de Gerenciamento de Submissão de Trabalhos

## Visão Geral

Sistema completo para gerenciamento de submissão de trabalhos científicos para congressos, desenvolvido em PHP 8.2+ com arquitetura MVC e API REST segura.

## Stack Tecnológica

- **Backend:** PHP 8.2+ (Orientado a Objetos, Padrão MVC)
- **Banco de Dados:** MySQL 8.0+
- **API:** REST API com JWT para autenticação
- **Autenticação Social:** Google OAuth 2.0 (preparado para integração)
- **Envio de E-mails:** PHPMailer / Symfony Mailer

## Estrutura do Projeto

```
/workspace
├── config/                 # Configurações da aplicação
│   ├── app.php            # Configurações gerais
│   └── database.php       # Configuração do banco de dados
├── database/
│   └── schema.sql         # Schema completo do banco de dados
├── src/
│   ├── Controllers/       # Controladores da API
│   │   ├── AuthController.php
│   │   ├── AbstractController.php
│   │   └── AdminController.php
│   ├── Middleware/        # Middlewares
│   │   └── AuthMiddleware.php
│   ├── Models/            # Modelos de dados
│   │   ├── Model.php      # Model base
│   │   ├── User.php
│   │   ├── Area.php
│   │   ├── AreaModerator.php
│   │   ├── AbstractModel.php
│   │   ├── AbstractHistory.php
│   │   └── SystemSettings.php
│   ├── Services/          # Serviços de negócio
│   │   ├── JwtService.php
│   │   ├── EmailService.php
│   │   └── AbstractService.php
│   ├── Validators/        # Validadores
│   │   └── FileUploadValidator.php
│   └── Helpers/           # Helpers utilitários
│       └── Database.php
├── public/
│   ├── api/
│   │   └── index.php      # Entry point da API
│   └── uploads/           # Arquivos uploadados
│       ├── abstracts/
│       └── payment_proofs/
└── templates/             # Templates de e-mail
    └── emails/
```

## Instalação

### 1. Pré-requisitos

- PHP 8.2+
- MySQL 8.0+
- Composer
- Extensões PHP: PDO, pdo_mysql, openssl, json, mbstring

### 2. Configurar Banco de Dados

```bash
mysql -u root -p < database/schema.sql
```

### 3. Instalar Dependências

```bash
composer require firebase/php-jwt phpmailer/phpmailer
```

### 4. Configurar Variáveis de Ambiente

Edite `config/database.php` e `config/app.php` ou use variáveis de ambiente:

```bash
export DB_HOST=localhost
export DB_NAME=congress_db
export DB_USER=root
export DB_PASS=sua_senha

export JWT_SECRET=sua_chave_secreta_forte
export APP_URL=http://localhost

export GOOGLE_CLIENT_ID=seu_client_id
export GOOGLE_CLIENT_SECRET=seu_client_secret

export MAIL_HOST=smtp.gmail.com
export MAIL_PORT=587
export MAIL_USERNAME=seu_email
export MAIL_PASSWORD=sua_senha
```

### 5. Configurar Servidor Web

**Apache (.htaccess):**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ public/api/index.php [QSA,L]
```

**Nginx:**
```nginx
location /api/ {
    try_files $uri $uri/ /public/api/index.php?$query_string;
}
```

## API Endpoints

### Autenticação

| Método | Endpoint | Descrição | Auth |
|--------|----------|-----------|------|
| POST | `/api/auth/register` | Registrar novo usuário | Não |
| POST | `/api/auth/login` | Login com email/senha | Não |
| GET | `/api/auth/me` | Obter dados do usuário logado | Sim |
| POST | `/api/auth/logout` | Logout | Sim |

### Submissões (Abstracts)

| Método | Endpoint | Descrição | Auth | Role |
|--------|----------|-----------|------|------|
| GET | `/api/abstracts` | Listar submissões | Sim | Todos |
| POST | `/api/abstracts` | Nova submissão | Sim | User (payment approved) |
| GET | `/api/abstracts/{id}` | Detalhes da submissão | Sim | Todos |
| PUT | `/api/abstracts/{id}/replace` | Substituir arquivo | Sim | User (payment approved) |
| PUT | `/api/abstracts/{id}/status` | Alterar status | Sim | Moderator/Admin |
| DELETE | `/api/abstracts/{id}` | Excluir submissão | Sim | Owner |

### Administrativo

| Método | Endpoint | Descrição | Role |
|--------|----------|-----------|------|
| GET | `/api/admin/users` | Listar usuários | Admin |
| GET | `/api/admin/payments/pending` | Pagamentos pendentes | Admin |
| PUT | `/api/admin/payments/{id}` | Aprovar/rejeitar pagamento | Admin |
| GET | `/api/admin/areas` | Listar áreas | Admin |
| POST | `/api/admin/areas` | Criar área | Admin |
| PUT | `/api/admin/settings/deadline` | Configurar prazo | Admin |
| GET | `/api/admin/stats` | Estatísticas do dashboard | Admin |
| POST | `/api/admin/moderators/assign` | Vincular moderador | Admin |

## Regras de Negócio Implementadas

### 1. Gestão de Usuários e Pagamentos

- Cadastro com email/senha ou Google OAuth
- Campos obrigatórios: email, nome, data de nascimento, CPF/Passaporte, país, instituição, categoria
- **Gatekeeper de Pagamento:** Usuários inadimplentes não podem submeter resumos
- Upload de comprovante de pagamento para aprovação administrativa

### 2. Submissão de Resumos

- Máximo de 2 arquivos PDF por usuário adimplente
- Submissões apenas antes da "Data Limite" configurável
- Vínculo com Área/Tema obrigatório
- Status inicial: "Pendente de avaliação"
- Substituição permitida enquanto status for "Pendente", "Aceito com correções" ou "Pendente de revisão"

### 3. Fluxo de Avaliação

- Moderadores vinculados a Áreas específicas
- Visualização apenas de resumos das suas áreas
- Moderador NÃO pode avaliar seus próprios resumos
- **Status possíveis:**
  - **Aceito:** Fim do fluxo
  - **Recusado:** Exige justificativa. Usuário pode enviar NOVO arquivo (não conta no limite)
  - **Aceito com correções:** Exige justificativa. Usuário corrige e reenvia
- Reenvio de "Aceito com correções" muda automaticamente para "Pendente de revisão"

### 4. Histórico e Notificações

- Toda mudança de status registrada em `abstract_history`
- Histórico visível para todos os perfis
- E-mail automático enviado a cada mudança de status

## Modelo de Dados

### Tabelas Principais

- `users` - Usuários com roles (user, moderator, admin) e status de pagamento
- `areas` - Áreas/Temas do congresso
- `area_moderators` - Vínculo N:N entre moderadores e áreas
- `abstracts` - Submissões de resumos com status e arquivos
- `abstract_history` - Log completo de alterações de status
- `system_settings` - Configurações do sistema (prazo, limites)
- `jwt_blacklist` - Tokens JWT revogados
- `user_refresh_tokens` - Refresh tokens para renovação de sessão

## Segurança

- Senhas hash com bcrypt
- JWT para autenticação stateless
- Prepared Statements (PDO) contra SQL Injection
- Validação estrita de uploads (apenas PDF, verificação de MIME type)
- Sanitização de inputs
- Proteção contra CSRF/XSS via headers e validação

## Próximos Passos

1. Instalar dependências via Composer
2. Configurar credenciais do Google OAuth Console
3. Ajustar configurações de e-mail SMTP
4. Desenvolver frontend (React/Vue/Angular ou blades PHP)
5. Implementar testes unitários e de integração
6. Configurar ambiente de produção com HTTPS

## Usuário Admin Padrão

- **Email:** admin@congress.com
- **Senha:** admin123 (alterar imediatamente!)

---

Desenvolvido com arquitetura limpa e padrões de mercado para fácil manutenção e escalabilidade.
