<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

final class LknPaymentPixForWoocommercePixRedeHelper
{
    /**
     * Obtém as credenciais de um gateway específico
     */
    final public static function get_gateway_credentials()
    {
        $gateway_settings = get_option('woocommerce_lkn_rede_pix_for_woocommerce_settings', array());

        // Verificar se o gateway está habilitado
        if (!isset($gateway_settings['enabled']) || $gateway_settings['enabled'] !== 'yes') {
            return false;
        }

        // Verificar se as credenciais estão configuradas
        $pv = isset($gateway_settings['pv']) ? trim($gateway_settings['pv']) : '';
        $token = isset($gateway_settings['token']) ? trim($gateway_settings['token']) : '';
        $environment = isset($gateway_settings['env']) ? $gateway_settings['env'] : 'test';

        if (empty($pv) || empty($token)) {
            return false;
        }

        return array(
            'pv' => $pv,
            'token' => $token,
            'env' => $environment
        );
    }

    /**
     * Verifica se o token está válido (não expirou)
     */
    final public static function is_rede_oauth_token_valid($cached_token)
    {
        if (!$cached_token || !isset($cached_token['generated_at'])) {
            return false;
        }

        $current_time = time();
        $token_age_minutes = ($current_time - $cached_token['generated_at']) / 60;

        // Token é válido se tem menos de 20 minutos (margem de segurança)
        return $token_age_minutes < 20;
    }

    /**
     * Salva token OAuth2 específico de um gateway no cache
     */
    final public static function cache_rede_oauth_token_for_gateway($token_data, $environment)
    {
        $gateway_id = 'lkn_rede_pix_for_woocommerce';
        $cache_data = array(
            'token' => $token_data['access_token'],
            'expires_in' => $token_data['expires_in'],
            'generated_at' => time(),
            'env' => $environment,
            'gateway_id' => $gateway_id
        );

        // Codifica em base64 para segurança
        $encoded_data = base64_encode(json_encode($cache_data));

        $option_name = 'lkn_rede_oauth_token_' . $gateway_id . '_' . $environment;
        update_option($option_name, $encoded_data);

        return $cache_data;
    }

    /**
     * Recupera token OAuth2 específico de um gateway do cache
     */
    final public static function get_cached_rede_oauth_token_for_gateway($environment)
    {
        $gateway_id = 'lkn_rede_pix_for_woocommerce';
        $option_name = 'lkn_rede_oauth_token_' . $gateway_id . '_' . $environment;
        $cached_data = get_option($option_name, '');

        if (empty($cached_data)) {
            return null;
        }

        // Decodifica do base64
        $decoded_data = json_decode(base64_decode($cached_data), true);

        if (!$decoded_data || !isset($decoded_data['token']) || !isset($decoded_data['generated_at'])) {
            return null;
        }

        return $decoded_data;
    }

    /**
     * Gera token OAuth2 para API Rede v2 usando credenciais específicas de um gateway
     */
    final public static function generate_rede_oauth_token_for_pix()
    {
        $credentials = self::get_gateway_credentials();

        if ($credentials === false) {
            return false;
        }

        $auth = base64_encode($credentials['pv'] . ':' . $credentials['token']);
        $environment = $credentials['env'];

        $oauth_url = $environment === 'production'
            ? 'https://api.userede.com.br/redelabs/oauth2/token'
            : 'https://rl7-sandbox-api.useredecloud.com.br/oauth2/token';

        $oauth_response = wp_remote_post($oauth_url, array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Basic ' . $auth,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => 'grant_type=client_credentials',
            'timeout' => 30
        ));

        if (is_wp_error($oauth_response)) {
            return false;
        }

        $oauth_body = wp_remote_retrieve_body($oauth_response);
        $oauth_data = json_decode($oauth_body, true);

        if (!isset($oauth_data['access_token'])) {
            return false;
        }

        return $oauth_data;
    }

    /**
     * Obtém o token OAuth2 para o gateway PIX Rede
     */
    final public static function get_rede_oauth_token_pix()
    {
        $credentials = self::get_gateway_credentials();

        if ($credentials === false) {
            return null;
        }

        $environment = $credentials['env'];

        // Tenta obter o token do cache
        $cached_token = self::get_cached_rede_oauth_token_for_gateway($environment);

        if ($cached_token && self::is_rede_oauth_token_valid($cached_token)) {
            return $cached_token['token'];
        }

        $token_data = self::generate_rede_oauth_token_for_pix();

        // Se falhou ao gerar novo token
        if ($token_data === false) {
            // Se há um token em cache (mesmo expirado), usa ele como fallback
            if ($cached_token && isset($cached_token['token'])) {
                return $cached_token['token'];
            }

            // Se não há token em cache, retorna null para forçar erro na API
            return null;
        }

        // Salva o novo token no cache
        self::cache_rede_oauth_token_for_gateway($token_data, $environment);
        return $token_data['access_token'];
    }
}
