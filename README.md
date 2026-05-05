# WAHA Dash

Projeto Laravel novo configurado para PostgreSQL e WAHA, a API HTTP para WhatsApp.

## Stack

- Laravel 13
- PHP 8.3
- PostgreSQL 16
- WAHA via imagem `devlikeapro/waha:latest`

## Subindo o ambiente

Este projeto está preparado para Docker Compose:

```bash
cp .env.example .env
```

```bash
docker compose up --build
```

Se sua máquina usa o binário legado:

```bash
docker-compose up --build
```

Serviços:

- Laravel: http://localhost:8000
- WAHA dashboard: http://localhost:3000/dashboard
- PostgreSQL: localhost:5432

O container da aplicação instala as dependências Composer, gera a chave do Laravel se necessário e roda as migrations automaticamente.

Por padrão, o ambiente local usa `WAHA_API_KEY=local-dev-waha-key` para autenticar a aplicação com a WAHA. Se quiser trocar a chave, atualize a mesma variável no `.env` antes de subir os containers pela primeira vez.

## Credenciais locais

As credenciais ficam no arquivo `.env`.

- Banco: `waha_dash`
- Usuário: `waha_dash`
- Senha: `secret`
- WAHA dashboard: `admin`
- Senha do dashboard: veja `WAHA_DASHBOARD_PASSWORD` no `.env`
- API key da WAHA: veja `WAHA_API_KEY` no `.env`

## Configuração WAHA

Variáveis relevantes:

```dotenv
WAHA_BASE_URL=http://waha:3000
WAHA_API_KEY=local-dev-waha-key
WAHA_SESSION=default
WAHA_TIMEOUT=15
WAHA_CONTACTS_PAGE_SIZE=100
WAHA_CONTACTS_MAX_PAGES=20
WAHA_CONTACTS_PREVIEW_CACHE_SECONDS=300
WAHA_DASHBOARD_ENABLED=true
WAHA_DASHBOARD_USERNAME=admin
WAHA_DASHBOARD_PASSWORD=admin123
```

O Laravel envia a chave no header `X-Api-Key`, conforme a configuração oficial da WAHA.
Para a listagem de contatos, o app usa paginação pequena por padrão para reduzir 500s transitórios da WAHA; se seu número tiver muitos contatos, você pode ajustar `WAHA_CONTACTS_PAGE_SIZE` no `.env`.

# Docker Commands

Compose install:
```bash
docker exec -it waha-dash-app /bin/sh -c "cd /var/www/html && composer install"
```

Key generate: 
```bash
docker exec -it waha-dash-app /bin/sh -c "cd /var/www/html && php artisan key:generate"
```

Migrations: 

```bash
docker exec -it waha-dash-app /bin/sh -c "cd /var/www/html && php artisan migrate"
```
