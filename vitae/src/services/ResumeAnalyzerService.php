<?php
namespace Services;

class ResumeAnalyzerService {
    
    /**
     * Analisa o currículo e retorna um score e sugestões.
     */
    public function analyze(array $data): array {
        $score = 100;
        $suggestions = [];
        $strengths = [];

        // 1. Verificação de Dados Pessoais (Impacto Crítico)
        if (empty($data['full_name'])) {
            $score -= 20;
            $suggestions[] = ['type' => 'critical', 'message' => 'O nome completo é obrigatório.'];
        }

        if (empty($data['contact_email'])) {
            $score -= 15;
            $suggestions[] = ['type' => 'critical', 'message' => 'Adicione um e-mail de contato profissional.'];
        } elseif ($this->isUnprofessionalEmail($data['contact_email'])) {
            $score -= 5;
            $suggestions[] = ['type' => 'improvement', 'message' => 'Considere usar um e-mail mais profissional (ex: nome.sobrenome@gmail.com).'];
        }

        if (empty($data['phone'])) {
            $score -= 10;
            $suggestions[] = ['type' => 'critical', 'message' => 'O telefone é essencial para contato rápido.'];
        }

        if (empty($data['links']) || stripos($data['links'], 'linkedin') === false) {
            $score -= 5;
            $suggestions[] = ['type' => 'improvement', 'message' => 'Adicionar o LinkedIn aumenta suas chances em 40%.'];
        }

        // 2. Resumo Profissional (Impacto Alto)
        $summary = $data['summary'] ?? '';
        $wordCount = str_word_count($summary);
        
        if (empty($summary)) {
            $score -= 15;
            $suggestions[] = ['type' => 'critical', 'message' => 'Escreva um Resumo Profissional para destacar seus objetivos.'];
        } elseif ($wordCount < 30) {
            $score -= 5;
            $suggestions[] = ['type' => 'warning', 'message' => 'Seu resumo está muito curto. Tente escrever entre 30 e 100 palavras.'];
        } elseif ($wordCount > 150) {
            $score -= 5;
            $suggestions[] = ['type' => 'warning', 'message' => 'Seu resumo está muito longo. Recrutadores gastam segundos lendo. Seja conciso.'];
        } else {
            $strengths[] = 'Tamanho do resumo ideal.';
        }

        // 3. Experiência Profissional (Impacto Crítico)
        $experiences = $data['experiences'] ?? [];
        if (empty($experiences)) {
            $score -= 20;
            $suggestions[] = ['type' => 'critical', 'message' => 'Adicione pelo menos uma experiência profissional.'];
        } else {
            $strengths[] = 'Seção de experiência preenchida.';
            
            foreach ($experiences as $exp) {
                if (empty($exp['desc'])) continue;
                
                // Análise de Verbos de Ação (Simplificada para PT-BR)
                if (!$this->hasActionVerbs($exp['desc'])) {
                    $suggestions[] = ['type' => 'improvement', 'message' => "Na experiência em '{$exp['company']}', tente começar frases com verbos de ação (ex: Liderei, Desenvolvi, Aumentei)."];
                    $score -= 2; // Penalidade leve por item
                    break; // Avisa só uma vez
                }

                // Verifica métricas (números)
                if (!preg_match('/[0-9]+%?/', $exp['desc'])) {
                    $suggestions[] = ['type' => 'tip', 'message' => "Na experiência em '{$exp['company']}', tente incluir números ou resultados mensuráveis (ex: 'Aumentei vendas em 20%')."];
                }
            }
        }

        // 4. Skills (ATS Optimization)
        if (empty($data['skills'])) {
            $score -= 10;
            $suggestions[] = ['type' => 'critical', 'message' => 'Liste suas habilidades técnicas. Isso é crucial para sistemas ATS.'];
        } else {
            $skillCount = count(explode(',', $data['skills']));
            if ($skillCount < 5) {
                $suggestions[] = ['type' => 'improvement', 'message' => 'Tente listar pelo menos 5 habilidades relevantes.'];
            }
        }

        return [
            'score' => max(0, $score),
            'suggestions' => $suggestions,
            'strengths' => $strengths
        ];
    }

    private function isUnprofessionalEmail(string $email): bool {
        // Lógica simples: verifica números excessivos ou palavras "estranhas" (difícil sem IA real, mas vamos tentar heurística)
        $localPart = explode('@', $email)[0];
        if (preg_match('/[0-9]{4,}/', $localPart)) { // Ex: joao123456
            return true;
        }
        return false;
    }

    private function hasActionVerbs(string $text): bool {
        $verbs = ['liderei', 'desenvolvi', 'criei', 'gerenciei', 'aumentei', 'reduzi', 'organizei', 'coordenei', 'implementei', 'lancei', 'melhorei', 'alcancei'];
        $lowerText = strtolower($text);
        foreach ($verbs as $verb) {
            if (stripos($lowerText, $verb) !== false) return true;
        }
        return false;
    }
}
