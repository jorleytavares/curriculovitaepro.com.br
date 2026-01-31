<?php
// --- SEO DINÂMICO ---
$current_page = basename($_SERVER['SCRIPT_NAME']); // ex: index.php
$base_url = "https://curriculovitaepro.com.br"; // Ajuste para seu domínio em produção
$site_name = "Currículo Vitae Pro";

// Configurações Padrão (Respeita variáveis pré-definidas por páginas individuais)
$seo_title = $seo_title ?? "Currículo Vitae Pro | Criador de Currículo com IA Grátis (ATS & Gupy)";
$seo_desc = $seo_desc ?? "Crie currículos profissionais otimizados para ATS e Gupy com inteligência artificial. Editor 100% gratuito, exportação em PDF Alta Resolução e suporte a PcD.";
$seo_robots = $seo_robots ?? "index, follow";
$seo_image = $seo_image ?? "$base_url/public/images/Curriculo Vitae Pro - logomarca.avif";
$seo_type = $seo_type ?? "website";
$seo_keywords = $seo_keywords ?? "criar currículo grátis, gerador de currículo ia, modelo curriculo ats, currículo gupy, editor de curriculo pdf";

// Lógica por Página
switch ($current_page) {
    case 'index.php':
        // Home usa os padrões (já definidos acima)
        break;
    case 'login.php':
        $seo_title = "Entrar - Currículo Vitae Pro";
        $seo_desc = "Acesse sua conta no Currículo Vitae Pro para gerenciar e editar seus currículos.";
        $seo_robots = "noindex, follow"; // Não indexar login
        break;
    case 'register.php':
        $seo_title = "Criar Conta Grátis - Currículo Vitae Pro";
        $seo_desc = "Cadastre-se no Currículo Vitae Pro. É rápido, fácil e gratuito. Comece sua carreira hoje.";
        break;
    case 'dashboard.php':
        $seo_title = "Meu Painel - Currículo Vitae Pro";
        $seo_robots = "noindex, nofollow"; // Área privada
        break;
    case 'editor.php':
        $seo_title = "Editor de Currículo - Currículo Vitae Pro";
        $seo_robots = "noindex, nofollow"; // Área privada
        break;
    case 'upgrade.php':
        $seo_title = "Planos e Preços - Currículo Vitae Pro";
        $seo_desc = "Torne-se PRO e crie currículos ilimitados com suporte prioritário e recursos de IA.";
        break;
}

// Canonical URL
$canonical_url = $base_url . $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="pt-br" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Primary Meta Tags -->
    <title><?php echo htmlspecialchars($seo_title); ?></title>
    <meta name="title" content="<?php echo htmlspecialchars($seo_title); ?>">
    <meta name="description" content="<?php echo htmlspecialchars($seo_desc); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seo_keywords); ?>">
    <meta name="author" content="Currículo Vitae Pro">
    <meta name="publisher" content="Host Amazonas - Criação de Sites">
    <meta name="robots" content="<?php echo htmlspecialchars($seo_robots); ?>">
    <meta name="revisit-after" content="7 days">
    <meta name="generator" content="Currículo Vitae Pro v2.0">
    
    <!-- Geo Tags (Brasil Focus) -->
    <meta name="geo.region" content="BR">
    <meta name="geo.placename" content="São Paulo">
    <meta name="geo.position" content="-23.55052;-46.633308">
    <meta name="ICBM" content="-23.55052, -46.633308">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?php echo htmlspecialchars($seo_type); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($seo_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seo_desc); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($seo_image); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($site_name); ?>">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($seo_title); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($seo_desc); ?>">
    <meta property="twitter:image" content="<?php echo htmlspecialchars($seo_image); ?>">
    
    <!-- Theme & Icons -->
    <meta name="theme-color" content="#0f172a">
    <meta name="msapplication-navbutton-color" content="#0f172a">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    
    <!-- Structured Data (JSON-LD) for Rich Snippets -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "Organization",
          "name": "Currículo Vitae Pro",
          "url": "<?php echo $base_url; ?>",
          "logo": "<?php echo $seo_image; ?>",
          "sameAs": [
            "https://www.facebook.com/curriculovitaepro",
            "https://www.instagram.com/curriculovitaepro",
            "https://www.linkedin.com/company/curriculo-vitae-pro"
          ],
          "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+55-11-99999-9999",
            "contactType": "customer service",
            "areaServed": "BR",
            "availableLanguage": "Portuguese"
          }
        },
        {
          "@type": "SoftwareApplication",
          "name": "Currículo Vitae Pro",
          "applicationCategory": "BusinessApplication",
          "operatingSystem": "Web, Android, iOS",
          "url": "<?php echo $base_url; ?>",
          "screenshot": "<?php echo $base_url; ?>/public/images/mobile-cv-editor-realism.png",
          "offers": {
            "@type": "Offer",
            "price": "0.00",
            "priceCurrency": "BRL"
          },
          "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.8",
            "ratingCount": "1250",
            "bestRating": "5",
            "worstRating": "1"
          },
          "featureList": "Gerador de Currículo com IA, Otimização para ATS, Exportação em PDF, Modelos Prontos"
        },
        {
          "@type": "WebSite",
          "name": "Currículo Vitae Pro",
          "url": "<?php echo $base_url; ?>",
          "potentialAction": {
            "@type": "SearchAction",
            "target": "<?php echo $base_url; ?>/search?q={search_term_string}",
            "query-input": "required name=search_term_string"
          }
        },
        <?php if ($current_page === 'index.php'): ?>
        {
          "@type": "FAQPage",
          "mainEntity": [
            {
              "@type": "Question",
              "name": "O Currículo Vitae Pro é realmente gratuito?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Sim, nossa versão básica é 100% gratuita. Você pode criar, editar e baixar seu currículo em PDF sem pagar nada e sem marca d'água."
              }
            },
            {
              "@type": "Question",
              "name": "Os modelos são compatíveis com sistemas ATS (Gupy, Kenoby)?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Sim. Todos os nossos modelos foram desenhados seguindo as melhores práticas de legibilidade para robôs ATS, garantindo que suas informações sejam lidas corretamente pelas plataformas de recrutamento."
              }
            },
            {
              "@type": "Question",
              "name": "Posso usar no celular?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Com certeza. Nossa plataforma é totalmente responsiva (mobile-first). Você pode criar ou editar seu currículo diretamente do seu smartphone, em qualquer lugar."
              }
            },
            {
              "@type": "Question",
              "name": "Como funciona a IA do site?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Nossa IA analisa seu perfil e sugere melhorias no texto, ajuda a escrever resumos profissionais impactantes e gera tópicos de experiência baseados no seu cargo, tudo focado em persuasão e palavras-chave."
              }
            }
          ]
        }
        <?php endif; ?>
      ]
    }
    </script>

    <!-- Google Analytics 4 (Consent Mode) -->
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      
      // Negar por padrão (LGPD)
      gtag('consent', 'default', {
        'ad_storage': 'denied',
        'analytics_storage': 'denied',
        'functionality_storage': 'denied',
        'personalization_storage': 'denied',
        'security_storage': 'granted', // Cookies essenciais de segurança
        'wait_for_update': 500
      });

      // Verificar consentimento salvo
      if (localStorage.getItem('vitae_cookie_consent') === 'accepted') {
          gtag('consent', 'update', {
            'analytics_storage': 'granted',
            'ad_storage': 'granted',
            'functionality_storage': 'granted',
            'personalization_storage': 'granted'
          });
      }
    </script>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-DXEW8RRB78"></script>
    <script>
      gtag('js', new Date());
      gtag('config', 'G-DXEW8RRB78');
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#581c87"> <!-- purple-900 -->

    <!-- SEO Meta Tags -->
    <title><?php echo htmlspecialchars($seo_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seo_desc); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seo_keywords ?? ''); ?>">
    <meta name="robots" content="<?php echo $seo_robots; ?>">
    <meta name="author" content="Currículo Vitae Pro">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/avif" href="/public/images/Curriculo Vitae Pro - Favicon.avif">
    <link rel="apple-touch-icon" href="/public/images/icon-192.png">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- Open Graph / Facebook / WhatsApp -->
    <meta property="og:type" content="<?php echo $seo_type; ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($seo_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seo_desc); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($seo_image); ?>">
    <meta property="og:site_name" content="<?php echo $site_name; ?>">
    <meta property="og:locale" content="pt_BR">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($seo_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($seo_desc); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($seo_image); ?>">

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "Currículo Vitae Pro",
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "Web Browser",
      "url": "https://curriculovitaepro.com.br/", 
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "BRL"
      },
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.8",
        "ratingCount": "12054"
      },
      "featureList": [
        "Otimização ATS para Gupy e Kenoby",
        "Assistente de Escrita com IA Generativa",
        "Campos para Nome Social e PcD",
        "Exportação PDF Alta Resolução"
      ],
      "description": "Editor de currículos online gratuito com inteligência artificial. Focado em aprovação em sistemas ATS."
    }
    </script>

    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        neon: {
                            purple: '#a855f7', // Purple-500
                            dark: '#0f172a',   // Slate-900
                            light: '#e2e8f0'
                        }
                    },
                    boxShadow: {
                        'neon': '0 0 10px rgba(168, 85, 247, 0.5), 0 0 20px rgba(168, 85, 247, 0.3)',
                    },
                    animation: {
                        'pulse-glow': 'pulse-glow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        'pulse-glow': {
                            '0%, 100%': { opacity: 1 },
                            '50%': { opacity: .5 },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        html { scroll-behavior: smooth; }
        
        /* Transições suaves de tema */
        body, div, nav, button, input, textarea, select {
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }
    </style>
    <script>
        // Lógica de Inicialização do Tema Unificada
        // O gerenciamento pesado é feito pelo script do footer.php para garantir o estilo Premium
        
        function toggleTheme() {
            // Verifica estado atual
            const isDark = document.documentElement.classList.contains('dark');
            const newMode = isDark ? 'light' : 'dark';
            
            // Chama o gerenciador global se disponível (carregado no footer)
            if (window.setTheme) {
                window.setTheme(newMode);
            } else {
                // Fallback de segurança
                if (newMode === 'dark') document.documentElement.classList.add('dark');
                else document.documentElement.classList.remove('dark');
            }
            updateModuleIcons(newMode);
        }

        function updateModuleIcons(mode) {
           const suns = document.querySelectorAll('.icon-sun');
           const moons = document.querySelectorAll('.icon-moon');
           if (mode === 'light') {
               suns.forEach(el => el.classList.remove('hidden'));
               moons.forEach(el => el.classList.add('hidden'));
           } else {
               suns.forEach(el => el.classList.add('hidden'));
               moons.forEach(el => el.classList.remove('hidden'));
           }
        }

        // Sincronizar ícones ao carregar
        document.addEventListener('DOMContentLoaded', () => {
            const currentTheme = localStorage.getItem('vitae_theme') || 'dark';
            updateModuleIcons(currentTheme);
            
            // Proteção de Marca - Console Warning
            console.log("%cATENÇÃO!", "color: red; font-size: 30px; font-weight: bold; -webkit-text-stroke: 1px black;");
            console.log("%cO código fonte deste projeto é verificado e protegido por direitos autorais. A cópia, reprodução ou engenharia reversa não autorizada é monitorada.", "font-size: 14px; color: #666;");
            console.log("%cCurrículo Vitae Pro © 2026", "font-weight: bold;");
        });
    </script>
    <!-- PWA Service Worker Register -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js')
                    .then(reg => console.log('PWA Service Worker registrado!', reg.scope))
                    .catch(err => console.error('Falha no SW:', err));
            });
        }
    </script>
</head>
<body class="bg-slate-50 dark:bg-[#131c31] text-slate-900 dark:text-slate-200 min-h-screen flex flex-col transition-colors duration-300">
    <?php if ((!isset($is_auth_page) || !$is_auth_page) && (!isset($hide_global_nav) || !$hide_global_nav)): ?>
    <nav class="border-b border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-800/50 backdrop-blur-md sticky top-0 z-50 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center shrink-0">
                    <a href="/" class="flex items-center group transition-transform duration-300 hover:scale-105" title="Currículo Vitae Pro - Página Inicial">
                        <img src="/public/images/Curriculo Vitae Pro - logomarca.avif" alt="Currículo Vitae Pro" title="Currículo Vitae Pro - Home" class="h-10 w-auto">
                    </a>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="/blog" class="text-slate-600 dark:text-slate-300 hover:text-purple-600 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors" title="Ver artigos do blog">Blog</a>
                        <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                            <div class="flex items-center gap-4">
                                <?php
                                $isAdmin = false;
                                if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                                    $isAdmin = true;
                                } elseif (isset($_SESSION['user_id'])) {
                                     require_once __DIR__ . '/../../config/database.php';
                                     $stmtAdm = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                                     $stmtAdm->execute([$_SESSION['user_id']]);
                                     $uData = $stmtAdm->fetch();
                                     if ($uData && $uData['role'] === 'admin') {
                                         $isAdmin = true;
                                         $_SESSION['role'] = 'admin'; 
                                     }
                                }
                                ?>
                                
                                <?php if ($isAdmin): ?>
                                    <a href="/admin_dashboard.php" class="bg-red-600 hover:bg-red-500 text-white px-3 py-2 rounded-md text-xs font-bold uppercase shadow-sm transition-colors border border-red-500 !text-white tracking-wider" title="Acessar painel administrativo">Painel Admin</a>
                                <?php endif; ?>

                                <!-- User Profile Link -->
                                <a href="/minha-conta" class="group flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors" title="Minha Conta">
                                    <div class="h-8 w-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 group-hover:border-purple-300 dark:group-hover:border-purple-700 transition-colors overflow-hidden">
                                        <?php if (isset($_SESSION['user_avatar'])): ?>
                                            <img src="<?php echo $_SESSION['user_avatar']; ?>" alt="Avatar" class="h-full w-full object-cover">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <span class="font-medium hidden lg:inline-block border-b border-transparent group-hover:border-purple-400">
                                        <?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?>
                                    </span>
                                </a>

                                <a href="/painel" class="text-slate-600 dark:text-slate-300 hover:text-purple-600 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors" title="Acessar meu painel de currículos">Dashboard</a>
                                <a href="/sair" class="text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-500/10 px-3 py-2 rounded-md text-sm font-medium transition-colors" title="Encerrar sessão">Sair</a>
                            </div>
                        <?php else: ?>
                            <a href="/entrar" class="text-slate-600 dark:text-slate-300 hover:text-purple-600 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors" title="Entrar na sua conta">Login</a>
                            <a href="/criar-conta" onclick="if(typeof trackSignupStart === 'function') trackSignupStart();" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white px-4 py-2 rounded-md text-sm font-medium shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all" title="Criar conta grátis">Criar Conta</a>
                        <?php endif; ?>
                        
                        <!-- Toggle Theme Button -->
                        <button onclick="toggleTheme()" class="ml-4 p-2 rounded-full text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700 transition-all" title="Alternar Tema">
                            <!-- Sun Icon (Show in Dark) -->
                            <svg class="w-5 h-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            <!-- Moon Icon (Show in Light) -->
                            <svg class="w-5 h-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                        </button>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <div class="-mr-2 flex md:hidden gap-2">
                     <button onclick="toggleTheme()" class="p-2 rounded-full text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">
                        <svg class="w-5 h-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        <svg class="w-5 h-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                    </button>
                    <button class="bg-white dark:bg-slate-800 p-2 rounded-md text-slate-600 dark:text-slate-400 hover:text-purple-600 dark:hover:text-white border border-slate-200 dark:border-slate-700">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    <main class="flex-grow flex flex-col <?php echo (isset($is_auth_page) && $is_auth_page) ? 'items-center justify-center py-12 px-4 sm:px-6 lg:px-8' : ''; ?> relative overflow-hidden w-full">
        <!-- Background decorative elements -->
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl -z-10 pointer-events-none"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl -z-10 pointer-events-none"></div>

