# Frontend - Sistema de Submissão de Resumos

Frontend React para o sistema de submissão de resumos acadêmicos.

## 🚀 Funcionalidades

- **Cadastro de Usuários**: Registro com nome, email, senha, instituição e área de interesse
- **Login**: Autenticação com JWT
- **Dashboard**: 
  - Visualização de estatísticas (para admin/moderador)
  - Lista de resumos submetidos
  - Atalhos administrativos
  - Status de submissões

## 📦 Instalação

```bash
# Instalar dependências
npm install

# Criar arquivo .env (opcional)
cp .env.example .env

# Iniciar servidor de desenvolvimento
npm run dev
```

## 🔧 Configuração

O arquivo `.env` pode ser configurado com a URL da API:

```env
VITE_API_URL=http://localhost:3000/api
```

## 📁 Estrutura de Pastas

```
src/
├── components/
│   └── ProtectedRoute.jsx    # Componente de rota protegida
├── context/
│   └── AuthContext.jsx       # Contexto de autenticação
├── pages/
│   ├── Login.jsx             # Página de login
│   ├── Login.css
│   ├── Register.jsx          # Página de cadastro
│   ├── Register.css
│   ├── Dashboard.jsx         # Dashboard principal
│   └── Dashboard.css
├── services/
│   └── api.js                # Configuração do Axios
├── App.jsx                   # Componente principal com rotas
└── main.jsx                  # Ponto de entrada
```

## 🔐 Autenticação

O sistema utiliza JWT para autenticação:

1. O token é armazenado no `localStorage`
2. Todas as requisições incluem o token no header `Authorization: Bearer <token>`
3. Rotas protegidas redirecionam para `/login` se não autenticado
4. Token expirado (401) faz logout automático

## 🎨 Estilização

- CSS moderno com gradientes
- Design responsivo
- Componentes reutilizáveis
- Badges coloridos para status

## 🛣️ Rotas

| Rota | Descrição | Protegida | Roles |
|------|-----------|-----------|-------|
| `/login` | Login | Não | - |
| `/register` | Cadastro | Não | - |
| `/dashboard` | Dashboard | Sim | Todos |
| `/` | Redireciona para dashboard | - | - |

## 📝 Endpoints da API

O frontend consome os seguintes endpoints:

- `POST /auth/login` - Login
- `POST /auth/register` - Cadastro
- `GET /abstracts` - Listar resumos
- `GET /admin/stats` - Estatísticas (admin/moderador)

## 🚀 Build

```bash
# Build de produção
npm run build

# Preview do build
npm run preview
```

## 📄 Licença

MIT
