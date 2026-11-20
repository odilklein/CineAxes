<?php

declare(strict_types=1);

/**
 * Análise completa do perfil (dashboard). Recebe o view model montado em
 * resultado.php: $perfil, $codigo, $tituloPerfil, $arquetipo,
 * $radar, $temGroq e $raridade.
 */

$temRetro = $temRetro ?? false;
$bookmarks = $bookmarks ?? [];
$authLogado = $authLogado ?? false;

/**
 * Payload da ficha rápida de um personagem/diretor/estúdio.
 *
 * `tmdb` só é enviado para personagens (é o id do filme de origem); diretor e
 * estúdio usam os dados do catálogo + as obras essenciais.
 */
$quickEntidade = static function (array $entidade): array {
    $tipo = (string) $entidade['tipo'];

    return [
        'tipo'           => $tipo,
        'nome'           => $entidade['nome'],
        'obra'           => $entidade['obra_origem'],
        'ano'            => $entidade['ano'],
        'tmdb'           => $tipo === 'personagem' ? $entidade['tmdb_id'] : null,
        'poster'         => $entidade['imagem_url'],
        'bio'            => $entidade['bio'],
        'frase'          => $entidade['frase'] ?? null,
        'ator'           => $entidade['ator_nome'] ?? null,
        'atorFoto'       => $entidade['ator_foto_url'] ?? null,
        'atorTmdb'       => $entidade['ator_tmdb_id'] ?? null,
        'atorNascimento' => $entidade['nascimento'] ?? null,
        'atorPais'       => origem_curta($entidade['pais'] ?? null),
        'polos'          => $entidade['polos'] ?? [],
        'afinidade'      => $entidade['afinidade'] ?? null,
        'onde'           => array_map(
            static fn (array $p): array => [
                'nome'  => (string) ($p['nome'] ?? ''),
                'logo'  => tmdb_logo($p['logo'] ?? null),
                'preco' => $p['preco'] ?? null,
            ],
            (array) ($entidade['onde'] ?? [])
        ),
        'obras'          => array_map(
            static fn (array $obra): array => [
                'titulo' => $obra['titulo'],
                'ano'    => $obra['ano'],
                'tmdb'   => $obra['tmdb_id'] ?? null,
                'poster' => $obra['poster_url'] ?? null,
                'onde'   => array_map(
                    static fn (array $p): array => [
                        'nome'  => (string) ($p['nome'] ?? ''),
                        'logo'  => tmdb_logo($p['logo'] ?? null),
                        'preco' => $p['preco'] ?? null,
                    ],
                    (array) ($obra['onde'] ?? [])
                ),
            ],
            $tipo === 'personagem' ? [] : ($entidade['obras'] ?? [])
        ),
    ];
};
?>

    <section class="pc-result">
        <div class="pc-wrap">
            <div class="pc-result__head">
                <div>
                    <span class="pc-eyebrow"><?= e(t('resultado.eyebrow')) ?></span>
                    <h1 class="pc-title"><?= e(t('resultado.title')) ?></h1>
                </div>

                <div class="pc-result__code">
                    <?php if (!empty($temRetro)): ?>
                        <a class="pc-btn pc-btn--sm pc-btn--ghost pc-retro-open" href="<?= e(url('resultado.php', ['codigo' => $codigo, 'modo' => 'retro'])) ?>">
                            <?= icon('play', 14) ?>
                            <?= e(t('retro.open')) ?>
                        </a>
                    <?php endif; ?>
                    <span><?= e(t('resultado.meta', [
                        'codigo' => $codigo,
                        'modo'   => modo_rotulo((string) $perfil['resultado']['modo']),
                    ])) ?></span>
                    <button class="pc-code" type="button" data-copy="<?= e($codigo) ?>" data-copy-ok="<?= e(t('ui.copied')) ?>">
                        <?= e($codigo) ?>
                        <?= icon('copy', 14) ?>
                    </button>
                </div>
            </div>

            <article class="pc-profile-card reveal">
                <div class="pc-profile-card__main">
                    <span class="pc-profile-card__badge">
                        <?= icon('compass', 14) ?>
                        <?= e(t('resultado.archetype_label')) ?>
                    </span>

                    <h2 class="pc-profile-card__title"><?= e($tituloPerfil) ?></h2>

                    <?php if ($perfil['leituras'] !== []): ?>
                        <div class="pc-profile-card__poles">
                            <?php foreach (array_slice($perfil['leituras'], 0, 2) as $leitura): ?>
                                <span class="pc-chip">
                                    <i class="pc-chip__dot" style="background: <?= e($leitura['cor']) ?>"></i>
                                    <?= e($leitura['curto']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <p class="pc-profile-card__desc" data-narrativa-arquetipo>
                        <?= e($arquetipo['descricao'] ?? t('resultado.neutral_desc')) ?>
                    </p>

                    <?php $espelhoPrincipal = $perfil['personagens'][0] ?? null; ?>

                    <p class="pc-profile-card__frase" data-frase-perfil>
                        <?= e((string) ($perfil['retrato_salvo']['frase'] ?? $perfil['leituras'][0]['manchete'] ?? t('resultado.portrait_title'))) ?>
                    </p>

                    <?php if ($espelhoPrincipal !== null && !empty($espelhoPrincipal['frase'])): ?>
                        <figure class="pc-profile-card__quote">
                            <blockquote>“<?= e((string) $espelhoPrincipal['frase']) ?>”</blockquote>
                            <figcaption>
                                <?= e($espelhoPrincipal['nome']) ?>, <?= e($espelhoPrincipal['obra_origem']) ?><?= $espelhoPrincipal['ano'] !== null ? ' (' . (int) $espelhoPrincipal['ano'] . ')' : '' ?>
                            </figcaption>
                        </figure>
                    <?php endif; ?>

                    <div class="pc-ritual">
                        <span class="pc-ritual__icon"><?= icon('film', 19) ?></span>
                        <div>
                            <strong><?= e(t('resultado.ritual_label')) ?></strong>
                            <p><?= e($arquetipo['ritual_cinema'] ?? t('resultado.neutral_ritual')) ?></p>
                        </div>
                    </div>

                    <?php
                    $mixAtual = (string) ($perfil['mix'] ?? 'misturado');
                    $mixChave = ['classicos' => 'classics', 'descobertas' => 'discovery'][$mixAtual] ?? 'mixed';
                    ?>
                    <div class="pc-ficha">
                        <div class="pc-ficha__item">
                            <span><?= e(t('resultado.ficha_mix')) ?></span>
                            <b data-ficha-mix><?= e(t('resultado.mix_' . $mixChave)) ?></b>
                        </div>
                        <div class="pc-ficha__item pc-ficha__item--reading">
                            <span><?= e(t('resultado.ficha_reading')) ?></span>
                            <b><?= e($perfil['leituras'][0]['curto'] ?? '—') ?></b>
                        </div>
                        <div class="pc-ficha__item">
                            <span><?= e(t('resultado.ficha_decade')) ?></span>
                            <b><?= e($perfil['insights']['decada']['label'] ?? '—') ?></b>
                        </div>
                    </div>

                    <?php $confiancaPerfil = $perfil['confianca'] ?? []; ?>
                    <p class="pc-ficha__note">
                        <?= icon('compass', 12) ?>
                        <?= e(t('resultado.match_confidence', [
                            'rotulo' => t('resultado.confidence_' . (string) ($confiancaPerfil['rotulo'] ?? 'nao_estimada')),
                        ])) ?>
                        <?php if (($confiancaPerfil['rotulo'] ?? '') === 'baixa'): ?>
                            <span><?= e(t('resultado.match_confidence_low', [
                                'n' => (int) round((float) ($confiancaPerfil['por_eixo'] ?? 0)),
                            ])) ?></span>
                        <?php endif; ?>
                    </p>

                    <?php if (($perfil['consistencia']['rotulo'] ?? '') === 'indeterminado'): ?>
                        <p class="pc-ficha__note pc-ficha__note--alert">
                            <?= icon('x', 12) ?>
                            <?= e(t('resultado.inconsistent')) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <aside class="pc-profile-card__side pc-story-side" data-story-side>
                    <?php require __DIR__ . '/phone-frame.php'; ?>

                    <div class="pc-story-actions">
                        <button class="pc-btn pc-btn--sm pc-btn--light pc-story-share" type="button"
                                data-story-share
                                data-codigo="<?= e($codigo) ?>"
                                data-msg-preparing="<?= e(t('resultado.story_preparing')) ?>"
                                data-msg-error="<?= e(t('resultado.story_error')) ?>"
                                data-msg-download="<?= e(t('resultado.story_download')) ?>">
                            <?= icon('share', 15) ?>
                            <span><?= e(t('resultado.story_share')) ?></span>
                        </button>

                        <button class="pc-btn pc-btn--sm pc-btn--ghost pc-btn--on-dark pc-story-download" type="button"
                                data-story-download
                                data-codigo="<?= e($codigo) ?>"
                                data-msg-preparing="<?= e(t('resultado.story_preparing')) ?>"
                                data-msg-error="<?= e(t('resultado.story_error')) ?>"
                                data-msg-download="<?= e(t('resultado.story_download')) ?>">
                            <?= icon('download', 15) ?>
                            <span><?= e(t('resultado.story_download_btn')) ?></span>
                        </button>
                    </div>
                </aside>
            </article>


            <?php if ($temGroq): ?>
                <section class="pc-panel pc-critico reveal" data-retrato<?= !$modoCompleto ? ' data-retrato-lazy' : '' ?> data-codigo="<?= e($codigo) ?>" data-erro="<?= e(t('resultado.critic_error')) ?>">
                    <div class="pc-critico__head">
                        <span class="pc-eyebrow"><?= e(t('resultado.critic_eyebrow')) ?></span>
                        <h2><?= e(t('resultado.critic_title')) ?></h2>
                        <p><?= e(t('resultado.critic_subtitle')) ?></p>
                    </div>

                    <div class="pc-critico__texto" data-retrato-texto>
                        <?php if (!empty($perfil['retrato_salvo']['texto'])): ?>
                            <?php foreach (preg_split('/\n{2,}/', (string) $perfil['retrato_salvo']['texto']) as $paragrafo): ?>
                                <p><?= e($paragrafo) ?></p>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="pc-critico__loading"><?= e(t('resultado.critic_loading')) ?></p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="pc-section pc-films" id="filmes">
                <div class="pc-section__head">
                    <div>
                        <span class="pc-eyebrow"><?= e(t('resultado.films_eyebrow')) ?></span>
                        <h2 class="pc-title"><?= e(t('resultado.films_title')) ?></h2>
                    </div>
                    <p class="pc-lead" style="font-size:14px"><?= e(t('resultado.films_subtitle')) ?></p>
                </div>

                <div class="pc-films__toolbar">
                    <div class="pc-mix">
                        <?php foreach (['classicos' => 'classics', 'misturado' => 'mixed', 'descobertas' => 'discovery'] as $opcao => $chave): ?>
                            <a class="pc-mix__item<?= ($perfil['mix'] ?? 'misturado') === $opcao ? ' is-active' : '' ?>"
                               data-mix="<?= e($opcao) ?>"
                               href="<?= e(url('resultado.php', ['codigo' => $codigo, 'modo' => 'completo', 'mix' => $opcao])) ?>">
                                <?= e(t('resultado.mix_' . $chave)) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($config['features']['watchlist'] ?? false): ?>
                        <?php if ($authLogado): ?>
                            <a class="pc-seen-clear" href="<?= e(url('minha-lista.php')) ?>"><?= e(t('auth.my_list')) ?></a>
                        <?php else: ?>
                            <a class="pc-seen-clear" href="<?= e(url('entrar.php', ['next' => '/resultado.php?codigo=' . $codigo . '&modo=completo'])) ?>"><?= e(t('auth.login_to_save')) ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if (($perfil['filmes_top'] ?? []) === []): ?>
                    <div class="pc-empty"><?= e(t('admin.no_data')) ?></div>
                <?php else: ?>
                    <div class="pc-film-slider" data-slider>
                        <button class="pc-film-slider__nav pc-film-slider__nav--prev" type="button"
                                data-slider-prev aria-label="<?= e(t('trilhas.prev')) ?>">
                            <?= icon('arrow-left', 15) ?>
                        </button>

                        <div class="pc-film-slider__track" data-slider-track>
                        <?php foreach ($perfil['filmes_top'] as $indice => $filme): ?>
                            <?php
                            $poster = tmdb_img($filme['poster_path'] ?? null, 'w342');
                            $plataformasFilme = array_slice((array) ($filme['onde'] ?? []), 0, 3);
                            $estado = $bookmarks[(int) ($filme['tmdb_id'] ?? 0)] ?? null;
                            $quickFilme = [
                                'tipo' => 'catalogo',
                                'serie' => (string) ($filme['tipo'] ?? 'filme') === 'serie',
                                'titulo' => (string) $filme['titulo'],
                                'ano' => $filme['ano'],
                                'poster' => $poster !== '' ? $poster : null,
                                'sinopse' => $filme['sinopse'] ?? null,
                                'generos' => array_map(
                                    static fn (array $g): string => (string) ($g['nome'] ?? ''),
                                    (array) ($filme['generos'] ?? [])
                                ),
                                'nota' => $filme['nota'] ?? null,
                                'temporadas' => $filme['temporadas'] ?? null,
                                'onde' => array_map(
                                    static fn (array $p): array => [
                                        'nome' => (string) ($p['nome'] ?? ''),
                                        'logo' => tmdb_logo($p['logo'] ?? null),
                                        'preco' => $p['preco'] ?? null,
                                    ],
                                    (array) ($filme['onde'] ?? [])
                                ),
                                'tmdb' => (int) ($filme['tmdb_id'] ?? 0),
                            ];
                            ?>
                            <article class="pc-film" data-bookmark-card data-tmdb="<?= (int) ($filme['tmdb_id'] ?? 0) ?>">
                                <div class="pc-film__poster" data-quickview="<?= json_attr($quickFilme) ?>" tabindex="0" role="button" aria-label="Abrir ficha de <?= e((string) $filme['titulo']) ?>">
                                    <?php if ($poster !== ''): ?>
                                        <img src="<?= e($poster) ?>" alt="Pôster de <?= e((string) $filme['titulo']) ?>" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <span class="pc-film__fallback"><?= e((string) $filme['titulo']) ?></span>
                                    <?php endif; ?>
                                    <span class="pc-film__rank"><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                    <?php if (($filme['top_pct'] ?? 0) > 0): ?>
                                        <span class="pc-film__match">top <?= (int) $filme['top_pct'] ?>%</span>
                                    <?php endif; ?>
                                    <?php if (!empty($filme['descoberta'])): ?>
                                        <span class="pc-film__new"><?= e(t('resultado.new_badge')) ?></span>
                                    <?php endif; ?>
                                    <?php if ($plataformasFilme !== []): ?>
                                        <span class="pc-film__platforms" aria-label="Onde assistir">
                                            <?php foreach ($plataformasFilme as $plataforma): ?>
                                                <?php $logoPlataforma = tmdb_logo($plataforma['logo'] ?? null); ?>
                                                <?php if ($logoPlataforma !== ''): ?>
                                                    <img src="<?= e($logoPlataforma) ?>" alt="<?= e((string) ($plataforma['nome'] ?? '')) ?>" title="<?= e((string) ($plataforma['nome'] ?? '')) ?>" loading="lazy" decoding="async" width="16" height="16">
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php
                                    $bmTmdb = (int) ($filme['tmdb_id'] ?? 0);
                                    $bmTipo = (string) ($filme['tipo'] ?? 'filme');
                                    $bmTitulo = (string) $filme['titulo'];
                                    $bmAno = $filme['ano'];
                                    $bmPoster = (string) ($filme['poster_path'] ?? '');
                                    $bmEstado = $estado;
                                    $bmAuth = $authLogado;
                                    $bmInline = false;
                                    require __DIR__ . '/../bookmark-actions.php';
                                    ?>
                                </div>

                                <div class="pc-film__body">
                                    <b><?= e((string) $filme['titulo']) ?></b>
                                    <small class="pc-film__meta">
                                        <?php if ($filme['ano'] !== null): ?>
                                            <span class="pc-film__tag"><?= (int) $filme['ano'] ?></span>
                                        <?php endif; ?>
                                        <span class="pc-film__tag"><?= e((string) ($filme['tipo'] ?? 'filme')) ?></span>
                                    </small>

                                    <?php if (!empty($filme['personagem'])): ?>
                                        <p class="pc-film__character">
                                            <?= e((string) $filme['personagem']) ?>
                                            <?php if (!empty($filme['ator_nome'])): ?>
                                                <span>· <?= e((string) $filme['ator_nome']) ?></span>
                                            <?php endif; ?>
                                        </p>
                                    <?php endif; ?>

                                </div>
                            </article>
                        <?php endforeach; ?>
                        </div>

                        <button class="pc-film-slider__nav pc-film-slider__nav--next" type="button"
                                data-slider-next aria-label="<?= e(t('trilhas.next')) ?>">
                            <?= icon('arrow-right', 15) ?>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (($perfil['descobertas'] ?? []) !== []): ?>
                    <div class="pc-finds">
                        <div class="pc-finds__head">
                            <span class="pc-eyebrow"><?= e(t('resultado.finds_eyebrow')) ?></span>
                            <h3><?= e(t('resultado.finds_title')) ?></h3>
                        </div>
                        <div class="pc-finds__grid">
                            <?php foreach ($perfil['descobertas'] as $achado): ?>
                                <?php
                                $estadoAchado = $bookmarks[(int) ($achado['tmdb'] ?? 0)] ?? null;
                                $quickAchado = [
                                    'tipo' => 'catalogo',
                                    'serie' => (string) ($achado['tipo'] ?? 'filme') === 'serie',
                                    'titulo' => (string) $achado['titulo'],
                                    'ano' => $achado['ano'] ?? null,
                                    'poster' => $achado['poster'] ?? null,
                                    'tmdb' => (int) ($achado['tmdb'] ?? 0),
                                ];
                                ?>
                                <div class="pc-find" data-tmdb="<?= (int) ($achado['tmdb'] ?? 0) ?>"
                                     data-quickview="<?= json_attr($quickAchado) ?>" tabindex="0" role="button"
                                     aria-label="Abrir ficha de <?= e((string) $achado['titulo']) ?>">
                                    <?php if (!empty($achado['poster'])): ?>
                                        <img src="<?= e((string) $achado['poster']) ?>" alt="" loading="lazy" decoding="async">
                                    <?php endif; ?>
                                    <div>
                                        <b><?= e((string) $achado['titulo']) ?></b>
                                        <small><span data-narrativa-descoberta="<?= e((string) $achado['titulo']) ?>"><?= (int) ($achado['ano'] ?? 0) ?> · <?= e((string) $achado['porque']) ?></span></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="pc-panel pc-readings reveal" id="leituras">
                <div class="pc-panel__head">
                    <div>
                        <h2><?= e(t('resultado.readings_title')) ?></h2>
                        <p><?= e(t('resultado.readings_subtitle')) ?></p>
                    </div>
                </div>

                <div class="pc-readings__grid<?= count($perfil['leituras']) === 1 ? ' pc-readings__grid--single' : '' ?>">
                    <?php foreach ($perfil['leituras'] as $indice => $leitura): ?>
                        <article class="pc-reading" style="--reading:<?= e($leitura['cor']) ?>">
                            <div class="pc-reading__top">
                                <span class="pc-reading__num"><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                <span class="pc-reading__eixo"><?= e($leitura['eixo']) ?> · <?= e($leitura['polo']) ?></span>
                                <span class="pc-reading__forca"><?= e($leitura['forca']) ?></span>
                            </div>

                            <h3 class="pc-reading__title"><?= e($leitura['manchete']) ?></h3>
                            <p class="pc-reading__body" data-narrativa-leitura="<?= e((string) $leitura['slug']) ?>"><?= e($leitura['corpo']) ?></p>

                            <div class="pc-reading__films">
                                <span><?= e(t('retro.reading.matches')) ?></span>
                                <div class="pc-filme-row">
                                    <?php $filmeVariante = 'chip'; ?>
                                    <?php foreach ($leitura['combina'] as $filme): ?>
                                        <?php require __DIR__ . '/../card-filme.php'; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <p class="pc-reading__tired">
                                <span><?= e(t('retro.reading.tired')) ?></span>
                                <?= e($leitura['cansa']) ?>
                            </p>

                            <?php if ($leitura['espelho'] !== null): ?>
                                <p class="pc-reading__mirror">
                                    <?= e(t('retro.reading.mirror', [
                                        'nome' => $leitura['espelho']['nome'],
                                        'obra' => $leitura['espelho']['obra'],
                                    ])) ?>
                                </p>
                            <?php endif; ?>

                            <?php
                            $guiaEixo = \Models\EixoGuia::porSlug((string) $leitura['slug']);
                            $guiaLeitura = (array) ($guiaEixo['leitura'] ?? []);
                            $guiaCenas = (array) ($guiaEixo['cenas'] ?? []);
                            $guiaCitacoes = (array) ($guiaEixo['citacoes'] ?? []);
                            $ladoUsuario = $leitura['lado'] === 'dir' ? 'dir' : 'esq';
                            ?>

                            <?php if (($guiaEixo['resumo'] ?? '') !== ''): ?>
                                <details class="pc-reading__guide">
                                    <summary>
                                        <span><?= e(t('resultado.reading_guide')) ?></span>
                                        <small><?= e(t('resultado.reading_guide_hint')) ?></small>
                                    </summary>

                                    <div class="pc-reading__guide-body">
                                        <p class="pc-reading__guide-resumo"><?= e((string) $guiaEixo['resumo']) ?></p>

                                        <div class="pc-reading__sides">
                                            <?php foreach (['esq' => 'polo_esq', 'dir' => 'polo_dir'] as $lado => $campoPolo): ?>
                                                <div class="pc-reading__side<?= $lado === $ladoUsuario ? ' is-user' : '' ?>">
                                                    <b><?= e(t('resultado.reading_side', ['polo' => (string) $leitura[$campoPolo]])) ?></b>
                                                    <p><?= e((string) ($guiaLeitura[$lado] ?? '')) ?></p>

                                                    <ul class="pc-reading__scenes">
                                                        <?php foreach (array_slice(array_values(array_filter(
                                                            $guiaCenas,
                                                            static fn (array $cena): bool => ($cena['polo'] ?? '') === $lado
                                                        )), 0, 2) as $cena): ?>
                                                            <li>
                                                                <b><?= e((string) $cena['obra']) ?></b>
                                                                <small><?= (int) $cena['ano'] ?></small>
                                                                <span><?= e((string) $cena['cena']) ?></span>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <?php if ($guiaCitacoes !== []): ?>
                                            <?php $citacao = $guiaCitacoes[0]; ?>
                                            <p class="pc-reading__cite">
                                                <b><?= e((string) $citacao['autor']) ?></b>,
                                                <i><?= e((string) $citacao['obra']) ?></i>
                                                — <?= e((string) $citacao['ideia']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </details>
                            <?php endif; ?>

                            <span class="pc-reading__technical">
                                <?= e($leitura['pct'] !== null
                                    ? t('retro.reading.technical', [
                                        'eixo' => $leitura['eixo'],
                                        'polo' => $leitura['polo'],
                                        'pct' => numero((float) $leitura['pct'], 0),
                                    ])
                                    : t('retro.reading.technical_neutral', [
                                        'eixo' => $leitura['eixo'],
                                        'polo' => $leitura['polo'],
                                    ])) ?>
                            </span>
                        </article>
                    <?php endforeach; ?>
                </div>

                <p class="pc-readings__note"><?= e(t('resultado.readings_note')) ?></p>
            </section>

            <details class="pc-dados">
                <summary>
                    <span><?= e(t('resultado.technical_title')) ?></span>
                    <small><?= e(t('resultado.technical_help')) ?></small>
                </summary>

                <div class="pc-dados__body">
                    <p class="pc-top10__method">
                        <?= e(t('resultado.technical_version', [
                            'versao' => (string) ($perfil['resultado']['questionario_versao'] ?? t('resultado.technical_unknown')),
                            'modo' => modo_rotulo((string) $perfil['resultado']['modo']),
                        ])) ?>
                    </p>

                    <div class="pc-result__grid">
                        <section class="pc-panel">
                            <div class="pc-panel__head">
                                <div>
                                    <h2><?= e(t('resultado.axes_title')) ?></h2>
                                    <p><?= e(t('resultado.axes_subtitle')) ?></p>
                                </div>
                            </div>

                            <?php foreach ($perfil['linhas'] as $linha): ?>
                                <div class="pc-axis-line">
                                    <div class="pc-axis-line__top">
                                        <span class="pc-axis-line__name">
                                            <?= icon((string) $linha['eixo']['icone_esq'], 14) ?>
                                            <?= e(eixo_nome($linha['eixo'])) ?>
                                        </span>
                                        <span class="pc-axis-line__intensity" data-level="<?= e($linha['intensidade']) ?>">
                                            <?= e(t('resultado.intensity.' . $linha['intensidade'])) ?>
                                        </span>
                                    </div>

                                    <div class="pc-dual">
                                        <span class="pc-dual__pole"><b><?= pct($linha['pct_esq']) ?></b> <?= e($linha['rotulo_esq']) ?></span>
                                        <span class="pc-dual__track">
                                            <span class="pc-dual__esq" data-bar="<?= num_css($linha['pct_esq']) ?>" style="width:0;background:<?= e($linha['cor_esq']) ?>"></span>
                                            <span class="pc-dual__dir" data-bar="<?= num_css($linha['pct_dir']) ?>" style="width:0;background:<?= e($linha['cor_dir']) ?>"></span>
                                        </span>
                                        <span class="pc-dual__pole pc-dual__pole--dir"><?= e($linha['rotulo_dir']) ?> <b><?= pct($linha['pct_dir']) ?></b></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </section>

                        <section class="pc-panel pc-panel--radar reveal">
                            <div class="pc-panel__head">
                                <div>
                                    <h2><?= e(t('resultado.radar_title')) ?></h2>
                                    <p><?= e(t('resultado.radar_subtitle')) ?></p>
                                </div>
                            </div>

                            <div class="pc-radar-wrap">
                                <?= $radar ?>
                            </div>

                            <p class="pc-radar__caption"><?= e(t('resultado.radar_caption')) ?></p>
                        </section>
                    </div>
                </div>
            </details>

            <?php $ins = $perfil['insights']; ?>
            <section class="pc-section" id="insights">
                <div class="pc-section__head">
                    <div>
                        <span class="pc-eyebrow"><?= e(t('resultado.insights.eyebrow')) ?></span>
                        <h2 class="pc-title"><?= e(t('resultado.insights.title')) ?></h2>
                    </div>
                    <p class="pc-lead" style="font-size:14px"><?= e(t('resultado.insights.subtitle')) ?></p>
                </div>

                <div class="pc-insights">
                    <?php if ($ins['decada'] !== null): ?>
                        <article class="pc-insight pc-insight--decade reveal">
                            <span class="pc-insight__icon"><?= icon('clock', 18) ?></span>
                            <span class="pc-insight__label"><?= e(t('resultado.insights.decada_title')) ?></span>
                            <span class="pc-insight__value pc-insight__value--big"><?= e($ins['decada']['label']) ?></span>
                            <span class="pc-insight__epiteto"><?= e($ins['decada']['epiteto']) ?></span>
                            <p><?= e($ins['decada']['descricao']) ?></p>

                            <?php if ($ins['decada']['curiosidade'] !== ''): ?>
                                <p class="pc-insight__curio"><?= e($ins['decada']['curiosidade']) ?></p>
                            <?php endif; ?>

                            <p class="pc-insight__meta"><?= e(t('resultado.insights.decada_sample', ['n' => $ins['decada']['amostra']])) ?></p>
                        </article>
                    <?php endif; ?>

                    <?php if ($ins['secundario'] !== null): ?>
                        <article class="pc-insight pc-insight--archetype reveal">
                            <span class="pc-insight__icon"><?= icon('layers', 18) ?></span>
                            <span class="pc-insight__label"><?= e(t('resultado.insights.secundario_title')) ?></span>
                            <span class="pc-insight__value"><?= e($ins['secundario']['titulo']) ?></span>
                            <p class="pc-insight__compat"><?= e(t('resultado.insights.secundario_text')) ?></p>
                            <p><?= e((string) $ins['secundario']['descricao']) ?></p>

                            <?php if (!empty($ins['secundario']['polos'])): ?>
                                <div class="pc-insight__traits">
                                    <?php foreach ($ins['secundario']['polos'] as $polo): ?>
                                        <span class="pc-insight__trait" style="--trait:<?= e($polo['cor']) ?>">
                                            <?= e($polo['eixo']) ?> · <?= e($polo['polo']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($ins['secundario']['ritual'])): ?>
                                <p class="pc-insight__ritual">
                                    <span><?= e(t('resultado.ritual_label')) ?></span>
                                    <?= e((string) $ins['secundario']['ritual']) ?>
                                </p>
                            <?php endif; ?>
                        </article>
                    <?php endif; ?>
                </div>

                <?php if ($ins['decada'] !== null && $ins['decada']['obras'] !== []): ?>
                    <section class="pc-panel pc-decade-panel reveal">
                        <div class="pc-panel__head">
                            <div>
                                <h2><?= e(t('resultado.insights.decade_works_title', ['decada' => $ins['decada']['label']])) ?></h2>
                                <p><?= e(t('resultado.insights.decade_works_sub')) ?></p>
                            </div>
                        </div>

                        <div class="pc-film-slider pc-film-slider--decade" data-slider>
                            <button class="pc-film-slider__nav pc-film-slider__nav--prev" type="button"
                                    data-slider-prev aria-label="<?= e(t('trilhas.prev')) ?>">
                                <?= icon('arrow-left', 15) ?>
                            </button>

                            <div class="pc-film-slider__track pc-filme-slider-track" data-slider-track>
                                <?php $filmeVariante = 'card'; ?>
                                <?php foreach ($ins['decada']['obras'] as $filme): ?>
                                    <?php require __DIR__ . '/../card-filme.php'; ?>
                                <?php endforeach; ?>
                            </div>

                            <button class="pc-film-slider__nav pc-film-slider__nav--next" type="button"
                                    data-slider-next aria-label="<?= e(t('trilhas.next')) ?>">
                                <?= icon('arrow-right', 15) ?>
                            </button>
                        </div>

                        <?php if ($ins['decada']['marcos'] !== []): ?>
                            <div class="pc-decade-marks">
                                <span class="pc-decade-marks__label"><?= e(t('resultado.insights.decade_marks')) ?></span>
                                <div class="pc-filme-row">
                                    <?php $filmeVariante = 'chip'; ?>
                                    <?php foreach ($ins['decada']['marcos'] as $filme): ?>
                                        <?php require __DIR__ . '/../card-filme.php'; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

            </section>

            <?php require __DIR__ . '/streaming.php'; ?>

            <section class="pc-section" id="diretores">
                <div class="pc-section__head">
                    <div>
                        <span class="pc-eyebrow"><?= e(t('resultado.anchors_eyebrow')) ?></span>
                        <h2 class="pc-title"><?= e(t('resultado.anchors_title')) ?></h2>
                    </div>
                    <p class="pc-lead" style="font-size:14px"><?= e(t('resultado.anchors_subtitle')) ?></p>
                </div>

                <div class="pc-anchor-grid pc-mt-3">
                    <?php foreach ([
                        ['item' => $perfil['diretor'], 'tipo' => 'diretor', 'icone' => 'film'],
                        ['item' => $perfil['estudio'], 'tipo' => 'estudio', 'icone' => 'star'],
                    ] as $bloco): ?>
                        <?php if ($bloco['item'] === null): ?>
                            <div class="pc-empty"><?= e(t('admin.no_data')) ?></div>
                            <?php continue; ?>
                        <?php endif; ?>

                        <?php
                        $item = $bloco['item'];
                        $cor = cor_dominante($item['vetor'], $perfil['eixos']);
                        $ehEstudio = $bloco['tipo'] === 'estudio';
                        $quick = $quickEntidade($item);
                        ?>
                        <article class="pc-entity pc-entity--rich pc-entity--anchor reveal"
                                 data-quickview="<?= json_attr($quick) ?>"
                                 tabindex="0" role="button"
                                 aria-label="Abrir ficha de <?= e($item['nome']) ?>">
                            <div class="pc-entity__poster <?= $ehEstudio ? 'pc-entity__poster--logo' : '' ?>">
                                <?php if ($ehEstudio): ?>
                                    <span class="pc-entity__seal pc-entity__seal--back" style="--seal: <?= e($cor) ?>">
                                        <span class="pc-entity__seal-mono"><?= e(iniciais((string) $item['nome'])) ?></span>
                                        <small><?= e(t('resultado.types.estudio')) ?></small>
                                    </span>
                                <?php else: ?>
                                    <span class="pc-entity__mono-lg pc-entity__mono-lg--back" style="--mono-bg: <?= e($cor) ?>"><?= e(iniciais((string) $item['nome'])) ?></span>
                                <?php endif; ?>

                                <?php if (!empty($item['imagem_url'])): ?>
                                    <img src="<?= e($item['imagem_url']) ?>" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer" data-fallback-img>
                                <?php endif; ?>

                                <span class="pc-quick-hint">Ficha rápida</span>
                            </div>

                            <div class="pc-entity__content">
                                <div class="pc-entity__name"><?= e($item['nome']) ?></div>
                                <div class="pc-entity__meta">
                                    <?= e(origem_curta($item['pais'] ?? null)) ?>
                                    <?= $item['nascimento'] !== null ? ' · ' . (int) $item['nascimento'] : '' ?>
                                    <?= $item['pais'] === null && $item['nascimento'] === null ? e($item['obra_origem']) : '' ?>
                                </div>

                                <p class="pc-entity__bio"<?= $bloco['tipo'] === 'diretor' ? ' data-narrativa-diretor' : '' ?>><?= e((string) ($item['bio'] ?? '')) ?></p>

                                <?php if (!empty($item['frase'])): ?>
                                    <blockquote class="pc-entity__quote">“<?= e((string) $item['frase']) ?>”</blockquote>
                                <?php endif; ?>

                                <?php if (!empty($item['obras'])): ?>
                                    <ul class="pc-works">
                                        <?php foreach (array_slice($item['obras'], 0, 3) as $obra): ?>
                                            <li>
                                                <span><?= e($obra['titulo']) ?></span>
                                                <small><?= $obra['ano'] !== null ? (int) $obra['ano'] : '' ?></small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <?php if (!empty($item['motivos'])): ?>
                                    <p class="pc-entity__why">
                                        <?php foreach ($item['motivos'] as $motivo): ?>
                                            <span title="<?= e(t('resultado.match_why', [
                                                'eixo' => $motivo['eixo'],
                                                'voce' => (int) $motivo['voce'],
                                                'ele' => (int) $motivo['ele'],
                                            ])) ?>">
                                                <?= e($motivo['eixo']) ?>
                                                <b><?= (int) $motivo['voce'] ?>·<?= (int) $motivo['ele'] ?></b>
                                            </span>
                                        <?php endforeach; ?>
                                    </p>
                                <?php endif; ?>

                                <div class="pc-entity__foot">
                                    <span class="pc-entity__type"><?= e(t('resultado.types.' . $bloco['tipo'])) ?></span>
                                    <span class="pc-affinity">
                                        <b><?= (int) ($item['posicao'] ?? 1) ?>º</b>
                                        <small><?= e(t('resultado.match_of', ['n' => (int) ($item['de'] ?? 1)])) ?></small>
                                    </span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if ($perfil['atores'] !== []): ?>
                <section class="pc-section" id="atores">
                    <div class="pc-section__head">
                        <div>
                            <span class="pc-eyebrow"><?= e(t('resultado.actors_eyebrow')) ?></span>
                            <h2 class="pc-title"><?= e(t('resultado.actors_title')) ?></h2>
                        </div>
                        <p class="pc-lead" style="font-size:14px"><?= e(t('resultado.actors_subtitle')) ?></p>
                    </div>

                    <div class="pc-people">
                        <?php foreach ($perfil['atores'] as $ator): ?>
                            <?php
                            $quickAtor = [
                                'tipo'           => 'ator',
                                'nome'           => $ator['nome'],
                                'ator'           => $ator['nome'],
                                'atorTmdb'       => $ator['tmdb'],
                                'atorFoto'       => $ator['foto'],
                                'atorNascimento' => $ator['nascimento'],
                                'atorPais'       => origem_curta($ator['pais'] ?? null),
                                'personagem'     => $ator['personagem'],
                                'obra'           => $ator['obra'],
                                'ano'            => $ator['ano'],
                                'tmdb'           => $ator['filme_tmdb'],
                                'poster'         => null,
                            ];
                            ?>
                            <button class="pc-person reveal" type="button"
                                    data-quickview="<?= json_attr($quickAtor) ?>">
                                <span class="pc-person__photo">
                                    <?php if (!empty($ator['foto'])): ?>
                                        <img src="<?= e($ator['foto']) ?>" alt="" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <?= e(iniciais((string) $ator['nome'])) ?>
                                    <?php endif; ?>
                                </span>
                                <b><?= e($ator['nome']) ?></b>
                                <small><?= e(t('resultado.actor_as', [
                                    'personagem' => $ator['personagem'],
                                    'obra' => $ator['obra'],
                                ])) ?></small>
                                <span class="pc-person__meta">
                                    <?= e(origem_curta($ator['pais'] ?? null)) ?><?= $ator['nascimento'] !== null ? ' · ' . (int) $ator['nascimento'] : '' ?>
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php require __DIR__ . '/elenco.php'; ?>

            <?php require __DIR__ . '/trilhas.php'; ?>

            <div class="pc-share">
                <section class="pc-share__box">
                    <span class="pc-eyebrow"><?= e(t('resultado.share_title')) ?></span>
                    <p class="pc-lead" style="font-size:14px"><?= e(t('resultado.share_subtitle')) ?></p>

                    <div class="pc-share__code-row">
                        <button class="pc-btn pc-btn--sm" type="button" data-copy="<?= e($codigo) ?>" data-copy-ok="<?= e(t('ui.copied')) ?>">
                            <?= icon('copy', 16) ?>
                            <?= e(t('resultado.share_cta')) ?>
                        </button>

                        <button class="pc-btn pc-btn--sm pc-btn--ghost" type="button" data-share data-share-text="Meu perfil cinéfilo no PersonaCut: <?= e($codigo) ?>">
                            <?= icon('share', 16) ?>
                            <?= e(t('resultado.share_native')) ?>
                        </button>

                        <a class="pc-btn pc-btn--sm pc-btn--ghost" href="<?= url('quiz.php') ?>">
                            <?= icon('refresh', 16) ?>
                            <?= e(t('resultado.retake')) ?>
                        </a>
                    </div>
                </section>

                <section class="pc-compare-box">
                    <span class="pc-eyebrow"><?= e(t('resultado.compare_title')) ?></span>
                    <p class="pc-lead" style="font-size:14px"><?= e(t('resultado.compare_subtitle')) ?></p>

                    <form action="<?= url('sinergia.php') ?>" method="get">
                        <input type="hidden" name="a" value="<?= e($codigo) ?>">
                        <label class="pc-label" for="outro-codigo"><?= e(t('resultado.compare_placeholder')) ?></label>
                        <input class="pc-input" id="outro-codigo" name="b" type="text" maxlength="8" required data-code-input placeholder="WXYZ6789">
                        <button class="pc-btn pc-btn--sm" type="submit">
                            <?= e(t('resultado.compare_cta')) ?>
                            <?= icon('users', 16) ?>
                        </button>
                    </form>
                </section>
            </div>

            <p class="pc-disclaimer">
                <?= e(t('tmdb.credito')) ?>
            </p>
        </div>
    </section>
