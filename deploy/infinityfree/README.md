# Deploy no InfinityFree

Pacote pronto para subir o PersonaCut no InfinityFree (PHP + MariaDB, sem Docker).
O que está aqui:

```
deploy/infinityfree/
├── personacut.sql              ← schema + seed + enriquecimento + rótulos + tabelas do catálogo
├── catalogo.sql                ← snapshot do catálogo (obras, gêneros, países, elenco e streaming)
├── htdocs/.htaccess            ← roteia a raiz para public/ e bloqueia arquivos internos
├── config/env.example.php      ← modelo do config/env.php
└── README.md
```

> O `Core\Env` lê o `config/env.php` para um mapa estático. Ele não depende de
> `putenv()`, que é desabilitado em várias hospedagens compartilhadas (incluindo
> o InfinityFree).

## 1. Banco de dados

1. No painel do InfinityFree, abra **MySQL Databases** e crie um banco.
2. Anote:
   - **host**: `sqlXXX.infinityfree.com` (não é `localhost`);
   - **nome do banco**: `if0_XXXXXXXX_personacut`;
   - **usuário**: `if0_XXXXXXXX`;
   - **senha**.
3. Abra o **phpMyAdmin** desse banco e importe **nesta ordem**:
   1. `personacut.sql` — schema, seed, enriquecimento, rótulos e tabelas do catálogo;
   2. `catalogo.sql` — obras, gêneros, países, elenco e disponibilidade.
   - O arquivo já está com `utf8mb4_unicode_ci`, compatível com a MariaDB do
     InfinityFree. Se você importar um dump antigo do Docker, troque
     `utf8mb4_0900_ai_ci` por `utf8mb4_unicode_ci` antes.
   - Se o phpMyAdmin reclamar de tamanho, importe os arquivos de `sql/` na ordem
     `001` → `005`, depois `009` e `010` (o pacote é a concatenação deles).
   - O limite de arquivo do InfinityFree é **10 MB**. `bin/deploy_pacote.php`
     avisa quando o snapshot passar do limite; nesse caso divida `catalogo.sql`
     em partes (uma por tabela ou por faixa de ids) e importe em sequência.

### Atualizando um banco que já está no ar

**Modelo v2 (6 eixos).** O schema mudou de 8 para 6 eixos. Em um banco antigo,
reimporte `personacut.sql` (ele derruba e recria apenas as tabelas de perfil,
eixos, perguntas, arquétipos e entidades; o catálogo em `catalogo.sql` permanece).
Isso apaga perfis/retratos de teste — no ambiente acadêmico é o esperado. Depois,
se quiser o selo de raridade, rode `bin/base_sintetica.php` no ambiente local e
exporte a base, ou deixe a raridade desativada.

Não importe `personacut.sql` em um banco existente **sem querer apagar os
perfis**. Para habilitar o catálogo:

1. importe `sql/009_catalogo.sql` (cria as tabelas e as plataformas; pode rodar
   mais de uma vez);
2. importe `sql/010_catalogo_seed.sql` (obras, gêneros, países, elenco e
   disponibilidade);
3. importe `sql/migrations/011_calibracao.sql` (versão do questionário,
   procedência do vetor das entidades e remoção da base sintética antiga).
   O app funciona mesmo sem esta migração: a leitura e a escrita detectam a
   coluna ausente. Mas sem ela não é possível distinguir vetores calculados com
   questionários diferentes.

O site continua funcionando entre os dois passos: as seções de streaming,
trilhas e elenco simplesmente não aparecem enquanto as tabelas não existirem.
Depois do passo 2, elas passam a ser renderizadas sem precisar de novo deploy.

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

## 7. Catálogo e streaming

O ranking de plataformas e os cards de trilha leem **apenas o MySQL**. A
disponibilidade não é consultada em tempo de request: ela vem de
`bin/catalogo_sync.php` (na sua máquina) e do snapshot `catalogo.sql`.

- A cada **90 dias**, rode de novo o sync para atualizar disponibilidade e
  metadados (os termos da TMDB limitam o cache a 6 meses; o dado de
  disponibilidade é do JustWatch e exige atribuição).
- Preços das plataformas são editoriais: ajuste em **Painel → Plataformas**.
- Para ampliar o catálogo, edite `config/recomendacao.php` (trilhas e limites)
  e rode `php bin/catalogo_sync.php`; depois `php bin/catalogo_sync.php --creditos`
  (elenco), `php bin/catalogo_status.php` (cobertura), `php bin/catalogo_export.php`
  e `php bin/deploy_pacote.php`.
- A seção "Onde assistir" credita TMDB e JustWatch. Mantenha esse crédito.

## 8. Serviços externos

O InfinityFree libera cURL externo nas portas 80/443, então TMDB e Groq devem
funcionar. Alguns serviços são bloqueados por política da hospedagem; se o Groq
falhar, o retrato por IA cai no fallback. Se as imagens da TMDB falharem:

- o proxy `public/api/imagem.php` tenta baixar e cachear em `storage/cache/img`;
- você pode subir o conteúdo de `storage/cache/img` do seu ambiente local para
  pré-popular o cache.

## 9. Teste rápido

1. `https://seu-site/` → landing.
2. `https://seu-site/quiz.php` → quiz.
3. Termine um quiz → retrospectiva.
4. Abra a análise completa (`?modo=completo`), abra “Ver os 8 eixos e o radar”.
5. Exporte o card 9:16.
6. `https://seu-site/admin/` → login.
