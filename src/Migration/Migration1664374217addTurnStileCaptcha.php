<?php declare(strict_types=1);

namespace Melv\Turnstile\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1664374217addTurnStileCaptcha extends MigrationStep
{
    private const CONFIG_KEY = 'core.basicInformation.activeCaptchasV2';

    private array $captchaItems = [
        'honeypot' => [
            'name' => 'Honeypot',
            'isActive' => false,
        ],
        'basicCaptcha' => [
            'name' => 'basicCaptcha',
            'isActive' => false,
        ],
        'googleReCaptchaV2' => [
            'name' => 'googleReCaptchaV2',
            'isActive' => false,
            'config' => [
                'siteKey' => '',
                'secretKey' => '',
                'invisible' => false,
            ],
        ],
        'googleReCaptchaV3' => [
            'name' => 'googleReCaptchaV3',
            'isActive' => false,
            'config' => [
                'siteKey' => '',
                'secretKey' => '',
                'thresholdScore' => 0.5,
            ],
        ],
        'cloudFlareTurnstile' => [
            'name' => 'cloudFlareTurnstile',
            'isActive' => false,
            'config' => [
                'siteKey' => '',
                'secretKey' => ''
            ]
        ]
    ];


    public function getCreationTimestamp(): int
    {
        return 1664374217;
    }

    public function update(Connection $connection): void
    {
        //TODO: Can we prevent overriding current CAPTCHA settings?
        $configId = $connection->fetchColumn('SELECT id FROM system_config WHERE configuration_key = :key AND updated_at IS NULL', [
            'key' => self::CONFIG_KEY,
        ]);

        if (!$configId) {
            return;
        }

        $connection->update('system_config', [
            'configuration_key' => self::CONFIG_KEY,
            'configuration_value' => json_encode(['_value' => $this->captchaItems]),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => $configId,
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
