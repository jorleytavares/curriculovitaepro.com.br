<?php
// Ajustando paths para estrutura atual (src/index.php)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';

// ==============================================================
// MODO MANUTENÇÃO
// Gerenciado via .htaccess e maintenance.php
// ==============================================================
// ==============================================================

// Fetch Blog Posts for Home
$latestPosts = [];
try {
    $stmt = $pdo->query("SELECT * FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
    $latestPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { /* Ignore if table doesn't exist yet */ }

// Redirecionamento Inteligente: Se o usuário já está logado, vai direto para o dashboard
if (isLoggedIn()) {
    header("Location: /painel");
    exit;
}

include __DIR__ . '/includes/components/header.php';
?>

<!-- GSAP CDNs para animações de alta performance -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

<!-- HERO SECTION -->
<!-- Reduced padding from pt-32/pb-24 to pt-24/pb-16 -->
<section aria-label="Introdução" class="relative overflow-hidden pt-24 pb-16 lg:pt-36 lg:pb-28 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800/50 transition-colors duration-500">
    <div class="absolute top-[-10%] right-[-5%] w-[600px] md:w-[800px] h-[600px] md:h-[800px] bg-purple-600/20 rounded-full blur-[100px] opacity-60 dark:opacity-40 -z-10 bg-blob-1 pointer-events-none mix-blend-multiply dark:mix-blend-lighten"></div>
    <div class="absolute bottom-[-10%] left-[-10%] w-[500px] md:w-[700px] h-[500px] md:h-[700px] bg-cyan-500/20 rounded-full blur-[90px] opacity-60 dark:opacity-40 -z-10 bg-blob-2 pointer-events-none mix-blend-multiply dark:mix-blend-lighten"></div>
    <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20 dark:opacity-10 mix-blend-soft-light pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-5xl mx-auto">
            <div class="hero-element opacity-0 translate-y-8 inline-flex items-center gap-2.5 py-2 px-5 rounded-full bg-white/80 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/50 text-slate-600 dark:text-slate-300 text-sm font-semibold mb-8 backdrop-blur-md hover:bg-white dark:hover:bg-slate-800 transition-all shadow-sm">
                <span class="relative flex h-2.5 w-2.5">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                </span>
                <span class="group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">Nova IA de Escrita 2026</span>
                <span class="w-px h-3 bg-slate-300 dark:bg-slate-600 mx-1"></span>
                <span>Disponível Agora</span>
            </div>
            
            <h1 class="hero-element opacity-0 translate-y-8 text-5xl md:text-7xl lg:text-8xl font-black text-slate-900 dark:text-white tracking-tight leading-[0.95] mb-8">
                Construa sua carreira <br class="hidden md:block" />
                <span class="relative inline-block pb-2">
                    <span class="absolute bottom-2 left-0 w-full h-[0.2em] bg-purple-500/30 dark:bg-purple-500/50 -rotate-1 rounded-full"></span>
                    <span class="relative text-transparent bg-clip-text bg-gradient-to-r from-purple-600 via-pink-500 to-amber-500 dark:from-purple-400 dark:via-pink-400 dark:to-amber-400 animate-gradient-x">com autoridade.</span>
                </span>
            </h1>
            
            <p class="hero-element opacity-0 translate-y-8 mt-6 text-xl md:text-2xl text-slate-600 dark:text-slate-400 max-w-3xl mx-auto mb-12 leading-relaxed font-light">
                O único editor de currículos que une <strong class="text-slate-900 dark:text-white font-semibold">Design Premium</strong> e <strong class="text-slate-900 dark:text-white font-semibold">Tecnologia IA</strong> para criar documentos que realmente convertem entrevistas.
            </p>
            
            <div class="hero-element opacity-0 translate-y-8 flex flex-col sm:flex-row justify-center gap-5 items-center">
                <a href="/criar-conta" onclick="trackSignupStart()" class="group relative inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-white transition-all duration-200 bg-slate-900 dark:bg-white dark:text-slate-900 font-pj rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 hover:scale-[1.02] shadow-2xl hover:shadow-purple-500/20 w-full sm:w-auto">
                    <span class="relative flex items-center gap-3">
                        Criar Currículo Grátis
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                    </span>
                </a>
            </div>

            <div class="hero-element opacity-0 translate-y-8 mt-14 flex flex-col sm:flex-row items-center justify-center gap-5 animate-[fadeIn_1s_1s_forwards]">
                <div class="flex -space-x-4">
                    <img class="w-12 h-12 rounded-full border-4 border-slate-50 dark:border-slate-900 object-cover shadow-sm" width="48" height="48" src="/public/images/avatar-woman-professional.avif" alt="Profissional contratada - Caso de Sucesso" title="Profissional contratada">
                    <img class="w-12 h-12 rounded-full border-4 border-slate-50 dark:border-slate-900 object-cover shadow-sm" width="48" height="48" src="/public/images/avatar-man-executive.avif" alt="Executivo recolocado - Caso de Sucesso" title="Executivo recolocado">
                    <img class="w-12 h-12 rounded-full border-4 border-slate-50 dark:border-slate-900 object-cover shadow-sm" width="48" height="48" src="/public/images/avatar-woman-young.avif" alt="Jovem aprendiz contratada - Caso de Sucesso" title="Jovem aprendiz contratada">
                    <div class="w-12 h-12 rounded-full border-4 border-slate-50 dark:border-slate-900 bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-xs text-slate-900 dark:text-white font-bold shadow-sm z-10">+12k</div>
                </div>
                <div class="text-center sm:text-left">
                     <p class="text-slate-500 text-sm font-medium mt-1">Profissionais contratados em grandes empresas</p>
                </div>
            </div>
            
            <div class="hero-element opacity-0 translate-y-8 mt-16 pt-8 border-t border-slate-200 dark:border-slate-800/50">
                <p class="text-xs text-slate-400 dark:text-slate-500 mb-6 uppercase tracking-widest font-bold">Currículos compatíveis com</p>
                <div class="flex flex-wrap justify-center items-center gap-x-12 gap-y-8 opacity-40 grayscale hover:grayscale-0 transition-all duration-700">
                    <svg class="h-8 fill-current text-slate-500 dark:text-slate-400" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                    <svg class="h-8 fill-current text-slate-500 dark:text-slate-400" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 11.944 0zM7.3 16.5H4.8V8.6h2.5v7.9zm0-9.6H4.8V4.8h2.5v2.1zm5.1 2.7c0-2.8 3.9-2.9 3.9.1v6.8h-2.5v-6.3c0-1.9-2-1.6-1.4 0v6.3H9.9V8.6h2.5v1z"/></svg>
                    <svg class="h-8 text-slate-500 dark:text-slate-400" viewBox="0 0 24 24" fill="currentColor"><path d="M2.5 12a8.5 8.5 0 0 1 8.5-8.5h8.5V2.5a1 1 0 0 0-1-1H3.5a1 1 0 0 0-1 1v17a1 1 0 0 0 1 1h8.5v-9.5H3a.5.5 0 0 1-.5-.5zM12.5 12V3.5a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v17a1 1 0 0 1-1 1h-9V12z"/></svg>
                    <span class="text-2xl font-black tracking-tighter text-slate-500 dark:text-slate-400 font-sans">gupy</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- AUTHORITY BAR -->
<div class="w-full py-8 bg-black/30 border-y border-white/5 relative z-20 backdrop-blur-sm -mt-16 sm:-mt-20 lg:-mt-24 mb-16 sm:mb-20 lg:mb-24">
    <p class="text-center text-sm text-gray-500 mb-4 uppercase tracking-wider font-semibold">Compatível com os sistemas das melhores empresas</p>
    <div class="flex flex-wrap justify-center gap-8 items-center opacity-50 grayscale hover:grayscale-0 transition-all duration-500">
        <span class="text-xl font-bold text-slate-700 dark:text-white">Gupy</span>
        <span class="text-xl font-bold text-slate-700 dark:text-white">Kenoby</span>
        <span class="text-xl font-bold text-slate-700 dark:text-white">LinkedIn</span>
        <span class="text-xl font-bold text-slate-700 dark:text-white">Workday</span>
    </div>
</div>

<!-- WORKFLOW SECTION -->
<section id="como-funciona" class="py-24 bg-slate-50 dark:bg-[#0B1121] border-y border-slate-200 dark:border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-20">
            <h2 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white mb-4">Três passos para o <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-500 to-cyan-500">Sim</span></h2>
            <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">Em menos de 5 minutos, você terá um currículo que compete com profissionais experientes.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <div class="step-card group relative bg-white/50 dark:bg-slate-800/20 backdrop-blur-sm border border-slate-100 dark:border-slate-700/50 p-8 rounded-3xl hover:bg-white dark:hover:bg-slate-800 hover:shadow-2xl hover:shadow-purple-900/10 hover:-translate-y-2 transition-all duration-500">
                <div class="w-16 h-16 mb-6 rounded-2xl bg-purple-50 dark:bg-purple-900/10 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition-all duration-300 text-purple-600 dark:text-purple-400">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Escrita Guiada</h3>
                <p class="text-slate-600 dark:text-slate-300 leading-relaxed text-sm">Bloqueio criativo? Nossa IA sugere frases de impacto para descrever suas experiências em segundos.</p>
            </div>
            <div class="step-card group relative bg-white/50 dark:bg-slate-800/20 backdrop-blur-sm border border-slate-100 dark:border-slate-700/50 p-8 rounded-3xl hover:bg-white dark:hover:bg-slate-800 hover:shadow-2xl hover:shadow-cyan-900/10 hover:-translate-y-2 transition-all duration-500">
                <div class="w-16 h-16 mb-6 rounded-2xl bg-cyan-50 dark:bg-cyan-900/10 flex items-center justify-center group-hover:bg-cyan-600 group-hover:text-white transition-all duration-300 text-cyan-600 dark:text-cyan-400">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" /></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Design Instantâneo</h3>
                <p class="text-slate-600 dark:text-slate-300 leading-relaxed text-sm">Troque de "Executivo" para "Criativo" com um clique. Layouts testados que impressionam recrutadores.</p>
            </div>
            <div class="step-card group relative bg-white/50 dark:bg-slate-800/20 backdrop-blur-sm border border-slate-100 dark:border-slate-700/50 p-8 rounded-3xl hover:bg-white dark:hover:bg-slate-800 hover:shadow-2xl hover:shadow-green-900/10 hover:-translate-y-2 transition-all duration-500">
                <div class="w-16 h-16 mb-6 rounded-2xl bg-green-50 dark:bg-green-900/10 flex items-center justify-center group-hover:bg-green-600 group-hover:text-white transition-all duration-300 text-green-600 dark:text-green-400">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Exportação ATS PRO</h3>
                <p class="text-slate-600 dark:text-slate-300 leading-relaxed text-sm">PDFs limpos e estruturados que passam pelos robôs de triagem sem erros de leitura. Garantido.</p>
            </div>
        </div>
    </div>
</section>

<!-- BENEFÍCIOS SECTION (SLIDER 3D) -->
<!-- Reduced padding -->
<section class="py-16 lg:py-24 relative overflow-hidden bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-20 items-center">
            
            <div class="benefit-content opacity-0 translate-x-[-50px]">
                <h2 class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white mb-8 leading-tight">
                    O editor que <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-cyan-400">entende você.</span>
                </h2>
                
                <div class="space-y-10">
                    <div class="flex gap-6">
                        <div class="flex-shrink-0 w-16 h-16 rounded-full bg-pink-100 dark:bg-pink-900/20 flex items-center justify-center">
                            <svg class="w-8 h-8 text-pink-600 dark:text-pink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                        </div>
                        <div>
                            <h4 class="text-slate-900 dark:text-white font-bold text-xl mb-1">Identidade Real</h4>
                            <p class="text-slate-600 dark:text-slate-400 text-lg leading-relaxed">Primeiro editor do mercado com suporte nativo a <strong>Nome Social</strong> e <strong>Pronomes</strong>.</p>
                        </div>
                    </div>
                    <div class="flex gap-6">
                        <div class="flex-shrink-0 w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                        </div>
                        <div>
                             <h4 class="text-slate-900 dark:text-white font-bold text-xl mb-1">Foco em Vagas Afirmativas</h4>
                            <p class="text-slate-600 dark:text-slate-400 text-lg leading-relaxed">Campos específicos para PcD formatados para leitura rápida de recrutadores.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visual Mockup (Dynamic Slider) -->
            <div class="benefit-visual opacity-0 translate-x-[50px] relative perspective-1000 h-[600px] flex items-center justify-center">
                <div class="absolute inset-0 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full blur-[100px] opacity-20 animate-pulse"></div>
                
                <div class="relative w-full max-w-[420px] aspect-[21/29.7] isolate group">
                    <!-- CV 1: Executivo (COM FOTO + FULL CONTENT) -->
                    <div class="cv-slide absolute inset-0 bg-white shadow-2xl overflow-hidden transform transition-all duration-1000 ease-in-out origin-center rotate-y-0 opacity-100 z-40" data-slide="0">
                         <div class="w-full h-full p-6 flex flex-col bg-white text-black">
                             <div class="flex justify-between items-center border-b-2 border-slate-800 pb-4 mb-4">
                                <div><div class="text-2xl font-serif font-black uppercase text-slate-900" role="heading" aria-level="3">Carlos Mendes</div><p class="text-[10px] uppercase font-bold text-slate-600 tracking-wider mt-1">Gerente de Projetos Sênior</p></div>
                                <div class="w-12 h-12 rounded-full overflow-hidden border border-slate-300"><img src="/public/images/cv-profile-executive.avif" width="48" height="48" loading="lazy" class="w-full h-full object-cover" alt="Foto de perfil para currículo executivo - Modelo Profissional" title="Modelo de Currículo Executivo"></div>
                             </div>
                             <div class="flex-1 space-y-4">
                                 <div class="bg-slate-50 p-2 rounded border border-slate-100"><h3 class="text-[8px] font-bold uppercase tracking-widest mb-1 text-slate-900">Resumo</h3><p class="text-[8px] font-serif leading-tight text-slate-700 text-justify">Gestor com 12+ anos de experiência em projetos complexos de TI e Engenharia. Black Belt em Six Sigma, com foco em otimização de custos.</p></div>
                                 <div><h3 class="text-[8px] font-bold uppercase tracking-widest border-b border-slate-200 pb-1 mb-2 text-slate-900">Experiência Profissional</h3><div class="mb-2"><div class="flex justify-between items-baseline mb-0.5"><span class="font-bold text-[9px] text-slate-900">Global Tech Solutions</span><span class="text-[7px] font-bold text-slate-500">2019 - Presente</span></div><div class="text-[8px] italic text-slate-600 mb-1">Diretor de Operações LATAM</div><ul class="list-disc ml-3 text-[8px] text-slate-700 leading-tight space-y-0.5"><li>Gerenciamento de budget anual de R$ 45 Milhões.</li><li>Redução de turnover em 15%.</li></ul></div></div>
                             </div>
                         </div>
                    </div>
                    <!-- CV 2: Moderno -->
                    <div class="cv-slide absolute inset-0 bg-white shadow-2xl overflow-hidden transform transition-all duration-1000 ease-in-out origin-center rotate-y-180 opacity-0 z-30" data-slide="1" style="backface-visibility: hidden;">
                        <div class="flex h-full">
                            <div class="w-[35%] bg-slate-100 p-4 flex flex-col items-center pt-8 border-r border-slate-200">
                                <div class="w-20 h-20 rounded-full mb-4 mx-auto overflow-hidden border-4 border-white shadow-sm filter sepia-[.2]"><img src="/public/images/cv-profile-creative.avif" width="80" height="80" loading="lazy" class="w-full h-full object-cover" alt="Foto criativa para designer - Modelo Moderno" title="Modelo de Currículo Criativo"></div>
                                <h2 class="text-[8px] font-bold uppercase tracking-wider text-slate-800 mb-2 border-b border-slate-300 pb-1 w-full text-center">Contato</h2>
                                <h2 class="text-[8px] font-bold uppercase tracking-wider text-slate-800 mb-2 border-b border-slate-300 pb-1 w-full text-center">Skills</h2>
                            </div>
                            <div class="w-[65%] p-6 pt-8"><div class="text-3xl font-sans font-black text-slate-900 leading-none mb-1" role="heading" aria-level="3">Júlia<br>Silva</div><p class="text-[9px] text-indigo-600 font-black uppercase tracking-widest mb-6 border-l-2 border-indigo-600 pl-2">Creative Director</p><h3 class="text-[9px] font-bold uppercase text-slate-800 mb-1 flex items-center gap-1">Experiência</h3><div class="space-y-3 mb-4"><div class="pl-2 border-l border-slate-200"><div class="font-bold text-[9px] text-slate-900">Head de Design</div><p class="text-[7px] text-slate-600 leading-tight">Direção de arte para campanhas nacionais.</p></div></div></div>
                        </div>
                    </div>
                    <!-- CV 3: Acadêmico -->
                    <div class="cv-slide absolute inset-0 bg-white shadow-2xl overflow-hidden transform transition-all duration-1000 ease-in-out origin-center rotate-y-180 opacity-0 z-20" data-slide="2" style="backface-visibility: hidden;">
                        <div class="p-8 h-full flex flex-col font-serif relative">
                             <div class="flex justify-between items-start mb-4 border-b border-black pb-2 text-black"><div><div class="text-xl font-bold uppercase tracking-widest" role="heading" aria-level="3">Amanda Oliveira, PhD</div></div><div class="w-14 h-16 border border-gray-200 p-0.5 bg-white shadow-sm"><img src="/public/images/cv-profile-academic.avif" width="56" height="64" loading="lazy" class="w-full h-full object-cover grayscale" alt="Foto formal acadêmica - Modelo Lattes" title="Modelo de Currículo Lattes/Acadêmico"></div></div>
                             <div class="space-y-3 flex-1 overflow-hidden"><div><h3 class="text-[8px] font-bold uppercase text-black bg-gray-100 pl-1 mb-1">Formação</h3><div class="text-[8px] text-black">Doutorado em Genética, USP (2022)</div></div></div>
                        </div>
                    </div>
                    <!-- CV 4: Júnior -->
                    <div class="cv-slide absolute inset-0 bg-white shadow-2xl overflow-hidden transform transition-all duration-1000 ease-in-out origin-center rotate-y-180 opacity-0 z-10" data-slide="3" style="backface-visibility: hidden;">
                        <div class="p-6 h-full bg-white flex flex-col">
                            <div class="flex items-center gap-4 mb-4"><div class="w-14 h-14 rounded-lg bg-slate-200 overflow-hidden shrink-0"><img src="/public/images/cv-profile-junior.avif" width="56" height="56" loading="lazy" class="w-full h-full object-cover" alt="Foto de estudante jovem - Modelo Estágio" title="Modelo de Currículo para Estágio"></div><div><div class="text-xl font-black text-slate-800 leading-none" role="heading" aria-level="3">Pedro Santos</div><p class="text-[9px] text-blue-600 font-bold uppercase mt-1">Estudante de Economia</p></div></div>
                            <div class="flex-1 grid grid-cols-2 gap-4"><div class="space-y-3"><div><h3 class="text-[8px] font-black text-slate-900 uppercase mb-1 border-b-2 border-slate-900 w-full">Objetivo</h3><p class="text-[8px] text-slate-700 leading-tight">Estudante finalista buscando estágio em Controladoria.</p></div></div></div>
                        </div>
                    </div>
                </div>

                <div class="absolute -bottom-16 flex gap-3 z-50">
                    <button class="w-3 h-3 rounded-full bg-slate-300 hover:bg-purple-600 transition-colors duration-300 ring-2 ring-transparent ring-offset-2 focus:bg-purple-600 focus:outline-none" onclick="setSlide(0)"></button>
                    <button class="w-3 h-3 rounded-full bg-slate-300 hover:bg-purple-600 transition-colors duration-300 ring-2 ring-transparent ring-offset-2 focus:bg-purple-600 focus:outline-none" onclick="setSlide(1)"></button>
                    <button class="w-3 h-3 rounded-full bg-slate-300 hover:bg-purple-600 transition-colors duration-300 ring-2 ring-transparent ring-offset-2 focus:bg-purple-600 focus:outline-none" onclick="setSlide(2)"></button>
                    <button class="w-3 h-3 rounded-full bg-slate-300 hover:bg-purple-600 transition-colors duration-300 ring-2 ring-transparent ring-offset-2 focus:bg-purple-600 focus:outline-none" onclick="setSlide(3)"></button>
                </div>
                
                <script>
                    let currentSlide = 0;
                    const slides = document.querySelectorAll('.cv-slide');
                    const totalSlides = slides.length;
                    function updateSlides(){slides.forEach((slide,index)=>{if(index===currentSlide){slide.style.opacity='1';slide.style.transform='rotateY(0deg) scale(1)';slide.style.zIndex='40'}else if(index<currentSlide){slide.style.opacity='0';slide.style.transform='rotateY(-180deg) scale(0.9)';slide.style.zIndex='10'}else{slide.style.opacity='0';slide.style.transform='rotateY(180deg) scale(0.9)';slide.style.zIndex='10'}});const dots=document.querySelectorAll('.absolute.-bottom-16 button');dots.forEach((dot,idx)=>{dot.style.backgroundColor=idx===currentSlide?'#9333ea':'#cbd5e1'})}
                    function nextSlide(){currentSlide=(currentSlide+1)%totalSlides;updateSlides()}
                    function setSlide(index){currentSlide=index;updateSlides()}
                    let slideInterval=setInterval(nextSlide,5000);
                    const sliderContainer=document.querySelector('.benefit-visual');
                    sliderContainer.addEventListener('mouseenter',()=>clearInterval(slideInterval));
                    sliderContainer.addEventListener('mouseleave',()=>{clearInterval(slideInterval);slideInterval=setInterval(nextSlide,5000)});
                    setTimeout(updateSlides,100);
                </script>
            </div>
        </div>
    </div>
</section>

<!-- NEW SECTION: RECURSOS HUMANIZADOS (ZIG-ZAG) -->
<section class="py-24 bg-slate-50 dark:bg-[#0B1121] overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Feature 1: The Feeling of Relief (AI) -->
        <div class="flex flex-col md:flex-row items-center gap-16 mb-32">
            <div class="w-full md:w-1/2 relative group">
                <div class="absolute inset-0 bg-purple-200 dark:bg-purple-900/20 rounded-[2rem] rotate-3 transition-transform group-hover:rotate-6"></div>
                <img src="/public/images/feature-laptop-confidence.avif" width="600" height="500" loading="lazy" alt="Profissional sorrindo ao usar o computador, sentindo confiança no seu currículo - Editor com IA" title="Sentimento de realização profissional" class="relative rounded-[2rem] shadow-2xl w-full object-cover h-[400px] md:h-[500px] grayscale hover:grayscale-0 transition-all duration-700">
            </div>
            <div class="w-full md:w-1/2">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 font-bold text-sm mb-6">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Sem bloqueio criativo
                </div>
                <h2 class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white mb-6 leading-tight">Escreva sobre você com <span class="text-purple-600 dark:text-purple-400">confiança.</span></h2>
                <p class="text-lg text-slate-600 dark:text-slate-300 leading-relaxed mb-8">
                    Falar de nós mesmos é difícil. Transformamos essa ansiedade em orgulho. Nossa tecnologia sugere as palavras certas para valorizar carinhosamente cada conquista da sua jornada.
                </p>
                <div class="flex items-center gap-4 text-slate-500 font-medium">
                    <div class="flex -space-x-2">
                         <img class="w-8 h-8 rounded-full border-2 border-white" width="32" height="32" loading="lazy" src="/public/images/avatar-woman-hr.avif" alt="Avatar Usuária RH">
                         <img class="w-8 h-8 rounded-full border-2 border-white" width="32" height="32" loading="lazy" src="/public/images/avatar-man-startup.avif" alt="Avatar Usuário Startup">
                         <img class="w-8 h-8 rounded-full border-2 border-white" width="32" height="32" loading="lazy" src="/public/images/avatar-woman-casual.avif" alt="Avatar Usuária Casual">
                    </div>
                    <span>Usado por gente como a gente.</span>
                </div>
            </div>
        </div>

        <!-- Feature 2: Freedom (Mobile) -->
        <div class="flex flex-col md:flex-row-reverse items-center gap-12 md:gap-20">
            <div class="w-full md:w-1/2 relative group">
                <div class="absolute inset-0 bg-blue-200 dark:bg-blue-900/20 rounded-[2rem] -rotate-3 transition-transform group-hover:-rotate-6"></div>
                <!-- Humanized Mobile Moment -->
                <img src="/public/images/mobile-cv-editor-realism.avif" width="600" height="500" loading="lazy" alt="Tela do celular mostrando o editor de currículo aberto com campos de experiência profissional - Edição Mobile" title="Interface Mobile do Currículo Vitae Pro" class="relative rounded-[2rem] shadow-2xl w-full object-cover h-[400px] md:h-[500px] -rotate-2 hover:rotate-0 transition-all duration-700">
            </div>
            <div class="w-full md:w-1/2">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-bold text-sm mb-6">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                    No seu tempo, no seu lugar
                </div>
                <h2 class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white mb-6 leading-tight">Sua carreira cabe <span class="text-blue-500 dark:text-blue-400">no seu bolso.</span></h2>
                <p class="text-lg text-slate-600 dark:text-slate-300 leading-relaxed mb-8">
                    Não espere chegar em casa para aproveitar uma oportunidade. Atualize seu currículo no café, no ônibus ou no sofá. Simplicidade que acompanha sua vida real.
                </p>
                <ul class="space-y-3">
                    <li class="flex items-center gap-3 text-slate-700 dark:text-slate-300">
                        <span class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs">✓</span>
                        Sem apps pesados para baixar
                    </li>
                    <li class="flex items-center gap-3 text-slate-700 dark:text-slate-300">
                        <span class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs">✓</span>
                        Salvamento automático na nuvem
                    </li>
                </ul>
            </div>
        </div>

        <!-- Feature 3: Success (ATS/Result) -->
        <div class="flex flex-col md:flex-row items-center gap-12 md:gap-20">
             <div class="w-full md:w-1/2 relative group">
                <div class="absolute inset-0 bg-green-200 dark:bg-green-900/20 rounded-[2rem] rotate-3 transition-transform group-hover:rotate-6"></div>
                <img src="/public/images/feature-interview-ready.avif" width="600" height="500" loading="lazy" alt="Candidata confiante pronta para entrevista de emprego - Sucesso Profissional" title="Preparação para o sucesso" class="relative rounded-[2rem] shadow-2xl w-full object-cover h-[400px] md:h-[500px] grayscale hover:grayscale-0 transition-all duration-700">
            </div>
             <div class="w-full md:w-1/2">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 font-bold text-sm mb-6">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Pronto para o "Sim"
                </div>
                <h2 class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white mb-6 leading-tight">Chegue na entrevista <span class="text-green-600 dark:text-green-400">preparado.</span></h2>
                <p class="text-lg text-slate-600 dark:text-slate-300 leading-relaxed mb-8">
                    Nossos modelos não são apenas bonitos; eles falam a língua dos recrutadores e dos robôs de triagem. Menos barreiras técnicas, mais conversas humanas sobre o seu potencial.
                </p>
            </div>
        </div>

    </div>
</section>

<!-- PRICING SECTION -->
<section id="precos" aria-label="Planos e Preços" class="py-24 bg-white dark:bg-slate-900 transition-colors duration-500 relative">
    <!-- Divisor Visual Topo -->
    <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-purple-500/50 to-transparent"></div>
    
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center mb-16">
            <span class="inline-block px-4 py-2 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-xs font-bold uppercase tracking-wider mb-4">
                Planos Transparentes
            </span>
            <h2 class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white tracking-tight mb-4">
                Comece grátis, <span class="text-purple-600 dark:text-purple-400">evolua quando quiser</span>
            </h2>
            <p class="text-xl text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                Sem letras miúdas. Sem surpresas. Cancele quando quiser.
            </p>
        </div>

        <!-- Plans Grid -->
        <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            
            <!-- Plano Free -->
            <div class="bg-slate-50 dark:bg-slate-800 p-8 rounded-3xl border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 transition-all">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Iniciante</h3>
                <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">Perfeito para começar</p>
                
                <div class="text-5xl font-black text-slate-900 dark:text-white mb-6">
                    Grátis
                </div>
                
                <ul class="space-y-4 mb-8">
                    <li class="flex items-center gap-3 text-slate-600 dark:text-slate-300">
                        <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>1 Currículo completo</span>
                    </li>
                    <li class="flex items-center gap-3 text-slate-600 dark:text-slate-300">
                        <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Exportação em PDF</span>
                    </li>
                    <li class="flex items-center gap-3 text-slate-600 dark:text-slate-300">
                        <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Modelos profissionais</span>
                    </li>
                    <li class="flex items-center gap-3 text-slate-600 dark:text-slate-300">
                        <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>ATS-friendly (robôs)</span>
                    </li>
                </ul>
                
                <a href="/criar-conta" class="block w-full text-center py-4 px-6 rounded-xl border-2 border-slate-900 dark:border-white text-slate-900 dark:text-white font-bold hover:bg-slate-900 hover:text-white dark:hover:bg-white dark:hover:text-slate-900 transition-all">
                    Criar Conta Grátis
                </a>
            </div>

            <!-- Plano Pro -->
            <div class="relative bg-gradient-to-br from-slate-900 to-slate-800 dark:from-slate-800 dark:to-slate-900 p-8 rounded-3xl border-2 border-purple-500 shadow-2xl shadow-purple-500/20 overflow-hidden always-dark">
                
                <!-- Badge Promoção -->
                <div class="absolute top-0 left-0 right-0 bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 text-white text-xs font-black py-2 text-center tracking-wider">
                    🔥 50% OFF NOS 6 PRIMEIROS MESES
                </div>
                
                <div class="mt-6">
                    <h3 class="text-xl font-bold text-white mb-2">Profissional</h3>
                    <p class="text-slate-400 text-sm mb-6">Para quem leva a carreira a sério</p>
                    
                    <div class="mb-6">
                        <div class="flex items-center gap-3 mb-1">
                            <span class="text-lg text-slate-500 line-through">R$ 13,99</span>
                            <span class="bg-green-500 text-white text-xs font-bold px-2 py-1 rounded">-50%</span>
                        </div>
                        <div class="text-5xl font-black text-white">
                            R$ 6,99 <span class="text-lg text-slate-400 font-normal">/mês</span>
                        </div>
                        <p class="text-amber-400 text-sm mt-2 font-medium">⏰ Nos 6 primeiros meses</p>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3 text-white">
                            <svg class="w-5 h-5 text-cyan-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span><strong>Currículos ilimitados</strong></span>
                        </li>
                        <li class="flex items-center gap-3 text-white">
                            <svg class="w-5 h-5 text-cyan-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Editor com IA integrada</span>
                        </li>
                        <li class="flex items-center gap-3 text-white">
                            <svg class="w-5 h-5 text-cyan-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Sem marca d'água no PDF</span>
                        </li>
                        <li class="flex items-center gap-3 text-white">
                            <svg class="w-5 h-5 text-cyan-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Modelos Premium exclusivos</span>
                        </li>
                        <li class="flex items-center gap-3 text-white">
                            <svg class="w-5 h-5 text-cyan-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Suporte prioritário</span>
                        </li>
                    </ul>
                    
                    <a href="/criar-conta" class="block w-full text-center py-4 px-6 rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-400 hover:to-emerald-400 text-white font-bold shadow-lg shadow-green-500/30 transition-all transform hover:scale-[1.02]">
                        Começar por R$ 6,99/mês
                    </a>
                    
                    <!-- Garantia Destacada -->
                    <div class="mt-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl flex items-center justify-center gap-2">
                        <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span class="text-green-400 font-bold text-sm">Garantia de 7 dias ou seu dinheiro de volta</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Trust Badges com Ícones Modernos -->
        <div class="mt-16 text-center">
            <p class="text-slate-400 dark:text-slate-500 text-sm mb-8">Formas de pagamento aceitas</p>
            <div class="flex justify-center items-center gap-6">
                <!-- PIX -->
                <div class="group flex flex-col items-center gap-3 p-4 rounded-2xl hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all cursor-default">
                    <div class="w-16 h-16 bg-gradient-to-br from-teal-400 to-cyan-500 rounded-2xl flex items-center justify-center shadow-lg shadow-teal-500/20 group-hover:scale-110 transition-transform">
                        <svg class="w-9 h-9 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M13.17 6.17L17 10l-3.83 3.83M6.17 13.17L10 17l3.83-3.83M10 6.17L6.17 10 10 13.83M13.83 13.17L17 17l-3.17 3.17" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span class="text-sm font-bold text-slate-600 dark:text-slate-300">PIX</span>
                </div>
                
                <!-- Cartão -->
                <div class="group flex flex-col items-center gap-3 p-4 rounded-2xl hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all cursor-default">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/20 group-hover:scale-110 transition-transform">
                        <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <rect x="2" y="5" width="20" height="14" rx="3"/>
                            <path d="M2 10h20"/>
                            <path d="M6 15h4"/>
                        </svg>
                    </div>
                    <span class="text-sm font-bold text-slate-600 dark:text-slate-300">Cartão</span>
                </div>
                
                <!-- Boleto -->
                <div class="group flex flex-col items-center gap-3 p-4 rounded-2xl hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all cursor-default">
                    <div class="w-16 h-16 bg-gradient-to-br from-amber-400 to-orange-500 rounded-2xl flex items-center justify-center shadow-lg shadow-amber-500/20 group-hover:scale-110 transition-transform">
                        <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M4 5v14M8 5v14M12 5v14M16 5v14M20 5v14" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span class="text-sm font-bold text-slate-600 dark:text-slate-300">Boleto</span>
                </div>
            </div>
            
            <!-- Segurança -->
            <div class="mt-8 flex items-center justify-center gap-2 text-slate-400 dark:text-slate-500">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span class="text-xs">Pagamento 100% seguro e criptografado</span>
            </div>
        </div>
    </div>
</section>

<!-- NEW SECTION: DEPOIMENTOS (SCROLL) -->
<!-- Reduced padding and FIXED Infinite Scroll Logic -->
<section class="py-24 bg-slate-50 dark:bg-[#0B1121] border-y border-slate-200 dark:border-slate-800 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 mb-12 text-center">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Quem usa, recomenda</h2>
    </div>
    
    <!-- Marquee Container (Fixed for no gaps) -->
    <div class="flex overflow-hidden group space-x-6 pb-4">
        <!-- Content Set 1 -->
        <div class="flex animate-marquee space-x-6">
            <div class="w-80 bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex-shrink-0">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-10 h-10 rounded-full bg-slate-200 overflow-hidden"><img src="/public/images/avatar-woman-casual.avif" width="40" height="40" loading="lazy" class="w-full h-full object-cover" alt="Foto de Paula Martins - Usuária satisfeita" title="Paula Martins - Nubank"></div>
                    <div><p class="font-bold text-sm text-slate-900 dark:text-white">Paula Martins</p><p class="text-xs text-slate-500">Marketing • Nubank</p></div>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-normal">"A ferramenta de IA me salvou. Eu não sabia como descrever meus resultados, o editor fez isso por mim."</p>
            </div>
            <div class="w-80 bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex-shrink-0">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-10 h-10 rounded-full bg-slate-200 overflow-hidden"><img src="/public/images/avatar-man-startup.avif" width="40" height="40" loading="lazy" class="w-full h-full object-cover" alt="Foto de Ricardo Silva - Usuário satisfeito" title="Ricardo Silva - Mercado Livre"></div>
                    <div><p class="font-bold text-sm text-slate-900 dark:text-white">Ricardo Silva</p><p class="text-xs text-slate-500">Dev • Mercado Livre</p></div>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-normal">"Simples e direto. O template moderno é muito clean e passou direto no ATS da Gupy."</p>
            </div>
            <div class="w-80 bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex-shrink-0">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-10 h-10 rounded-full bg-slate-200 overflow-hidden"><img src="/public/images/avatar-woman-hr.avif" width="40" height="40" loading="lazy" class="w-full h-full object-cover" alt="Foto de Fernanda Costa - Usuária satisfeita" title="Fernanda Costa - Ambev"></div>
                    <div><p class="font-bold text-sm text-slate-900 dark:text-white">Fernanda Costa</p><p class="text-xs text-slate-500">RH • Ambev</p></div>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-normal">"Como recrutadora, digo: esses modelos são perfeitos. Fáceis de ler e profissionais."</p>
            </div>
            <div class="w-80 bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex-shrink-0">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-10 h-10 rounded-full bg-slate-200 overflow-hidden"><img src="/public/images/avatar-man-student.avif" width="40" height="40" loading="lazy" class="w-full h-full object-cover" alt="Foto de Lucas P. - Usuário satisfeito" title="Lucas P. - Itaú"></div>
                    <div><p class="font-bold text-sm text-slate-900 dark:text-white">Lucas P.</p><p class="text-xs text-slate-500">Estagiário • Itaú</p></div>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-normal">"Consegui meu estágio usando o modelo Júnior. Muito bom!"</p>
            </div>
        </div>
        <!-- Content Set 2 (Duplicate for Infinite Scroll) -->
        <div class="flex animate-marquee space-x-6" aria-hidden="true">
            <div class="w-80 bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex-shrink-0">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-10 h-10 rounded-full bg-slate-200 overflow-hidden"><img src="/public/images/avatar-woman-casual.avif" width="40" height="40" loading="lazy" class="w-full h-full object-cover" alt="Foto de Paula Martins - Usuária satisfeita" title="Paula Martins - Nubank"></div>
                    <div><p class="font-bold text-sm text-slate-900 dark:text-white">Paula Martins</p><p class="text-xs text-slate-500">Marketing • Nubank</p></div>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-normal">"A ferramenta de IA me salvou. Eu não sabia como descrever meus resultados, o editor fez isso por mim."</p>
            </div>
            <div class="w-80 bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex-shrink-0">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-10 h-10 rounded-full bg-slate-200 overflow-hidden"><img src="/public/images/avatar-man-startup.avif" width="40" height="40" loading="lazy" class="w-full h-full object-cover" alt="Foto de Ricardo Silva - Usuário satisfeito" title="Ricardo Silva - Mercado Livre"></div>
                    <div><p class="font-bold text-sm text-slate-900 dark:text-white">Ricardo Silva</p><p class="text-xs text-slate-500">Dev • Mercado Livre</p></div>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-normal">"Simples e direto. O template moderno é muito clean e passou direto no ATS da Gupy."</p>
            </div>
            <div class="w-80 bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex-shrink-0">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-10 h-10 rounded-full bg-slate-200 overflow-hidden"><img src="/public/images/avatar-woman-hr.avif" width="40" height="40" loading="lazy" class="w-full h-full object-cover" alt="Foto de Fernanda Costa - Usuária satisfeita" title="Fernanda Costa - Ambev"></div>
                    <div><p class="font-bold text-sm text-slate-900 dark:text-white">Fernanda Costa</p><p class="text-xs text-slate-500">RH • Ambev</p></div>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-normal">"Como recrutadora, digo: esses modelos são perfeitos. Fáceis de ler e profissionais."</p>
            </div>
            <div class="w-80 bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex-shrink-0">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-10 h-10 rounded-full bg-slate-200 overflow-hidden"><img src="/public/images/avatar-man-student.avif" width="40" height="40" loading="lazy" class="w-full h-full object-cover" alt="Foto de Lucas P. - Usuário satisfeito" title="Lucas P. - Itaú"></div>
                    <div><p class="font-bold text-sm text-slate-900 dark:text-white">Lucas P.</p><p class="text-xs text-slate-500">Estagiário • Itaú</p></div>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-normal">"Consegui meu estágio usando o modelo Júnior. Muito bom!"</p>
            </div>
        </div>
    </div>
</section>

<!-- NEW SECTION: FAQ (Grid 2 Colunas Detalhado) -->
<section class="py-24 bg-slate-50 dark:bg-slate-900 transition-colors duration-300 border-t border-slate-200 dark:border-slate-800/50">
     <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-5xl font-black text-slate-900 dark:text-white mb-6">Perguntas Frequentes</h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto text-lg">
                Tudo o que você precisa saber antes de criar o currículo que vai mudar sua carreira.
            </p>
        </div>
        
        <div class="grid md:grid-cols-2 gap-8 lg:gap-12">
            <!-- Coluna 1 -->
            <div class="space-y-8">
                <div class="bg-white dark:bg-slate-800 p-8 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 hover:border-purple-500/30 transition-colors">
                    <h3 class="font-bold text-xl text-slate-900 dark:text-white mb-3 flex items-start gap-3">
                        <span class="text-purple-600 mt-1">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        É realmente 100% gratuito?
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        Sim! Diferente de outros sites que cobram na hora de baixar, você pode criar, editar e fazer o download do seu currículo no modelo "Clássico" totalmente de graça. Oferecemos planos Premium apenas para quem quer acesso à nossa IA de escrita e skins exclusivas.
                    </p>
                </div>
                
                <div class="bg-white dark:bg-slate-800 p-8 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 hover:border-purple-500/30 transition-colors">
                    <h3 class="font-bold text-xl text-slate-900 dark:text-white mb-3 flex items-start gap-3">
                         <span class="text-purple-600 mt-1">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        O currículo passa nos robôs (ATS)?
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        Absolutamente. Nossos templates foram desenvolvidos por especialistas em RH. A estrutura do código é limpa e segue os padrões internacionais, garantindo que plataformas como Gupy, Kenoby e LinkedIn leiam suas informações corretamente.
                    </p>
                </div>
                
                <div class="bg-white dark:bg-slate-800 p-8 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 hover:border-purple-500/30 transition-colors">
                    <h3 class="font-bold text-xl text-slate-900 dark:text-white mb-3 flex items-start gap-3">
                         <span class="text-purple-600 mt-1">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </span>
                        Meus dados estão seguros?
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        Segurança é nossa prioridade. Seus dados são criptografados e nunca são vendidos para terceiros. Além disso, você tem um botão de "Exclusão Total" no seu painel para apagar sua conta e todos os documentos a qualquer momento.
                    </p>
                </div>
            </div>

            <!-- Coluna 2 -->
             <div class="space-y-8">
                <div class="bg-white dark:bg-slate-800 p-8 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 hover:border-purple-500/30 transition-colors">
                    <h3 class="font-bold text-xl text-slate-900 dark:text-white mb-3 flex items-start gap-3">
                         <span class="text-purple-600 mt-1">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </span>
                        Como funciona a IA de escrita?
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        Nossa IA analisa o cargo que você deseja (ex: "Vendedor") e sugere frases de impacto baseadas em currículos de sucesso reais. Você pode aceitar, editar ou pedir novas sugestões com um clique. É como ter um consultor de carreira do seu lado.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-800 p-8 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 hover:border-purple-500/30 transition-colors">
                    <h3 class="font-bold text-xl text-slate-900 dark:text-white mb-3 flex items-start gap-3">
                         <span class="text-purple-600 mt-1">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        </span>
                        Posso baixar em Word?
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        Nós focamos exclusivamente em PDF de Alta Resolução. Por quê? Arquivos Word desformatam facilmente quando abertos em computadores diferentes ou no celular dos recrutadores. O PDF garante que seu currículo será visto exatamente como você criou.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-800 p-8 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 hover:border-purple-500/30 transition-colors">
                    <h3 class="font-bold text-xl text-slate-900 dark:text-white mb-3 flex items-start gap-3">
                         <span class="text-purple-600 mt-1">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </span>
                        Consigo cancelar quando quiser?
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        Sim, sem letras miúdas. Se você assinar o plano Pro mensal, pode cancelar a renovação a qualquer momento direto pelo painel, sem precisar falar com ninguém. Seu acesso continua ativo até o fim do período pago.
                    </p>
                </div>
            </div>
        </div>
     </div>
</section>

</section>

<!-- SECTION: BLOG / NOTÍCIAS -->
<?php if (!empty($latestPosts)): ?>
<section class="py-24 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-5xl font-black text-slate-900 dark:text-white mb-6">Dicas de Carreira</h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto text-lg">
                Conteúdos exclusivos para ajudar você a conquistar sua próxima vaga.
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <?php foreach($latestPosts as $post): ?>
            <article class="bg-slate-50 dark:bg-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group border border-slate-100 dark:border-slate-700 h-full flex flex-col">
                <a href="/blog/<?php echo htmlspecialchars($post['slug']); ?>" class="block relative overflow-hidden h-48">
                    <?php if($post['cover_image']): ?>
                        <img src="<?php echo htmlspecialchars($post['cover_image']); ?>" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700">
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-purple-600 to-blue-600 flex items-center justify-center text-white font-bold opacity-80">
                            Currículo Vitae Pro
                        </div>
                    <?php endif; ?>
                    
                    <!-- Badge -->
                    <span class="absolute top-4 left-4 bg-white/90 dark:bg-slate-900/90 backdrop-blur-sm px-2.5 py-1 rounded-lg text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider shadow-sm">
                        <?php echo htmlspecialchars($post['main_tag'] ?? 'Geral'); ?>
                    </span>
                </a>
                <div class="p-6 flex flex-col flex-grow">
                    <div class="text-xs font-bold text-slate-400 mb-2 uppercase tracking-wider flex items-center gap-2">
                        <span><?php echo date('d M, Y', strtotime($post['created_at'])); ?></span>
                        <?php if(!empty($post['subtitle'])): ?>
                            <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                            <span class="line-clamp-1 max-w-[150px]"><?php echo htmlspecialchars($post['subtitle']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <a href="/blog/<?php echo htmlspecialchars($post['slug']); ?>" class="block mb-3">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-purple-600 transition-colors leading-tight">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </h3>
                    </a>
                    
                    <p class="text-slate-600 dark:text-slate-400 text-sm line-clamp-3 mb-6 flex-grow">
                        <?php echo htmlspecialchars($post['excerpt'] ?? strip_tags($post['content'])); ?>
                    </p>
                    
                    <a href="/blog/<?php echo htmlspecialchars($post['slug']); ?>" class="text-purple-600 dark:text-purple-400 font-bold text-sm hover:underline inline-flex items-center gap-1 mt-auto">
                        Ler artigo <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- MINI CTA FINAL -->
<section aria-label="Comece Agora" class="py-16 bg-slate-900 border-t border-slate-800 always-dark">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <p class="text-slate-400 text-lg mb-6">
            Pronto para dar o próximo passo na sua carreira?
        </p>
        <a href="/criar-conta" class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-cyan-600 text-white font-bold py-4 px-10 rounded-full hover:from-purple-500 hover:to-cyan-500 transition-all transform hover:scale-105 shadow-lg shadow-purple-500/25">
            Criar Meu Currículo Grátis
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </a>
        <p class="text-slate-500 text-sm mt-4">Sem cartão de crédito • Leva menos de 2 minutos</p>
    </div>
</section>

<?php include __DIR__ . '/includes/components/footer.php'; ?>

<!-- Animações -->
<!-- Animações e Lógica Otimizada (Minified) -->
<script>
    document.addEventListener("DOMContentLoaded",()=>{gsap.registerPlugin(ScrollTrigger);const t=gsap.timeline();t.to(".hero-element",{y:0,opacity:1,duration:1,stagger:.15,ease:"power3.out"});gsap.utils.toArray(".step-card").forEach((t,e)=>{gsap.from(t,{scrollTrigger:{trigger:t,start:"top 85%"},y:50,opacity:0,duration:.8,delay:.1*e,ease:"back.out(1.2)"})});gsap.to(".benefit-content",{scrollTrigger:{trigger:".benefit-content",start:"top 80%"},x:0,opacity:1,duration:1,ease:"power2.out"});gsap.to(".benefit-visual",{scrollTrigger:{trigger:".benefit-visual",start:"top 80%"},x:0,opacity:1,duration:1,delay:.2,ease:"power2.out"});window.addEventListener("mousemove",t=>{const e=t.clientX/window.innerWidth,a=t.clientY/window.innerHeight;gsap.to(".bg-blob-1",{x:50*e,y:50*a,duration:2});gsap.to(".bg-blob-2",{x:-50*e,y:-50*a,duration:2})})});function trackSignupStart(){typeof gtag=="function"&&gtag("event","begin_checkout",{app_name:"CurriculoVitaePro",screen_name:"Home",conversion_goal:"lead_generation"})}
</script>

<style>
/* Animação Marquee Otimizada */
@keyframes marquee{0%{transform:translateX(0)}100%{transform:translateX(-100%)}}.animate-marquee{animation:marquee 25s linear infinite}.group:hover .animate-marquee{animation-play-state:paused}
</style>
