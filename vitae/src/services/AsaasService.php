<?php
/**
 * Asaas Service
 * Responsável por toda a comunicação com a API do Asaas
 */

class AsaasService {
    
    private $apiKey;
    private $baseUrl;
    private $config;

    public function __construct() {
        // Carrega configurações
        $this->config = require __DIR__ . '/../config/asaas.php';
        
        $env = $this->config['environment'];
        $this->apiKey = $this->config[$env]['api_key'];
        $this->baseUrl = $this->config[$env]['base_url'];
    }

    /**
     * Cria ou Atualiza um Cliente no Asaas
     * Verifica se já existe um asaas_id no banco, se não cria no asaas e salva
     * Se já existe, atualiza o CPF se fornecido
     */
    public function createOrGetCustomer($user_id, $name, $email, $cpfCnpj = null, $phone = null) {
        global $pdo;

        // 1. Verifica no banco local
        $stmt = $pdo->prepare("SELECT asaas_id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && !empty($user['asaas_id'])) {
            // Cliente já existe, mas precisamos garantir que tem CPF
            if ($cpfCnpj) {
                $this->updateCustomerCpf($user['asaas_id'], $cpfCnpj);
            }
            return $user['asaas_id'];
        }

        // 2. Se não tem ID local, cria no Asaas
        $data = [
            'name' => $name,
            'email' => $email,
            'externalReference' => (string) $user_id
        ];
        
        if ($cpfCnpj) $data['cpfCnpj'] = $cpfCnpj;
        if ($phone) $data['mobilePhone'] = $phone;

        $response = $this->request('/customers', 'POST', $data);

        if (isset($response['id'])) {
            // 3. Salva o ID retornado no banco local
            $update = $pdo->prepare("UPDATE users SET asaas_id = ? WHERE id = ?");
            $update->execute([$response['id'], $user_id]);
            
            return $response['id'];
        }

        throw new Exception("Erro ao criar cliente no Asaas: " . json_encode($response));
    }

    /**
     * Atualiza o CPF de um cliente existente no Asaas
     */
    private function updateCustomerCpf($customerId, $cpfCnpj) {
        $data = ['cpfCnpj' => $cpfCnpj];
        $response = $this->request("/customers/$customerId", 'PUT', $data);
        
        if (isset($response['errors'])) {
            error_log("[Asaas] Erro ao atualizar CPF: " . json_encode($response));
        }
        
        return $response;
    }

    /**
     * Cria uma Cobrança de Assinatura (Recorrente)
     */
    public function createSubscription($customerId) {
        $plan = $this->config['plan_pro'];

        $data = [
            'customer' => $customerId,
            'billingType' => 'UNDEFINED', // Deixa o cliente escolher (Pix, Boleto ou Cartão)
            'value' => $plan['promo_value'],
            'nextDueDate' => date('Y-m-d', strtotime('+1 day')), // Vence amanhã
            'cycle' => $plan['cycle'], // MONTHLY
            'description' => $plan['description'],
            'maxPayments' => $plan['promo_months'], // Encerra o preço promocional após X meses (opcional)
            //"externalReference" => "plan_pro_promo"
        ];

        // Se quiser cobrar no cartão direto, precisaria dos dados do cartão tokenizados. 
        // Para simplificar, vamos criar um Link de Pagamento da Assinatura ou Cobrança Avulsa.
        // O Endpoint de Assinatura retorna um ID, mas não um "link direto" de checkout único como o Stripe.
        // Estratégia Melhor para MVP: Criar uma Cbrança Avulsa Única para ativar, e depois automatizar.
        // MAS o Asaas tem Checkout de Assinatura? Sim, via Link de Pagamento vinculado à assinatura.
        
        // Vamos usar a estratégia de COBRANÇA AVULSA para o primeiro pagamento (garante acesso imediato)
        // e depois o sistema gera as próximas.
        // OU: Usar o endpoint de Assinatura e pegar o link da fatura gerada.
        
        $response = $this->request('/subscriptions', 'POST', $data);
        
        if (isset($response['id'])) {
            // A assinatura foi criada. Agora precisamos pegar a primeira cobrança dela para o cliente pagar.
            // O Asaas gera a cobrança automaticamente. Vamos listar as cobranças dessa assinatura.
            return $this->getFirstPaymentUrl($response['id']);
        }

        throw new Exception("Erro ao criar assinatura: " . json_encode($response));
    }

    private function getFirstPaymentUrl($subscriptionId) {
        // Busca as cobranças dessa assinatura
        $response = $this->request("/subscriptions/$subscriptionId/payments", 'GET');
        
        if (isset($response['data']) && count($response['data']) > 0) {
            // Retorna a URL da fatura (invoiceUrl) da primeira cobrança
            return $response['data'][0]['invoiceUrl']; // O cliente paga aqui e ativa
        }
        
        throw new Exception("Assinatura criada, mas nenhuma cobrança foi gerada ainda.");
    }

    /**
     * Função Genérica de Requisição cURL
     */
    private function request($endpoint, $method = 'GET', $data = []) {
        $ch = curl_init();
        
        $url = $this->baseUrl . $endpoint;
        $headers = [
            'Content-Type: application/json',
            'access_token: ' . $this->apiKey,
            'User-Agent: CurriculoVitaePro/1.0'
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            // Habilita verificação SSL apenas em produção para segurança
            CURLOPT_SSL_VERIFYPEER => ($this->config['environment'] === 'production')
        ];

        if ($method === 'POST' || $method === 'PUT') {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $options);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        $decoded = json_decode($result, true);
        
        if ($httpCode >= 400) {
            // Loga erro para debug
            error_log("[Asaas API Error] Endpoint: $endpoint | Code: $httpCode | Resp: " . $result);
        }

        return $decoded;
    }
}
