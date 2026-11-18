<?php

declare(strict_types=1);

/**
 * Retrospectiva do perfil em capítulos (estilo stories).
 *
 * Recebe o view model montado em resultado.php. Todos os capítulos são
 * renderizados no servidor; o retro.js mostra um por vez e cuida da navegação.
 * Sem JavaScript, a capa continua visível e o link para a análise completa
 * funciona normalmente.
 *
 * A partir desta versão, a retrospectiva não mostra os 8 eixos como produto:
 * mostra as leituras que eles geram. Os capítulos variam de 10 (perfil de
 * trânsito) a 13 (quatro leituras).
 */

$retroLeituras = $perfil['leituras'] ?? [];
$retroPersonagens = $perfil['personagens'];
$retroPrincipal = $retroPersonagens[0] ?? null;
$retroOutros = array_slice($retroPersonagens, 1);
$retroDiretor = $perfil['diretor'];
$retroEstudio = $perfil['estudio'];
$retroInsights = $perfil['insights'];
$retroTextoSalvo = trim((string) ($perfil['retrato_salvo']['texto'] ?? ''));
$retroTotal = 9 + count($retroLeituras);
$retroCap = 0;

$retroUrlCompleta = url('resultado.php', ['codigo' => $codigo, 'modo' => 'completo']);
$retroUrlPerfil = url('resultado.php', ['codigo' => $codigo]);

$retroQuickPersonagem = static function (array $entidade): array {
    return [
        'tipo'           => 'personagem',
        'nome'           => $entidade['nome'],
        'obra'           => $entidade['obra_origem'],
        'ano'            => $entidade['ano'],
        'tmdb'           => $entidade['tmdb_id'],
        'poster'         => $entidade['imagem_url'],
        'bio'            => $entidade['bio'],
        'frase'          => $entidade['frase'] ?? null,
        'ator'           => $entidade['ator_nome'],
        'atorFoto'       => $entidade['ator_foto_url'],
        'atorTmdb'       => $entidade['ator_tmdb_id'] ?? null,
        'atorNascimento' => $entidade['nascimento'] ?? null,
        'atorPais'       => origem_curta($entidade['pais'] ?? null),
        'polos'          => $entidade['polos'] ?? [],
        'afinidade'      => $entidade['afinidade'],
    ];
};

$retroQuickAncora = static function (array $item, string $tipo): array {
    return [
        'tipo'      => $tipo,
        'nome'      => $item['nome'],
        'bio'       => $item['bio'],
        'frase'     => $item['frase'] ?? null,
        'poster'    => $item['imagem_url'],
        'polos'     => $item['polos'] ?? [],
        'obras'     => array_map(
            static fn (array $obra): array => [
                'titulo' => $obra['titulo'],
                'ano'    => $obra['ano'],
                'tmdb'   => $obra['tmdb_id'] ?? null,
                'poster' => $obra['poster_url'] ?? null,
            ],
            $item['obras'] ?? []
        ),
        'afinidade' => $item['afinidade'],
    ];
};
?>

<section class="pc-retro" id="retro" data-retro data-total="<?= $retroTotal ?>"
         role="dialog" aria-modal="true" aria-label="<?= e(t('retro.aria')) ?>">

    <div class="pc-retro__frame">
        <header class="pc-retro__bar">
            <a class="pc-retro__brand" href="<?= e($retroUrlPerfil) ?>" data-retro-home>
                Persona<span>Cut</span>
            </a>

            <div class="pc-retro__progress" data-retro-progress aria-label="<?= e(t('retro.aria')) ?>">
                <?php for ($i = 1; $i <= $retroTotal; $i++): ?>
                    <button class="pc-retro__seg<?= $i === 1 ? ' is-current' : '' ?>" type="button"
                            data-retro-goto="<?= $i - 1 ?>"
                            aria-label="<?= e(t('retro.count', ['atual' => $i, 'total' => $retroTotal])) ?>">
                        <i aria-hidden="true"></i>
                    </button>
                <?php endfor; ?>
            </div>

            <a class="pc-retro__icon" href="<?= e($retroUrlCompleta) ?>" data-retro-close
               aria-label="<?= e(t('retro.close')) ?>" title="<?= e(t('retro.close')) ?>">
                <?= icon('x', 18) ?>
            </a>
        </header>

        <div class="pc-retro__stage" data-retro-stage>
            <span class="pc-retro__status pc-visually-hidden" data-retro-status aria-live="polite"></span>

            <?php $c = $retroCap++; ?>
            <article class="pc-retro__chapter pc-retro__chapter--cover is-active" data-chapter="<?= $c ?>"
                     aria-roledescription="capítulo" aria-labelledby="retro-titulo-capa">
                <div class="pc-retro__cover-strip" aria-hidden="true">
                    <?php foreach (array_slice($retroPersonagens, 0, 3) as $personagem): ?>
                        <?php $retroFundo = img_proxy((string) ($personagem['imagem_url'] ?? '')); ?>
                        <span<?php if ($retroFundo !== ''): ?> style="background-image:url('<?= e($retroFundo) ?>')"<?php endif; ?>></span>
                    <?php endforeach; ?>
                </div>

                <div class="pc-retro__cover-body">
                    <span class="pc-retro__eyebrow" data-reveal><?= e(t('retro.cover.eyebrow')) ?></span>
                    <h2 class="pc-retro__display pc-retro__display--xl" id="retro-titulo-capa" data-reveal style="--d:.06s">
                        <?= e(t('retro.cover.title')) ?>
                    </h2>
                    <p class="pc-retro__lead" data-reveal style="--d:.14s"><?= e(t('retro.cover.lead')) ?></p>

                    <button class="pc-retro__start" type="button" data-retro-next data-reveal style="--d:.22s">
                        <?= icon('play', 16) ?>
                        <?= e(t('retro.start')) ?>
                    </button>

                    <div class="pc-retro__cover-meta" data-reveal style="--d:.3s">
                        <span><?= e(t('retro.cover.code')) ?> <b><?= e($codigo) ?></b></span>
                        <span><?= e(t('retro.cover.note')) ?></span>
                        <a class="pc-retro__inline-link" href="<?= e($retroUrlCompleta) ?>" data-retro-close>
                            <?= e(t('retro.full')) ?>
                            <?= icon('arrow-right', 13) ?>
                        </a>
                    </div>
                </div>

                <p class="pc-retro__hint" data-reveal style="--d:.36s"><?= e(t('retro.hint')) ?></p>
            </article>

            <?php $c = $retroCap++; ?>
            <article class="pc-retro__chapter" data-chapter="<?= $c ?>" aria-roledescription="capítulo"
                     aria-labelledby="retro-titulo-arquetipo" hidden>
                <span class="pc-retro__ghost" aria-hidden="true"><?= str_pad((string) ($c + 1), 2, '0', STR_PAD_LEFT) ?></span>
                <span class="pc-retro__eyebrow" data-reveal><?= e(t('retro.archetype.eyebrow')) ?></span>

                <div class="pc-retro__seal" data-reveal style="--d:.06s" aria-hidden="true">
                    <?= icon('compass', 30) ?>
                </div>

                <h2 class="pc-retro__display" id="retro-titulo-arquetipo" data-reveal style="--d:.12s">
                    <?= e($tituloPerfil) ?>
                </h2>

                <p class="pc-retro__lead" data-reveal style="--d:.2s">
                    <?= e($arquetipo['descricao'] ?? t('resultado.neutral_desc')) ?>
                </p>

                <p class="pc-retro__note" data-reveal style="--d:.28s">
                    <?= e($arquetipo === null ? t('retro.archetype.neutral') : t('retro.archetype.lead')) ?>
                </p>
            </article>

            <?php $c = $retroCap++; ?>
            <article class="pc-retro__chapter" data-chapter="<?= $c ?>" aria-roledescription="capítulo"
                     aria-labelledby="retro-titulo-marcas" hidden>
                <span class="pc-retro__ghost" aria-hidden="true"><?= str_pad((string) ($c + 1), 2, '0', STR_PAD_LEFT) ?></span>
                <span class="pc-retro__eyebrow" data-reveal><?= e(t('retro.marks.eyebrow')) ?></span>
                <h2 class="pc-retro__display" id="retro-titulo-marcas" data-reveal style="--d:.06s">
                    <?= e(count($retroLeituras) > 1 ? t('retro.marks.title') : t('retro.marks.neutral_title')) ?>
                </h2>
                <p class="pc-retro__lead" data-reveal style="--d:.14s">
                    <?= e(count($retroLeituras) > 1 ? t('retro.marks.lead') : t('retro.marks.neutral_lead')) ?>
                </p>

                <ul class="pc-retro__marks" role="list">
                    <?php foreach ($retroLeituras as $indice => $leitura): ?>
                        <li class="pc-retro__mark" data-reveal style="--d:<?= 0.2 + ($indice * 0.07) ?>s;--mark:<?= e($leitura['cor']) ?>">
                            <span class="pc-retro__mark-num" aria-hidden="true"><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="pc-retro__mark-body">
                                <b><?= e($leitura['curto']) ?></b>
                                <small><?= e($leitura['manchete']) ?></small>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </article>

            <?php foreach ($retroLeituras as $indice => $leitura): ?>
                <?php $c = $retroCap++; ?>
                <article class="pc-retro__chapter pc-retro__chapter--scroll pc-retro__chapter--reading"
                         data-chapter="<?= $c ?>" aria-roledescription="capítulo"
                         aria-labelledby="retro-titulo-leitura-<?= $indice ?>" hidden
                         style="--reading:<?= e($leitura['cor']) ?>">
                    <span class="pc-retro__ghost" aria-hidden="true"><?= str_pad((string) ($c + 1), 2, '0', STR_PAD_LEFT) ?></span>

                    <div class="pc-retro__reading-top" data-reveal>
                        <span class="pc-retro__eyebrow">
                            <?= e(t('retro.reading.eyebrow', ['n' => $indice + 1])) ?>
                            · <?= e($leitura['eixo']) ?>
                        </span>
                        <span class="pc-retro__forca"><?= e($leitura['forca']) ?></span>
                    </div>

                    <h2 class="pc-retro__display" id="retro-titulo-leitura-<?= $indice ?>" data-reveal style="--d:.06s">
                        <?= e($leitura['manchete']) ?>
                    </h2>

                    <p class="pc-retro__lead" data-reveal style="--d:.14s"><?= e($leitura['corpo']) ?></p>

                    <div class="pc-retro__films" data-reveal style="--d:.22s">
                        <span class="pc-retro__films-label"><?= e(t('retro.reading.matches')) ?></span>
                        <div class="pc-filme-row pc-filme-row--retro">
                            <?php $filmeVariante = 'chip'; ?>
                            <?php foreach ($leitura['combina'] as $filme): ?>
                                <?php require __DIR__ . '/../card-filme.php'; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <p class="pc-retro__cansa" data-reveal style="--d:.3s">
                        <span><?= e(t('retro.reading.tired')) ?></span>
                        <?= e($leitura['cansa']) ?>
                    </p>

                    <?php if ($leitura['espelho'] !== null): ?>
                        <p class="pc-retro__mirror-line" data-reveal style="--d:.36s">
                            <i aria-hidden="true" style="background:<?= e($leitura['cor']) ?>"></i>
                            <?= e(t('retro.reading.mirror', [
                                'nome' => $leitura['espelho']['nome'],
                                'obra' => $leitura['espelho']['obra'],
                            ])) ?>
                        </p>
                    <?php endif; ?>

                    <span class="pc-retro__technical" data-reveal style="--d:.42s">
                        <?php if ($leitura['pct'] !== null): ?>
                            <?= e(t('retro.reading.technical', [
                                'eixo' => $leitura['eixo'],
                                'polo' => $leitura['polo'],
                                'pct' => numero((float) $leitura['pct'], 0),
                            ])) ?>
                        <?php else: ?>
                            <?= e(t('retro.reading.technical_neutral', [
                                'eixo' => $leitura['eixo'],
                                'polo' => $leitura['polo'],
                            ])) ?>
                        <?php endif; ?>
                    </span>
                </article>
            <?php endforeach; ?>

            <?php $c = $retroCap++; ?>
            <article class="pc-retro__chapter pc-retro__chapter--scroll" data-chapter="<?= $c ?>" aria-roledescription="capítulo"
                     aria-labelledby="retro-titulo-espelho" hidden>
                <span class="pc-retro__ghost" aria-hidden="true"><?= str_pad((string) ($c + 1), 2, '0', STR_PAD_LEFT) ?></span>
                <span class="pc-retro__eyebrow" data-reveal><?= e(t('retro.mirror.eyebrow')) ?></span>
                <h2 class="pc-retro__display" id="retro-titulo-espelho" data-reveal style="--d:.06s">
                    <?= e(t('retro.mirror.title')) ?>
                </h2>
                <p class="pc-retro__lead" data-reveal style="--d:.14s"><?= e(t('retro.mirror.lead')) ?></p>

                <?php if ($retroPrincipal !== null): ?>
                    <?php
                    $retroFoto = (string) ($retroPrincipal['ator_foto_url'] ?? '');
                    $retroFotoProxy = img_proxy($retroFoto !== '' ? $retroFoto : (string) $retroPrincipal['imagem_url']);
                    $retroCenaProxy = img_proxy((string) $retroPrincipal['imagem_url']);
                    ?>
                    <div class="pc-retro__mirror" data-reveal style="--d:.2s">
                        <?php if ($retroCenaProxy !== ''): ?>
                            <span class="pc-retro__mirror-bg" style="background-image:url('<?= e($retroCenaProxy) ?>')" aria-hidden="true"></span>
                        <?php endif; ?>

                        <span class="pc-retro__portrait<?= $retroFotoProxy !== '' ? ' has-photo' : '' ?>"
                              <?php if ($retroFotoProxy !== ''): ?>style="background-image:url('<?= e($retroFotoProxy) ?>')" role="img" aria-label="<?= e($retroPrincipal['nome']) ?>"<?php endif; ?>>
                            <?php if ($retroFotoProxy === ''): ?>
                                <?= e(iniciais((string) $retroPrincipal['nome'])) ?>
                            <?php endif; ?>
                        </span>

                        <h3 class="pc-retro__mirror-name"><?= e($retroPrincipal['nome']) ?></h3>
                        <p class="pc-retro__mirror-work">
                            <?= e($retroPrincipal['obra_origem']) ?><?= $retroPrincipal['ano'] !== null ? ' · ' . (int) $retroPrincipal['ano'] : '' ?>
                        </p>

                        <?php if (!empty($retroPrincipal['bio'])): ?>
                            <p class="pc-retro__mirror-bio"><?= e((string) $retroPrincipal['bio']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($retroPrincipal['frase'])): ?>
                            <blockquote class="pc-retro__mirror-quote">
                                “<?= e((string) $retroPrincipal['frase']) ?>”
                                <cite>
                                    <?= e($retroPrincipal['nome']) ?>, <?= e($retroPrincipal['obra_origem']) ?><?= $retroPrincipal['ano'] !== null ? ' (' . (int) $retroPrincipal['ano'] . ')' : '' ?>
                                </cite>
                            </blockquote>
                        <?php endif; ?>

                        <div class="pc-retro__mirror-foot">
                            <span class="pc-retro__affinity"><?= pct($retroPrincipal['afinidade']) ?></span>
                            <span><?= e(t('retro.mirror.affinity_label')) ?></span>
                        </div>

                        <button class="pc-retro__ghost-btn" type="button"
                                data-quickview="<?= json_attr($retroQuickPersonagem($retroPrincipal)) ?>">
                            <?= e(t('retro.mirror.open')) ?>
                            <?= icon('arrow-right', 15) ?>
                        </button>
                    </div>
                <?php else: ?>
                    <p class="pc-retro__lead"><?= e(t('admin.no_data')) ?></p>
                <?php endif; ?>
            </article>

            <?php $c = $retroCap++; ?>
            <article class="pc-retro__chapter" data-chapter="<?= $c ?>" aria-roledescription="capítulo"
                     aria-labelledby="retro-titulo-espelhos" hidden>
                <span class="pc-retro__ghost" aria-hidden="true"><?= str_pad((string) ($c + 1), 2, '0', STR_PAD_LEFT) ?></span>
                <span class="pc-retro__eyebrow" data-reveal><?= e(t('retro.mirrors.eyebrow')) ?></span>
                <h2 class="pc-retro__display" id="retro-titulo-espelhos" data-reveal style="--d:.06s">
                    <?= e(t('retro.mirrors.title')) ?>
                </h2>
                <p class="pc-retro__lead" data-reveal style="--d:.14s"><?= e(t('retro.mirrors.lead')) ?></p>

                <div class="pc-retro__mirrors">
                    <?php foreach ($retroOutros as $indice => $personagem): ?>
                        <?php
                        $foto = (string) ($personagem['ator_foto_url'] ?? '');
                        $fotoProxy = img_proxy($foto !== '' ? $foto : (string) $personagem['imagem_url']);
                        ?>
                        <button class="pc-retro__mini" type="button"
                                style="--d:<?= 0.2 + ($indice * 0.06) ?>s" data-reveal
                                data-quickview="<?= json_attr($retroQuickPersonagem($personagem)) ?>">
                            <span class="pc-retro__mini-photo<?= $fotoProxy !== '' ? ' has-photo' : '' ?>"
                                  <?php if ($fotoProxy !== ''): ?>style="background-image:url('<?= e($fotoProxy) ?>')" aria-hidden="true"<?php endif; ?>>
                                <?php if ($fotoProxy === ''): ?>
                                    <?= e(iniciais((string) $personagem['nome'])) ?>
                                <?php endif; ?>
                            </span>
                            <b><?= e(nome_curto((string) $personagem['nome'])) ?></b>
                            <small><?= e($personagem['obra_origem']) ?></small>
                            <span class="pc-retro__mini-pct"><?= e(t('retro.mirrors.affinity', ['pct' => numero($personagem['afinidade'], 0)])) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php if ($retroOutros === []): ?>
                    <p class="pc-retro__note pc-retro__note--center"><?= e(t('admin.no_data')) ?></p>
                <?php endif; ?>
            </article>

            <?php $c = $retroCap++; ?>
            <article class="pc-retro__chapter" data-chapter="<?= $c ?>" aria-roledescription="capítulo"
                     aria-labelledby="retro-titulo-ancoras" hidden>
                <span class="pc-retro__ghost" aria-hidden="true"><?= str_pad((string) ($c + 1), 2, '0', STR_PAD_LEFT) ?></span>
                <span class="pc-retro__eyebrow" data-reveal><?= e(t('retro.anchors.eyebrow')) ?></span>
                <h2 class="pc-retro__display" id="retro-titulo-ancoras" data-reveal style="--d:.06s">
                    <?= e(t('retro.anchors.title')) ?>
                </h2>
                <p class="pc-retro__lead" data-reveal style="--d:.14s"><?= e(t('retro.anchors.lead')) ?></p>

                <div class="pc-retro__anchors">
                    <?php foreach ([
                        ['item' => $retroDiretor, 'tipo' => 'diretor', 'rotulo' => t('retro.anchors.director')],
                        ['item' => $retroEstudio, 'tipo' => 'estudio', 'rotulo' => t('retro.anchors.studio')],
                    ] as $indice => $bloco): ?>
                        <?php if ($bloco['item'] === null): ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <?php
                        $retroAncora = $bloco['item'];
                        $retroPoster = img_proxy((string) $retroAncora['imagem_url']);
                        ?>
                        <button class="pc-retro__anchor" type="button"
                                style="--d:<?= 0.2 + ($indice * 0.08) ?>s" data-reveal
                                data-quickview="<?= json_attr($retroQuickAncora($retroAncora, $bloco['tipo'])) ?>">
                            <span class="pc-retro__anchor-photo<?= $retroPoster !== '' ? ' has-photo' : '' ?><?= $bloco['tipo'] === 'estudio' ? ' is-logo' : '' ?><?= $retroPoster === '' ? ' is-seal' : '' ?>"
                                  <?php if ($retroPoster !== ''): ?>style="background-image:url('<?= e($retroPoster) ?>')" aria-hidden="true"<?php endif; ?>>
                                <?php if ($retroPoster === ''): ?>
                                    <span class="pc-retro__anchor-seal" aria-hidden="true">
                                        <b><?= e(iniciais((string) $retroAncora['nome'])) ?></b>
                                        <small><?= e($bloco['tipo'] === 'estudio' ? t('retro.anchors.studio') : t('retro.anchors.director')) ?></small>
                                    </span>
                                <?php endif; ?>
                            </span>
                            <span class="pc-retro__anchor-role"><?= e($bloco['rotulo']) ?></span>
                            <b class="pc-retro__anchor-name"><?= e($retroAncora['nome']) ?></b>
                            <?php if (!empty($retroAncora['obras'])): ?>
                                <small class="pc-retro__anchor-works">
                                    <?= e(implode(' · ', array_map(
                                        static fn (array $obra): string => (string) $obra['titulo']
                                            . ($obra['ano'] !== null ? ' (' . (int) $obra['ano'] . ')' : ''),
                                        array_slice($retroAncora['obras'], 0, 2)
                                    ))) ?>
                                </small>
                            <?php endif; ?>
                            <span class="pc-retro__affinity"><?= pct($retroAncora['afinidade']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php if ($retroDiretor === null && $retroEstudio === null): ?>
                    <p class="pc-retro__lead" data-reveal style="--d:.2s"><?= e(t('admin.no_data')) ?></p>
                <?php endif; ?>
            </article>

            <?php $c = $retroCap++; ?>
            <article class="pc-retro__chapter pc-retro__chapter--scroll" data-chapter="<?= $c ?>" data-retro-critic
                     aria-roledescription="capítulo" aria-labelledby="retro-titulo-critica" hidden>
                <span class="pc-retro__ghost" aria-hidden="true"><?= str_pad((string) ($c + 1), 2, '0', STR_PAD_LEFT) ?></span>
                <span class="pc-retro__eyebrow" data-reveal><?= e(t('retro.critic.eyebrow')) ?></span>
                <h2 class="pc-retro__display" id="retro-titulo-critica" data-reveal style="--d:.06s">
                    <?= e(t('retro.critic.title')) ?>
                </h2>
                <p class="pc-retro__lead" data-reveal style="--d:.14s">
                    <?= e($temGroq ? t('retro.critic.lead') : t('retro.critic.fallback_lead')) ?>
                </p>

                <p class="pc-retro__critic-frase" data-frase-perfil data-reveal style="--d:.16s">
                    <?= e((string) ($perfil['retrato_salvo']['frase'] ?? $perfil['leituras'][0]['manchete'] ?? t('resultado.portrait_title'))) ?>
                </p>

                <figure class="pc-retro__critic" data-reveal style="--d:.2s">
                    <span class="pc-retro__quote" aria-hidden="true">“</span>

                    <?php if ($retroTextoSalvo !== ''): ?>
                        <blockquote>
                            <?php foreach (preg_split('/\n{2,}/', $retroTextoSalvo) as $paragrafo): ?>
                                <p><?= e($paragrafo) ?></p>
                            <?php endforeach; ?>
                        </blockquote>
                    <?php elseif ($temGroq): ?>
                        <blockquote data-retrato data-retrato-lazy data-codigo="<?= e($codigo) ?>"
                                    data-erro="<?= e(t('resultado.critic_error')) ?>">
                            <div data-retrato-texto>
                                <p class="pc-retro__loading"><?= e(t('resultado.critic_loading')) ?></p>
                            </div>
                        </blockquote>
                    <?php else: ?>
                        <blockquote>
                            <?php foreach ($perfil['retrato'] as $item): ?>
                                <p><?= e($item['texto']) ?></p>
                            <?php endforeach; ?>
                        </blockquote>
                    <?php endif; ?>

                    <figcaption>
                        <?= e($temGroq ? t('retro.critic.note') : t('resultado.portrait_subtitle')) ?>
                    </figcaption>
                </figure>
            </article>

            <?php $c = $retroCap++; ?>
            <article class="pc-retro__chapter pc-retro__chapter--scroll" data-chapter="<?= $c ?>" aria-roledescription="capítulo"
                     aria-labelledby="retro-titulo-alem" hidden>
                <span class="pc-retro__ghost" aria-hidden="true"><?= str_pad((string) ($c + 1), 2, '0', STR_PAD_LEFT) ?></span>
                <span class="pc-retro__eyebrow" data-reveal><?= e(t('retro.insights.eyebrow')) ?></span>
                <h2 class="pc-retro__display" id="retro-titulo-alem" data-reveal style="--d:.06s">
                    <?= e(t('retro.insights.title')) ?>
                </h2>
                <p class="pc-retro__lead" data-reveal style="--d:.14s"><?= e(t('retro.insights.lead')) ?></p>

                <div class="pc-retro__insights">
                    <?php if ($retroInsights['decada'] !== null): ?>
                        <div class="pc-retro__insight" data-reveal style="--d:.2s">
                            <span class="pc-retro__insight-label"><?= e(t('retro.insights.decada_label')) ?></span>
                            <div class="pc-retro__insight-number"><?= e($retroInsights['decada']['label']) ?></div>
                            <?php if ($retroInsights['decada']['epiteto'] !== ''): ?>
                                <p class="pc-retro__insight-epiteto"><?= e($retroInsights['decada']['epiteto']) ?></p>
                            <?php endif; ?>
                            <p class="pc-retro__insight-line"><?= e($retroInsights['decada']['descricao']) ?></p>
                            <?php if ($retroInsights['decada']['curiosidade'] !== ''): ?>
                                <p class="pc-retro__insight-curio"><?= e($retroInsights['decada']['curiosidade']) ?></p>
                            <?php endif; ?>

                            <?php if ($retroInsights['decada']['marcos'] !== []): ?>
                                <span class="pc-retro__insight-sublabel"><?= e(t('retro.insights.decada_marks')) ?></span>
                                <div class="pc-filme-row pc-retro__marks">
                                    <?php $filmeVariante = 'poster'; ?>
                                    <?php foreach ($retroInsights['decada']['marcos'] as $filme): ?>
                                        <?php require __DIR__ . '/../card-filme.php'; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($retroInsights['oposto'] !== null): ?>
                        <?php $oposto = $retroInsights['oposto']; ?>
                        <div class="pc-retro__insight" data-reveal style="--d:.3s">
                            <span class="pc-retro__insight-label"><?= e(t('retro.insights.oposto_label')) ?></span>
                            <div class="pc-retro__insight-name"><?= e($oposto['nome']) ?></div>
                            <p class="pc-retro__insight-line">
                                <?= e(t('retro.insights.oposto_meta', [
                                    'tipo' => t('resultado.types.' . $oposto['tipo']),
                                    'obra' => $oposto['obra'],
                                ])) ?>
                            </p>
                            <p class="pc-retro__insight-line">
                                <?= e(t('retro.insights.oposto_cta', ['obra' => $oposto['obra']])) ?>
                            </p>
                            <span class="pc-retro__insight-meta">
                                <?= e(t('retro.insights.oposto_affinity', ['pct' => numero($oposto['afinidade'], 0)])) ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if ($retroInsights['secundario'] !== null): ?>
                        <div class="pc-retro__insight" data-reveal style="--d:.4s">
                            <span class="pc-retro__insight-label"><?= e(t('retro.insights.secundario_label')) ?></span>
                            <div class="pc-retro__insight-name"><?= e($retroInsights['secundario']['titulo']) ?></div>
                            <p class="pc-retro__insight-line">
                                <?= e(t('resultado.insights.secundario_text', [
                                    'pct' => numero($retroInsights['secundario']['compatibilidade'], 0),
                                ])) ?>
                            </p>
                            <p class="pc-retro__insight-line"><?= e((string) $retroInsights['secundario']['descricao']) ?></p>

                            <?php if (!empty($retroInsights['secundario']['polos'])): ?>
                                <div class="pc-retro__traits">
                                    <?php foreach ($retroInsights['secundario']['polos'] as $polo): ?>
                                        <span class="pc-retro__trait" style="--trait:<?= e($polo['cor']) ?>">
                                            <?= e($polo['eixo']) ?> · <?= e($polo['polo']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (
                        $retroInsights['decada'] === null
                        && $retroInsights['oposto'] === null
                        && $retroInsights['secundario'] === null
                    ): ?>
                        <p class="pc-retro__lead"><?= e(t('admin.no_data')) ?></p>
                    <?php endif; ?>
                </div>
            </article>

            <?php $c = $retroCap++; ?>
            <article class="pc-retro__chapter pc-retro__chapter--scroll" data-chapter="<?= $c ?>" aria-roledescription="capítulo"
                     aria-labelledby="retro-titulo-final" hidden>
                <span class="pc-retro__ghost" aria-hidden="true"><?= str_pad((string) ($c + 1), 2, '0', STR_PAD_LEFT) ?></span>
                <span class="pc-retro__eyebrow" data-reveal><?= e(t('retro.share.eyebrow')) ?></span>
                <h2 class="pc-retro__display" id="retro-titulo-final" data-reveal style="--d:.06s">
                    <?= e(t('retro.share.title')) ?>
                </h2>
                <p class="pc-retro__lead" data-reveal style="--d:.14s"><?= e(t('retro.share.lead')) ?></p>

                <a class="pc-retro__inline-link" href="<?= e($retroUrlCompleta) ?>" data-retro-close data-reveal style="--d:.18s">
                    <?= e(t('retro.full')) ?>
                    <?= icon('arrow-right', 13) ?>
                </a>

                <div class="pc-retro__share-card" data-story-side data-reveal style="--d:.2s">
                    <?php require __DIR__ . '/story-card.php'; ?>

                    <button class="pc-btn pc-btn--sm pc-retro__share-btn" type="button"
                            data-story-share
                            data-codigo="<?= e($codigo) ?>"
                            data-msg-preparing="<?= e(t('resultado.story_preparing')) ?>"
                            data-msg-error="<?= e(t('resultado.story_error')) ?>"
                            data-msg-download="<?= e(t('resultado.story_download')) ?>">
                        <?= icon('share', 15) ?>
                        <span><?= e(t('resultado.story_share')) ?></span>
                    </button>
                    <p class="pc-retro__note pc-retro__note--center" data-story-note><?= e(t('resultado.story_note')) ?></p>
                </div>

                <div class="pc-retro__final-actions" data-reveal style="--d:.28s">
                    <div class="pc-retro__code-row">
                        <span class="pc-retro__code-label"><?= e(t('retro.share.code')) ?></span>
                        <button class="pc-retro__code" type="button" data-copy="<?= e($codigo) ?>" data-copy-ok="<?= e(t('ui.copied')) ?>">
                            <?= e($codigo) ?>
                            <?= icon('copy', 14) ?>
                        </button>
                    </div>

                    <a class="pc-btn pc-btn--sm pc-btn--light" href="<?= e($retroUrlCompleta) ?>" data-retro-close>
                        <?= e(t('retro.full')) ?>
                        <?= icon('arrow-right', 15) ?>
                    </a>

                    <a class="pc-btn pc-btn--sm pc-btn--ghost pc-btn--on-dark" href="<?= url('quiz.php') ?>">
                        <?= icon('refresh', 15) ?>
                        <?= e(t('retro.share.retake')) ?>
                    </a>
                </div>

                <form class="pc-retro__compare" action="<?= url('sinergia.php') ?>" method="get" data-reveal style="--d:.34s">
                    <input type="hidden" name="a" value="<?= e($codigo) ?>">
                    <label class="pc-retro__compare-label" for="retro-outro"><?= e(t('retro.share.compare')) ?></label>
                    <div class="pc-retro__compare-row">
                        <input class="pc-retro__input" id="retro-outro" name="b" type="text" maxlength="8" required
                               data-code-input placeholder="<?= e(t('retro.share.compare_placeholder')) ?>">
                        <button class="pc-btn pc-btn--sm pc-btn--light" type="submit">
                            <?= e(t('retro.share.compare_cta')) ?>
                        </button>
                    </div>
                </form>
            </article>
        </div>

        <footer class="pc-retro__foot">
            <button class="pc-retro__arrow" type="button" data-retro-prev aria-label="<?= e(t('retro.prev')) ?>" disabled>
                <?= icon('arrow-left', 20) ?>
            </button>

            <span class="pc-retro__count" data-retro-count
                  data-template="<?= e(t('retro.count', ['atual' => '{atual}', 'total' => '{total}'])) ?>"><?= e(t('retro.count', ['atual' => 1, 'total' => $retroTotal])) ?></span>

            <button class="pc-retro__arrow" type="button" data-retro-next aria-label="<?= e(t('retro.next')) ?>">
                <?= icon('arrow-right', 20) ?>
            </button>
        </footer>
    </div>
</section>
