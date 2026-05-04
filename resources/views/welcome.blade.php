<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'WAHA Dash') }}</title>
        <style>
            :root {
                color-scheme: light;
                --bg: #f6f7f4;
                --panel: #ffffff;
                --ink: #1e2523;
                --muted: #66736f;
                --line: #dfe5df;
                --accent: #1f9d68;
                --accent-dark: #14764d;
                --warn: #b7791f;
                --danger: #b42318;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                background: var(--bg);
                color: var(--ink);
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            }

            main {
                width: min(980px, calc(100% - 32px));
                margin: 0 auto;
                padding: 40px 0;
            }

            header {
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                gap: 24px;
                margin-bottom: 24px;
            }

            h1 {
                margin: 0;
                font-size: 32px;
                line-height: 1.1;
                letter-spacing: 0;
            }

            p {
                margin: 8px 0 0;
                color: var(--muted);
            }

            a {
                color: var(--accent-dark);
                font-weight: 700;
                text-decoration: none;
            }

            .grid {
                display: grid;
                grid-template-columns: 1fr 1.4fr;
                gap: 16px;
            }

            .panel {
                background: var(--panel);
                border: 1px solid var(--line);
                border-radius: 8px;
                padding: 20px;
            }

            .panel h2 {
                margin: 0 0 16px;
                font-size: 18px;
                letter-spacing: 0;
            }

            .status {
                display: inline-flex;
                align-items: center;
                min-height: 36px;
                padding: 0 12px;
                border-radius: 999px;
                background: #eef7f1;
                color: var(--accent-dark);
                font-weight: 700;
            }

            .status.error {
                background: #fff1f0;
                color: var(--danger);
            }

            label {
                display: block;
                margin-bottom: 6px;
                color: #33413d;
                font-size: 14px;
                font-weight: 700;
            }

            input,
            textarea {
                width: 100%;
                border: 1px solid var(--line);
                border-radius: 8px;
                color: var(--ink);
                font: inherit;
                padding: 12px;
                outline: none;
            }

            textarea {
                min-height: 132px;
                resize: vertical;
            }

            input:focus,
            textarea:focus {
                border-color: var(--accent);
                box-shadow: 0 0 0 3px rgba(31, 157, 104, 0.16);
            }

            .field {
                margin-bottom: 14px;
            }

            .actions {
                display: flex;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap;
            }

            button {
                min-height: 42px;
                border: 0;
                border-radius: 8px;
                background: var(--accent);
                color: #fff;
                cursor: pointer;
                font: inherit;
                font-weight: 800;
                padding: 0 16px;
            }

            button:hover {
                background: var(--accent-dark);
            }

            button.secondary {
                background: #25302c;
            }

            pre {
                min-height: 120px;
                overflow: auto;
                margin: 16px 0 0;
                padding: 14px;
                border-radius: 8px;
                background: #17201d;
                color: #e6efe9;
                font-size: 13px;
                line-height: 1.45;
                white-space: pre-wrap;
            }

            @media (max-width: 760px) {
                main {
                    padding: 24px 0;
                }

                header,
                .grid {
                    display: block;
                }

                header .actions,
                .panel + .panel {
                    margin-top: 16px;
                }
            }
        </style>
    </head>
    <body>
        <main>
            <header>
                <div>
                    <h1>WAHA Dash</h1>
                    <p>Laravel, PostgreSQL e WAHA prontos para desenvolvimento local.</p>
                </div>
                <div class="actions">
                    <a href="http://localhost:3000/dashboard" target="_blank" rel="noreferrer">WAHA Dashboard</a>
                </div>
            </header>

            <section class="grid">
                <article class="panel">
                    <h2>Sessao default</h2>
                    <div id="status" class="status">Carregando</div>
                    <pre id="statusPayload">{}</pre>
                    <div class="actions">
                        <button class="secondary" type="button" id="refreshStatus">Atualizar</button>
                    </div>
                </article>

                <article class="panel">
                    <h2>Enviar texto</h2>
                    <form id="sendForm">
                        <div class="field">
                            <label for="chatId">Chat ID</label>
                            <input id="chatId" name="chatId" placeholder="55DDDNUMERO@c.us" required>
                        </div>
                        <div class="field">
                            <label for="text">Mensagem</label>
                            <textarea id="text" name="text" required>Mensagem enviada pelo Laravel + WAHA</textarea>
                        </div>
                        <button type="submit">Enviar</button>
                    </form>
                    <pre id="sendPayload">{}</pre>
                </article>
            </section>
        </main>

        <script>
            const statusBadge = document.querySelector('#status');
            const statusPayload = document.querySelector('#statusPayload');
            const sendPayload = document.querySelector('#sendPayload');

            async function readJson(response) {
                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw payload;
                }

                return payload;
            }

            function paintPayload(element, payload) {
                element.textContent = JSON.stringify(payload, null, 2);
            }

            async function loadStatus() {
                statusBadge.textContent = 'Carregando';
                statusBadge.classList.remove('error');

                try {
                    const payload = await fetch('/api/waha/status').then(readJson);
                    statusBadge.textContent = payload.status || 'OK';
                    paintPayload(statusPayload, payload);
                } catch (error) {
                    statusBadge.textContent = 'Indisponivel';
                    statusBadge.classList.add('error');
                    paintPayload(statusPayload, error);
                }
            }

            document.querySelector('#refreshStatus').addEventListener('click', loadStatus);
            document.querySelector('#sendForm').addEventListener('submit', async (event) => {
                event.preventDefault();

                const form = new FormData(event.currentTarget);

                try {
                    const payload = await fetch('/api/waha/send-text', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            chatId: form.get('chatId'),
                            text: form.get('text'),
                        }),
                    }).then(readJson);

                    paintPayload(sendPayload, payload);
                } catch (error) {
                    paintPayload(sendPayload, error);
                }
            });

            loadStatus();
        </script>
    </body>
</html>
