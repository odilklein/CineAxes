# Deploy no InfinityFree

Pacote pronto para subir o PersonaCut no InfinityFree (PHP + MariaDB, sem Docker).
O que está aqui:

```
deploy/infinityfree/
├── personacut.sql              ← banco completo (schema + seed + enriquecimento + rótulos)
├── htdocs/.htaccess            ← roteia a raiz para public/ e bloqueia arquivos internos
├── config/env.example.php      ← modelo do config/env.php
└── README.md
```

## 1. Banco de dados

1. No painel do InfinityFree, abra **MySQL Databases** e crie um banco.
2. Anote:
   - **host**: `sqlXXX.infinityfree.com` (não é `localhost`);
   - **nome do banco**: `if0_XXXXXXXX_personacut`;
   - **usuário**: `if0_XXXXXXXX`;
   - **senha**.
3. Abra o **phpMyAdmin** desse banco e importe `personacut.sql`.
   - O arquivo já está com `utf8mb4_unicode_ci`, compatível com a MariaDB do
     InfinityFree. Se você importar um dump antigo do Docker, troque
     `utf8mb4_0900_ai_ci` por `utf8mb4_unicode_ci` antes.
   - Se o phpMyAdmin reclamar de tamanho, importe os arquivos de `sql/` na ordem
     `001` → `005` (o pacote é a concatenação deles).

## 2. Upload dos arquivos

Envie o projeto para dentro de `htdocs/` **mantendo a pasta `public/`**. A raiz
do site no InfinityFree é `htdocs`, então o `.htaccess` da raiz encaminha tudo
para `public/`.

```
htdocs/
├── .htaccess
├── public/
├── src/
├── config/
│   └── env.php          ← criado no passo 3
├── lang/
├── storage/
└── ...
```

Não é preciso enviar `.git/`, `Dockerfile`, `docker-compose.yml`, `.env`,
`.playwright-mcp/` nem `deploy/`. O `storage/cache/` pode ser enviado se você
quiser pré-popular o cache de imagens da TMDB (opcional).

## 3. Configuração (o ponto do .env)

O `.env` **não é lido pelo PHP**: ele só existe para o Docker Compose. No
InfinityFree, a configuração fica em **`config/env.php`**.

1. Copie `deploy/infinityfree/config/env.example.php` para
   `htdocs/config/env.php`.
2. Preencha com os dados do banco e as chaves:

```php
<?php
return [
    'DB_HOST' => 'sqlXXX.infinityfree.com',
    'DB_PORT' => '3306',
    'DB_NAME' => 'if0_XXXXXXXX_personacut',
    'DB_USER' => 'if0_XXXXXXXX',
    'DB_PASS' => 'senha_do_banco',

    'APP_DEBUG' => 'false',

    'TMDB_API_KEY' => 'sua_chave_tmdb',
    'GROQ_API_KEY' => 'sua_chave_groq',
    'GROQ_MODEL' => '',
    'GROQ_ENABLED' => 'true',
];
```

- `config/env.php` já está no `.gitignore`; não versione.
- O `.htaccess` bloqueia `/config/`, então o arquivo não é servido pela web.
- Sem `GROQ_API_KEY`, o site funciona; o retrato por IA cai no fallback.

## 4. `.htaccess`

Copie `deploy/infinityfree/htdocs/.htaccess` para `htdocs/.htaccess`.
Ele:

- encaminha `/`, `/quiz.php`, `/assets/...` etc. para `public/`;
- bloqueia `src/`, `config/`, `lang/`, `sql/`, `storage/`, `uploads/` e `docs/`;
- bloqueia `.env`, `.git`, `Dockerfile` e `docker-compose.yml`;
- redireciona `/public/...` para a URL limpa.

O site precisa ficar na raiz do domínio/subdomínio, porque o app usa URLs
absolutas (`/quiz.php`, `/assets/...`). Se instalar em subpasta, troque
`RewriteBase /` e prepare-se para ajustar os caminhos absolutos.

## 5. PHP e permissões

- Selecione **PHP 8.2 ou superior** no painel.
- Habilite as extensões **`pdo_mysql`**, **`mbstring`** e **`curl`**.
- Deixe `htdocs/storage/` gravável (o proxy de imagens cria
  `storage/cache/img` sozinho). Se puder, crie a pasta antes e dê permissão
  `755`/`775`.
- `APP_DEBUG` precisa ser `false` em produção.

## 6. Segurança

- O seed cria o admin padrão **`admin` / `admin123`**. Troque a senha antes de
  divulgar o site (pelo banco, com um hash bcrypt novo, ou por um painel de
  usuários, se você adicionar um).
- O `config/env.php` contém segredos: não suba para o GitHub e não deixe uma
  cópia em `public/`.
- O `.env` local não deve ser enviado.

## 7. Serviços externos

O InfinityFree libera cURL externo nas portas 80/443, então TMDB e Groq devem
funcionar. Alguns serviços são bloqueados por política da hospedagem; se o Groq
falhar, o retrato por IA cai no fallback. Se as imagens da TMDB falharem:

- o proxy `public/api/imagem.php` tenta baixar e cachear em `storage/cache/img`;
- você pode subir o conteúdo de `storage/cache/img` do seu ambiente local para
  pré-popular o cache.

## 8. Teste rápido

1. `https://seu-site/` → landing.
2. `https://seu-site/quiz.php` → quiz.
3. Termine um quiz → retrospectiva.
4. Abra a análise completa (`?modo=completo`), abra “Ver os 8 eixos e o radar”.
5. Exporte o card 9:16.
6. `https://seu-site/admin/` → login.
