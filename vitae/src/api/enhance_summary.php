<?php
/**
 * API: Enhance Summary with AI
 * Gera resumos profissionais otimizados para ATS (Applicant Tracking Systems)
 * 
 * LÓGICA INTELIGENTE:
 * 1. Primeiro verifica o CARGO DESEJADO (job_title)
 * 2. Analisa o texto escrito pelo usuário
 * 3. Gera resumo com competências e skills relevantes
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$text = trim($input['text'] ?? '');
$jobTitle = trim($input['job_title'] ?? '');
$name = trim($input['name'] ?? '');

if (empty($text) && empty($jobTitle)) {
    echo json_encode(['success' => false, 'message' => 'Informe o cargo desejado ou escreva algo no resumo']);
    exit;
}

/**
 * BASE DE COMPETÊNCIAS EXPANDIDA
 * Cada área contém: padrões de regex, título, competências comportamentais, competências técnicas e skills
 */
$competencias = [
    
    // ==================== TECNOLOGIA ====================
    
    // WORDPRESS / CMS
    'wordpress|theme|themes|plugin|plugins|woocommerce|elementor|cms|wp|gutenberg|divi|avada' => [
        'titulo' => 'Desenvolvedor WordPress',
        'competencias' => [
            'criação de themes customizados do zero',
            'desenvolvimento de plugins personalizados',
            'customização avançada de templates',
            'otimização de performance e velocidade',
            'configuração de SEO técnico',
            'integração com gateways de pagamento',
            'migração e atualização de sites',
            'implementação de segurança WordPress',
            'desenvolvimento de lojas virtuais WooCommerce',
            'criação de landing pages de alta conversão',
            'manutenção preventiva e corretiva',
            'integração com APIs REST e webhooks'
        ],
        'skills' => ['PHP', 'HTML5', 'CSS3', 'JavaScript', 'jQuery', 'MySQL', 'WordPress Hooks', 'Filters', 'Actions', 'Custom Post Types', 'Custom Fields', 'ACF Pro', 'WooCommerce', 'Elementor Pro', 'Divi Builder', 'Gutenberg Blocks', 'REST API', 'WP-CLI', 'Git', 'Sass/SCSS', 'Bootstrap', 'Tailwind CSS', 'Page Builders', 'Child Themes', 'Multisite', 'BuddyPress', 'bbPress', 'WPML', 'Polylang', 'Yoast SEO', 'RankMath', 'WP Rocket', 'LiteSpeed Cache', 'Cloudflare', 'cPanel', 'Plesk']
    ],
    
    // FULL STACK
    'full.?stack|fullstack|desenvolvedor web|web developer|programador web' => [
        'titulo' => 'Desenvolvedor Full Stack',
        'competencias' => [
            'desenvolvimento de aplicações web completas',
            'arquitetura de sistemas escaláveis',
            'criação de APIs RESTful e GraphQL',
            'desenvolvimento de interfaces responsivas',
            'implementação de autenticação e autorização',
            'otimização de performance front e back-end',
            'testes automatizados e TDD',
            'deploy e CI/CD',
            'containerização e orquestração',
            'modelagem e otimização de banco de dados',
            'integração com serviços de terceiros',
            'mentoria técnica de desenvolvedores junior'
        ],
        'skills' => ['JavaScript', 'TypeScript', 'React', 'Vue.js', 'Angular', 'Node.js', 'Express', 'NestJS', 'PHP', 'Laravel', 'Python', 'Django', 'Flask', 'Java', 'Spring Boot', 'C#', '.NET', 'SQL', 'PostgreSQL', 'MySQL', 'MongoDB', 'Redis', 'Docker', 'Kubernetes', 'AWS', 'Azure', 'GCP', 'Git', 'GitHub Actions', 'Jenkins', 'CI/CD', 'REST APIs', 'GraphQL', 'WebSockets', 'OAuth', 'JWT', 'Microservices', 'Clean Architecture', 'SOLID', 'Design Patterns', 'Agile', 'Scrum', 'Kanban', 'Jira', 'Linux', 'Nginx', 'Apache']
    ],
    
    // FRONTEND
    'frontend|front.?end|react|vue|angular|interface|ui developer|front end' => [
        'titulo' => 'Desenvolvedor Front-end',
        'competencias' => [
            'desenvolvimento de interfaces responsivas e acessíveis',
            'implementação de designs pixel-perfect',
            'componentização e reutilização de código',
            'otimização de Core Web Vitals',
            'consumo e integração de APIs',
            'gerenciamento de estado global',
            'animações e micro-interações',
            'testes unitários e de integração',
            'code review e pair programming',
            'documentação de componentes',
            'acessibilidade WCAG',
            'performance optimization'
        ],
        'skills' => ['HTML5', 'CSS3', 'JavaScript ES6+', 'TypeScript', 'React', 'React Hooks', 'Redux', 'Context API', 'Vue.js', 'Vuex', 'Pinia', 'Angular', 'RxJS', 'Next.js', 'Nuxt.js', 'Gatsby', 'Sass', 'SCSS', 'Less', 'Styled Components', 'CSS Modules', 'Tailwind CSS', 'Bootstrap', 'Material UI', 'Chakra UI', 'Ant Design', 'Webpack', 'Vite', 'Babel', 'ESLint', 'Prettier', 'Jest', 'React Testing Library', 'Cypress', 'Storybook', 'Figma', 'Git', 'npm', 'yarn', 'pnpm', 'PWA', 'Service Workers', 'Web Performance', 'SEO Técnico', 'Responsive Design', 'Mobile First', 'Cross-Browser']
    ],
    
    // BACKEND
    'backend|back.?end|api|servidor|server|node|php|python|java|.net|c#|golang|ruby' => [
        'titulo' => 'Desenvolvedor Back-end',
        'competencias' => [
            'desenvolvimento de APIs escaláveis',
            'arquitetura de microservices',
            'design de banco de dados relacional e NoSQL',
            'implementação de filas e mensageria',
            'autenticação e segurança de APIs',
            'otimização de queries e índices',
            'cache e performance',
            'integração com serviços externos',
            'documentação de APIs (Swagger/OpenAPI)',
            'monitoramento e observabilidade',
            'tratamento de erros e logging',
            'testes automatizados'
        ],
        'skills' => ['Node.js', 'Express', 'NestJS', 'Fastify', 'PHP', 'Laravel', 'Symfony', 'CodeIgniter', 'Python', 'Django', 'Flask', 'FastAPI', 'Java', 'Spring Boot', 'C#', '.NET Core', 'Go', 'Golang', 'Ruby', 'Rails', 'Rust', 'SQL', 'PostgreSQL', 'MySQL', 'SQL Server', 'Oracle', 'MongoDB', 'Redis', 'Elasticsearch', 'RabbitMQ', 'Apache Kafka', 'Docker', 'Kubernetes', 'AWS Lambda', 'Serverless', 'GraphQL', 'gRPC', 'OAuth2', 'JWT', 'SOLID', 'Clean Code', 'DDD', 'TDD', 'CI/CD', 'Linux', 'Shell Script', 'Nginx', 'Apache', 'Prometheus', 'Grafana', 'ELK Stack', 'New Relic', 'Datadog']
    ],
    
    // MOBILE
    'mobile|android|ios|flutter|react native|kotlin|swift|app|aplicativo' => [
        'titulo' => 'Desenvolvedor Mobile',
        'competencias' => [
            'desenvolvimento de apps nativos e híbridos',
            'publicação em App Store e Google Play',
            'integração com APIs REST e GraphQL',
            'implementação de push notifications',
            'armazenamento local e sincronização',
            'autenticação biométrica',
            'geolocalização e mapas',
            'integração com câmera e sensores',
            'otimização de performance e bateria',
            'analytics e crash reporting',
            'testes em múltiplos dispositivos',
            'acessibilidade mobile'
        ],
        'skills' => ['React Native', 'Flutter', 'Dart', 'Kotlin', 'Java Android', 'Swift', 'SwiftUI', 'Objective-C', 'Expo', 'Android Studio', 'Xcode', 'Firebase', 'Firestore', 'Push Notifications', 'FCM', 'APNs', 'SQLite', 'Realm', 'AsyncStorage', 'Redux', 'MobX', 'Provider', 'Riverpod', 'REST APIs', 'GraphQL', 'Retrofit', 'Alamofire', 'CI/CD Mobile', 'Fastlane', 'TestFlight', 'App Center', 'Crashlytics', 'Google Analytics', 'Mixpanel', 'Deep Linking', 'App Clips', 'Widgets', 'WatchOS', 'WearOS']
    ],
    
    // DATA / BI / ANALYTICS
    '\\bdados\\b|\\bdata\\b|\\bbi\\b|business intelligence|analytics|cientista|data science|machine learning|\\bml\\b|\\bia\\b|inteligência artificial|etl|power bi|tableau' => [
        'titulo' => 'Profissional de Dados',
        'competencias' => [
            'análise exploratória de dados',
            'criação de dashboards e relatórios',
            'modelagem preditiva',
            'machine learning e IA',
            'ETL e pipelines de dados',
            'data warehousing',
            'análise estatística',
            'data storytelling',
            'otimização de processos com dados',
            'governança de dados',
            'Big Data processing',
            'A/B testing'
        ],
        'skills' => ['Python', 'Pandas', 'NumPy', 'Scikit-learn', 'TensorFlow', 'PyTorch', 'Keras', 'SQL', 'PostgreSQL', 'MySQL', 'MongoDB', 'Power BI', 'Tableau', 'Looker', 'Metabase', 'Excel Avançado', 'R', 'Spark', 'Hadoop', 'Airflow', 'dbt', 'Snowflake', 'BigQuery', 'Redshift', 'AWS', 'Azure', 'GCP', 'DataBricks', 'Jupyter', 'Google Colab', 'Git', 'Docker', 'APIs', 'Web Scraping', 'Beautiful Soup', 'Selenium', 'NLP', 'Computer Vision', 'Deep Learning', 'MLOps', 'Feature Engineering', 'Data Visualization', 'Estatística', 'Probabilidade']
    ],
    
    // DEVOPS / INFRA / CLOUD
    'devops|cloud|aws|azure|gcp|infraestrutura|infra|sre|site reliability|kubernetes|docker|terraform|ansible' => [
        'titulo' => 'Engenheiro DevOps',
        'competencias' => [
            'automação de infraestrutura',
            'CI/CD pipelines',
            'containerização e orquestração',
            'monitoramento e alertas',
            'gerenciamento de cloud',
            'segurança de infraestrutura',
            'alta disponibilidade e disaster recovery',
            'otimização de custos cloud',
            'Infrastructure as Code',
            'configuration management',
            'incident management',
            'performance tuning'
        ],
        'skills' => ['AWS', 'EC2', 'S3', 'RDS', 'Lambda', 'ECS', 'EKS', 'CloudFormation', 'Azure', 'Azure DevOps', 'GCP', 'Docker', 'Kubernetes', 'Helm', 'Terraform', 'Ansible', 'Puppet', 'Chef', 'Jenkins', 'GitLab CI', 'GitHub Actions', 'CircleCI', 'ArgoCD', 'Linux', 'Ubuntu', 'CentOS', 'Shell Script', 'Bash', 'Python', 'Go', 'Nginx', 'Apache', 'HAProxy', 'Prometheus', 'Grafana', 'ELK Stack', 'Datadog', 'New Relic', 'PagerDuty', 'Vault', 'Consul', 'Istio', 'Service Mesh', 'GitOps', 'SRE Practices']
    ],
    
    // QA / TESTES
    '\\bqa\\b|quality|\\bteste\\b|testing|automação de testes|selenium|cypress|qualidade' => [
        'titulo' => 'Analista de QA',
        'competencias' => [
            'planejamento e execução de testes',
            'automação de testes funcionais',
            'testes de API',
            'testes de performance',
            'testes de segurança',
            'gestão de defeitos',
            'análise de requisitos',
            'BDD e TDD',
            'testes de regressão',
            'testes mobile',
            'integração com CI/CD',
            'métricas de qualidade'
        ],
        'skills' => ['Selenium', 'Cypress', 'Playwright', 'Appium', 'Robot Framework', 'Cucumber', 'Jest', 'Mocha', 'Pytest', 'JUnit', 'TestNG', 'Postman', 'REST Assured', 'JMeter', 'Gatling', 'K6', 'LoadRunner', 'OWASP', 'Burp Suite', 'SQL', 'Git', 'Jenkins', 'Azure DevOps', 'Jira', 'Zephyr', 'TestRail', 'Allure', 'BDD', 'Gherkin', 'Page Object Model', 'API Testing', 'Mobile Testing', 'Cross-Browser Testing', 'BrowserStack', 'Sauce Labs', 'Agile Testing', 'Exploratory Testing', 'Shift-Left Testing']
    ],
    
    // SEGURANÇA / CYBERSECURITY
    'segurança|security|cybersecurity|pentest|infosec|soc|siem|ethical hacking' => [
        'titulo' => 'Analista de Segurança',
        'competencias' => [
            'análise de vulnerabilidades',
            'pentest e ethical hacking',
            'monitoramento de segurança',
            'resposta a incidentes',
            'gestão de identidade e acesso',
            'compliance e auditoria',
            'security awareness',
            'hardening de sistemas',
            'análise de malware',
            'forense digital',
            'gestão de riscos',
            'implementação de LGPD/GDPR'
        ],
        'skills' => ['OWASP', 'Burp Suite', 'Metasploit', 'Nmap', 'Wireshark', 'Kali Linux', 'Python', 'Bash', 'PowerShell', 'SIEM', 'Splunk', 'QRadar', 'Azure Sentinel', 'Firewall', 'IDS/IPS', 'WAF', 'IAM', 'SSO', 'MFA', 'OAuth', 'SAML', 'ISO 27001', 'NIST', 'SOC 2', 'PCI-DSS', 'LGPD', 'GDPR', 'Vulnerability Assessment', 'Threat Intelligence', 'Incident Response', 'Digital Forensics', 'Cloud Security', 'Container Security', 'DevSecOps', 'Zero Trust', 'Endpoint Protection', 'DLP', 'CASB']
    ],
    
    // ==================== DESIGN / CRIATIVO ====================
    
    // DESIGN / UX / UI
    'design|designer|\\bux\\b|\\bui\\b|gráfico|criativo|figma|photoshop|ilustrador|product design|webdesign' => [
        'titulo' => 'Designer',
        'competencias' => [
            'criação de identidade visual',
            'design de interfaces digitais',
            'prototipagem de alta fidelidade',
            'pesquisa com usuários',
            'testes de usabilidade',
            'design system',
            'wireframes e fluxos',
            'design responsivo',
            'acessibilidade WCAG',
            'animações e micro-interações',
            'handoff para desenvolvimento',
            'design thinking'
        ],
        'skills' => ['Figma', 'Adobe XD', 'Sketch', 'InVision', 'Principle', 'Framer', 'Photoshop', 'Illustrator', 'After Effects', 'Premiere Pro', 'Lightroom', 'InDesign', 'CorelDRAW', 'Canva Pro', 'Zeplin', 'Abstract', 'Miro', 'Notion', 'Design System', 'Atomic Design', 'Material Design', 'iOS Human Interface', 'Typography', 'Color Theory', 'Grid Systems', 'Responsive Design', 'Mobile First', 'Accessibility', 'WCAG', 'Usability Testing', 'User Research', 'Personas', 'Journey Mapping', 'Information Architecture', 'Interaction Design', 'Motion Design', 'Branding', 'Logo Design', 'Print Design', 'Packaging Design']
    ],
    
    // ==================== MARKETING ====================
    
    // MARKETING DIGITAL
    'marketing|social media|redes sociais|tráfego|\\bseo\\b|growth|\\bads\\b|mídia|publicidade|conteúdo|copywriting|inbound|performance' => [
        'titulo' => 'Profissional de Marketing',
        'competencias' => [
            'gestão de campanhas de performance',
            'planejamento de mídia paga',
            'estratégias de SEO e conteúdo',
            'gestão de redes sociais',
            'automação de marketing',
            'e-mail marketing',
            'growth hacking',
            'análise de métricas e ROI',
            'copywriting persuasivo',
            'branding e posicionamento',
            'inbound marketing',
            'CRO e otimização de conversão'
        ],
        'skills' => ['Google Ads', 'Meta Ads', 'Facebook Ads', 'Instagram Ads', 'LinkedIn Ads', 'TikTok Ads', 'Google Analytics 4', 'Google Tag Manager', 'Google Search Console', 'SEMrush', 'Ahrefs', 'Moz', 'Ubersuggest', 'RD Station', 'HubSpot', 'Mailchimp', 'ActiveCampaign', 'Klaviyo', 'Hootsuite', 'mLabs', 'Etus', 'Canva', 'CapCut', 'Hotjar', 'Clarity', 'Optimizely', 'VWO', 'Data Studio', 'Looker Studio', 'Power BI', 'Excel', 'SQL', 'WordPress', 'Landing Pages', 'Unbounce', 'Leadpages', 'Copywriting', 'Storytelling', 'Branding', 'Content Marketing', 'Influencer Marketing', 'Affiliate Marketing', 'E-commerce Marketing', 'B2B Marketing', 'Account-Based Marketing']
    ],
    
    // ==================== COMERCIAL / VENDAS ====================
    
    // VENDAS
    'vendas|comercial|vendedor|sales|closer|sdr|inside sales|consultor comercial|representante|executivo de contas|account executive|bdr' => [
        'titulo' => 'Profissional de Vendas',
        'competencias' => [
            'prospecção ativa de clientes',
            'qualificação de leads',
            'negociação consultiva',
            'apresentação de soluções',
            'fechamento de contratos',
            'gestão de pipeline',
            'forecast e planejamento',
            'upsell e cross-sell',
            'gestão de carteira de clientes',
            'relacionamento com stakeholders',
            'vendas complexas B2B',
            'vendas por telefone e videoconferência'
        ],
        'skills' => ['CRM', 'Salesforce', 'Pipedrive', 'HubSpot CRM', 'RD Station CRM', 'Ploomes', 'Agendor', 'LinkedIn Sales Navigator', 'Apollo.io', 'Outreach', 'SalesLoft', 'Zoom', 'Google Meet', 'Teams', 'Slack', 'SPIN Selling', 'BANT', 'MEDDIC', 'Challenger Sale', 'Solution Selling', 'Consultative Selling', 'Cold Calling', 'Cold Email', 'Social Selling', 'Account Planning', 'Territory Management', 'Sales Enablement', 'Proposal Writing', 'Contract Negotiation', 'Objection Handling', 'Discovery Calls', 'Demo Presentation', 'POC Management', 'Customer Success Handoff', 'Sales Metrics', 'KPIs', 'Excel', 'PowerPoint']
    ],
    
    // ==================== GESTÃO / ADMINISTRAÇÃO ====================
    
    // ADMINISTRAÇÃO / GESTÃO
    'administra|gerente|coordenador|supervisor|gestor|líder|gestão|diretor|head|manager|management' => [
        'titulo' => 'Profissional de Gestão',
        'competencias' => [
            'liderança de equipes',
            'planejamento estratégico',
            'gestão de projetos',
            'controle orçamentário',
            'análise de indicadores',
            'tomada de decisão',
            'desenvolvimento de pessoas',
            'gestão de conflitos',
            'comunicação executiva',
            'negociação com stakeholders',
            'gestão de mudanças',
            'melhoria contínua de processos'
        ],
        'skills' => ['Liderança', 'Gestão de Pessoas', 'Feedback', 'Coaching', 'Mentoring', 'OKRs', 'KPIs', 'BSC', 'Excel Avançado', 'Power BI', 'Tableau', 'ERP', 'SAP', 'TOTVS', 'Gestão de Projetos', 'PMI', 'PMBOK', 'Scrum', 'Kanban', 'Agile', 'Lean', 'Six Sigma', 'Design Thinking', 'Jira', 'Trello', 'Asana', 'Monday', 'MS Project', 'Miro', 'Notion', 'Slack', 'Teams', 'Zoom', 'PowerPoint', 'Apresentação Executiva', 'Storytelling', 'Comunicação Assertiva', 'Inteligência Emocional', 'Gestão de Tempo', 'Priorização', 'Delegação', 'Tomada de Decisão', 'Resolução de Problemas', 'Pensamento Analítico', 'Visão Estratégica', 'Gestão de Crise']
    ],
    
    // PROJETOS (PMO / SCRUM MASTER / PO)
    'projeto|project|pmo|scrum master|product owner|agile coach|agilista|pm' => [
        'titulo' => 'Profissional de Projetos',
        'competencias' => [
            'planejamento e execução de projetos',
            'gestão de escopo, prazo e custo',
            'facilitação de cerimônias ágeis',
            'gestão de backlog',
            'remoção de impedimentos',
            'gestão de riscos',
            'comunicação com stakeholders',
            'controle de qualidade',
            'gestão de mudanças',
            'métricas ágeis',
            'coaching de times',
            'transformação ágil'
        ],
        'skills' => ['Scrum', 'Kanban', 'SAFe', 'LeSS', 'Nexus', 'PMI', 'PMBOK', 'PRINCE2', 'Waterfall', 'Híbrido', 'Jira', 'Confluence', 'Trello', 'Asana', 'Monday', 'Azure Boards', 'MS Project', 'Smartsheet', 'Miro', 'Mural', 'Notion', 'Figma', 'User Stories', 'Story Mapping', 'Story Points', 'Planning Poker', 'Sprint Planning', 'Daily Scrum', 'Sprint Review', 'Retrospective', 'Refinement', 'Burn-down', 'Burn-up', 'Velocity', 'Lead Time', 'Cycle Time', 'CFD', 'Definition of Done', 'Definition of Ready', 'Roadmap', 'Release Planning', 'Stakeholder Management', 'Risk Management', 'Change Management', 'Conflict Resolution']
    ],
    
    // ==================== RH / PESSOAS ====================
    
    // RH / RECURSOS HUMANOS
    'rh|recursos humanos|recrutamento|seleção|dp|departamento pessoal|talent|hunting|people|gente|gestão de pessoas' => [
        'titulo' => 'Profissional de RH',
        'competencias' => [
            'recrutamento e seleção end-to-end',
            'hunting e atração de talentos',
            'entrevistas por competências',
            'onboarding de colaboradores',
            'treinamento e desenvolvimento',
            'avaliação de desempenho',
            'gestão de clima organizacional',
            'administração de pessoal',
            'gestão de benefícios',
            'employer branding',
            'people analytics',
            'gestão de carreira e sucessão'
        ],
        'skills' => ['Gupy', 'Kenoby', 'Greenhouse', 'Lever', 'Workable', 'LinkedIn Recruiter', 'Indeed', 'Catho', 'InfoJobs', 'ATS', 'Entrevista por Competências', 'Entrevista Estruturada', 'Assessment', 'DISC', 'MBTI', 'Testes Psicológicos', 'Dinâmica de Grupo', 'Onboarding', 'Learning', 'LMS', 'Treinamento', 'E-learning', 'PDI', 'Avaliação 360', 'Nine Box', 'Feedback', '1:1', 'HRIS', 'eSocial', 'Folha de Pagamento', 'Benefícios', 'VT', 'VR', 'VA', 'Plano de Saúde', 'Ponto Eletrônico', 'Férias', 'Rescisão', 'CLT', 'Legislação Trabalhista', 'Convenção Coletiva', 'Employee Experience', 'Cultura Organizacional', 'Employer Branding', 'EVP', 'Pesquisa de Clima', 'eNPS', 'People Analytics', 'Turnover', 'Headcount', 'Excel', 'Power BI']
    ],
    
    // ==================== FINANÇAS / CONTABILIDADE ====================
    
    // FINANÇAS / CONTABILIDADE
    'financeiro|finanças|contabil|contador|fiscal|tesouraria|controladoria|custos|auditoria|controller' => [
        'titulo' => 'Profissional Financeiro',
        'competencias' => [
            'análise financeira e DRE',
            'planejamento orçamentário',
            'gestão de fluxo de caixa',
            'conciliação bancária',
            'análise de viabilidade econômica',
            'controle de custos e despesas',
            'relatórios gerenciais',
            'fechamento contábil',
            'apuração de impostos',
            'compliance fiscal',
            'auditoria interna',
            'valuation e M&A'
        ],
        'skills' => ['Excel Avançado', 'Power BI', 'Tableau', 'VBA', 'Python', 'SQL', 'ERP', 'SAP FI/CO', 'TOTVS Protheus', 'Sankhya', 'Omie', 'Conta Azul', 'QuickBooks', 'ContaAzul', 'Contas a Pagar', 'Contas a Receber', 'Tesouraria', 'Cash Flow', 'DRE', 'Balanço', 'EBITDA', 'ROI', 'Payback', 'VPL', 'TIR', 'Budget', 'Forecast', 'Rolling Forecast', 'Variance Analysis', 'Cost Accounting', 'ABC', 'Transfer Pricing', 'SPED', 'ECD', 'ECF', 'EFD', 'DCTF', 'DIRF', 'PIS', 'COFINS', 'ICMS', 'ISS', 'IRPJ', 'CSLL', 'Lucro Real', 'Lucro Presumido', 'Simples Nacional', 'CPC', 'IFRS', 'Normas Contábeis', 'Auditoria', 'Compliance', 'SOX', 'Controles Internos']
    ],
    
    // ==================== OPERAÇÕES / LOGÍSTICA ====================
    
    // LOGÍSTICA
    'logística|estoque|almoxarifado|supply chain|compras|expedição|armazém|distribuição' => [
        'titulo' => 'Profissional de Logística',
        'competencias' => [
            'gestão de estoque e inventário',
            'planejamento de demanda',
            'gestão de armazém',
            'recebimento e expedição',
            'roteirização de entregas',
            'negociação com fornecedores',
            'gestão de transportadoras',
            'controle de custos logísticos',
            'otimização de processos',
            'indicadores de performance',
            'gestão de last mile',
            'logística reversa'
        ],
        'skills' => ['WMS', 'TMS', 'ERP', 'SAP MM/WM', 'TOTVS', 'Oracle SCM', 'Excel Avançado', 'Power BI', 'Gestão de Estoque', 'FIFO', 'FEFO', 'LIFO', 'Curva ABC', 'Inventário Rotativo', 'Picking', 'Packing', 'Cross-docking', 'Just-in-Time', 'Kanban Logístico', 'Lean Logistics', 'S&OP', 'MRP', 'DRP', 'Forecast', 'Lead Time', 'Safety Stock', 'Ponto de Pedido', 'Lote Econômico', 'KPIs Logísticos', 'OTIF', 'Fill Rate', 'Acuracidade', 'Giro de Estoque', 'Roteirização', 'Frete', 'Incoterms', 'Comércio Exterior', 'Importação', 'Exportação', 'Desembaraço Aduaneiro', 'Licenciamento', 'Siscomex', 'Drawback']
    ],

    // MOTOBOY / MOTORISTA / ENTREGAS
    'motoboy|motoqueiro|entregador|motorista|delivery|courier|transporte|cnh|entregas' => [
        'titulo' => 'Profissional de Entregas',
        'competencias' => [
            'entregas rápidas e seguras',
            'conhecimento avançado de rotas',
            'manutenção preventiva do veículo',
            'atendimento cordial ao cliente',
            'cumprimento rigoroso de prazos',
            'uso eficiente de aplicativos de rota',
            'direção defensiva e segura',
            'conferência e zelo pela carga',
            'manuseio de máquinas de cartão',
            'resolução de imprevistos no trânsito',
            'logística de última milha',
            'coleta e despacho de encomendas'
        ],
        'skills' => ['CNH A', 'CNH B', 'EAR (Exerce Atividade Remunerada)', 'Direção Defensiva', 'Mecânica Básica', 'GPS', 'Waze', 'Google Maps', 'Logística Urbana', 'Pontualidade', 'Responsabilidade', 'Agilidade', 'Comunicação', 'Ética Profissional', 'Primeiros Socorros', 'Legislação de Trânsito', 'Pilotagem Segura', 'Manuseio de Cargas', 'Atendimento ao Cliente']
    ],

    // LIMPEZA / SERVIÇOS DOMÉSTICOS
    'diarista|doméstica|domestica|faxineira|limpeza|auxiliar de limpeza|serviços gerais|arrumadeira|passadeira|zeladora' => [
        'titulo' => 'Profissional de Serviços Domésticos',
        'competencias' => [
            'limpeza residencial e comercial detalhada',
            'higienização profunda de ambientes',
            'organização de armários, despensas e rouparia',
            'lavagem e passadoria de roupas com técnicas adequadas',
            'manuseio seguro de produtos químicos de limpeza',
            'limpeza de vidros, janelas e áreas externas',
            'manutenção da ordem, higiene e bem-estar',
            'preparo de refeições básicas e cafés',
            'cuidados com plantas e animais de estimação',
            'gestão de rotinas de limpeza diária e semanal',
            'limpeza pós-obra e pré-mudança',
            'zelo e cuidado com o patrimônio do cliente'
        ],
        'skills' => ['Organização', 'Pontualidade', 'Agilidade', 'Discrição', 'Honestidade', 'Proatividade', 'Capricho', 'Produtos de Limpeza', 'Técnicas de Higienização', 'Cozinha Básica', 'Lavanderia', 'Passadoria', 'Trabalho em Equipe', 'Etiqueta Profissional', 'Gestão de Tempo']
    ],

    // BABÁ / CUIDADORA INFANTIL
    'babá|baba|babysitter|baby sister|nanny|berçarista|cuidadora de criança|recreacionista|monitora infantil|auxiliar de creche' => [
        'titulo' => 'Cuidadora Infantil',
        'competencias' => [
            'cuidados integrais com recém-nascidos e crianças',
            'preparo de alimentação balanceada e mamadeiras',
            'higiene pessoal, banho e troca de fraldas',
            'estímulo ao desenvolvimento cognitivo e motor',
            'criação de atividades lúdicas e educativas',
            'acompanhamento escolar e auxílio no dever de casa',
            'monitoramento de sono e estabelecimento de rotina',
            'organização de brinquedos, roupas e ambiente infantil',
            'cuidados em situações de emergência e febre',
            'acompanhamento em consultas médicas e passeios',
            'introdução alimentar segura',
            'recreação e brincadeiras dirigidas'
        ],
        'skills' => ['Primeiros Socorros Infantis', 'Paciência', 'Responsabilidade', 'Carinho', 'Atenção aos Detalhes', 'Criatividade', 'Pedagogia Básica', 'Nutrição Infantil', 'Segurança Doméstica', 'Disponibilidade de Horário', 'Confiança', 'Contação de Histórias', 'Recreação', 'Inteligência Emocional', 'Higiene Infantil']
    ],
    
    // ==================== ATENDIMENTO / SUPORTE ====================
    
    // ATENDIMENTO / CUSTOMER SUCCESS
    'atendimento|suporte|sac|help desk|customer|service desk|recepcionista|customer success|cs|cx' => [
        'titulo' => 'Profissional de Atendimento',
        'competencias' => [
            'atendimento ao cliente multicanal',
            'resolução de problemas complexos',
            'gestão de tickets e chamados',
            'escalonamento de demandas',
            'sucesso do cliente',
            'onboarding de clientes',
            'retenção e churn management',
            'upsell e expansão de contas',
            'gestão de SLA',
            'pesquisas de satisfação',
            'documentação de processos',
            'treinamento de usuários'
        ],
        'skills' => ['Zendesk', 'Freshdesk', 'Intercom', 'HubSpot Service', 'Salesforce Service Cloud', 'ServiceNow', 'Jira Service Management', 'Movidesk', 'Octadesk', 'Blip', 'Take', 'Chatbot', 'WhatsApp Business', 'Telefonia', 'PABX', 'VoIP', 'Twilio', 'E-mail', 'Chat', 'Ticket Management', 'SLA', 'CSAT', 'NPS', 'CES', 'First Response Time', 'Resolution Time', 'FCR', 'Knowledge Base', 'FAQ', 'Self-Service', 'Customer Journey', 'Touchpoints', 'Churn Analysis', 'Health Score', 'QBR', 'Success Plan', 'Playbooks', 'Customer Training', 'Webinars', 'Comunicação', 'Empatia', 'Escuta Ativa', 'Resolução de Conflitos', 'Paciência', 'Proatividade']
    ],
    
    // ==================== SAÚDE ====================
    
    // SAÚDE
    'saúde|enfermagem|enfermeiro|técnico enfermagem|cuidador|hospital|farmácia|fisioterapia|nutrição|médico|odonto' => [
        'titulo' => 'Profissional da Saúde',
        'competencias' => [
            'atendimento humanizado ao paciente',
            'procedimentos clínicos e técnicos',
            'administração de medicamentos',
            'monitoramento de sinais vitais',
            'registro em prontuário eletrônico',
            'trabalho em equipe multidisciplinar',
            'educação em saúde',
            'biossegurança',
            'acolhimento e classificação de risco',
            'cuidados paliativos',
            'procedimentos de emergência',
            'ética profissional'
        ],
        'skills' => ['Prontuário Eletrônico', 'Tasy', 'MV', 'Philips Tasy', 'Wareline', 'Soul MV', 'SUS', 'TISS', 'ANS', 'ANVISA', 'RDC', 'NR32', 'Biossegurança', 'EPI', 'Higienização', 'Esterilização', 'CCIH', 'Protocolos Clínicos', 'SAE', 'Sistematização da Assistência', 'Coleta de Exames', 'Curativos', 'Punção Venosa', 'Sondagem', 'Oxigenoterapia', 'Ventilação', 'Monitorização', 'ECG', 'Sinais Vitais', 'Medicação', 'Prescrição', 'Farmácia Clínica', 'Dispensação', 'Fisioterapia Respiratória', 'Fisioterapia Motora', 'Avaliação Nutricional', 'Dieta', 'Primeiros Socorros', 'BLS', 'ACLS', 'Triagem Manchester', 'Acolhimento', 'Humanização', 'Ética']
    ],
    
    // ==================== EDUCAÇÃO ====================
    
    // EDUCAÇÃO
    'professor|educação|docente|pedagogo|ensino|educador|escola|instrutor|treinador|facilitador' => [
        'titulo' => 'Educador',
        'competencias' => [
            'planejamento pedagógico',
            'desenvolvimento de material didático',
            'metodologias ativas de ensino',
            'avaliação de aprendizagem',
            'gestão de sala de aula',
            'uso de tecnologias educacionais',
            'educação inclusiva',
            'mediação de conflitos',
            'comunicação com famílias',
            'formação continuada',
            'tutoria e mentoria',
            'desenvolvimento socioemocional'
        ],
        'skills' => ['Didática', 'Pedagogia', 'Psicologia da Educação', 'BNCC', 'PCNs', 'PPP', 'Plano de Aula', 'Sequência Didática', 'Avaliação Formativa', 'Avaliação Somativa', 'Rubrica', 'Feedback Pedagógico', 'Metodologias Ativas', 'PBL', 'Sala de Aula Invertida', 'Gamificação', 'Aprendizagem Baseada em Projetos', 'STEM', 'Maker', 'Google Classroom', 'Microsoft Teams', 'Moodle', 'Canvas', 'Kahoot', 'Mentimeter', 'Padlet', 'Canva', 'PowerPoint', 'Prezi', 'Zoom', 'Meet', 'EAD', 'Ensino Híbrido', 'Educação Especial', 'AEE', 'Inclusão', 'Libras', 'Acessibilidade', 'Inteligências Múltiplas', 'Competências Socioemocionais', 'Gestão de Comportamento', 'Comunicação Não-Violenta']
    ],
    
    // ==================== JURÍDICO ====================
    
    // JURÍDICO
    'advogado|jurídico|direito|paralegal|contratos|compliance|advocacia|legal' => [
        'titulo' => 'Profissional Jurídico',
        'competencias' => [
            'análise e elaboração de contratos',
            'consultoria jurídica preventiva',
            'contencioso judicial e administrativo',
            'negociação e mediação',
            'compliance e governança',
            'due diligence',
            'gestão de processos judiciais',
            'pesquisa jurisprudencial',
            'elaboração de pareceres',
            'auditoria legal',
            'proteção de dados (LGPD)',
            'propriedade intelectual'
        ],
        'skills' => ['Direito Civil', 'Direito Empresarial', 'Direito Trabalhista', 'Direito Tributário', 'Direito do Consumidor', 'Direito Digital', 'LGPD', 'Contratos', 'M&A', 'Due Diligence', 'Societário', 'Compliance', 'Governança Corporativa', 'Contencioso', 'Arbitragem', 'Mediação', 'Processo Civil', 'Processo Trabalhista', 'Processo Tributário', 'PJe', 'e-SAJ', 'Projudi', 'Jurisprudência', 'Doutrina', 'Legislação', 'Pesquisa Jurídica', 'Parecer', 'Petição', 'Recursos', 'Audiências', 'Sustentação Oral', 'Negociação', 'Legal Design', 'Lawtechs', 'Contract Lifecycle Management', 'Word', 'Excel', 'PowerPoint', 'TOTVS Jurídico', 'Projuris', 'Astrea', 'LegalOne']
    ],
    
    // AGROPECUÁRIA / RURAL / LEITEIRO
    'leiteiro|ordenha|leite|fazenda|rural|agropecuária|agropecuario|pecuária|pecuario|vaqueiro|gado|bovino|agricultor|agricultura|campo|plantio|colheita|tratador|curral|caseiro' => [
        'titulo' => 'Trabalhador Rural',
        'competencias' => [
            'ordenha manual e mecânica',
            'manejo de animais de grande porte',
            'alimentação e nutrição animal',
            'higienização de equipamentos de ordenha',
            'controle de qualidade do leite',
            'cuidados com saúde animal',
            'manutenção de pastagens',
            'operação de máquinas agrícolas',
            'plantio e colheita',
            'preparo do solo',
            'controle de pragas e doenças',
            'gestão de rebanho'
        ],
        'skills' => ['Ordenha Mecânica', 'Ordenha Manual', 'Manejo de Gado', 'Sanidade Animal', 'Nutrição Animal', 'Inseminação Artificial', 'Vacinação', 'Medicação Animal', 'Trator', 'Implementos Agrícolas', 'Irrigação', 'Plantio Direto', 'Colheitadeira', 'Pulverização', 'Fenação', 'Silagem', 'Cerca Elétrica', 'Curral', 'Tanque de Leite', 'Resfriador', 'Boas Práticas Agropecuárias', 'NR 31', 'Segurança Rural', 'Carteira de Operador de Máquinas', 'CNH', 'Trabalho em Equipe', 'Proatividade', 'Responsabilidade', 'Pontualidade', 'Disponibilidade de Horário']
    ],

    // ==================== MANUTENÇÃO E CONSTRUÇÃO ====================

    // ENCANADOR / BOMBEIRO HIDRÁULICO
    'encanador|bombeiro hidráulico|hidraulica|tubulação|esgoto|vazamento|caça vazamento|instalação hidráulica' => [
        'titulo' => 'Encanador Profissional',
        'competencias' => [
            'instalação e manutenção de redes hidráulicas',
            'reparo de vazamentos e infiltrações',
            'instalação de louças e metais sanitários',
            'desentupimento de tubulações e esgoto',
            'manutenção de caixas d\'água e reservatórios',
            'leitura e interpretação de projetos hidráulicos',
            'instalação de sistemas de aquecimento solar/gás',
            'testes de pressão e estanqueidade',
            'reparos em bombas d\'água',
            'instalação de rede de esgoto e pluvial',
            'soldagem de tubos PVC, cobre e PPR',
            'atendimento emergencial a clientes'
        ],
        'skills' => ['Hidráulica Predial', 'Hidráulica Residencial', 'Tigre/Amanco', 'PVC', 'CPVC', 'PPR', 'Cobre', 'Solda', 'Caça Vazamentos', 'Desentupimento', 'Instalação de Louças', 'Torneiras', 'Válvulas de Descarga', 'Caixa Acoplada', 'Sifão', 'Caixa de Gordura', 'Limpeza de Caixa d\'água', 'Aquecedores', 'Pressurizadores', 'Ferramentas Manuais', 'Leitura de Projetos', 'Normas Técnicas (NBR)', 'Segurança do Trabalho', 'Capricho', 'Limpeza Pós-Obra']
    ],

    // ELETRICISTA
    'eletricista|elétrica|eletrica|fiação|quadro de força|disjuntor|tomada|iluminação|alta tensão|baixa tensão' => [
        'titulo' => 'Eletricista',
        'competencias' => [
            'instalação e manutenção elétrica residencial/predial',
            'montagem de quadros de distribuição',
            'passagem de cabos e fiação',
            'instalação de tomadas, interruptores e luminárias',
            'dimensionamento de circuitos elétricos',
            'reparos em curto-circuitos e falhas',
            'instalação de aterramento e DPS',
            'leitura de diagramas unifilares',
            'instalação de ventiladores e chuveiros',
            'manutenção preventiva em instalações',
            'adequação a normas NR-10',
            'diagnóstico de consumo de energia'
        ],
        'skills' => ['NR-10', 'NR-35', 'Baixa Tensão', 'Média Tensão', 'Comandos Elétricos', 'Quadro de Distribuição', 'Disjuntores', 'DR', 'DPS', 'Aterramento', 'Cabeamento', 'Multímetro', 'Alicate Amperímetro', 'Leitura de Projetos', 'Iluminação LED', 'Automação Residencial', 'Sensores de Presença', 'Portão Eletrônico', 'Interfone', 'Cerca Elétrica', 'Segurança em Eletricidade', 'Ferramentas Elétricas', 'Resolução de Problemas']
    ],

    // REPAROS GERAIS / MARIDO DE ALUGUEL / MANUTENÇÃO
    'reparos|marido de aluguel|manutenção predial|consertos|pedreiro|pintor|reformas|faz tudo|serviços gerais manutenção' => [
        'titulo' => 'Profissional de Manutenção Predial',
        'competencias' => [
            'execução de reparos hidráulicos e elétricos básicos',
            'pintura de paredes e acabamentos',
            'montagem e desmontagem de móveis',
            'instalação de prateleiras, cortinas e quadros',
            'pequenos reparos em alvenaria e pisos',
            'troca de fechaduras e maçanetas',
            'manutenção preventiva de instalações',
            'limpeza e conservação de áreas comuns',
            'jardinagem e poda básica',
            'impermeabilização de superfícies',
            'resolução rápida de problemas domésticos',
            'atendimento multitarefa'
        ],
        'skills' => ['Pintura', 'Alvenaria Básica', 'Hidráulica Básica', 'Elétrica Básica', 'Montagem de Móveis', 'Furadeira', 'Parafusadeira', 'Ferramentas Manuais', 'Gesso', 'Drywall', 'Assentamento de Pisos', 'Rejunte', 'Acabamentos', 'Telhados', 'Calhas', 'Jardinagem', 'Limpeza', 'Organização', 'Agilidade', 'Solução de Problemas', 'Capricho', 'Atendimento ao Cliente']
    ],

    // ==================== SEGURANÇA E PORTARIA ====================

    // PORTEIRO / VIGIA
    'porteiro|vigia|vigilante|controlador de acesso|portaria|guarita|segurança patrimonial|zeladoria|ronda' => [
        'titulo' => 'Porteiro / Controlador de Acesso',
        'competencias' => [
            'controle rigoroso de entrada e saída de pessoas/veículos',
            'monitoramento de câmeras de segurança (CFTV)',
            'recebimento e distribuição de correspondências',
            'atendimento interfone e telefônico',
            'execução de rondas perimetrais',
            'registro de ocorrências em livro de ata',
            'acionamento de protocolos de emergência',
            'zelo pela ordem e silêncio no condomínio',
            'operação de portões eletrônicos',
            'orientação a visitantes e prestadores de serviço',
            'cumprimento das normas do regimento interno',
            'postura vigilante e preventiva'
        ],
        'skills' => ['Controle de Acesso', 'CFTV', 'Monitoramento', 'Rádio HT', 'Informática Básica', 'Livro de Ocorrências', 'Protocolos de Segurança', 'Primeiros Socorros', 'Combate a Incêndio', 'Atendimento ao Público', 'Discrição', 'Atenção', 'Responsabilidade', 'Pontualidade', 'Postura Profissional', 'Gerenciamento de Conflitos', 'Ronda']
    ],

    // ==================== AUTOMOTIVO ====================

    // MECÂNICO
    'mecânico|mecanico|automotivo|oficina|motor|suspensão|freios|injeção eletrônica|revisão automotiva' => [
        'titulo' => 'Mecânico Automotivo',
        'competencias' => [
            'diagnóstico de falhas mecânicas e eletrônicas',
            'manutenção preventiva e corretiva de veículos',
            'revisão de motores, câmbio e suspensão',
            'troca de óleo, filtros e fluidos',
            'reparo em sistemas de freios e embreagem',
            'alinhamento e balanceamento',
            'uso de scanner automotivo para diagnóstico',
            'substituição de correias e tensores',
            'regulagem de válvulas e injeção',
            'teste de rodagem e verificação de qualidade',
            'limpeza e organização da oficina',
            'orçamentação de peças e serviços'
        ],
        'skills' => ['Mecânica Geral', 'Injeção Eletrônica', 'Scanner Automotivo', 'Motores Ciclo Otto', 'Motores Diesel', 'Suspensão', 'Freios ABS', 'Câmbio Manual', 'Câmbio Automático', 'Elétrica Automotiva', 'Ar Condicionado Automotivo', 'Metrologia', 'Ferramentas Pneumáticas', 'Interpretação de Esquemas', 'Atendimento ao Cliente', 'Organização', 'Honestidade', 'Trabalho em Equipe']
    ],

    // BORRACHEIRO
    'borracheiro|pneu|pneus|vulcanização|calibragem|rodas|câmara de ar' => [
        'titulo' => 'Borracheiro',
        'competencias' => [
            'reparo e vulcanização de pneus e câmaras de ar',
            'montagem e desmontagem de rodas',
            'calibragem correta de pneus leves e pesados',
            'balanceamento de rodas',
            'recauchutagem e frisagem de pneus',
            'troca de válvulas e bicos',
            'avaliação de desgaste e vida útil dos pneus',
            'socorro e atendimento emergencial a veículos',
            'operação de máquinas pneumáticas',
            'manuseio seguro de macacos hidráulicos',
            'rodízio de pneus',
            'organização e limpeza do local de trabalho'
        ],
        'skills' => ['Vulcanização', 'Montagem de Pneus', 'Balanceamento', 'Alinhamento', 'Ferramentas Pneumáticas', 'Macaco Hidráulico', 'Chave de Roda', 'Segurança no Trabalho', 'Pneus de Carga', 'Pneus de Passeio', 'Agilidade', 'Força Física', 'Atendimento ao Cliente', 'Honestidade', 'Disponibilidade']
    ],

    // MANOBRISTA
    'manobrista|valet|estacionamento|garagista|motorista manobrista' => [
        'titulo' => 'Manobrista',
        'competencias' => [
            'recepção cordial de clientes e veículos',
            'manobra segura de veículos nacionais e importados',
            'organização eficiente do pátio de estacionamento',
            'controle de entrada e saída de tickets',
            'inspeção visual de avarias no recebimento',
            'zelo absoluto pelo patrimônio do cliente',
            'auxílio no embarque e desembarque de passageiros',
            'operação de caixas e recebimento de valores',
            'controle de chaves e segurança',
            'condução defensiva em espaços reduzidos',
            'atendimento vip e personalizado',
            'gestão de fluxo em horários de pico'
        ],
        'skills' => ['CNH B (EAR)', 'Direção Defensiva', 'Manobras de Precisão', 'Carros Automáticos', 'Carros Manuais', 'Atendimento VIP', 'Cordialidade', 'Atenção', 'Responsabilidade', 'Agilidade', 'Controle de Avarias', 'Honestidade', 'Ética', 'Organização de Pátio']
    ],

    // ==================== ALIMENTAÇÃO E GASTRONOMIA ====================

    // GARÇOM / GARÇONETE / ATENDENTE DE MESA
    'garçom|garçonete|garcom|atendente de mesa|cumim|maitre|restaurante|serviço de mesa' => [
        'titulo' => 'Garçom / Garçonete',
        'competencias' => [
            'recepção e acomodação de clientes',
            'apresentação do cardápio e sugestão de pratos',
            'anotação precisa de pedidos (comanda/tablet)',
            'serviço de alimentos e bebidas à francesa/americana',
            'montagem e organização de mesas (mise en place)',
            'polimento de talheres e taças',
            'fechamento de contas e operação de caixa',
            'resolução de problemas e reclamações',
            'trabalho em sincronia com a cozinha',
            'vendas sugestivas para aumentar ticket médio',
            'higienização do salão',
            'atendimento ágil e cordial'
        ],
        'skills' => ['Atendimento ao Cliente', 'Vendas', 'Simpatia', 'Agilidade', 'Memorização', 'Bandeja', 'Mise en Place', 'Vinhos', 'Drinks', 'Etiqueta', 'Higiene', 'Trabalho sob Pressão', 'Organização', 'Comunicação', 'Inglês Básico (Diferencial)', 'Sistema de Comandas']
    ],
    
    // COZINHA / MERENDEIRA / CHAPEIRO / CHURRASQUEIRO
    'cozinheira|cozinheiro|merendeira|chapeiro|churrasqueiro|parrilheiro|lancheiro|auxiliar de cozinha|preparo de alimentos' => [
        'titulo' => 'Profissional de Cozinha',
        'competencias' => [
            'preparo e manipulação segura de alimentos',
            'execução de fichas técnicas e receitas',
            'controle de estoque e validade de produtos (PVPS)',
            'higienização de utensílios e ambiente (boas práticas)',
            'corte de carnes, legumes e temperos',
            'operação de chapas, fornos e churrasqueiras',
            'montagem de pratos e lanches com padrão visual',
            'trabalho ágil em horários de pico',
            'preparo de merenda escolar balanceada',
            'controle de desperdício',
            'organização do mise en place diário',
            'zelo pelo sabor e qualidade final'
        ],
        'skills' => ['Boas Práticas de Manipulação', 'Higiene e Segurança Alimentar', 'Cortes de Carnes', 'Chapa', 'Fritadeira', 'Forno Combinado', 'Churrasqueira', 'Temperos', 'Agilidade', 'Organização', 'Trabalho em Equipe', 'Resistência Física', 'Paladar Apurado', 'Criatividade', 'Controle de Estoque', 'Limpeza']
    ],
    
    // ATENDENTE DE BALCÃO
    'atendente de balcão|balconista|padaria|lanchonete|cafeteria|atendente de loja' => [
        'titulo' => 'Atendente de Balcão',
        'competencias' => [
            'atendimento ágil e simpático no balcão',
            'reposição e organização de vitrines',
            'fatiamento de frios e preparo de lanches rápidos',
            'operação de caixa e recebimento de valores',
            'preparo de cafés, sucos e bebidas',
            'limpeza e higienização da área de trabalho',
            'verificação de validade dos produtos expostos',
            'embalagem correta de produtos para viagem',
            'vendas sugestivas e promoções',
            'controle de fluxo de pedidos',
            'esclarecimento de dúvidas sobre produtos',
            'manutenção da boa apresentação da loja'
        ],
        'skills' => ['Atendimento ao Cliente', 'Simpatia', 'Agilidade', 'Higiene', 'Manipulação de Alimentos', 'Fatiadora de Frios', 'Máquina de Café', 'Caixa', 'Matemática Básica', 'Organização', 'Comunicação', 'Vendas', 'Paciência', 'Trabalho em Equipe']
    ],

    // ==================== OPERACIONAL E LOGÍSTICA (EXPANSÃO) ====================

    // REPOSITOR
    'repositor|estoquista|reposição|abastecimento|gôndola|supermercado|mercado|loja|organização de estoque' => [
        'titulo' => 'Repositor de Mercadorias',
        'competencias' => [
            'abastecimento e organização de gôndolas/prateleiras',
            'precificação correta de produtos',
            'verificação de validade e rotação (FIFO/PEPS)',
            'controle de rupturas e falta de mercadoria',
            'recebimento e conferência de cargas',
            'organização do depósito e estoque',
            'limpeza e conservação dos produtos expostos',
            'montagem de ilhas promocionais e pontos extras',
            'atendimento e orientação a clientes nos corredores',
            'inventário rotativo de mercadorias',
            'prevenção de perdas e avarias',
            'manuseio de paleteiras manuais'
        ],
        'skills' => ['Organização', 'Atenção aos Detalhes', 'PEPS/FIFO', 'Precificação', 'Layout de Loja', 'Merchandising', 'Controle de Validade', 'Agilidade', 'Força Física', 'Trabalho em Equipe', 'Proatividade', 'Honestidade', 'Inventário']
    ],

    // ==================== OUTROS E ADMINISTRATIVO ====================

    // DIGITADOR / ADMINISTRATIVO OPERACIONAL
    'digitador|digitação|data entry|lançamento de dados|arquivo|auxiliar de escritório|copiadora' => [
        'titulo' => 'Digitador / Auxiliar Administrativo',
        'competencias' => [
            'digitação rápida e precisa de documentos',
            'lançamento de dados em sistemas informatizados',
            'conferência e validação de informações',
            'organização e arquivamento físico e digital',
            'digitalização e cópia de documentos',
            'formatação de textos e planilhas',
            'transcrição de áudios e atas',
            'atualização de cadastros de clientes',
            'suporte administrativo geral',
            'controle de fluxo de documentos',
            'redação de e-mails e correspondências simples',
            'manutenção do sigilo das informações'
        ],
        'skills' => ['Digitação Rápida', 'Pacote Office', 'Word', 'Excel', 'Sistemas ERP', 'Atenção Concentrada', 'Organização', 'Português Correto', 'Agilidade', 'Discrição', 'Arquivamento', 'Informática Avançada', 'Gestão de Tempo']
    ],

    // RELIGIOSO / PASTOR
    'pastor|padre|religioso|teologia|igreja|ministerio|capelão|missionário|pregador|liderança religiosa' => [
        'titulo' => 'Líder Religioso / Ministro',
        'competencias' => [
            'liderança espiritual e aconselhamento',
            'pregação e ensino bíblico/teológico',
            'gestão de voluntários e equipes ministeriais',
            'organização de eventos e celebrações',
            'visitação e assistência comunitária',
            'mediação de conflitos familiares/sociais',
            'administração eclesiástica',
            'oratória e comunicação pública',
            'desenvolvimento de projetos sociais',
            'capelania e suporte emocional',
            'mentoria e discipulado',
            'planejamento estratégico ministerial'
        ],
        'skills' => ['Liderança', 'Oratória', 'Teologia', 'Aconselhamento', 'Empatia', 'Escuta Ativa', 'Gestão de Pessoas', 'Organização de Eventos', 'Ensino', 'Comunicação', 'Ética', 'Mediação', 'Inteligência Emocional', 'Serviço Social', 'Música (opcional)']
    ],

    // DESIGN EXPANDIDO (WEB GRÁFICO)
    'web designer|web design|webgrafico|web gráfico|criador de sites|designer digital' => [
        'titulo' => 'Web Designer',
        'competencias' => [
            'criação de layouts para websites e landing pages',
            'desenvolvimento de banners e peças digitais',
            'edição e tratamento de imagens para web',
            'criação de identidades visuais online',
            'prototipagem de interfaces (UI/UX)',
            'criação de newsletters e e-mail marketing',
            'manutenção visual de portais e blogs',
            'conhecimento em tipografia e cores para telas',
            'otimização de imagens para performance',
            'colaboração com desenvolvedores front-end',
            'adaptação de artes para redes sociais',
            'design responsivo'
        ],
        'skills' => ['Photoshop', 'Illustrator', 'Figma', 'Adobe XD', 'Canva', 'HTML/CSS Básico', 'WordPress', 'Elementor', 'Noções de UX/UI', 'Teoria das Cores', 'Tipografia', 'Design Responsivo', 'Criatividade', 'Atenção aos Detalhes', 'Portfólio']
    ],
    
    // PROFESSOR (EXPANDIDO) - Se já houver, este complementa
    'professor|professora|docente|ensino fundamental|ensino médio|escola|educação infantil' => [
        'titulo' => 'Professor(a)',
        'competencias' => [
            'planejamento e execução de aulas dinâmicas',
            'elaboração de planos de ensino (BNCC)',
            'avaliação contínua do aprendizado dos alunos',
            'gestão de sala de aula e disciplina',
            'adaptação de conteúdo para necessidades especiais',
            'uso de tecnologias educacionais e lousa digital',
            'correção de provas e atividades',
            'comunicação efetiva com pais e coordenação',
            'participação em conselhos de classe',
            'desenvolvimento de projetos interdisciplinares',
            'mediação de conflitos escolares',
            'formação continuada e pesquisa'
        ],
        'skills' => ['Didática', 'Pedagogia', 'BNCC', 'Liderança', 'Oratória', 'Paciência', 'Gestão de Tempo', 'Informática Educativa', 'Metodologias Ativas', 'Psicologia da Educação', 'Inclusão', 'Criatividade', 'Empatia', 'Dominio de Turma']
    ],

    // AGROPECUÁRIA / RURAL / LEITEIRO (Mantido o original, apenas ajustado o regex se necessário)
    'leiteiro|ordenha|leite|fazenda|rural|agropecuária|agropecuario|pecuária|pecuario|vaqueiro|gado|bovino|agricultor|agricultura|campo|plantio|colheita|tratador|curral|caseiro' => [
        'titulo' => 'Trabalhador Rural',
        'competencias' => [
            'ordenha manual e mecânica',
            'manejo de animais de grande porte',
            'alimentação e nutrição animal',
            'higienização de equipamentos de ordenha',
            'controle de qualidade do leite',
            'cuidados com saúde animal',
            'manutenção de pastagens',
            'operação de máquinas agrícolas',
            'plantio e colheita',
            'preparo do solo',
            'controle de pragas e doenças',
            'gestão de rebanho'
        ],
        'skills' => ['Ordenha Mecânica', 'Ordenha Manual', 'Manejo de Gado', 'Sanidade Animal', 'Nutrição Animal', 'Inseminação Artificial', 'Vacinação', 'Medicação Animal', 'Trator', 'Implementos Agrícolas', 'Irrigação', 'Plantio Direto', 'Colheitadeira', 'Pulverização', 'Fenação', 'Silagem', 'Cerca Elétrica', 'Curral', 'Tanque de Leite', 'Resfriador', 'Boas Práticas Agropecuárias', 'NR 31', 'Segurança Rural', 'Carteira de Operador de Máquinas', 'CNH', 'Trabalho em Equipe', 'Proatividade', 'Responsabilidade', 'Pontualidade', 'Disponibilidade de Horário']
    ],
];

/**
 * Função principal: Gera resumo inteligente
 * Retorna array com resumo e flag de match
 */
function generateSmartSummary($jobTitle, $userText, $competencias) {
    $searchText = mb_strtolower($jobTitle . ' ' . $userText, 'UTF-8');
    $matchedArea = null;
    $areaMatched = null;
    
    foreach ($competencias as $pattern => $data) {
        if (preg_match('/(' . $pattern . ')/iu', $searchText)) {
            $matchedArea = $data;
            $areaMatched = $data['titulo'];
            break;
        }
    }
    
    if (!$matchedArea) {
        return [
            'resumo' => generateGenericSummary($jobTitle, $userText),
            'matched' => false,
            'area' => null
        ];
    }
    
    // Título: prioriza o cargo informado pelo usuário
    $titulo = !empty($jobTitle) ? $jobTitle : $matchedArea['titulo'];
    
    // Seleciona 4-5 competências aleatórias
    shuffle($matchedArea['competencias']);
    $competenciasEscolhidas = array_slice($matchedArea['competencias'], 0, rand(4, 5));
    
    // Seleciona 6-8 skills
    shuffle($matchedArea['skills']);
    $skillsEscolhidas = array_slice($matchedArea['skills'], 0, rand(6, 8));
    
    $competenciasTexto = implode(', ', $competenciasEscolhidas);
    $skillsTexto = implode(', ', $skillsEscolhidas);
    
    // Estrutura ATS
    $resumo = "{$titulo} com experiência em {$competenciasTexto}. ";
    $resumo .= "Domínio em {$skillsTexto}. ";
    $resumo .= "Profissional comprometido com entregas de qualidade, aprendizado contínuo e resultados.";
    
    return [
        'resumo' => $resumo,
        'matched' => true,
        'area' => $areaMatched
    ];
}

function generateGenericSummary($jobTitle, $userText) {
    $titulo = !empty($jobTitle) ? $jobTitle : 'Profissional';
    
    return "{$titulo} comprometido com resultados e desenvolvimento contínuo. Competências em comunicação, organização, trabalho em equipe e resolução de problemas. Capacidade de adaptação a novos ambientes e desafios. Busco oportunidade para aplicar minhas habilidades e contribuir com os objetivos organizacionais.";
}

$result = generateSmartSummary($jobTitle, $text, $competencias);

echo json_encode([
    'success' => true,
    'enhanced_text' => $result['resumo'],
    'original_text' => $text,
    'job_title_used' => $jobTitle,
    'area_matched' => $result['area'],
    'used_generic' => !$result['matched'],
    'ats_optimized' => true
]);

