# Doe Me API

API para o aplicativo Doe Me - uma plataforma de doações que conecta pessoas dispostas a doar bens usados com pessoas que precisam deles.

## 📋 Sobre o Projeto

O Doe Me é um aplicativo mobile que funciona como um marketplace de doações, promovendo a economia circular, solidariedade e sustentabilidade. A API fornece todas as funcionalidades necessárias para:

- Cadastro e autenticação de usuários (tradicional e social)
- Gerenciamento de itens de doação
- Sistema de chat entre doadores e interessados
- Sistema de avaliações entre usuários
- Categorização de itens

## 🚀 Tecnologias Utilizadas

- **Laravel 10** - Framework PHP
- **PostgreSQL** - Banco de dados principal (produção)
- **SQLite** - Banco de dados para desenvolvimento
- **Redis** - Cache e sessões
- **Laravel Sanctum** - Autenticação por tokens
- **Laravel Socialite** - Autenticação social (Google, Facebook)
- **Swagger/OpenAPI** - Documentação da API
- **Docker** - Containerização

## 📦 Instalação e Configuração

### Pré-requisitos

- PHP 8.1+
- Composer
- Docker e Docker Compose (para ambiente containerizado)

### Instalação Local

1. Clone o repositório:
```bash
git clone <repository-url>
cd doe-me-api
```

2. Instale as dependências:
```bash
composer install
```

3. Configure o ambiente:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure o banco de dados no arquivo `.env`:
```env
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite
```

5. Execute as migrações e seeders:
```bash
touch database/database.sqlite
php artisan migrate
php artisan db:seed
```

6. Gere a documentação do Swagger:
```bash
php artisan l5-swagger:generate
```

7. Inicie o servidor:
```bash
php artisan serve
```

### Instalação com Docker

1. Configure o ambiente Docker:
```bash
cp .env.docker .env
```

2. Inicie os containers:
```bash
docker-compose up -d
```

3. Execute as migrações:
```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

## 🔧 Configuração

### Variáveis de Ambiente

#### Banco de Dados
```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=doe_me
DB_USERNAME=doe_me_user
DB_PASSWORD=doe_me_password
```

#### Redis
```env
REDIS_HOST=redis
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

#### Autenticação Social
```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URL=http://localhost:8000/auth/google/callback

FACEBOOK_CLIENT_ID=your_facebook_client_id
FACEBOOK_CLIENT_SECRET=your_facebook_client_secret
FACEBOOK_REDIRECT_URL=http://localhost:8000/auth/facebook/callback
```

## 📚 Documentação da API

A documentação completa da API está disponível via Swagger UI:

- **Desenvolvimento**: http://localhost:8000/api/documentation
- **Produção**: https://api.doeme.com/api/documentation

### Principais Endpoints

#### Autenticação
- `POST /api/auth/register` - Registro de usuário
- `POST /api/auth/login` - Login
- `GET /api/auth/google/redirect` - Redirecionamento para Google OAuth
- `GET /api/auth/google/callback` - Callback do Google OAuth
- `GET /api/auth/facebook/redirect` - Redirecionamento para Facebook OAuth
- `GET /api/auth/facebook/callback` - Callback do Facebook OAuth
- `POST /api/auth/logout` - Logout
- `GET /api/auth/user` - Dados do usuário autenticado

#### Categorias
- `GET /api/categories` - Listar categorias
- `GET /api/categories/{id}` - Obter categoria específica
- `POST /api/categories` - Criar categoria (autenticado)
- `PUT /api/categories/{id}` - Atualizar categoria (autenticado)
- `DELETE /api/categories/{id}` - Excluir categoria (autenticado)

#### Itens de Doação
- `GET /api/donation-items` - Listar itens disponíveis
- `GET /api/donation-items/{id}` - Obter item específico
- `POST /api/donation-items` - Criar item (autenticado)
- `PUT /api/donation-items/{id}` - Atualizar item (autenticado)
- `DELETE /api/donation-items/{id}` - Excluir item (autenticado)
- `GET /api/my-donations` - Meus itens (autenticado)

#### Chat
- `GET /api/chats` - Listar meus chats (autenticado)
- `POST /api/chats` - Iniciar novo chat (autenticado)
- `GET /api/chats/{id}` - Obter mensagens do chat (autenticado)
- `POST /api/chats/{id}/messages` - Enviar mensagem (autenticado)

#### Avaliações
- `GET /api/reviews` - Listar avaliações
- `POST /api/reviews` - Criar avaliação (autenticado)
- `GET /api/reviews/{id}` - Obter avaliação específica
- `PUT /api/reviews/{id}` - Atualizar avaliação (autenticado)
- `DELETE /api/reviews/{id}` - Excluir avaliação (autenticado)
- `GET /api/users/{id}/reviews` - Avaliações de um usuário

## 🔐 Autenticação

A API utiliza Laravel Sanctum para autenticação por tokens. Após o login, inclua o token no header das requisições:

```
Authorization: Bearer {token}
```

### Fluxo de Autenticação Social

1. Redirecione o usuário para `/api/auth/{provider}/redirect`
2. O usuário será redirecionado para o provedor (Google/Facebook)
3. Após autorização, o usuário retorna para `/api/auth/{provider}/callback`
4. A API retorna os dados do usuário e o token de acesso

## 🗄️ Estrutura do Banco de Dados

### Principais Tabelas

- **users** - Usuários do sistema
- **categories** - Categorias de itens
- **donation_items** - Itens para doação
- **chats** - Conversas entre usuários
- **chat_messages** - Mensagens dos chats
- **reviews** - Avaliações entre usuários

### Relacionamentos

- Um usuário pode ter muitos itens de doação
- Um item pertence a uma categoria
- Um chat conecta dois usuários sobre um item específico
- Avaliações são feitas entre usuários após uma doação

## 🧪 Testes

Execute os testes com:

```bash
php artisan test
```

Para testes específicos:
```bash
php artisan test --filter=CategoryTest
```

## 🚀 Deploy

### Docker

O projeto inclui configuração completa para Docker:

```bash
docker-compose up -d
```

### Produção

1. Configure as variáveis de ambiente para produção
2. Execute as migrações:
```bash
php artisan migrate --force
```

3. Otimize a aplicação:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📱 Integração com Frontend

### Headers Necessários

```javascript
const headers = {
  'Content-Type': 'application/json',
  'Accept': 'application/json',
  'Authorization': `Bearer ${token}`
};
```

### Exemplo de Uso

```javascript
// Login
const login = async (email, password) => {
  const response = await fetch('/api/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({ email, password })
  });
  
  const data = await response.json();
  return data;
};

// Listar itens
const getItems = async (token) => {
  const response = await fetch('/api/donation-items', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
};
```

## 🔧 Configurações Adicionais

### CORS

O CORS está configurado para aceitar requisições de qualquer origem durante o desenvolvimento. Para produção, configure adequadamente no arquivo `config/cors.php`.

### Rate Limiting

A API inclui rate limiting padrão do Laravel. Ajuste conforme necessário no arquivo `app/Http/Kernel.php`.

### Logs

Os logs são armazenados em `storage/logs/laravel.log`. Configure o nível de log no arquivo `.env`:

```env
LOG_LEVEL=debug
```

## 🤝 Contribuição

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

## 📞 Suporte

Para suporte, envie um email para contato@doeme.com ou abra uma issue no repositório.

---

Desenvolvido com ❤️ pela equipe Doe Me



## 🔄 CI/CD

O projeto inclui workflows automatizados do GitHub Actions:

### Testes Automatizados

- **Trigger**: Pull Requests para `develop` e pushes para `develop`
- **Matriz de Testes**: PHP 8.1, 8.2, 8.3
- **Serviços**: PostgreSQL 16, Redis 7
- **Cobertura**: Relatórios enviados para Codecov

### Verificações de Qualidade

- **PHP CS Fixer**: Verificação de padrões de código
- **PHPStan**: Análise estática de código (nível 5)
- **Security Audit**: Verificação de vulnerabilidades

### Deploy Automático

- **Trigger**: Push para `main`
- **Ambiente**: Produção
- **Notificações**: Slack para status de deploy

### Configuração de Secrets

Para usar os workflows, configure os seguintes secrets no GitHub:

```
HOST=seu-servidor.com
USERNAME=usuario-ssh
SSH_KEY=sua-chave-ssh-privada
PORT=22
SLACK_WEBHOOK=https://hooks.slack.com/...
```

### Badges de Status

[![Tests](https://github.com/seu-usuario/doe-me-api/workflows/Tests/badge.svg)](https://github.com/seu-usuario/doe-me-api/actions)
[![Deploy](https://github.com/seu-usuario/doe-me-api/workflows/Deploy/badge.svg)](https://github.com/seu-usuario/doe-me-api/actions)
[![codecov](https://codecov.io/gh/seu-usuario/doe-me-api/branch/main/graph/badge.svg)](https://codecov.io/gh/seu-usuario/doe-me-api)

