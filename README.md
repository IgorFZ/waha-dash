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

## Credenciais locais

As credenciais ficam no arquivo `.env`.

- Banco: `waha_dash`
- Usuário: `waha_dash`
- Senha: `secret`
- WAHA dashboard: `admin`
- Senha do dashboard: veja `WAHA_DASHBOARD_PASSWORD` no `.env`
- API key da WAHA: veja `WAHA_API_KEY` no `.env`

## Endpoints Laravel para WAHA

Ver status da sessão padrão:

```bash
curl http://localhost:8000/api/waha/status
```

Enviar mensagem de texto:

```bash
curl -X POST http://localhost:8000/api/waha/send-text \
  -H "Content-Type: application/json" \
  -d '{
    "chatId": "55DDDNUMERO@c.us",
    "text": "Mensagem enviada pelo Laravel + WAHA"
  }'
```

Antes de enviar mensagens, crie/inicie a sessão `default` no dashboard da WAHA e escaneie o QR Code pelo WhatsApp.

## Configuração WAHA

Variáveis relevantes:

```dotenv
WAHA_BASE_URL=http://waha:3000
WAHA_API_KEY=
WAHA_SESSION=default
WAHA_TIMEOUT=15
```

O Laravel envia a chave no header `X-Api-Key`, conforme a configuração oficial da WAHA.

# Docker Commands

Run: 

```bash
docker exec -it waha-dash-app /bin/sh -c "cd /var/www/html && php artisan migrate"
```
