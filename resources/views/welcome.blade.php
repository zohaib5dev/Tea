<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pulse · social reimagined</title>
    <!-- Tailwind via CDN (v3) for easy styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Inter + a stylish display font for the brand -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300..700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome icons (free version) for social icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* small custom tweaks to make it extra smooth */
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Space Grotesk', sans-serif; }
        /* custom glow effect for buttons & accent */
        .glow-ring { transition: box-shadow 0.25s ease, transform 0.2s ease; }
        .glow-ring:hover { box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.35); transform: scale(1.02); }
        .gradient-text {
            background: linear-gradient(135deg, #D4AF37 0%, #F5E56B 50%, #B8860B 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        /* shimmer/golden text for special headings */
        .gold-text {
            background: linear-gradient(145deg, #BF953F, #FCF6B5, #B38728, #FBF5B7, #AA771C);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        /* subtle floating animation for hero image */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .float { animation: float 6s ease-in-out infinite; }
        .float-slow { animation: float 8s ease-in-out infinite; }
        /* soft golden blob shadow */
        .blob-shadow {
            filter: blur(80px);
            opacity: 0.35;
        }
        /* custom card border shine */
        .gold-border {
            border-image: linear-gradient(135deg, #D4AF37, #F5E56B, #B8860B) 1;
        }
    </style>
</head>
<body class="bg-[#FDFBF7] text-gray-800 antialiased selection:bg-amber-200 selection:text-amber-900 overflow-x-hidden">

    <!-- Background decorative blobs (golden radial gradients) -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[500px] h-[500px] rounded-full bg-amber-200 blur-[120px] opacity-50"></div>
        <div class="absolute bottom-[-5%] right-[-5%] w-[600px] h-[600px] rounded-full bg-yellow-200 blur-[130px] opacity-40"></div>
        <div class="absolute top-[40%] right-[10%] w-[350px] h-[350px] rounded-full bg-orange-100 blur-[100px] opacity-30"></div>
        <div class="absolute top-[20%] left-[30%] w-[250px] h-[250px] rounded-full bg-yellow-300 blur-[90px] opacity-20"></div>
    </div>

    <!-- HEADER / NAVBAR -->
    <header class="w-full max-w-7xl mx-auto px-6 py-6 flex items-center justify-between relative z-10">
        <!-- Logo with icon -->
        <div class="flex items-center gap-2">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 via-yellow-400 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-200/60">
                <i class="fa-solid fa-bolt text-white text-xl"></i>
            </div>
            <span class="font-display font-bold text-2xl tracking-tight text-gray-800">pulse<span class="text-amber-600">.</span></span>
        </div>

        <!-- Desktop navigation -->
        <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-600">
            <a href="#" class="hover:text-amber-600 transition-colors">Features</a>
            <a href="#" class="hover:text-amber-600 transition-colors">Explore</a>
            <a href="#" class="hover:text-amber-600 transition-colors">Community</a>
            <a href="#" class="hover:text-amber-600 transition-colors">Pricing</a>
        </nav>

        <!-- CTA buttons -->
        <div class="flex items-center gap-3">
            <a href="/login" class="hidden sm:inline-block text-sm font-medium text-gray-600 hover:text-amber-600 transition-colors px-3 py-2">Log in</a>
            <a href="/register" class="bg-white/80 backdrop-blur-sm text-gray-800 border border-amber-200/80 text-sm font-semibold px-5 py-2.5 rounded-full shadow-sm hover:shadow-md hover:border-amber-400 transition-all flex items-center gap-2">
                <span>Sign up</span>
                <i class="fa-solid fa-arrow-right text-xs text-amber-600"></i>
            </a>
        </div>
    </header>

    <!-- HERO SECTION -->
    <main class="relative z-10">
        <section class="max-w-7xl mx-auto px-6 pt-8 pb-16 md:pt-12 md:pb-24 grid md:grid-cols-2 gap-12 items-center">
            <!-- Left column: headline & CTA -->
            <div class="space-y-8">
                <!-- small pill badge -->
                <div class="inline-flex items-center gap-2 bg-amber-100/80 backdrop-blur-sm text-amber-800 text-sm font-medium px-4 py-2 rounded-full border border-amber-300/60 w-fit">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                    </span>
                    <span>New · now in public beta</span>
                </div>

                <h1 class="font-display text-5xl sm:text-6xl lg:text-7xl font-bold leading-tight tracking-tight text-gray-900">
                    Where <span class="gold-text">vibes</span> meet <br>
                    <span class="relative inline-block">
                        community
                        <!-- decorative golden underline -->
                        <svg class="absolute -bottom-2 left-0 w-full h-3 text-amber-400/80" viewBox="0 0 200 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2 10C40 2 80 2 100 6C120 10 160 10 198 4" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                        </svg>
                    </span>
                </h1>

                <p class="text-lg text-gray-500 max-w-md leading-relaxed">
                    Pulse is the social app that connects you with people who *get* you. Share moments, discover communities, and build real connections — without the noise.
                </p>

                <!-- Download buttons group -->
                <div class="flex flex-wrap items-center gap-4 pt-2">
                    <a href="#" class="group bg-gradient-to-r from-amber-500 via-yellow-400 to-amber-600 text-white font-semibold px-8 py-4 rounded-2xl shadow-lg shadow-amber-200/80 hover:shadow-amber-300/80 transition-all duration-300 flex items-center gap-3 glow-ring">
                        <i class="fa-brands fa-apple text-xl"></i>
                        <span>Download for iOS</span>
                        <i class="fa-solid fa-arrow-right text-sm opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all"></i>
                    </a>
                    <a href="#" class="bg-white/80 backdrop-blur-sm text-gray-800 border border-amber-200/80 font-semibold px-8 py-4 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 flex items-center gap-3 glow-ring">
                        <i class="fa-brands fa-android text-xl text-green-600"></i>
                        <span>Android</span>
                    </a>
                </div>

                <!-- Social proof / avatars & rating -->
                <div class="flex items-center gap-5 pt-4">
                    <div class="flex -space-x-2">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-amber-300 to-amber-500 ring-2 ring-white flex items-center justify-center text-white text-xs font-bold shadow-sm">JD</div>
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-yellow-300 to-yellow-500 ring-2 ring-white flex items-center justify-center text-white text-xs font-bold shadow-sm">AM</div>
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-orange-300 to-orange-500 ring-2 ring-white flex items-center justify-center text-white text-xs font-bold shadow-sm">KL</div>
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-amber-400 to-yellow-600 ring-2 ring-white flex items-center justify-center text-white text-xs font-bold shadow-sm">RS</div>
                        <div class="w-9 h-9 rounded-full bg-gray-100 ring-2 ring-white flex items-center justify-center text-xs font-semibold text-gray-500 shadow-sm">+12k</div>
                    </div>
                    <div class="text-sm text-gray-500">
                        <div class="flex items-center gap-1 text-amber-400">
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star-half-alt"></i>
                            <span class="text-gray-700 font-medium ml-1">4.9</span>
                        </div>
                        <span class="text-xs">from 8,000+ reviews</span>
                    </div>
                </div>
            </div>

            <!-- Right column: phone mockup / hero visual -->
            <div class="relative flex justify-center md:justify-end items-center">
                <!-- decorative golden glow behind phone -->
                <div class="absolute w-[350px] h-[350px] md:w-[450px] md:h-[450px] bg-gradient-to-tr from-amber-300 via-yellow-200 to-orange-300 rounded-full blur-[100px] opacity-40 -z-10"></div>

                <!-- main phone mockup (simple CSS + SVG shapes) -->
                <div class="relative w-[280px] h-[560px] md:w-[300px] md:h-[600px] float">
                    <!-- phone frame with gold accent -->
                    <div class="absolute inset-0 bg-gray-900 rounded-[40px] shadow-2xl border-[6px] border-amber-600/80 overflow-hidden ring-1 ring-amber-300/40">
                        <!-- notch/dynamic island -->
                        <div class="absolute top-3 left-1/2 -translate-x-1/2 w-28 h-6 bg-black rounded-full z-20"></div>
                        <!-- screen content: abstract social feed mockup -->
                        <div class="w-full h-full bg-gradient-to-br from-amber-50 via-white to-yellow-50 pt-12 px-4 pb-6 flex flex-col gap-4 overflow-hidden">
                            <!-- status bar (fake) -->
                            <div class="flex justify-between text-[10px] text-gray-400 px-1">
                                <span>9:41</span>
                                <span><i class="fa-solid fa-signal"></i> <i class="fa-solid fa-wifi ml-1"></i> <i class="fa-solid fa-battery-full ml-1"></i></span>
                            </div>
                            <!-- app header inside phone -->
                            <div class="flex justify-between items-center mt-2">
                                <span class="font-display font-bold text-lg text-gray-800">pulse<span class="text-amber-600">.</span></span>
                                <i class="fa-regular fa-bell text-amber-600/70"></i>
                            </div>
                            <!-- stories row -->
                            <div class="flex gap-3 mt-1">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-amber-400 to-yellow-300 p-[2px]"><div class="w-full h-full rounded-full bg-white flex items-center justify-center text-xs font-bold text-amber-600">You</div></div>
                                <div class="w-12 h-12 rounded-full bg-amber-200 p-[2px]"><div class="w-full h-full rounded-full bg-gradient-to-br from-amber-300 to-yellow-400 flex items-center justify-center text-white text-xs">D</div></div>
                                <div class="w-12 h-12 rounded-full bg-amber-200 p-[2px]"><div class="w-full h-full rounded-full bg-gradient-to-br from-orange-300 to-amber-400 flex items-center justify-center text-white text-xs">M</div></div>
                                <div class="w-12 h-12 rounded-full bg-amber-200 p-[2px]"><div class="w-full h-full rounded-full bg-gradient-to-br from-yellow-300 to-amber-500 flex items-center justify-center text-white text-xs">S</div></div>
                            </div>
                            <!-- feed card 1 -->
                            <div class="bg-white rounded-2xl p-3 shadow-sm border border-amber-100 mt-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-gradient-to-br from-amber-400 to-yellow-400"></div>
                                    <span class="text-xs font-semibold text-gray-700">alex_creates</span>
                                    <span class="text-[8px] text-gray-400 ml-auto">2m ago</span>
                                </div>
                                <div class="mt-2 h-20 bg-gradient-to-br from-amber-100 to-yellow-100 rounded-xl flex items-center justify-center text-amber-700 text-xs">📸 golden hour</div>
                                <div class="flex items-center gap-3 mt-2 text-amber-500 text-xs">
                                    <i class="fa-regular fa-heart"></i> <span>248</span>
                                    <i class="fa-regular fa-comment ml-2"></i> <span>32</span>
                                    <i class="fa-regular fa-paper-plane ml-auto"></i>
                                </div>
                            </div>
                            <!-- feed card 2 (smaller) -->
                            <div class="bg-white rounded-2xl p-3 shadow-sm border border-amber-100">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-gradient-to-br from-orange-400 to-amber-400"></div>
                                    <span class="text-xs font-semibold text-gray-700">design_lab</span>
                                </div>
                                <div class="mt-2 h-12 bg-gradient-to-br from-orange-100 to-amber-100 rounded-xl flex items-center justify-center text-amber-700 text-xs">🎨 new drop</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- floating decorative elements around phone (gold accents) -->
                <div class="absolute -left-6 top-20 w-14 h-14 bg-white rounded-2xl shadow-xl border border-amber-200 flex items-center justify-center float-slow">
                    <i class="fa-solid fa-heart text-amber-500 text-xl"></i>
                </div>
                <div class="absolute -right-4 bottom-32 w-16 h-16 bg-white rounded-2xl shadow-xl border border-amber-200 flex items-center justify-center float-slow">
                    <i class="fa-regular fa-comment-dots text-amber-600 text-2xl"></i>
                </div>
                <div class="absolute right-10 top-10 w-12 h-12 bg-white rounded-full shadow-lg border border-amber-200 flex items-center justify-center float">
                    <i class="fa-solid fa-user-plus text-amber-600 text-lg"></i>
                </div>
            </div>
        </section>

        <!-- LOGO / TRUST BADGE SECTION (light) -->
        <div class="max-w-7xl mx-auto px-6 pb-16">
            <p class="text-center text-sm font-medium text-gray-400 uppercase tracking-widest">Trusted by communities worldwide</p>
            <div class="flex flex-wrap justify-center items-center gap-8 md:gap-12 mt-6 opacity-60">
                <i class="fa-brands fa-discord text-2xl text-amber-700/70"></i>
                <i class="fa-brands fa-slack text-2xl text-amber-700/70"></i>
                <i class="fa-brands fa-spotify text-2xl text-amber-700/70"></i>
                <i class="fa-brands fa-figma text-2xl text-amber-700/70"></i>
                <i class="fa-brands fa-github text-2xl text-amber-700/70"></i>
                <i class="fa-brands fa-reddit-alien text-2xl text-amber-700/70"></i>
            </div>
        </div>

        <!-- FEATURES SECTION (cards with icons) -->
        <section class="max-w-7xl mx-auto px-6 py-20 bg-white/60 backdrop-blur-sm rounded-[60px] border border-amber-100/80 shadow-sm relative">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-50/50 to-yellow-50/50 rounded-[60px] -z-10"></div>
            <div class="text-center max-w-2xl mx-auto mb-14">
                <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900">Everything you need to <span class="gold-text">connect</span></h2>
                <p class="text-gray-500 mt-3 text-lg">No algorithms hiding your friends. Just pure, authentic social.</p>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                <!-- Card 1 -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-amber-100/80 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 flex items-center justify-center text-amber-600 text-xl group-hover:bg-amber-500 group-hover:text-white transition-colors">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <h3 class="font-semibold text-xl mt-5 text-gray-800">Real-time vibes</h3>
                    <p class="text-gray-500 mt-2 leading-relaxed">Share moments as they happen with lightning-fast posts and live reactions.</p>
                </div>
                <!-- Card 2 -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-amber-100/80 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-2xl bg-yellow-100 flex items-center justify-center text-yellow-600 text-xl group-hover:bg-yellow-500 group-hover:text-white transition-colors">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <h3 class="font-semibold text-xl mt-5 text-gray-800">Niche communities</h3>
                    <p class="text-gray-500 mt-2 leading-relaxed">From indie artists to tech tinkerers — find your people in curated spaces.</p>
                </div>
                <!-- Card 3 -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-amber-100/80 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-2xl bg-orange-100 flex items-center justify-center text-orange-600 text-xl group-hover:bg-orange-500 group-hover:text-white transition-colors">
                        <i class="fa-solid fa-shield"></i>
                    </div>
                    <h3 class="font-semibold text-xl mt-5 text-gray-800">Privacy first</h3>
                    <p class="text-gray-500 mt-2 leading-relaxed">Your data is yours. No ads, no tracking, no creepy algorithms. Ever.</p>
                </div>
            </div>
        </section>

        <!-- CALL TO ACTION SECTION (big golden gradient) -->
        <section class="max-w-6xl mx-auto px-6 py-20">
            <div class="bg-gradient-to-r from-amber-500 via-yellow-400 to-amber-600 rounded-[40px] p-1 shadow-2xl shadow-amber-200/80">
                <div class="bg-white/10 backdrop-blur-sm rounded-[38px] px-8 py-14 md:px-16 text-center">
                    <h2 class="font-display text-3xl md:text-5xl font-bold text-white leading-tight drop-shadow-md">Ready to feel the pulse?</h2>
                    <p class="text-white/90 text-lg mt-4 max-w-xl mx-auto">Join 50k+ early adopters and experience social media that actually feels social.</p>
                    <div class="flex flex-wrap justify-center gap-4 mt-8">
                        <a href="#" class="bg-white text-gray-900 font-semibold px-8 py-4 rounded-2xl shadow-lg hover:shadow-xl transition-all hover:scale-[1.02] flex items-center gap-2">
                            <i class="fa-brands fa-apple text-xl"></i> App Store
                        </a>
                        <a href="#" class="bg-black/30 backdrop-blur-sm text-white border border-white/40 font-semibold px-8 py-4 rounded-2xl hover:bg-black/40 transition-all flex items-center gap-2">
                            <i class="fa-brands fa-google-play text-xl"></i> Google Play
                        </a>
                    </div>
                    <p class="text-white/60 text-sm mt-6">Free to join · no credit card needed</p>
                </div>
            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <footer class="border-t border-amber-200/60 bg-white/50 backdrop-blur-sm relative z-10">
        <div class="max-w-7xl mx-auto px-6 py-12 flex flex-col md:flex-row justify-between gap-8">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-amber-500 via-yellow-400 to-amber-600 flex items-center justify-center shadow-sm">
                    <i class="fa-solid fa-bolt text-white text-sm"></i>
                </div>
                <span class="font-display font-bold text-xl text-gray-800">pulse<span class="text-amber-600">.</span></span>
                <span class="text-gray-400 text-sm ml-4">© 2025 Pulse Inc.</span>
            </div>
            <div class="flex flex-wrap gap-8 text-sm text-gray-500">
                <a href="#" class="hover:text-amber-600 transition">Privacy</a>
                <a href="#" class="hover:text-amber-600 transition">Terms</a>
                <a href="#" class="hover:text-amber-600 transition">Help</a>
                <a href="#" class="hover:text-amber-600 transition">Careers</a>
            </div>
            <div class="flex gap-4 text-gray-400">
                <a href="#" class="hover:text-amber-600 transition text-lg"><i class="fa-brands fa-twitter"></i></a>
                <a href="#" class="hover:text-amber-600 transition text-lg"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" class="hover:text-amber-600 transition text-lg"><i class="fa-brands fa-tiktok"></i></a>
                <a href="#" class="hover:text-amber-600 transition text-lg"><i class="fa-brands fa-discord"></i></a>
            </div>
        </div>
    </footer>
</body>
</html>