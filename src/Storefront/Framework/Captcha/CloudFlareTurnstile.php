<?php declare(strict_types=1);

namespace Melv\Turnstile\Storefront\Framework\Captcha;

use GuzzleHttp\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Shopware\Core\Framework\Feature;
use Shopware\Storefront\Framework\Captcha\AbstractCaptcha;
use Symfony\Component\HttpFoundation\Request;

class CloudFlareTurnstile extends AbstractCaptcha
{
    public const CAPTCHA_NAME = 'cloudFlareTurnstile';
    //Injected by CF in form
    public const CAPTCHA_REQUEST_PARAMETER = 'cf-turnstile-response';
    private const CLOUDFLARE_CAPTCHA_VERIFY_ENDPOINT = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    private ClientInterface $client;

    /**
     * @internal
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(Request $request /* , array $captchaConfig */): bool
    {
        if (\func_num_args() < 2 || !\is_array(func_get_arg(1))) {
            Feature::triggerDeprecationOrThrow(
                'v6.5.0.0',
                'Method `isValid()` in `CloudFlareTurnstile` expects passing the `$captchaConfig` as array as the second parameter in v6.5.0.0.'
            );
        }

        if (!$request->get(self::CAPTCHA_REQUEST_PARAMETER)) {
            return false;
        }

        $captchaConfig = \func_get_args()[1] ?? [];

        $secretKey = !empty($captchaConfig['config']['secretKey']) ? $captchaConfig['config']['secretKey'] : null;

        if (!\is_string($secretKey)) {
            return false;
        }

        try {
            $response = $this->client->request('POST', self::CLOUDFLARE_CAPTCHA_VERIFY_ENDPOINT, [
                'form_params' => [
                    'secret' => $secretKey,
                    'response' => $request->get(self::CAPTCHA_REQUEST_PARAMETER),
                    'remoteip' => $request->getClientIp(),
                ],
            ]);

            $responseRaw = $response->getBody()->getContents();
            $response = json_decode($responseRaw, true);

            return $response && (bool) $response['success'];
        } catch (ClientExceptionInterface $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::CAPTCHA_NAME;
    }
}
