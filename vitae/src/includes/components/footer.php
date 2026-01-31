</main>
    <?php if (!isset($is_auth_page) || !$is_auth_page): ?>
    <footer class="bg-slate-900 border-t border-slate-800 py-12 mt-auto relative z-10 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8 mb-8 border-b border-slate-800 pb-8">
                <div class="md:col-span-2">
                <a href="/" class="inline-block mb-4 transition-transform duration-300 hover:scale-105" title="Currículo Vitae Pro - Página Inicial">
                        <img src="/public/images/Curriculo Vitae Pro - logomarca.avif" alt="Currículo Vitae Pro" title="Currículo Vitae Pro - Rodapé" class="h-8 w-auto">
                    </a>
                    <p class="text-slate-400 text-sm mt-4 leading-relaxed max-w-sm">
                        Plataforma profissional para construção de currículos de alta performance. Tecnologia compatível com ATS e otimizada para o mercado brasileiro.
                    </p>
                </div>
                <div>
                    <div class="text-white font-bold mb-4 text-base">Legal</div>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="/termos-de-uso" class="hover:text-purple-400 transition-colors" title="Ler termos de uso do serviço">Termos de Uso</a></li>
                        <li><a href="/privacidade" class="hover:text-purple-400 transition-colors" title="Ler política de privacidade">Política de Privacidade</a></li>
                    </ul>
                </div>
                <div>
                    <div class="text-white font-bold mb-4 text-base">Host Amazonas</div>
                    <ul class="space-y-1 text-sm text-slate-400">
                        <li>CNPJ: 32.716.688/0001-05</li>
                        <li>Rua Barao do Rio Branco, 699</li>
                        <li>Manaus - AM, 69058-581</li>
                        <li class="pt-2"><a href="mailto:suporte@curriculovitaepro.com.br" class="hover:text-purple-400" title="Enviar email para suporte">suporte@curriculovitaepro.com.br</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="flex flex-col md:flex-row items-center justify-between text-slate-400 text-xs">
                <div class="mb-4 md:mb-0">
                    &copy; <?php echo date('Y'); ?> Currículo Vitae Pro. Todos os direitos reservados.
                </div>
                <div class="flex flex-col items-center md:items-end">
                    <div class="max-w-md text-center md:text-right opacity-60">
                        Este site não faz parte do site do Facebook ou Facebook Inc. Além disso, este site NÃO é endossado pelo Facebook de nenhuma maneira. FACEBOOK é uma marca comercial da FACEBOOK, Inc.
                    </div>
                    <div class="mt-2 text-[10px] text-slate-400 text-center md:text-right opacity-70 max-w-lg">
                        * Imagens de perfil e depoimentos apresentados são representações ilustrativas geradas por Inteligência Artificial para preservar a privacidade e identidade de nossos usuários reais.
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- BANNER DE COOKIES (LGPD) -->
    <div id="cookie-banner" class="fixed bottom-0 left-0 w-full bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 p-6 shadow-[0_-4px_20px_rgba(0,0,0,0.1)] z-[100] transform translate-y-full transition-transform duration-500 font-sans hidden">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-start gap-4 max-w-2xl">
                <div class="bg-purple-100 dark:bg-purple-900/30 p-2 rounded-lg text-purple-600 dark:text-purple-400 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <p class="font-bold text-slate-900 dark:text-white mb-1 text-lg">Valorizamos sua privacidade</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                        Utilizamos cookies para personalizar conteúdo, melhorar a navegação e analisar nosso tráfego (Google Analytics). Ao clicar em "Aceitar", você concorda com o uso de todos os cookies conforme nossa <a href="/privacidade" class="text-purple-600 hover:underline" title="Ler política de privacidade">Política de Privacidade</a>.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <button onclick="manageConsent('denied')" class="px-5 py-2.5 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors border border-transparent hover:border-slate-300">
                    Recusar
                </button>
                <button onclick="manageConsent('granted')" class="px-6 py-2.5 text-sm font-bold text-white bg-purple-600 hover:bg-purple-700 rounded-lg shadow-lg hover:shadow-purple-500/25 transition-all transform hover:-translate-y-0.5">
                    Aceitar Todos
                </button>
            </div>
        </div>
    </div>

    <!-- SCRIPT DO BANNER DE COOKIES -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
             const banner = document.getElementById('cookie-banner');
             // Verifica se já existe decisão
             if (!localStorage.getItem('vitae_cookie_consent')) {
                 // Mostra banner com delay suave
                 banner.classList.remove('hidden');
                 setTimeout(() => {
                     banner.classList.remove('translate-y-full');
                 }, 500);
             }
        });

        function manageConsent(state) {
            const banner = document.getElementById('cookie-banner');
            
            // Salva decisão
            const decision = state === 'granted' ? 'accepted' : 'rejected';
            localStorage.setItem('vitae_cookie_consent', decision);
            
            // Atualiza Google Consent Mode
            if (typeof gtag === 'function') {
                gtag('consent', 'update', {
                    'analytics_storage': state,
                    'ad_storage': state,
                    'functionality_storage': state,
                    'personalization_storage': state
                });
                
                // Se aceitou, dispara evento de página vista agora (já que o inicial foi bloqueado)
                if (state === 'granted') {
                    gtag('event', 'consent_accepted');
                }
            }

            // Esconde banner
            banner.classList.add('translate-y-full');
            setTimeout(() => {
                banner.classList.add('hidden');
            }, 500);
        }
    </script>
    <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-2 group font-sans">
        
        <!-- Painel de Controles -->
        <div id="a11y-panel" class="bg-white text-slate-900 p-4 rounded-xl shadow-2xl border border-slate-200 hidden mb-2 w-56 transition-all animate-[fadeIn_0.2s_ease-out]">
            <div class="flex justify-between items-center mb-3">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Acessibilidade</div>
                <button onclick="toggleA11yPanel()" class="text-slate-400 hover:text-red-500">&times;</button>
            </div>
            
            <!-- Tema -->
            <div class="mb-4">
                <label class="text-sm font-medium text-slate-700 block mb-2">Contraste</label>
                <div class="flex bg-slate-100 rounded-lg p-1">
                    <button onclick="setTheme('dark')" id="btn-theme-dark" class="flex-1 p-1.5 rounded-md text-xs font-bold transition-all bg-white shadow text-purple-600">Escuro</button>
                    <button onclick="setTheme('light')" id="btn-theme-light" class="flex-1 p-1.5 rounded-md text-xs font-bold text-slate-500 hover:text-slate-900 transition-all">Claro</button>
                </div>
            </div>

            <!-- Fonte -->
            <div class="mb-4">
                <label class="text-sm font-medium text-slate-700 block mb-2">Tamanho do Texto</label>
                <div class="flex bg-slate-100 rounded-lg p-1 gap-1">
                    <button onclick="adjustFont(-10)" class="flex-1 p-1.5 bg-white shadow rounded hover:bg-slate-50 text-slate-700 text-xs">A-</button>
                    <button onclick="adjustFont(0)" class="flex-1 p-1.5 bg-white shadow rounded hover:bg-slate-50 text-slate-700 text-xs font-bold">100%</button>
                    <button onclick="adjustFont(10)" class="flex-1 p-1.5 bg-white shadow rounded hover:bg-slate-50 text-slate-700 text-xs text-lg">A+</button>
                </div>
            </div>

            <!-- Reset -->
            <button onclick="resetA11y()" class="w-full text-center text-xs text-slate-400 hover:text-purple-600 underline py-1 transition-colors">
                Restaurar Padrões
            </button>
        </div>

        <!-- Botão Toggle -->
        <button id="a11y-toggle" onclick="toggleA11yPanel()" class="bg-purple-600 hover:bg-purple-500 text-white w-12 h-12 rounded-full shadow-lg shadow-purple-500/30 transition-transform hover:scale-110 flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-slate-900" aria-label="Opções de Acessibilidade" title="Acessibilidade">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        </button>
    </div>

    <!-- SCRIPT DE ACESSIBILIDADE -->
    <script>
        (function() {
            const html = document.documentElement;
            const body = document.body;
            const panel = document.getElementById('a11y-panel');
            const btnDark = document.getElementById('btn-theme-dark');
            const btnLight = document.getElementById('btn-theme-light');
            let lightStyleTag = document.getElementById('light-mode-overrides');
            
            let currentZoom = 100;

            // Carregar preferências
            const savedTheme = localStorage.getItem('vitae_theme') || 'dark';
            const savedZoom = localStorage.getItem('vitae_zoom');

            // Inicialização
            if (!lightStyleTag) {
                lightStyleTag = document.createElement('style');
                lightStyleTag.id = 'light-mode-overrides';
                document.head.appendChild(lightStyleTag);
            }
            
            applyTheme(savedTheme);

            if (savedZoom) {
                currentZoom = parseInt(savedZoom);
                html.style.fontSize = currentZoom + '%';
            }

            // Expor funções globais
            window.toggleA11yPanel = function() {
                panel.classList.toggle('hidden');
            };

            window.setTheme = function(mode) {
                applyTheme(mode);
                localStorage.setItem('vitae_theme', mode);
            };

            window.adjustFont = function(change) {
                if (change === 0) {
                    currentZoom = 100;
                } else {
                    currentZoom += change;
                    if (currentZoom < 70) currentZoom = 70;
                    if (currentZoom > 150) currentZoom = 150;
                }
                html.style.fontSize = currentZoom + '%';
                localStorage.setItem('vitae_zoom', currentZoom);
            };

            window.resetA11y = function() {
                setTheme('dark');
                adjustFont(0);
                localStorage.removeItem('vitae_theme');
                localStorage.removeItem('vitae_zoom');
            };

            function applyTheme(mode) {
                if (mode === 'light') {
                    // MODO CLARO (LIGHT MODE)
                    html.classList.remove('dark');
                    html.classList.add('light'); // Útil para seletores CSS
                    
                    // Injeção CSS: TEMA 'MEU CURRÍCULO PERFEITO' (Reference Copy)
                    lightStyleTag.innerHTML = `
                        /* === GLOBAL === */
                        body { 
                            background-color: #fbf9f4 !important; /* Creme Suave (Papel Antigo) */
                            color: #374151 !important; /* Cinza Carvão Suave */
                            font-family: 'Inter', system-ui, sans-serif;
                        }

                        /* === TIPOGRAFIA === */
                        h1, h2, h3, h4, h5, h6 { 
                            color: #1a365d !important; /* Azul Marinho Profundo (Navy) */
                            font-weight: 800 !important;
                        }
                        p, li, div { 
                            color: #4b5563 !important; /* Cinza Médio */
                        }
                        
                        /* Destaques de Texto (Remover Gradientes) */
                        .text-transparent, .bg-clip-text, span.bg-gradient-to-r {
                            background: none !important;
                            -webkit-text-fill-color: initial !important;
                            color: #1a365d !important; /* Mesmo Navy do Título */
                        }

                        /* === BOTÕES (A Alma da Paleta) === */
                        /* Botão Principal (TEAL / VERDE PETRÓLEO) */
                        a.bg-purple-600, button.bg-purple-600, 
                        a.bg-gradient-to-r, button.bg-gradient-to-r {
                            background-color: #0d9488 !important; /* Teal-600 (Verde Petróleo) */
                            background-image: none !important;
                            color: #ffffff !important;
                            border-radius: 9999px !important; /* Pill Shape (Arredondado como na ref) */
                            font-weight: 700 !important;
                            box-shadow: 0 4px 6px -1px rgba(13, 148, 136, 0.3) !important;
                            transition: all 0.2s ease;
                        }
                        a.bg-purple-600:hover, button.bg-purple-600:hover {
                            background-color: #0f766e !important; /* Teal-700 */
                            transform: translateY(-2px);
                        }
                        
                        /* Botão Secundário (Outline ou Clean) */
                        a.bg-slate-800, button.bg-slate-800 {
                            background-color: transparent !important;
                            color: #1a365d !important;
                            border: 2px solid #1a365d !important;
                            border-radius: 9999px !important;
                            box-shadow: none !important;
                        }
                        a.bg-slate-800:hover {
                            background-color: #1a365d !important;
                            color: #ffffff !important;
                        }

                        /* === COMPONENTES === */
                        /* Cards: Fundo Branco para destacar no Creme */
                        /* Cards: Fundo Branco para destacar no Creme */
                        .bg-slate-900, article, 
                        div.bg-slate-800, div.bg-slate-800\/60, 
                        .bg-slate-800\/50, .bg-slate-800\/40 {
                            background-color: #ffffff !important;
                            border: 1px solid #e5e7eb !important; /* Borda suave */
                            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05) !important;
                            border-radius: 16px !important;
                            color: #374151 !important; /* Texto legível */
                        }

                        /* Navbar Clean */
                        nav {
                            background-color: #fbf9f4 !important; /* Mesmo do fundo (blend) */
                            border-bottom: none !important;
                            box-shadow: none !important;
                        }
                        nav a, nav button { 
                            color: #1a365d !important; 
                            font-weight: 600 !important;
                            text-transform: uppercase !important;
                            font-size: 0.75rem !important;
                            letter-spacing: 0.05em !important;
                        }
                        
                        /* Exceção: Botões Vermelhos na Navbar (Admin) */
                        nav a.bg-red-600 {
                             color: #ffffff !important;
                             background-color: #dc2626 !important; /* Red 600 */
                             border-color: #dc2626 !important;
                        }
                        
                        /* Logo */
                        nav .font-bold { color: #d97706 !important; /* Âmbar (Toque quente opcional ou Navy) */ }
                        nav .first-letter { color: #1a365d !important; }

                        /* Inputs */
                        input, textarea, select {
                            background-color: #ffffff !important;
                            border: 1px solid #d1d5db !important;
                            color: #1f2937 !important;
                            border-radius: 8px !important;
                        }
                        input:focus {
                            border-color: #0d9488 !important; /* Teal Focus */
                            ring: 2px solid #0d9488 !important;
                        }

                        /* Footer - Claro no Light Mode */
                        footer {
                            background-color: #f3f4f6 !important; /* Cinza claro */
                            color: #1f2937 !important; /* Texto escuro */
                            border-top: 1px solid #e5e7eb !important;
                        }
                        footer p, footer a, footer div {
                            color: #4b5563 !important; /* Cinza médio */
                        }
                        footer h4 {
                            color: #1a365d !important; /* Navy para títulos */
                        }
                        footer a:hover {
                            color: #0d9488 !important; /* Teal no hover */
                        }

                        /* Efeitos de Fundo (Bolhas) */
                        .blur-\\[120px\\], .blur-\\[100px\\] { 
                            opacity: 0 !important;
                        }
                        
                        /* Seção FAQ */
                        details {
                            background-color: #ffffff !important;
                            border: 1px solid #e5e7eb !important;
                        }
                        details summary { color: #1a365d !important; font-weight: 600; }

                        /* === EXCEÇÕES (ALWAYS DARK) - "NUCLEAR OPTION" === */
                        .always-dark,
                        .always-dark h1, .always-dark h2, .always-dark h3, .always-dark h4,
                        .always-dark p, .always-dark li, 
                        .always-dark .text-white {
                            color: #ffffff !important;
                        }
                        
                        /* Regra Base para DIVs e SPANs (com menos especificidade se possível, ou sobrescrita abaixo) */
                        .always-dark div, .always-dark span {
                             color: #ffffff !important;
                        }

                        /* === CORREÇÕES PARA BOTÕES E INPUTS DENTRO DE DARK === */
                        /* Sobrescreve a regra acima para trazer a cor de volta */
                        .always-dark button, 
                        .always-dark input,
                        .always-dark select,
                        .always-dark textarea,
                        .always-dark .bg-white, /* Se tem fundo branco, provavelmente quer texto escuro */
                        .always-dark .text-slate-900,
                        .always-dark .text-indigo-900,
                        .always-dark .text-black,
                        .always-dark button span,
                        .always-dark .bg-white span,
                        .always-dark .bg-white div {
                            color: #0f172a !important; /* Slate 900 */
                        }
                        
                        /* Cores específicas de destaque */
                        .always-dark .text-amber-400 { color: #fbbf24 !important; }
                        .always-dark .text-green-400 { color: #4ade80 !important; }
                        .always-dark .text-cyan-400 { color: #22d3ee !important; }
                        .always-dark .text-indigo-200 { color: #c7d2fe !important; }
                    `;
                    
                    // UI Estado dos Botões
                    btnLight.classList.add('bg-white', 'shadow', 'text-purple-600');
                    btnLight.classList.remove('text-slate-500');
                    btnDark.classList.remove('bg-white', 'shadow', 'text-purple-600');
                    btnDark.classList.add('text-slate-500');

                } else {
                    // MODO ESCURO (DARK MODE - Padrão)
                    html.classList.add('dark');
                    html.classList.remove('light');
                    
                    // Remove injeção CSS
                    lightStyleTag.innerHTML = '';
                    
                    // UI Estado dos Botões
                    btnDark.classList.add('bg-white', 'shadow', 'text-purple-600');
                    btnDark.classList.remove('text-slate-500');
                    btnLight.classList.remove('bg-white', 'shadow', 'text-purple-600');
                    btnLight.classList.add('text-slate-500');
                }
            }
        })();
    </script>
</body>
</html>
