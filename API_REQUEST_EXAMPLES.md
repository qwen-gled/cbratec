# Exemplos de Requisições à API

## Base URL
```
http://localhost/api/
```

---

## 🔐 ENDPOINTS DE AUTENTICAÇÃO

### 1. Registrar Novo Usuário
**Endpoint:** `POST /api/auth/register`

```bash
curl -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao.silva@email.com",
    "password": "senha123",
    "institution": "Universidade Federal",
    "role": "participant"
  }'
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "João Silva",
    "email": "joao.silva@email.com"
  }
}
```

---

### 2. Login
**Endpoint:** `POST /api/auth/login`

```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "joao.silva@email.com",
    "password": "senha123"
  }'
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao.silva@email.com",
      "role": "participant"
    }
  }
}
```

---

### 3. Obter Dados do Usuário Autenticado
**Endpoint:** `GET /api/auth/me`

```bash
curl -X GET http://localhost/api/auth/me \
  -H "Authorization: Bearer SEU_TOKEN_JWT_AQUI"
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "João Silva",
    "email": "joao.silva@email.com",
    "role": "participant",
    "institution": "Universidade Federal"
  }
}
```

---

### 4. Logout
**Endpoint:** `POST /api/auth/logout`

```bash
curl -X POST http://localhost/api/auth/logout \
  -H "Authorization: Bearer SEU_TOKEN_JWT_AQUI"
```

**Resposta esperada:**
```json
{
  "success": true,
  "message": "Logout realizado com sucesso"
}
```

---

## 📄 ENDPOINTS DE RESUMOS (ABSTRACTS)

### 5. Listar Resumos
**Endpoint:** `GET /api/abstracts`

**Como participante:**
```bash
curl -X GET http://localhost/api/abstracts \
  -H "Authorization: Bearer SEU_TOKEN_JWT_AQUI"
```

**Como moderador (com filtro de status):**
```bash
curl -X GET "http://localhost/api/abstracts?status=pending" \
  -H "Authorization: Bearer SEU_TOKEN_JWT_AQUI"
```

**Como admin (com filtros):**
```bash
curl -X GET "http://localhost/api/abstracts?status=approved&area_id=1" \
  -H "Authorization: Bearer SEU_TOKEN_JWT_AQUI"
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Título do Resumo",
      "status": "pending",
      "created_at": "2024-01-15 10:30:00"
    }
  ]
}
```

---

### 6. Submeter Novo Resumo
**Endpoint:** `POST /api/abstracts`

**Requisito:** Pagamento aprovado

```bash
curl -X POST http://localhost/api/abstracts \
  -H "Authorization: Bearer SEU_TOKEN_JWT_AQUI" \
  -F "title=Título do Resumo" \
  -F "authors=João Silva; Maria Santos" \
  -F "institution=Universidade Federal" \
  -F "area_id=1" \
  -F "keywords=palavra1,palavra2,palavra3" \
  -F "content=Conteúdo do resumo..." \
  -F "file=@/caminho/para/arquivo.pdf"
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": {
    "id": 1
  }
}
```

---

### 7. Obter Detalhes de um Resumo
**Endpoint:** `GET /api/abstracts/{id}`

```bash
curl -X GET http://localhost/api/abstracts/1 \
  -H "Authorization: Bearer SEU_TOKEN_JWT_AQUI"
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Título do Resumo",
    "authors": "João Silva; Maria Santos",
    "institution": "Universidade Federal",
    "area_id": 1,
    "keywords": "palavra1,palavra2,palavra3",
    "content": "Conteúdo do resumo...",
    "status": "pending",
    "file_path": "/uploads/abstracts/1/resumo.pdf",
    "created_at": "2024-01-15 10:30:00"
  }
}
```

---

### 8. Substituir Arquivo do Resumo
**Endpoint:** `PUT /api/abstracts/{id}/replace`

**Requisito:** Pagamento aprovado

```bash
curl -X PUT http://localhost/api/abstracts/1/replace \
  -H "Authorization: Bearer SEU_TOKEN_JWT_AQUI" \
  -F "file=@/caminho/para/novo-arquivo.pdf"
```

**Resposta esperada:**
```json
{
  "success": true,
  "message": "Arquivo substituído com sucesso"
}
```

---

### 9. Atualizar Status do Resumo
**Endpoint:** `PUT /api/abstracts/{id}/status`

**Requisito:** Moderador

```bash
curl -X PUT http://localhost/api/abstracts/1/status \
  -H "Authorization: Bearer SEU_TOKEN_JWT_DE_MODERADOR" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "approved",
    "justification": "Resumo atende todos os critérios."
  }'
```

**Status possíveis:** `pending`, `approved`, `rejected`

**Resposta esperada:**
```json
{
  "success": true,
  "message": "Status atualizado com sucesso"
}
```

---

### 10. Excluir Resumo
**Endpoint:** `DELETE /api/abstracts/{id}`

```bash
curl -X DELETE http://localhost/api/abstracts/1 \
  -H "Authorization: Bearer SEU_TOKEN_JWT_AQUI"
```

**Resposta esperada:**
```json
{
  "success": true,
  "message": "Resumo excluído com sucesso"
}
```

---

## 👨‍💼 ENDPOINTS ADMINISTRATIVOS

### 11. Listar Todos os Usuários
**Endpoint:** `GET /api/admin/users`

**Requisito:** Admin

```bash
curl -X GET http://localhost/api/admin/users \
  -H "Authorization: Bearer SEU_TOKEN_JWT_DE_ADMIN"
```

**Com filtro por role:**
```bash
curl -X GET "http://localhost/api/admin/users?role=moderator" \
  -H "Authorization: Bearer SEU_TOKEN_JWT_DE_ADMIN"
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "João Silva",
      "email": "joao@email.com",
      "role": "participant",
      "payment_status": "pending"
    }
  ]
}
```

---

### 12. Listar Pagamentos Pendentes
**Endpoint:** `GET /api/admin/payments/pending`

**Requisito:** Admin

```bash
curl -X GET http://localhost/api/admin/payments/pending \
  -H "Authorization: Bearer SEU_TOKEN_JWT_DE_ADMIN"
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "user_name": "João Silva",
      "amount": 100.00,
      "status": "pending",
      "created_at": "2024-01-15 10:30:00"
    }
  ]
}
```

---

### 13. Processar Pagamento
**Endpoint:** `PUT /api/admin/payments/{id}`

**Requisito:** Admin

```bash
curl -X PUT http://localhost/api/admin/payments/1 \
  -H "Authorization: Bearer SEU_TOKEN_JWT_DE_ADMIN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "approved"
  }'
```

**Status possíveis:** `pending`, `approved`, `rejected`

**Resposta esperada:**
```json
{
  "success": true,
  "message": "Pagamento processado com sucesso"
}
```

---

### 14. Listar Todas as Áreas
**Endpoint:** `GET /api/admin/areas`

**Requisito:** Usuário autenticado

```bash
curl -X GET http://localhost/api/admin/areas \
  -H "Authorization: Bearer SEU_TOKEN_JWT_AQUI"
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Ciência da Computação",
      "description": "Área de computação e tecnologia"
    }
  ]
}
```

---

### 15. Criar Nova Área
**Endpoint:** `POST /api/admin/areas`

**Requisito:** Admin

```bash
curl -X POST http://localhost/api/admin/areas \
  -H "Authorization: Bearer SEU_TOKEN_JWT_DE_ADMIN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Engenharia de Software",
    "description": "Área focada em desenvolvimento e engenharia de software"
  }'
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": {
    "id": 2
  }
}
```

---

### 16. Obter Configurações do Sistema
**Endpoint:** `GET /api/admin/settings`

**Requisito:** Admin

```bash
curl -X GET http://localhost/api/admin/settings \
  -H "Authorization: Bearer SEU_TOKEN_JWT_DE_ADMIN"
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": {
    "submission_deadline": "2024-12-31 23:59:59",
    "max_file_size": 5242880,
    "allowed_file_types": ["pdf"]
  }
}
```

---

### 17. Atualizar Prazo de Submissão
**Endpoint:** `PUT /api/admin/settings/deadline`

**Requisito:** Admin

```bash
curl -X PUT http://localhost/api/admin/settings/deadline \
  -H "Authorization: Bearer SEU_TOKEN_JWT_DE_ADMIN" \
  -H "Content-Type: application/json" \
  -d '{
    "deadline": "2024-12-31 23:59:59"
  }'
```

**Resposta esperada:**
```json
{
  "success": true,
  "message": "Prazo atualizado com sucesso"
}
```

---

### 18. Obter Estatísticas do Dashboard
**Endpoint:** `GET /api/admin/stats`

**Requisito:** Admin

```bash
curl -X GET http://localhost/api/admin/stats \
  -H "Authorization: Bearer SEU_TOKEN_JWT_DE_ADMIN"
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": {
    "total_users": 150,
    "total_abstracts": 75,
    "pending_abstracts": 20,
    "approved_abstracts": 45,
    "rejected_abstracts": 10,
    "pending_payments": 30
  }
}
```

---

### 19. Listar Atribuições de Moderadores
**Endpoint:** `GET /api/admin/moderators/assignments`

**Requisito:** Admin

```bash
curl -X GET http://localhost/api/admin/moderators/assignments \
  -H "Authorization: Bearer SEU_TOKEN_JWT_DE_ADMIN"
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": [
    {
      "moderator_id": 2,
      "moderator_name": "Maria Santos",
      "area_id": 1,
      "area_name": "Ciência da Computação"
    }
  ]
}
```

---

### 20. Atribuir Moderador a uma Área
**Endpoint:** `POST /api/admin/moderators/assign`

**Requisito:** Admin

```bash
curl -X POST http://localhost/api/admin/moderators/assign \
  -H "Authorization: Bearer SEU_TOKEN_JWT_DE_ADMIN" \
  -H "Content-Type: application/json" \
  -d '{
    "area_id": 1,
    "user_id": 2
  }'
```

**Resposta esperada:**
```json
{
  "success": true,
  "message": "Moderador vinculado com sucesso"
}
```

---

## 📋 RESUMO DOS REQUISITOS DE AUTENTICAÇÃO

| Endpoint | Método | Requer Auth | Role Mínima |
|----------|--------|-------------|-------------|
| `/auth/register` | POST | ❌ | - |
| `/auth/login` | POST | ❌ | - |
| `/auth/me` | GET | ✅ | Qualquer |
| `/auth/logout` | POST | ✅ | Qualquer |
| `/abstracts` | GET | ✅ | Qualquer |
| `/abstracts` | POST | ✅ | Pagamento Aprovado |
| `/abstracts/{id}` | GET | ✅ | Qualquer |
| `/abstracts/{id}/replace` | PUT | ✅ | Pagamento Aprovado |
| `/abstracts/{id}/status` | PUT | ✅ | Moderador |
| `/abstracts/{id}` | DELETE | ✅ | Qualquer (proprietário) |
| `/admin/users` | GET | ✅ | Admin |
| `/admin/payments/pending` | GET | ✅ | Admin |
| `/admin/payments/{id}` | PUT | ✅ | Admin |
| `/admin/areas` | GET | ✅ | Qualquer |
| `/admin/areas` | POST | ✅ | Admin |
| `/admin/settings` | GET | ✅ | Admin |
| `/admin/settings/deadline` | PUT | ✅ | Admin |
| `/admin/stats` | GET | ✅ | Admin |
| `/admin/moderators/assignments` | GET | ✅ | Admin |
| `/admin/moderators/assign` | POST | ✅ | Admin |

---

## 🔑 COMO OBTER E USAR O TOKEN JWT

1. **Faça login** para obter o token:
```bash
TOKEN=$(curl -s -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@email.com","password":"admin123"}' \
  | jq -r '.data.token')
```

2. **Use o token nas requisições:**
```bash
curl -X GET http://localhost/api/auth/me \
  -H "Authorization: Bearer $TOKEN"
```

---

## 📝 NOTAS IMPORTANTES

- Substitua `SEU_TOKEN_JWT_AQUI` pelo token real obtido no login
- Para upload de arquivos, use a flag `-F` do curl (form-data)
- Endpoints que exigem "Pagamento Aprovado" só funcionam se o usuário tiver pagamento aprovado
- Moderadores podem apenas atualizar status de resumos das áreas que moderam
- Admins têm acesso total a todos os endpoints
