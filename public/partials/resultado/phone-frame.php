<?php

declare(strict_types=1);

/**
 * Moldura de iPhone 14 Pro (devices.css, MIT) em volta do card vertical.
 * Precisa das mesmas variáveis de story-card.php no escopo.
 */
?>
<div class="pc-phone-stage">
    <div class="device device-iphone-14-pro device-black pc-phone-device">
        <div class="device-frame">
            <div class="device-screen pc-phone-screen">
                <div class="pc-phone-scaler">
                    <?php require __DIR__ . '/story-card.php'; ?>
                </div>
            </div>
        </div>
        <div class="device-stripe"></div>
        <div class="device-sensors"></div>
        <div class="device-btns"></div>
        <div class="device-power"></div>
    </div>
</div>
