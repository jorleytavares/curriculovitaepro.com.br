<?php
/**
 * Asaas Payment Gateway Configuration
 * Documentação: https://docs.asaas.com/
 */

return [
    // Ambiente: 'sandbox' ou 'production'
    'environment' => 'production',
    
    // API Keys (obter em: https://www.asaas.com/customerConfigApi/index)
    'sandbox' => [
        'api_key' => '$aact_hmlg_000MzkwODA2MWY2OGM3MWRlMDU2NWM3MzJlNzZmNGZhZGY6OjhkNTMwNWE4LTg1MjEtNGY1ZS04NTY3LTNhMmE3ZjZiNWQwMTo6JGFhY2hfYmJjNzJkMjEtNjdmYS00NTlkLTk4NjItYjlhNTViMzIxMDNk',
        'base_url' => 'https://sandbox.asaas.com/api/v3'
    ],
    'production' => [
        'api_key' => '$aact_prod_000MzkwODA2MWY2OGM3MWRlMDU2NWM3MzJlNzZmNGZhZGY6OjJhZDIyMTFmLTJjMzgtNDMwNS1hYTg1LTFhYmQwODk4YzM3Yjo6JGFhY2hfNWYyMmEwZmItNzkyYS00OGUzLTkzY2YtYWZkMmY1YzI4Y2U2',
        'base_url' => 'https://api.asaas.com/v3'
    ],
    
    // Configurações do Plano PRO
    'plan_pro' => [
        'name' => 'Currículo Vitae PRO',
        'description' => 'Acesso completo: currículos ilimitados, sem marca d\'água, suporte prioritário',
        'value' => 13.99, // Preço normal mensal
        'promo_value' => 6.99, // 50% off nos 6 primeiros meses
        'promo_months' => 6,
        'cycle' => 'MONTHLY',
        'billing_type' => 'UNDEFINED'
    ],
    
    // Webhook Secret (para validar callbacks)
    'webhook_token' => '' // Gerar um token seguro
];
?>
