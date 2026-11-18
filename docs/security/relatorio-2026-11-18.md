# Relatório de teste de intrusão — PersonaCut

- **Data:** 18/11/2026
- **Alvo:** `http://127.0.0.1:8090` (Docker `personacut-app`, Apache 2.4.69 + PHP 8.2.35)
- **Escopo:** somente a instância local do projeto. Nenhum host externo testado.
- **Ferramentas:** HexStrike AI (MCP no OpenCode) → nmap 7.99, nikto 2.6.1,
  nuclei 3.11.1, sqlmap 1.10.8, ffuf 2.1.0, wafw00f 2.4.2 + curl.
- **Ambiente das ferramentas:** container Kali isolado (`docs/security/hexstrike.Dockerfile`).
- **Limpeza:** o resultado de teste criado durante a validação (`2GC2YXQP`) foi
  removido do banco ao final.

## Resumo

| # | Severidade | Achado | Status |
| --- | --- | --- | --- |
| 1 | **Média** | Sem limite de requisições no quiz anônimo e no login admin | Corrigido |
| 2 | Baixa | Headers de segurança ausentes (CSP, nosniff, referrer, permissions, HSTS) | Corrigido |
| 3 | Baixa | Fingerprint exposto (`X-Powered-By: PHP/8.2.35`, `Server: Apache/2.4.69`) | Corrigido |
| 4 | Baixa | `PUT`/`DELETE`/`OPTIONS` respondendo 200 na home | Corrigido |
| 5 | Baixa | PHP 8.2.35 desatualizado | Documentado |

Sem achados de SQL injection, XSS refletido, CSRF, SSRF, exposição de arquivos
ou listagem de diretório. Nuclei (CVE, exposição, misconfig, login padrão, SSL):
**0 resultados**.

---

## 1. Sem rate limiting no quiz e no login (Média) — corrigido

**Evidência (antes)**
- `POST /api/quiz.php` aceitou envios seguidos e criou um resultado novo em cada
  um (`200 {"ok":true,"codigo":...}`).
- O login em `/admin/auth.php` não tinha atraso, bloqueio progressivo nem limite
  por IP.
- O schema já gravava `ip_hash`, mas ele não era usado para limitar nada.

**Impacto:** enchimento do banco com resultados anônimos e força bruta de senha
no admin (a senha é o único fator).

**Correção aplicada**
- `src/Core/RateLimit.php` — limitador em arquivo (`storage/cache/ratelimit`),
  com `flock`, janela deslizante e faxina ocasional; *fail-open* se o diretório
  não for gravável.
- `public/api/quiz.php` — **12 envios/minuto por IP**; resposta `429` com
  `Retry-After` e mensagem amigável.
- `public/admin/auth.php` — **8 tentativas a cada 10 minutos por IP**; a
  contagem é zerada no login bem-sucedido.
- Testes em `tests/RateLimitTest.php` (limite, expiração da janela e arquivo).

**Evidência (depois)**
```
quiz:  12 requisições → 422/200 ; 13ª → 429 "Muitas tentativas…"
login: 8 tentativas  → redirect com erro ; 9ª → "Muitas tentativas de login. Tente novamente em 10 minuto(s)."
```

## 2. Headers de segurança ausentes (Baixa) — corrigido

**Antes:** sem `Content-Security-Policy`, `X-Content-Type-Options`,
`Referrer-Policy`, `Permissions-Policy` e `X-Frame-Options`.

**Correção:** `public/.htaccess` (mod_headers) com CSP, `nosniff`,
`Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy` e remoção do
`X-Powered-By`. HSTS ficou comentado até o site ir para HTTPS.

A CSP `script-src 'self'` exigiu duas limpezas:
- o script de tema saiu do HTML inline e virou `public/assets/js/theme.js`;
- os `onsubmit="confirm(...)"` do admin viraram `data-confirm` com delegação em
  `app.js` (evita `unsafe-inline`).

Também foi removido o bundle `bootstrap.bundle.js` (327 KB por página), que não
era usado e usava `eval` — era o único bloqueio da política.

## 3. Fingerprint exposto (Baixa) — corrigido

**Antes:** `Server: Apache/2.4.69 (Debian)` e `X-Powered-By: PHP/8.2.35`.

**Correção:**
- `docker/php-security.ini` com `expose_php=Off`;
- `docker/apache-security.conf` com `ServerTokens Prod`, `ServerSignature Off`
  e `TraceEnable Off`, habilitado no `Dockerfile` como
  `zz-personacut-security` (carrega depois do `security.conf` padrão);
- em hospedagem compartilhada, o `Header unset X-Powered-By` do `.htaccess`.

## 4. Métodos HTTP não restritos (Baixa) — corrigido

**Antes:** `PUT`, `DELETE` e `OPTIONS` em `/` devolviam 200 servindo a home
(o rewrite manda tudo para o PHP). Sem efeito colateral, mas confunde caches e
scanners.

**Correção:** `<LimitExcept GET POST HEAD> Require all denied </LimitExcept>` no
`public/.htaccess` → agora `403`.

## 5. PHP desatualizado (Baixa) — documentado

Nikto apontava PHP 8.2.35 como desatualizado. A imagem continua em 8.2 por
compatibilidade; a atualização para 8.3/8.4 fica como próximo passo de
manutenção.

---

## Falsos positivos

- **phpAuction / CVE-2002-0995** em `/admin/login.php?action=insert...`: falso
  positivo. A página responde igual (2742 bytes) para qualquer query string e o
  app ignora `action`.

## O que foi testado e está OK

| Teste | Resultado |
| --- | --- |
| SQL injection em `resultado.php?codigo=` (sqlmap `--level 1 --risk 1`) | não injetável |
| XSS refletido em `codigo` e `sinergia.php?a=&b=` | payload não refletido |
| CSRF no `POST /api/quiz.php` | 403 sem token; 422 com valores inválidos |
| Validação de payload (JSON quebrado, escala inválida, vazio) | 422 com campo |
| SSRF em `api/imagem.php?u=` (localhost, 169.254.169.254, `file://`, externo) | 400 (allowlist) |
| `.git/`, `.env`, `config/`, `storage/`, `sql/`, `docs/` | 404 (docroot é `public/`) |
| Listagem de diretório (`/api/`, `/.htaccess`) | 403 |
| WAF | nenhum (esperado em local) |
| Cookie de sessão | `HttpOnly`, `SameSite=Lax`, `Secure` sob HTTPS |
| Nuclei (CVE, exposure, misconfig, default-login, ssl) | 0 achados |

## Depois das correções

```
Server: Apache
(sem X-Powered-By)
Content-Security-Policy: default-src 'self'; script-src 'self'; …
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
PUT / → 403    DELETE / → 403    OPTIONS / → 403
nikto: 3 itens (HSTS, falso positivo e página de login encontrada)
nmap http-methods: GET HEAD POST
```

## Reprodução

```bash
git clone https://github.com/0x4m4/hexstrike-ai ~/tools/hexstrike-ai
cp docs/security/hexstrike.Dockerfile ~/tools/hexstrike-ai/Dockerfile
docker build -t hexstrike-ai:kali ~/tools/hexstrike-ai
docker run -d --name hexstrike --network host hexstrike-ai:kali

docker exec hexstrike nmap -sT -sV -Pn -p 8090 --script http-headers,http-methods 127.0.0.1
docker exec hexstrike nikto -h http://127.0.0.1:8090 -nointeractive
docker exec hexstrike sqlmap -u 'http://127.0.0.1:8090/resultado.php?codigo=ABCD1234' --batch

curl -sI http://127.0.0.1:8090/ | grep -iE 'content-security|x-powered|server'
```
