# Compartilhar o PersonaCut

Textos prontos para colar no WhatsApp, Discord, X e Instagram/LinkedIn.
O link oficial é **https://personacut.page.gd**.

## Metadados de link (Open Graph / Twitter Card)

As páginas já enviam o preview:

- **Título:** PersonaCut — seu perfil cinéfilo em 6 eixos
- **Descrição:** Descubra seu perfil cinéfilo em 6 eixos: arquétipo, 10 filmes,
  diretor, escola e onde assistir. Quiz grátis, sem cadastro, em 3 minutos.
- **Imagem:** `public/assets/img/og-personacut.jpg` (1200×630)
- **Card do X:** `summary_large_image`
- **Páginas de resultado:** título vira o arquétipo do perfil (ex.: “O Cartógrafo
  de Mundos — PersonaCut”) e a descrição é a leitura principal daquele perfil.

O `canonical` e o `og:url` são montados a partir do domínio acessado, então o
mesmo código funciona em localhost, `page.gd` ou em um domínio próprio.

## Whatsapp

> 🎬 Que tipo de cinema mora em você no PersonaCut?
>
> São 24 a 48 afirmações sobre o que você gosta (ou evita) na tela. No fim, o
> site monta seu perfil em 6 eixos: arquétipo, 10 filmes, diretor, escola e onde
> assistir — e ainda gera um card 9:16 pros stories.
>
> É grátis, sem cadastro e leva uns 3 minutos:
> https://personacut.page.gd

Versão curta, para grupo de amigos:

> Achei um quiz de cinema bem diferente: ele descobre seu perfil em 6 eixos e
> recomenda 10 filmes com base nas suas respostas. O meu deu *O Espectador de
> Multidão* 😅
> https://personacut.page.gd

## Discord

> **PersonaCut** — que tipo de cinema mora em você?
>
> Responda de 24 a 48 afirmações e receba um retrato do seu gosto: 6 eixos,
> arquétipo, 10 filmes, diretor, escola e onde assistir.
>
> 🎟️ Grátis · sem cadastro · ~3 min
> 🔗 https://personacut.page.gd
>
> *O resultado vira um card 9:16 pra story — e trocar o mix de recomendações
> muda a lista de filmes.*

## X (Twitter)

> Que tipo de cinema mora em você?
>
> 24 a 48 afirmações viram seu perfil em 6 eixos: arquétipo, 10 filmes, diretor,
> escola e onde assistir.
>
> Grátis, sem cadastro, em 3 minutos.
> https://personacut.page.gd
>
> #cinema

Versão com gancho de debate:

> Popular ou autoral? Claro ou sombrio? Responda 24 a 48 afirmações no PersonaCut
> e descubra seu perfil cinéfilo em 6 eixos — com 10 filmes que combinam com
> você.
>
> https://personacut.page.gd

## Instagram / LinkedIn (bio ou legenda)

> Seu perfil cinéfilo em 6 eixos. Quiz grátis, sem cadastro: arquétipo, 10
> filmes, diretor, escola e onde assistir. → personacut.page.gd

## Descrição para diretórios e formulários (até 160 caracteres)

> Descubra seu perfil cinéfilo em 6 eixos: arquétipo, 10 filmes, diretor, escola
> e onde assistir. Quiz grátis, sem cadastro, em 3 minutos.

## Se o preview não atualizar

1. **WhatsApp:** apague a mensagem e cole de novo depois de alguns minutos (ele
   guarda o preview em cache).
2. **X:** valide em <https://cards-dev.twitter.com/validator>.
3. **Facebook/Instagram/WhatsApp:** recache em
   <https://developers.facebook.com/tools/debug/> com
   `https://personacut.page.gd/`.
4. **Discord:** o embed é montado na hora; se aparecer só o link, confira se o
   `og:image` está acessível e tente reenviar.

> Observação: o InfinityFree tem uma proteção anti-bot que libera os principais
> crawlers (Facebook, Google, Telegram e outros), mas pode atrasar o preview em
> algum serviço. A imagem é estática (`/assets/img/og-personacut.jpg`), então não
> depende de PHP para ser baixada.
