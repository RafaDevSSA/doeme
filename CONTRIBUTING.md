# Guia de Contribuição - Doe Me API

Obrigado por considerar contribuir para o projeto Doe Me API! 🎉

## 📋 Código de Conduta

Este projeto e todos os participantes estão sujeitos ao [Código de Conduta](CODE_OF_CONDUCT.md). Ao participar, você concorda em manter este código.

## 🚀 Como Contribuir

### Reportando Bugs

1. Verifique se o bug já não foi reportado nas [Issues](https://github.com/seu-usuario/doe-me-api/issues)
2. Use o template de bug report
3. Inclua o máximo de detalhes possível
4. Adicione screenshots se aplicável

### Sugerindo Funcionalidades

1. Verifique se a funcionalidade já não foi sugerida
2. Use o template de feature request
3. Explique claramente o problema que a funcionalidade resolve
4. Descreva a solução proposta

### Contribuindo com Código

#### Configuração do Ambiente

1. Faça um fork do repositório
2. Clone seu fork:
   ```bash
   git clone https://github.com/seu-usuario/doe-me-api.git
   cd doe-me-api
   ```

3. Instale as dependências:
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure o banco de dados e execute as migrações:
   ```bash
   touch database/database.sqlite
   php artisan migrate
   php artisan db:seed
   ```

#### Fluxo de Desenvolvimento

1. Crie uma branch para sua funcionalidade:
   ```bash
   git checkout -b feature/nome-da-funcionalidade
   ```

2. Faça suas mudanças seguindo os padrões do projeto

3. Execute os testes:
   ```bash
   php artisan test
   ```

4. Execute as verificações de qualidade:
   ```bash
   ./vendor/bin/php-cs-fixer fix
   ./vendor/bin/phpstan analyse
   ```

5. Commit suas mudanças:
   ```bash
   git commit -m "feat: adiciona nova funcionalidade"
   ```

6. Push para sua branch:
   ```bash
   git push origin feature/nome-da-funcionalidade
   ```

7. Abra um Pull Request para a branch `develop`

## 📝 Padrões de Código

### Convenções de Commit

Usamos [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` nova funcionalidade
- `fix:` correção de bug
- `docs:` mudanças na documentação
- `style:` formatação, ponto e vírgula ausente, etc
- `refactor:` refatoração de código
- `test:` adição ou correção de testes
- `chore:` mudanças no processo de build ou ferramentas auxiliares

### Padrões PHP

- Seguimos o [PSR-12](https://www.php-fig.org/psr/psr-12/)
- Use PHP CS Fixer para formatação automática
- Mantenha o nível 5 do PHPStan sem erros

### Testes

- Escreva testes para novas funcionalidades
- Mantenha a cobertura de testes acima de 80%
- Use factories para dados de teste
- Nomeie os testes de forma descritiva

### Documentação

- Documente APIs com anotações Swagger
- Atualize o README.md se necessário
- Comente código complexo
- Use PHPDoc para métodos públicos

## 🧪 Executando Testes

### Testes Locais

```bash
# Todos os testes
php artisan test

# Testes específicos
php artisan test --filter=CategoryTest

# Com cobertura
php artisan test --coverage
```

### Testes com Docker

```bash
docker-compose exec app php artisan test
```

## 🔍 Verificações de Qualidade

### PHP CS Fixer

```bash
# Verificar problemas
./vendor/bin/php-cs-fixer fix --dry-run --diff

# Corrigir automaticamente
./vendor/bin/php-cs-fixer fix
```

### PHPStan

```bash
./vendor/bin/phpstan analyse
```

### Auditoria de Segurança

```bash
composer audit
```

## 📦 Estrutura do Projeto

```
doe-me-api/
├── app/
│   ├── Http/Controllers/    # Controllers da API
│   ├── Models/             # Modelos Eloquent
│   └── ...
├── database/
│   ├── migrations/         # Migrações do banco
│   ├── seeders/           # Seeders
│   └── factories/         # Factories para testes
├── tests/
│   ├── Feature/           # Testes de funcionalidade
│   └── Unit/              # Testes unitários
├── .github/
│   └── workflows/         # GitHub Actions
└── ...
```

## 🎯 Prioridades de Desenvolvimento

1. **Segurança**: Sempre priorize a segurança
2. **Performance**: Otimize consultas e cache
3. **Testes**: Mantenha alta cobertura de testes
4. **Documentação**: Mantenha a documentação atualizada
5. **Compatibilidade**: Mantenha compatibilidade com versões anteriores

## ❓ Dúvidas

Se você tiver dúvidas sobre como contribuir:

1. Verifique a documentação existente
2. Procure em issues fechadas
3. Abra uma nova issue com a tag `question`
4. Entre em contato com os mantenedores

## 🏆 Reconhecimento

Todos os contribuidores serão reconhecidos no README.md do projeto.

Obrigado por contribuir! 🚀

